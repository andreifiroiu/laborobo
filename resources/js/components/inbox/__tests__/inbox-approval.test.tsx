import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi } from 'vitest';
import { ApprovalListItem } from '../approval-list-item';
import type { InboxItem } from '@/types/inbox';

// Mock sample approval item
const mockApprovalItem: InboxItem = {
    id: '1',
    type: 'approval',
    title: 'Review Budget Proposal',
    contentPreview: 'Please review the updated budget proposal for Q2...',
    fullContent: 'Full content of the budget proposal...',
    sourceId: 'agent-1',
    sourceName: 'Budget Assistant',
    sourceType: 'ai_agent',
    relatedWorkOrderId: 'wo-123',
    relatedWorkOrderTitle: 'Q2 Financial Planning',
    relatedProjectId: 'proj-456',
    relatedProjectName: 'Finance Operations',
    urgency: 'urgent',
    aiConfidence: 'high',
    qaValidation: 'passed',
    createdAt: '2026-01-18T10:00:00Z',
    waitingHours: 4,
};

describe('ApprovalListItem', () => {
    it('displays source, context, and waiting time for approval items', () => {
        const onSelect = vi.fn();
        const onView = vi.fn();

        render(
            <ApprovalListItem
                item={mockApprovalItem}
                isSelected={false}
                onSelect={onSelect}
                onView={onView}
            />
        );

        // Check source name is displayed
        expect(screen.getByText('Budget Assistant')).toBeInTheDocument();

        // Check work order context is displayed
        expect(screen.getByText('Q2 Financial Planning')).toBeInTheDocument();

        // Check waiting time is displayed
        expect(screen.getByText('4h')).toBeInTheDocument();

        // Check AI confidence badge is displayed
        expect(screen.getByText(/high/i)).toBeInTheDocument();
    });

    it('displays urgency badge when item is urgent', () => {
        const onSelect = vi.fn();
        const onView = vi.fn();

        render(
            <ApprovalListItem
                item={mockApprovalItem}
                isSelected={false}
                onSelect={onSelect}
                onView={onView}
            />
        );

        // Check URGENT badge is displayed
        expect(screen.getByText('URGENT')).toBeInTheDocument();
    });

    it('supports checkbox selection for bulk actions', async () => {
        const user = userEvent.setup();
        const onSelect = vi.fn();
        const onView = vi.fn();

        render(
            <ApprovalListItem
                item={mockApprovalItem}
                isSelected={false}
                onSelect={onSelect}
                onView={onView}
            />
        );

        // Find and click checkbox
        const checkbox = screen.getByRole('checkbox');
        expect(checkbox).toBeInTheDocument();

        await user.click(checkbox);
        expect(onSelect).toHaveBeenCalled();
    });
});
