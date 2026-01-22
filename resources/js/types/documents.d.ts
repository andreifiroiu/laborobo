// =============================================================================
// Document Types
// =============================================================================

import type { CommunicationMessage, CommunicationThread } from './communications.d';

/**
 * Annotation data from API response.
 * Represents a positional comment marker on a document.
 */
export interface DocumentAnnotation {
    id: string;
    documentId: string;
    page: number | null;
    xPercent: number;
    yPercent: number;
    isForPdf: boolean;
    createdAt: string;
    updatedAt: string;
    creator: {
        id: string;
        name: string;
    } | null;
    thread: {
        id: string;
        messageCount: number;
        lastActivity: string | null;
    } | null;
    preview?: {
        content: string;
        authorName: string;
    };
    messages?: CommunicationMessage[];
    canEdit: boolean;
    canDelete: boolean;
}

/**
 * Document comment thread data from API response.
 */
export interface DocumentCommentThread {
    thread: CommunicationThread | null;
    messages: CommunicationMessage[];
}

/**
 * Props for DocumentComments component.
 */
export interface DocumentCommentsProps {
    documentId: string;
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
}

/**
 * Props for AnnotationLayer component.
 */
export interface AnnotationLayerProps {
    documentId: string;
    annotations: DocumentAnnotation[];
    currentPage?: number;
    onAnnotationClick: (annotation: DocumentAnnotation) => void;
    onCreateAnnotation?: (position: AnnotationPosition) => void;
    showAnnotations?: boolean;
    className?: string;
}

/**
 * Position data for creating a new annotation.
 */
export interface AnnotationPosition {
    xPercent: number;
    yPercent: number;
    page: number | null;
}

/**
 * Props for AnnotationMarker component.
 */
export interface AnnotationMarkerProps {
    annotation: DocumentAnnotation;
    index: number;
    onClick: () => void;
    isActive?: boolean;
}

/**
 * Props for AnnotationPopover component.
 */
export interface AnnotationPopoverProps {
    annotation: DocumentAnnotation | null;
    documentId: string;
    onClose: () => void;
    onReplyAdded?: () => void;
    anchorPosition?: { x: number; y: number };
}

/**
 * Props for creating a new annotation.
 */
export interface CreateAnnotationData {
    page: number | null;
    xPercent: number;
    yPercent: number;
    content: string;
}

// =============================================================================
// Share Link Types
// =============================================================================

/**
 * Access log entry for a share link.
 */
export interface ShareAccessLog {
    id: string;
    accessedAt: string;
    ipAddress: string | null;
    userAgent: string | null;
}

/**
 * Share link data from API response.
 */
export interface DocumentShareLink {
    id: string;
    documentId: string;
    token: string;
    url: string;
    expiresAt: string | null;
    isExpired: boolean;
    hasPassword: boolean;
    allowDownload: boolean;
    accessCount: number;
    createdAt: string;
    creator: {
        id: string;
        name: string;
    } | null;
    recentAccesses?: Array<{
        accessedAt: string;
        ipAddress: string | null;
    }>;
    accessLog?: ShareAccessLog[];
}

/**
 * Props for ShareLinkDialog component.
 */
export interface ShareLinkDialogProps {
    documentId: string;
    isOpen: boolean;
    onOpenChange: (open: boolean) => void;
    onCreated?: (shareLink: DocumentShareLink) => void;
}

/**
 * Props for ShareLinkManagement component.
 */
export interface ShareLinkManagementProps {
    documentId: string;
    className?: string;
}

/**
 * Props for AccessLogTable component.
 */
export interface AccessLogTableProps {
    accessLogs: ShareAccessLog[];
    isLoading?: boolean;
    className?: string;
}

/**
 * Shared document data for public view.
 */
export interface SharedDocument {
    id: string;
    name: string;
    type: string;
    fileSize: number;
    previewUrl: string;
    allowDownload: boolean;
}

/**
 * Props for SharedDocumentPage component.
 */
export interface SharedDocumentPageProps {
    document?: SharedDocument;
    token: string;
    requiresPassword?: boolean;
    documentName?: string;
    error?: string;
}

/**
 * Data for creating a share link.
 */
export interface CreateShareLinkData {
    expires_in_days: number | null;
    password: string | null;
    allow_download: boolean;
}
