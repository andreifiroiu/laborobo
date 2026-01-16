import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { ActiveTimerIndicator } from '../active-timer-indicator';

const mockRouterPost = vi.fn();
const mockUsePage = vi.fn();

vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args: unknown[]) => mockRouterPost(...args),
    },
    usePage: () => mockUsePage(),
}));

describe('ActiveTimerIndicator', () => {
    beforeEach(() => {
        mockRouterPost.mockClear();
        mockUsePage.mockClear();
    });

    it('does not render when no active timer', () => {
        mockUsePage.mockReturnValue({
            props: { activeTimer: null },
        });

        const { container } = render(<ActiveTimerIndicator />);

        expect(container).toBeEmptyDOMElement();
    });

    it('shows task name and elapsed time when timer is active', () => {
        mockUsePage.mockReturnValue({
            props: {
                activeTimer: {
                    id: 1,
                    taskId: 10,
                    taskTitle: 'Implement feature',
                    projectName: 'Test Project',
                    startedAt: new Date(Date.now() - 3661000).toISOString(), // 1h 1m 1s ago
                    isBillable: true,
                },
            },
        });

        render(<ActiveTimerIndicator />);

        expect(screen.getByText(/implement feature/i)).toBeInTheDocument();
        expect(screen.getByTestId('active-timer-elapsed')).toBeInTheDocument();
    });

    it('stop button in popover stops timer', async () => {
        const user = userEvent.setup();
        mockUsePage.mockReturnValue({
            props: {
                activeTimer: {
                    id: 42,
                    taskId: 10,
                    taskTitle: 'Implement feature',
                    projectName: 'Test Project',
                    startedAt: new Date().toISOString(),
                    isBillable: true,
                },
            },
        });

        render(<ActiveTimerIndicator />);

        // Open the popover
        const trigger = screen.getByRole('button', { name: /timer running/i });
        await user.click(trigger);

        // Find and click the stop button in the popover
        await waitFor(() => {
            expect(screen.getByRole('button', { name: /stop timer/i })).toBeInTheDocument();
        });

        const stopButton = screen.getByRole('button', { name: /stop timer/i });
        await user.click(stopButton);

        expect(mockRouterPost).toHaveBeenCalledWith(
            '/work/time-entries/42/stop',
            {},
            expect.any(Object)
        );
    });
});
