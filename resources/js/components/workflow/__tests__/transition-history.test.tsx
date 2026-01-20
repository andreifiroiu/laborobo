import { render, screen, within } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { TransitionHistory, type StatusTransition } from '../transition-history';

const mockUser = {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    avatar: undefined,
};

const mockTransitions: StatusTransition[] = [
    {
        id: 1,
        actionType: 'status_change',
        fromStatus: 'todo',
        toStatus: 'in_progress',
        fromAssignedTo: null,
        toAssignedTo: null,
        fromAssignedAgent: null,
        toAssignedAgent: null,
        user: mockUser,
        createdAt: '2024-01-15T10:00:00Z',
        comment: null,
        commentCategory: null,
    },
    {
        id: 2,
        actionType: 'status_change',
        fromStatus: 'in_progress',
        toStatus: 'in_review',
        fromAssignedTo: null,
        toAssignedTo: null,
        fromAssignedAgent: null,
        toAssignedAgent: null,
        user: mockUser,
        createdAt: '2024-01-15T14:00:00Z',
        comment: null,
        commentCategory: null,
    },
    {
        id: 3,
        actionType: 'status_change',
        fromStatus: 'in_review',
        toStatus: 'revision_requested',
        fromAssignedTo: null,
        toAssignedTo: null,
        fromAssignedAgent: null,
        toAssignedAgent: null,
        user: { ...mockUser, id: 2, name: 'Jane Smith' },
        createdAt: '2024-01-16T09:00:00Z',
        comment: 'The design needs to match the brand guidelines more closely. Please update the color scheme.',
        commentCategory: 'design_impact',
    },
    {
        id: 4,
        actionType: 'status_change',
        fromStatus: 'in_progress',
        toStatus: 'in_review',
        fromAssignedTo: null,
        toAssignedTo: null,
        fromAssignedAgent: null,
        toAssignedAgent: null,
        user: mockUser,
        createdAt: '2024-01-16T15:00:00Z',
        comment: 'Updated the design as requested.',
        commentCategory: null,
    },
];

describe('TransitionHistory', () => {
    it('displays transitions in chronological order', () => {
        render(<TransitionHistory transitions={mockTransitions} variant="task" />);

        const historyItems = screen.getAllByRole('listitem');
        expect(historyItems).toHaveLength(4);

        // Verify order by checking that the first item shows the earliest transition
        expect(within(historyItems[0]).getByText(/to do/i)).toBeInTheDocument();
        expect(within(historyItems[0]).getByText(/in progress/i)).toBeInTheDocument();

        // Last item should be the most recent
        expect(within(historyItems[3]).getByText(/in review/i)).toBeInTheDocument();
    });

    it('displays rejection comments prominently with distinct styling', () => {
        render(<TransitionHistory transitions={mockTransitions} variant="task" />);

        // Find the rejection comment
        const rejectionComment = screen.getByText(/the design needs to match the brand guidelines/i);
        expect(rejectionComment).toBeInTheDocument();

        // The rejection item should have the comment visible
        const rejectionItem = rejectionComment.closest('[role="listitem"]');
        expect(rejectionItem).toBeInTheDocument();

        // Verify the revision_requested status is shown
        expect(within(rejectionItem!).getByText(/revision requested/i)).toBeInTheDocument();

        // Verify the category badge is shown for rejection feedback
        expect(screen.getByText(/design impact/i)).toBeInTheDocument();
    });

    it('displays user information with avatar fallback', () => {
        render(<TransitionHistory transitions={mockTransitions} variant="task" />);

        // Check that user names are displayed
        expect(screen.getAllByText('John Doe').length).toBeGreaterThan(0);
        expect(screen.getByText('Jane Smith')).toBeInTheDocument();
    });

    it('renders empty state when no transitions provided', () => {
        render(<TransitionHistory transitions={[]} variant="task" />);

        expect(screen.getByText(/no activity yet/i)).toBeInTheDocument();
    });
});
