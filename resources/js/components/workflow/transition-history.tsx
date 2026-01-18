import * as React from 'react';
import { ArrowRightIcon, MessageSquareIcon } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
    StatusBadge,
    type TaskStatus,
    type WorkOrderStatus,
    type Status,
} from '@/components/ui/status-badge';
import { cn } from '@/lib/utils';

/**
 * Comment categories for transitions, matching backend categories
 */
export type CommentCategory =
    | 'design_impact'
    | 'change_request'
    | 'scope_change'
    | 'quality_issue'
    | 'technical_debt'
    | 'general';

/**
 * User information for transition display
 */
export interface TransitionUser {
    id: number;
    name: string;
    email: string;
    avatar?: string;
}

/**
 * Status transition record from the backend
 */
export interface StatusTransition {
    id: number;
    fromStatus: string;
    toStatus: string;
    user: TransitionUser;
    createdAt: string;
    comment: string | null;
    commentCategory: CommentCategory | null;
}

export interface TransitionHistoryProps {
    /** Array of status transitions to display */
    transitions: StatusTransition[];
    /** Whether transitions are for a task or work order */
    variant: 'task' | 'work_order';
    /** Additional className for the container */
    className?: string;
}

/**
 * Category badge styling and labels
 */
const categoryConfig: Record<CommentCategory, { label: string; className: string }> = {
    design_impact: {
        label: 'Design Impact',
        className:
            'bg-purple-100 text-purple-700 border-purple-200 dark:bg-purple-900 dark:text-purple-300 dark:border-purple-800',
    },
    change_request: {
        label: 'Change Request',
        className:
            'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-900 dark:text-blue-300 dark:border-blue-800',
    },
    scope_change: {
        label: 'Scope Change',
        className:
            'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-900 dark:text-yellow-300 dark:border-yellow-800',
    },
    quality_issue: {
        label: 'Quality Issue',
        className:
            'bg-red-100 text-red-700 border-red-200 dark:bg-red-900 dark:text-red-300 dark:border-red-800',
    },
    technical_debt: {
        label: 'Technical Debt',
        className:
            'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
    },
    general: {
        label: 'General',
        className:
            'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
    },
};

/**
 * Check if a status indicates a rejection/revision request
 */
function isRejectionTransition(toStatus: string): boolean {
    return toStatus === 'revision_requested';
}

/**
 * Format a date string to a human-readable format
 */
function formatTimestamp(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
        // Today - show time
        return date.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    } else if (diffDays === 1) {
        return 'Yesterday';
    } else if (diffDays < 7) {
        return `${diffDays} days ago`;
    } else {
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined,
        });
    }
}

/**
 * Get initials from a user's name for avatar fallback
 */
function getInitials(name: string): string {
    const parts = name.split(' ').filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
}

interface TransitionHistoryItemProps {
    transition: StatusTransition;
    variant: 'task' | 'work_order';
    isLast: boolean;
}

/**
 * TransitionHistoryItem displays a single transition with avatar, status badges, timestamp, and comment.
 */
function TransitionHistoryItem({ transition, variant, isLast }: TransitionHistoryItemProps) {
    const isRejection = isRejectionTransition(transition.toStatus);
    const hasComment = transition.comment !== null && transition.comment.trim().length > 0;

    return (
        <li
            role="listitem"
            className={cn('relative flex gap-3 pb-6', isLast && 'pb-0')}
            data-transition-id={transition.id}
        >
            {/* Timeline connector line */}
            {!isLast && (
                <div
                    className="bg-border absolute top-8 left-4 -ml-px h-full w-0.5"
                    aria-hidden="true"
                />
            )}

            {/* Avatar */}
            <Avatar className="relative z-10 size-8 shrink-0">
                <AvatarImage src={transition.user.avatar} alt={transition.user.name} />
                <AvatarFallback className="text-xs">
                    {getInitials(transition.user.name)}
                </AvatarFallback>
            </Avatar>

            {/* Content */}
            <div className="flex min-w-0 flex-1 flex-col gap-1.5">
                {/* Header row with user name and timestamp */}
                <div className="flex items-center gap-2">
                    <span className="text-foreground text-sm font-medium">
                        {transition.user.name}
                    </span>
                    <span className="text-muted-foreground text-xs">
                        {formatTimestamp(transition.createdAt)}
                    </span>
                </div>

                {/* Status transition badges */}
                <div className="flex flex-wrap items-center gap-1.5">
                    <StatusBadge
                        status={transition.fromStatus as Status}
                        variant={variant}
                        size="sm"
                    />
                    <ArrowRightIcon
                        className="text-muted-foreground size-3 shrink-0"
                        aria-label="transitioned to"
                    />
                    <StatusBadge
                        status={transition.toStatus as Status}
                        variant={variant}
                        size="sm"
                    />
                </div>

                {/* Comment section */}
                {hasComment && (
                    <div
                        className={cn(
                            'mt-2 rounded-lg border p-3',
                            isRejection
                                ? 'border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950/50'
                                : 'border-border bg-muted/30'
                        )}
                    >
                        {/* Comment header with category badge if present */}
                        <div className="mb-1.5 flex items-center gap-2">
                            <MessageSquareIcon
                                className={cn(
                                    'size-3.5 shrink-0',
                                    isRejection ? 'text-orange-600 dark:text-orange-400' : 'text-muted-foreground'
                                )}
                                aria-hidden="true"
                            />
                            {transition.commentCategory && (
                                <Badge
                                    variant="outline"
                                    className={cn(
                                        'text-[10px] px-1.5 py-0',
                                        categoryConfig[transition.commentCategory].className
                                    )}
                                >
                                    {categoryConfig[transition.commentCategory].label}
                                </Badge>
                            )}
                        </div>
                        {/* Comment text */}
                        <p
                            className={cn(
                                'text-sm leading-relaxed',
                                isRejection
                                    ? 'text-orange-900 dark:text-orange-100'
                                    : 'text-foreground'
                            )}
                        >
                            {transition.comment}
                        </p>
                    </div>
                )}
            </div>
        </li>
    );
}

/**
 * TransitionHistory displays a chronological list of status transitions.
 * Rejection feedback is prominently displayed with distinct styling.
 */
function TransitionHistory({ transitions, variant, className }: TransitionHistoryProps) {
    if (transitions.length === 0) {
        return (
            <div
                className={cn(
                    'text-muted-foreground flex flex-col items-center justify-center py-8 text-center',
                    className
                )}
            >
                <MessageSquareIcon className="text-muted-foreground/50 mb-2 size-8" aria-hidden="true" />
                <p className="text-sm">No activity yet</p>
            </div>
        );
    }

    return (
        <div className={cn('relative', className)}>
            <ul className="space-y-0" role="list" aria-label="Status transition history">
                {transitions.map((transition, index) => (
                    <TransitionHistoryItem
                        key={transition.id}
                        transition={transition}
                        variant={variant}
                        isLast={index === transitions.length - 1}
                    />
                ))}
            </ul>
        </div>
    );
}

export { TransitionHistory, TransitionHistoryItem, categoryConfig, formatTimestamp, getInitials };
