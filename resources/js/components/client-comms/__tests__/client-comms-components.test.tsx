import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { DraftClientUpdateButton } from '../DraftClientUpdateButton';
import { DraftPreviewModal } from '../DraftPreviewModal';
import { CommunicationTypeSelector } from '../CommunicationTypeSelector';
import type { DraftMessage, DraftRecipient, DraftEntity } from '@/types/client-comms.d';

// Mock Inertia router
vi.mock('@inertiajs/react', () => ({
    router: {
        post: vi.fn(),
        patch: vi.fn(),
    },
    useForm: () => ({
        data: {
            entity_type: 'project',
            entity_id: '1',
            communication_type: 'status_update',
            notes: '',
        },
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        errors: {},
        reset: vi.fn(),
    }),
    usePage: () => ({
        props: {
            auth: { user: { id: '1', name: 'Test User' } },
        },
    }),
}));

// Mock draft message for testing
const mockDraft: DraftMessage = {
    id: 'draft-1',
    content: 'Dear Client,\n\nWe are pleased to provide you with a progress report on your project.\n\nBest regards,\nThe Team',
    communicationType: 'status_update',
    confidence: 'high',
    targetLanguage: 'en',
    createdAt: '2024-01-23T10:00:00Z',
    draftStatus: 'draft',
    editedAt: null,
};

// Mock recipient for testing
const mockRecipient: DraftRecipient = {
    name: 'John Client',
    email: 'john@example.com',
    preferredLanguage: 'en',
};

// Mock entity for testing
const mockEntity: DraftEntity = {
    type: 'Project',
    id: '123',
    name: 'Website Redesign',
};

describe('DraftClientUpdateButton', () => {
    it('renders button with correct label', () => {
        render(
            <DraftClientUpdateButton
                entityType="project"
                entityId="123"
            />
        );

        const button = screen.getByRole('button', { name: /draft client update/i });
        expect(button).toBeInTheDocument();
    });

    it('opens dialog on click and shows communication type options', async () => {
        const user = userEvent.setup();

        render(
            <DraftClientUpdateButton
                entityType="project"
                entityId="123"
            />
        );

        const button = screen.getByRole('button', { name: /draft client update/i });
        await user.click(button);

        // Dialog should be open
        await waitFor(() => {
            expect(screen.getByRole('dialog')).toBeInTheDocument();
        });

        // Check that communication type selector is visible
        expect(screen.getByText(/communication type/i)).toBeInTheDocument();
    });

    it('respects disabled prop', () => {
        render(
            <DraftClientUpdateButton
                entityType="project"
                entityId="123"
                disabled={true}
            />
        );

        const button = screen.getByRole('button', { name: /draft client update/i });
        expect(button).toBeDisabled();
    });
});

describe('DraftPreviewModal', () => {
    const defaultProps = {
        draft: mockDraft,
        recipient: mockRecipient,
        entity: mockEntity,
        isOpen: true,
        onClose: vi.fn(),
        onApprove: vi.fn(),
        onReject: vi.fn(),
        onEdit: vi.fn(),
    };

    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('displays draft content with formatting', () => {
        render(<DraftPreviewModal {...defaultProps} />);

        // Check that draft content is displayed
        expect(screen.getByText(/progress report on your project/i)).toBeInTheDocument();
    });

    it('shows metadata including communication type badge and confidence badge', () => {
        render(<DraftPreviewModal {...defaultProps} />);

        // Check metadata is displayed - use getAllByText since "Status Update" appears in multiple places
        const statusUpdateBadges = screen.getAllByText(/status update/i);
        expect(statusUpdateBadges.length).toBeGreaterThan(0);

        // Check confidence badge
        const confidenceBadge = screen.getByText(/high confidence/i);
        expect(confidenceBadge).toBeInTheDocument();
    });

    it('shows recipient info (Party name and email)', () => {
        render(<DraftPreviewModal {...defaultProps} />);

        // Check recipient info
        expect(screen.getByText(/john client/i)).toBeInTheDocument();
        expect(screen.getByText(/john@example.com/i)).toBeInTheDocument();
    });

    it('has action buttons: Approve, Reject, Edit', () => {
        render(<DraftPreviewModal {...defaultProps} />);

        expect(screen.getByRole('button', { name: /approve/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /reject/i })).toBeInTheDocument();
        expect(screen.getByRole('button', { name: /edit/i })).toBeInTheDocument();
    });

    it('calls onApprove when approve button is clicked', async () => {
        const user = userEvent.setup();
        const onApprove = vi.fn();

        render(<DraftPreviewModal {...defaultProps} onApprove={onApprove} />);

        const approveButton = screen.getByRole('button', { name: /approve/i });
        await user.click(approveButton);

        expect(onApprove).toHaveBeenCalledTimes(1);
    });
});

describe('CommunicationTypeSelector', () => {
    it('renders the selector with placeholder', () => {
        const onChange = vi.fn();

        render(
            <CommunicationTypeSelector
                value={undefined}
                onChange={onChange}
            />
        );

        // Check the trigger is rendered
        const trigger = screen.getByRole('combobox');
        expect(trigger).toBeInTheDocument();
    });

    it('displays current value correctly when provided', () => {
        const onChange = vi.fn();

        render(
            <CommunicationTypeSelector
                value="deliverable_notification"
                onChange={onChange}
            />
        );

        expect(screen.getByText('Deliverable Notification')).toBeInTheDocument();
    });

    it('displays status update when selected', () => {
        const onChange = vi.fn();

        render(
            <CommunicationTypeSelector
                value="status_update"
                onChange={onChange}
            />
        );

        expect(screen.getByText('Status Update')).toBeInTheDocument();
    });

    it('respects disabled prop', () => {
        const onChange = vi.fn();

        render(
            <CommunicationTypeSelector
                value="status_update"
                onChange={onChange}
                disabled={true}
            />
        );

        const trigger = screen.getByRole('combobox');
        // When disabled, Radix UI sets data-disabled attribute
        expect(trigger).toHaveAttribute('data-disabled');
    });
});
