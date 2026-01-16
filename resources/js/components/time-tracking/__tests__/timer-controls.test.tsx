import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { TimerControls } from '../timer-controls';

const mockRouterPost = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args: unknown[]) => mockRouterPost(...args),
    },
}));

describe('TimerControls', () => {
    beforeEach(() => {
        mockRouterPost.mockClear();
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

    it('calls start endpoint when start button clicked', async () => {
        const user = userEvent.setup();
        render(<TimerControls taskId={42} />);

        const startButton = screen.getByRole('button', { name: /start timer/i });
        await user.click(startButton);

        expect(mockRouterPost).toHaveBeenCalledWith(
            '/work/tasks/42/timer/start',
            expect.objectContaining({ is_billable: true }),
            expect.any(Object)
        );
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
