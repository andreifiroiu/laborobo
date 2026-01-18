import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { ApprovalDetailPanel } from '../approval-detail-panel';
import type { InboxItem } from '@/types/inbox';

// Mock router
const mockRouterPost = vi.fn();
vi.mock('@inertiajs/react', () => ({
    router: {
        post: (...args: unknown[]) => mockRouterPost(...args),
    },
}));

// Mock sample approval item with full context
const mockApprovalItem: InboxItem = {
    id: '1',
    type: 'approval',
    title: 'Review Budget Proposal',
    contentPreview: 'Please review the updated budget proposal for Q2...',
    fullContent: `# Missing Information

**Work Order:** Homepage Redesign Implementation
**Blocked Item:** Design mockups task

## Issue
I need the client's logo files (SVG and PNG formats) to create the design mockups, but they're not in the project assets folder.

## Impact
- Design mockups task is blocked
- 2-day delay if not resolved quickly
- Downstream development tasks will be affected

## What I've Checked
- Project assets folder: Empty
- Previous work order attachments: No logo files
- Brand guidelines document: Only shows logo examples, no source files

## Action Needed
Request logo files from the client (Acme Corp contact: jane@acmecorp.com) in these formats:
- SVG (vector)
- PNG (transparent, high-res)
- Color and monochrome versions`,
    sourceId: 'agent-1',
    sourceName: 'Designer AI',
    sourceType: 'ai_agent',
    relatedWorkOrderId: 'wo-123',
    relatedWorkOrderTitle: 'Acme Corp Homepage Redesign',
    relatedProjectId: 'proj-456',
    relatedProjectName: 'Acme Corp Rebrand',
    urgency: 'urgent',
    aiConfidence: 'high',
    qaValidation: 'passed',
    createdAt: '2026-01-05T12:00:00Z',
    waitingHours: 5,
};

describe('ApprovalDetailPanel', () => {
    beforeEach(() => {
        mockRouterPost.mockClear();
    });

    it('displays full item content and context', () => {
        const onClose = vi.fn();

        render(
            <ApprovalDetailPanel
                item={mockApprovalItem}
                isOpen={true}
                onClose={onClose}
            />
        );

        // Check title is displayed
        expect(screen.getByText('Review Budget Proposal')).toBeInTheDocument();

        // Check source name is displayed (use getAllByText since it may appear multiple places)
        const sourceElements = screen.getAllByText('Designer AI');
        expect(sourceElements.length).toBeGreaterThan(0);

        // Check project context
        expect(screen.getByText('Acme Corp Rebrand')).toBeInTheDocument();

        // Check work order context (use getAllByText for partial matches)
        const workOrderLinks = screen.getAllByText(/Acme Corp Homepage Redesign/);
        expect(workOrderLinks.length).toBeGreaterThan(0);

        // Check AI confidence is displayed
        expect(screen.getByText('HIGH')).toBeInTheDocument();

        // Check waiting time is displayed
        expect(screen.getByText(/5h/)).toBeInTheDocument();

        // Check urgency badge
        expect(screen.getByText('URGENT')).toBeInTheDocument();

        // Check full content is rendered (looking for part of the markdown content)
        expect(screen.getByText(/Missing Information/)).toBeInTheDocument();
    });

    it('Approve button triggers transition to Approved status', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();

        render(
            <ApprovalDetailPanel
                item={mockApprovalItem}
                isOpen={true}
                onClose={onClose}
            />
        );

        // Find and click the Approve button
        const approveButton = screen.getByRole('button', { name: /approve/i });
        expect(approveButton).toBeInTheDocument();

        await user.click(approveButton);

        // Verify the router.post was called with the right parameters
        await waitFor(() => {
            expect(mockRouterPost).toHaveBeenCalled();
            const [url] = mockRouterPost.mock.calls[0];
            expect(url).toContain('/approve');
        });
    });

    it('Request Changes button opens rejection dialog and requires comment', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();

        render(
            <ApprovalDetailPanel
                item={mockApprovalItem}
                isOpen={true}
                onClose={onClose}
            />
        );

        // Find and click the Request Changes button
        const requestChangesButton = screen.getByRole('button', { name: /request changes/i });
        expect(requestChangesButton).toBeInTheDocument();

        await user.click(requestChangesButton);

        // Verify the rejection dialog opens with feedback textarea
        await waitFor(() => {
            expect(screen.getByText(/provide feedback/i)).toBeInTheDocument();
        });

        // Check that the feedback textarea is present
        const textarea = screen.getByRole('textbox');
        expect(textarea).toBeInTheDocument();

        // Type feedback
        await user.type(textarea, 'Please include more details on the budget breakdown.');

        // Click confirm in the dialog
        const confirmButton = screen.getByRole('button', { name: /reject with feedback/i });
        await user.click(confirmButton);

        // Verify the router.post was called with feedback
        await waitFor(() => {
            expect(mockRouterPost).toHaveBeenCalled();
            const [url, data] = mockRouterPost.mock.calls[0];
            expect(url).toContain('/reject');
            expect(data.feedback).toBe('Please include more details on the budget breakdown.');
        });
    });
});
