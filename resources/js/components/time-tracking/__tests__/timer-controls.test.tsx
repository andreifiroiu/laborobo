import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach, afterEach } from 'vitest';
import { TimerControls } from '../timer-controls';

const mockRouterPost = vi.fn();
const mockRouterVisit = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args: unknown[]) => mockRouterPost(...args),
        visit: (...args: unknown[]) => mockRouterVisit(...args),
    },
}));

// Mock fetch globally
const mockFetch = vi.fn();
global.fetch = mockFetch;

// Mock document.cookie for CSRF token
Object.defineProperty(document, 'cookie', {
    writable: true,
    value: 'XSRF-TOKEN=test-csrf-token',
});

describe('TimerControls', () => {
    beforeEach(() => {
        mockRouterPost.mockClear();
        mockRouterVisit.mockClear();
        mockFetch.mockClear();
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    it('renders "Start Timer" button when no active timer for task', () => {
        render(<TimerControls taskId={1} />);

        const startButton = screen.getByRole('button', { name: /start timer/i });
        expect(startButton).toBeInTheDocument();
        expect(screen.queryByText(/stop timer/i)).not.toBeInTheDocument();
    });

    it('renders "Stop Timer" button with elapsed time when timer active', () => {
        const activeTimer = {
            id: 1,
            taskId: 1,
            taskTitle: 'Test Task',
            projectName: 'Test Project',
            startedAt: new Date(Date.now() - 3661000).toISOString(), // 1h 1m 1s ago
            isBillable: true,
        };

        render(<TimerControls taskId={1} activeTimerForTask={activeTimer} />);

        const stopButton = screen.getByRole('button', { name: /stop timer/i });
        expect(stopButton).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: /start timer/i })).not.toBeInTheDocument();
        expect(screen.getByTestId('elapsed-time')).toBeInTheDocument();
    });

    it('calls fetch to check timer start and shows confirmation dialog when confirmation_required', async () => {
        const user = userEvent.setup();

        // Mock API response that requires confirmation
        mockFetch.mockResolvedValueOnce({
            json: () => Promise.resolve({
                confirmation_required: true,
                message: "Task is currently 'Done'. Starting a timer will move it back to In Progress.",
                current_status: 'done',
            }),
        });

        render(<TimerControls taskId={42} />);

        const startButton = screen.getByRole('button', { name: /start timer/i });
        await user.click(startButton);

        // Wait for fetch to be called
        await waitFor(() => {
            expect(mockFetch).toHaveBeenCalledWith(
                '/work/tasks/42/timer/start',
                expect.objectContaining({
                    method: 'POST',
                    headers: expect.objectContaining({
                        'Accept': 'application/json',
                    }),
                })
            );
        });

        // Confirmation dialog should appear
        await waitFor(() => {
            expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        });

        expect(screen.getByText(/done/i)).toBeInTheDocument();
        expect(screen.getByText(/in progress/i)).toBeInTheDocument();
    });

    it('confirms timer start and transitions status when user confirms dialog', async () => {
        const user = userEvent.setup();

        // First call returns confirmation_required
        mockFetch.mockResolvedValueOnce({
            json: () => Promise.resolve({
                confirmation_required: true,
                current_status: 'in_review',
            }),
        });

        // Second call (with confirmed=true) returns started
        mockFetch.mockResolvedValueOnce({
            json: () => Promise.resolve({
                started: true,
                message: 'Timer started successfully.',
                time_entry: {
                    id: '1',
                    task_id: '42',
                    user_id: '1',
                    started_at: new Date().toISOString(),
                    is_billable: true,
                },
            }),
        });

        render(<TimerControls taskId={42} />);

        // Click start timer
        const startButton = screen.getByRole('button', { name: /start timer/i });
        await user.click(startButton);

        // Wait for dialog to appear
        await waitFor(() => {
            expect(screen.getByRole('alertdialog')).toBeInTheDocument();
        });

        // Click confirm button
        const confirmButton = screen.getByRole('button', { name: /confirm/i });
        await user.click(confirmButton);

        // Verify the confirmed endpoint was called
        await waitFor(() => {
            expect(mockFetch).toHaveBeenCalledWith(
                '/work/tasks/42/timer/start?confirmed=true',
                expect.objectContaining({
                    method: 'POST',
                })
            );
        });

        // Page should reload after successful start (using router.visit)
        await waitFor(() => {
            expect(mockRouterVisit).toHaveBeenCalledWith(
                expect.any(String),
                expect.objectContaining({ preserveScroll: true })
            );
        });
    });

    it('starts timer directly when no confirmation required', async () => {
        const user = userEvent.setup();

        // Mock API response that starts immediately
        mockFetch.mockResolvedValueOnce({
            json: () => Promise.resolve({
                started: true,
                message: 'Timer started successfully.',
                time_entry: {
                    id: '1',
                    task_id: '42',
                    user_id: '1',
                    started_at: new Date().toISOString(),
                    is_billable: true,
                },
            }),
        });

        render(<TimerControls taskId={42} />);

        const startButton = screen.getByRole('button', { name: /start timer/i });
        await user.click(startButton);

        // Verify fetch was called
        await waitFor(() => {
            expect(mockFetch).toHaveBeenCalledWith(
                '/work/tasks/42/timer/start',
                expect.any(Object)
            );
        });

        // Page should reload after successful start
        await waitFor(() => {
            expect(mockRouterVisit).toHaveBeenCalled();
        });

        // No confirmation dialog should appear
        expect(screen.queryByRole('alertdialog')).not.toBeInTheDocument();
    });

    it('calls stop endpoint when stop button clicked', async () => {
        const user = userEvent.setup();
        const activeTimer = {
            id: 1,
            taskId: 42,
            taskTitle: 'Test Task',
            projectName: 'Test Project',
            startedAt: new Date().toISOString(),
            isBillable: true,
        };

        render(<TimerControls taskId={42} activeTimerForTask={activeTimer} />);

        const stopButton = screen.getByRole('button', { name: /stop timer/i });
        await user.click(stopButton);

        expect(mockRouterPost).toHaveBeenCalledWith(
            '/work/tasks/42/timer/stop',
            {},
            expect.any(Object)
        );
    });
});
