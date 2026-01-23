import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import type {
    PMCopilotSuggestionsResponse,
    PMCopilotWorkflowState,
    ProjectInsight,
} from '@/types/pm-copilot.d';

/**
 * PM Copilot mode options
 */
export type PMCopilotMode = 'staged' | 'full';

/**
 * Hook for triggering PM Copilot workflow on a work order.
 * Returns mutation-like interface for triggering and tracking workflow state.
 */
export function useTriggerPMCopilot() {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [workflowState, setWorkflowState] = useState<PMCopilotWorkflowState | null>(null);

    const trigger = useCallback(async (workOrderId: string) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch(`/work/work-orders/${workOrderId}/pm-copilot/trigger`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || 'Failed to trigger PM Copilot');
                return { success: false, error: data.message };
            }

            setWorkflowState(data.workflowState);
            return { success: true, data };
        } catch (err) {
            const message = err instanceof Error ? err.message : 'An error occurred';
            setError(message);
            return { success: false, error: message };
        } finally {
            setIsLoading(false);
        }
    }, []);

    const reset = useCallback(() => {
        setIsLoading(false);
        setError(null);
        setWorkflowState(null);
    }, []);

    return {
        trigger,
        isLoading,
        error,
        workflowState,
        reset,
    };
}

/**
 * Hook for fetching PM Copilot suggestions for a work order.
 * Returns query-like interface for fetching and refreshing suggestions.
 */
export function usePMCopilotSuggestions(workOrderId: string) {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [data, setData] = useState<PMCopilotSuggestionsResponse | null>(null);

    const fetch = useCallback(async () => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await window.fetch(
                `/work/work-orders/${workOrderId}/pm-copilot/suggestions`,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            const responseData = await response.json();

            if (!response.ok) {
                setError(responseData.message || 'Failed to fetch suggestions');
                return { success: false, error: responseData.message };
            }

            setData(responseData);
            return { success: true, data: responseData };
        } catch (err) {
            const message = err instanceof Error ? err.message : 'An error occurred';
            setError(message);
            return { success: false, error: message };
        } finally {
            setIsLoading(false);
        }
    }, [workOrderId]);

    const refetch = useCallback(() => {
        return fetch();
    }, [fetch]);

    return {
        data,
        isLoading,
        error,
        fetch,
        refetch,
    };
}

/**
 * Hook for approving a PM Copilot suggestion.
 * Creates the deliverables/tasks from the approved alternative.
 */
export function useApproveSuggestion() {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const approve = useCallback(async (suggestionId: string) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch(`/work/pm-copilot/suggestions/${suggestionId}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || 'Failed to approve suggestion');
                return { success: false, error: data.message };
            }

            // Reload the page to get fresh data after approval
            router.reload({ only: ['tasks', 'deliverables'] });

            return { success: true, data };
        } catch (err) {
            const message = err instanceof Error ? err.message : 'An error occurred';
            setError(message);
            return { success: false, error: message };
        } finally {
            setIsLoading(false);
        }
    }, []);

    const reset = useCallback(() => {
        setIsLoading(false);
        setError(null);
    }, []);

    return {
        approve,
        isLoading,
        error,
        reset,
    };
}

/**
 * Hook for rejecting a PM Copilot suggestion.
 * Marks the suggestion as rejected with optional reason.
 */
export function useRejectSuggestion() {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const reject = useCallback(async (suggestionId: string, reason?: string) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch(`/work/pm-copilot/suggestions/${suggestionId}/reject`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ reason }),
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || 'Failed to reject suggestion');
                return { success: false, error: data.message };
            }

            return { success: true, data };
        } catch (err) {
            const message = err instanceof Error ? err.message : 'An error occurred';
            setError(message);
            return { success: false, error: message };
        } finally {
            setIsLoading(false);
        }
    }, []);

    const reset = useCallback(() => {
        setIsLoading(false);
        setError(null);
    }, []);

    return {
        reject,
        isLoading,
        error,
        reset,
    };
}

/**
 * Hook for updating PM Copilot mode setting on a work order.
 */
export function useUpdatePMCopilotMode() {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const updateMode = useCallback(async (workOrderId: string, mode: PMCopilotMode) => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await fetch(`/work/work-orders/${workOrderId}/agent-settings`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ pm_copilot_mode: mode }),
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || 'Failed to update PM Copilot mode');
                return { success: false, error: data.message };
            }

            return { success: true, data };
        } catch (err) {
            const message = err instanceof Error ? err.message : 'An error occurred';
            setError(message);
            return { success: false, error: message };
        } finally {
            setIsLoading(false);
        }
    }, []);

    return {
        updateMode,
        isLoading,
        error,
    };
}

/**
 * Hook for fetching project insights from PM Copilot.
 */
export function useProjectInsights(projectId: string) {
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [insights, setInsights] = useState<ProjectInsight[]>([]);

    const fetch = useCallback(async () => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await window.fetch(`/work/projects/${projectId}/insights`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json();

            if (!response.ok) {
                setError(data.message || 'Failed to fetch insights');
                return { success: false, error: data.message };
            }

            setInsights(data.insights || []);
            return { success: true, data: data.insights };
        } catch (err) {
            const message = err instanceof Error ? err.message : 'An error occurred';
            setError(message);
            return { success: false, error: message };
        } finally {
            setIsLoading(false);
        }
    }, [projectId]);

    const refetch = useCallback(() => {
        return fetch();
    }, [fetch]);

    return {
        insights,
        isLoading,
        error,
        fetch,
        refetch,
    };
}
