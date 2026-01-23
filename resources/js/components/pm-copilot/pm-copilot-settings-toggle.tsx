import { useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Settings2, Loader2 } from 'lucide-react';
import { useUpdatePMCopilotMode, type PMCopilotMode } from '@/hooks/use-pm-copilot';
import { cn } from '@/lib/utils';

interface PMCopilotSettingsToggleProps {
    /** The work order ID to update settings for */
    workOrderId: string;
    /** Current PM Copilot mode setting */
    currentMode: PMCopilotMode;
    /** Callback when mode changes successfully */
    onChange?: (newMode: PMCopilotMode) => void;
    /** Whether the toggle is disabled */
    disabled?: boolean;
}

/**
 * Toggle component for switching between PM Copilot "Staged" and "Full Plan" modes.
 * - Staged mode: Pauses after deliverable generation for approval before task breakdown
 * - Full Plan mode: Generates deliverables and tasks in a single pass
 */
export function PMCopilotSettingsToggle({
    workOrderId,
    currentMode,
    onChange,
    disabled = false,
}: PMCopilotSettingsToggleProps) {
    const [localMode, setLocalMode] = useState<PMCopilotMode>(currentMode);
    const { updateMode, isLoading, error } = useUpdatePMCopilotMode();

    const isStagedMode = localMode === 'staged';

    const handleToggle = async (checked: boolean) => {
        const newMode: PMCopilotMode = checked ? 'staged' : 'full';

        // Optimistic update
        setLocalMode(newMode);

        const result = await updateMode(workOrderId, newMode);

        if (result.success) {
            onChange?.(newMode);
        } else {
            // Revert on error
            setLocalMode(currentMode);
        }
    };

    return (
        <div className="rounded-lg border border-border bg-muted/30 p-4">
            <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <div className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {isLoading ? (
                            <Loader2 className="h-5 w-5 animate-spin" />
                        ) : (
                            <Settings2 className="h-5 w-5" />
                        )}
                    </div>
                    <div className="space-y-0.5">
                        <div className="flex items-center gap-2">
                            <Label
                                htmlFor="pm-copilot-mode-toggle"
                                className="cursor-pointer text-sm font-medium"
                            >
                                Review Mode
                            </Label>
                            <Badge
                                variant="outline"
                                className={cn(
                                    'text-xs',
                                    isStagedMode
                                        ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-400'
                                        : 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400'
                                )}
                            >
                                {isStagedMode ? 'Staged' : 'Full Plan'}
                            </Badge>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            {isStagedMode
                                ? 'Review deliverables before generating tasks'
                                : 'Generate deliverables and tasks together'}
                        </p>
                    </div>
                </div>
                <Switch
                    id="pm-copilot-mode-toggle"
                    checked={isStagedMode}
                    onCheckedChange={handleToggle}
                    disabled={disabled || isLoading}
                    aria-label={`Switch to ${isStagedMode ? 'full plan' : 'staged'} mode`}
                />
            </div>
            {error && (
                <p className="mt-2 text-xs text-destructive">{error}</p>
            )}
        </div>
    );
}
