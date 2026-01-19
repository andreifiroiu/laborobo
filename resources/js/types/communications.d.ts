// =============================================================================
// Communications Types
// =============================================================================

export type MessageType =
    | 'note'
    | 'suggestion'
    | 'decision'
    | 'question'
    | 'status_update'
    | 'approval_request'
    | 'message';

export type AuthorType = 'human' | 'ai_agent';

export interface MessageMention {
    id: string;
    type: string;
    entityId: string;
    name: string;
}

export interface MessageAttachment {
    id: string;
    name: string;
    fileUrl: string;
    fileSize: number;
    mimeType: string;
}

export interface MessageReaction {
    emoji: string;
    count: number;
    hasReacted: boolean;
    users: Array<{ id: string }>;
}

export interface CommunicationMessage {
    id: string;
    authorId: string;
    authorName: string;
    authorType: AuthorType;
    timestamp: string;
    content: string;
    type: MessageType;
    editedAt: string | null;
    canEdit: boolean;
    canDelete: boolean;
    mentions: MessageMention[];
    attachments: MessageAttachment[];
    reactions: MessageReaction[];
}

export interface CommunicationThread {
    id: string;
    messageCount: number;
    lastActivity: string | null;
}

export interface MentionSuggestion {
    id: string;
    name: string;
    type: 'user' | 'project' | 'work_order' | 'task';
    username?: string;
    prefix?: string;
}

export interface CommunicationsPanelProps {
    threadableType: 'projects' | 'work-orders' | 'tasks';
    threadableId: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export interface MessageItemProps {
    message: CommunicationMessage;
    currentUserId: string;
    onEdit: (messageId: string, content: string) => void;
    onDelete: (messageId: string) => void;
}

export interface MessageInputProps {
    threadableType: 'projects' | 'work-orders' | 'tasks';
    threadableId: string;
    onMessageSent?: () => void;
}

export interface MentionInputProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;
    className?: string;
}

export interface ReactionPickerProps {
    messageId: string;
    onReactionAdd: (emoji: string) => void;
}

export interface MessageReactionsProps {
    reactions: MessageReaction[];
    messageId: string;
    currentUserId: string;
    onToggleReaction: (emoji: string) => void;
}

export interface MessageAttachmentsProps {
    attachments: MessageAttachment[];
}
