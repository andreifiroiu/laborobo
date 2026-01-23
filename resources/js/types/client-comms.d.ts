// =============================================================================
// Client Communications Types
// =============================================================================

import type { AIConfidence } from './pm-copilot.d';

/**
 * Communication type enum values matching backend CommunicationType enum
 */
export type CommunicationType =
    | 'status_update'
    | 'deliverable_notification'
    | 'clarification_request'
    | 'milestone_announcement';

/**
 * Communication type option for selector
 */
export interface CommunicationTypeOption {
    value: CommunicationType;
    label: string;
    description: string;
}

/**
 * Draft status enum values matching backend DraftStatus enum
 */
export type DraftStatus = 'draft' | 'approved' | 'rejected' | 'sent';

/**
 * Entity type for client communications
 */
export type EntityType = 'project' | 'work_order';

/**
 * Draft message from the backend
 */
export interface DraftMessage {
    id: string;
    content: string;
    communicationType: CommunicationType | null;
    confidence: AIConfidence;
    targetLanguage: string;
    createdAt: string;
    draftStatus: DraftStatus | null;
    editedAt: string | null;
}

/**
 * Recipient info for draft preview
 */
export interface DraftRecipient {
    name: string;
    email: string | null;
    preferredLanguage: string;
}

/**
 * Entity info for draft preview
 */
export interface DraftEntity {
    type: 'Project' | 'WorkOrder';
    id: string;
    name: string;
}

// =============================================================================
// Component Props Types
// =============================================================================

/**
 * Props for DraftClientUpdateButton component
 */
export interface DraftClientUpdateButtonProps {
    entityType: EntityType;
    entityId: string;
    onDraftCreated?: () => void;
    disabled?: boolean;
}

/**
 * Props for CommunicationTypeSelector component
 */
export interface CommunicationTypeSelectorProps {
    value: CommunicationType | undefined;
    onChange: (value: CommunicationType) => void;
    disabled?: boolean;
}

/**
 * Props for DraftPreviewModal component
 */
export interface DraftPreviewModalProps {
    draft: DraftMessage | null;
    recipient: DraftRecipient | null;
    entity: DraftEntity | null;
    isOpen: boolean;
    onClose: () => void;
    onApprove: () => void;
    onReject: (reason: string) => void;
    onEdit: (content: string) => void;
    isApproving?: boolean;
    isRejecting?: boolean;
    isEditing?: boolean;
}

/**
 * Props for LanguageSelector component
 */
export interface LanguageSelectorProps {
    value: string;
    onChange: (value: string) => void;
    availableLanguages?: string[];
    disabled?: boolean;
}

/**
 * Props for AgentDraftInboxItem component
 */
export interface AgentDraftInboxItemProps {
    item: {
        id: string;
        title: string;
        contentPreview: string;
        fullContent: string;
        communicationType: CommunicationType | null;
        confidence: AIConfidence;
        recipientName: string | null;
        recipientEmail: string | null;
        relatedWorkOrderId: string | null;
        relatedWorkOrderTitle: string | null;
        relatedProjectId: string | null;
        relatedProjectName: string | null;
        createdAt: string;
        waitingHours: number;
    };
    isSelected: boolean;
    onSelect: () => void;
    onView: () => void;
    onQuickApprove?: () => void;
    onQuickReject?: () => void;
}

// =============================================================================
// Form Types
// =============================================================================

/**
 * Form data for creating a draft
 */
export interface CreateDraftFormData {
    entity_type: EntityType;
    entity_id: string;
    communication_type: CommunicationType;
    notes?: string;
}

/**
 * Form data for rejecting a draft
 */
export interface RejectDraftFormData {
    reason: string;
}
