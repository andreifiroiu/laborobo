import { useState, useEffect, useCallback, useRef } from 'react';
import { router } from '@inertiajs/react';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Loader2, RefreshCw, MessageSquare } from 'lucide-react';
import { MessageItem } from './message-item';
import { MessageInput } from './message-input';
import { cn } from '@/lib/utils';
import type {
    CommunicationsPanelProps,
    CommunicationMessage,
    CommunicationThread,
} from '@/types/communications';

const DEFAULT_POLL_INTERVAL_MS = 30000; // 30 seconds

interface ApiResponse {
    thread: CommunicationThread | null;
    messages: CommunicationMessage[];
}

export function CommunicationsPanel({
    threadableType,
    threadableId,
    open,
    onOpenChange,
}: CommunicationsPanelProps) {
    const [thread, setThread] = useState<CommunicationThread | null>(null);
    const [messages, setMessages] = useState<CommunicationMessage[]>([]);
    const [isLoading, setIsLoading] = useState(true);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [currentUserId, setCurrentUserId] = useState<string>('');

    const pollIntervalRef = useRef<NodeJS.Timeout | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    // Get API route path based on threadable type
    const getApiPath = useCallback(() => {
        const idPart = threadableId;
        switch (threadableType) {
            case 'projects':
                return `/work/projects/${idPart}/communications`;
            case 'work-orders':
                return `/work/work-orders/${idPart}/communications`;
            case 'tasks':
                return `/work/tasks/${idPart}/communications`;
            default:
                return `/work/${threadableType}/${idPart}/communications`;
        }
    }, [threadableType, threadableId]);

    // Fetch messages from API
    const fetchMessages = useCallback(
        async (showLoading = true) => {
            if (showLoading) {
                setIsLoading(true);
            } else {
                setIsRefreshing(true);
            }
            setError(null);

            try {
                const response = await fetch(getApiPath(), {
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch communications');
                }

                const data: ApiResponse = await response.json();
                setThread(data.thread);
                setMessages(data.messages || []);

                // Try to get current user ID from the first message authored by current user
                // or fall back to first message's canEdit flag
                if (data.messages.length > 0) {
                    const ownMessage = data.messages.find((m) => m.canEdit || m.canDelete);
                    if (ownMessage) {
                        setCurrentUserId(ownMessage.authorId);
                    }
                }
            } catch (err) {
                setError(err instanceof Error ? err.message : 'An error occurred');
            } finally {
                setIsLoading(false);
                setIsRefreshing(false);
            }
        },
        [getApiPath]
    );

    // Handle message edit
    const handleEdit = useCallback(
        (messageId: string, content: string) => {
            router.patch(
                `/work/communications/messages/${messageId}`,
                { content },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        fetchMessages(false);
                    },
                }
            );
        },
        [fetchMessages]
    );

    // Handle message delete
    const handleDelete = useCallback(
        (messageId: string) => {
            if (!confirm('Are you sure you want to delete this message?')) {
                return;
            }

            router.delete(`/work/communications/messages/${messageId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    fetchMessages(false);
                },
            });
        },
        [fetchMessages]
    );

    // Handle message sent
    const handleMessageSent = useCallback(() => {
        fetchMessages(false);
    }, [fetchMessages]);

    // Scroll to bottom of messages
    const scrollToBottom = useCallback(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, []);

    // Set up polling when panel is open
    useEffect(() => {
        if (open) {
            // Initial fetch
            fetchMessages();

            // Set up polling interval
            pollIntervalRef.current = setInterval(() => {
                fetchMessages(false);
            }, DEFAULT_POLL_INTERVAL_MS);
        }

        return () => {
            if (pollIntervalRef.current) {
                clearInterval(pollIntervalRef.current);
                pollIntervalRef.current = null;
            }
        };
    }, [open, fetchMessages]);

    // Scroll to bottom when messages change
    useEffect(() => {
        if (messages.length > 0 && !isLoading) {
            scrollToBottom();
        }
    }, [messages.length, isLoading, scrollToBottom]);

    // Get title based on threadable type
    const getTitle = () => {
        switch (threadableType) {
            case 'projects':
                return 'Project Communications';
            case 'work-orders':
                return 'Work Order Communications';
            case 'tasks':
                return 'Task Communications';
            default:
                return 'Communications';
        }
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent className="flex flex-col w-full sm:max-w-md">
                <SheetHeader>
                    <div className="flex items-center justify-between">
                        <SheetTitle className="flex items-center gap-2">
                            <MessageSquare className="h-5 w-5" />
                            {getTitle()}
                        </SheetTitle>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => fetchMessages(false)}
                            disabled={isRefreshing}
                            aria-label="Refresh messages"
                        >
                            <RefreshCw
                                className={cn('h-4 w-4', isRefreshing && 'animate-spin')}
                            />
                        </Button>
                    </div>
                    <SheetDescription>
                        {thread
                            ? `${thread.messageCount} message${thread.messageCount !== 1 ? 's' : ''}`
                            : 'Discussion thread for this work item'}
                    </SheetDescription>
                </SheetHeader>

                {/* Messages area */}
                <div className="flex-1 overflow-hidden flex flex-col mt-4">
                    {isLoading ? (
                        <div className="flex-1 flex items-center justify-center">
                            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                        </div>
                    ) : error ? (
                        <div className="flex-1 flex flex-col items-center justify-center gap-2">
                            <p className="text-sm text-destructive">{error}</p>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => fetchMessages()}
                            >
                                Try again
                            </Button>
                        </div>
                    ) : messages.length === 0 ? (
                        <div className="flex-1 flex flex-col items-center justify-center text-center p-4">
                            <MessageSquare className="h-12 w-12 text-muted-foreground/50 mb-3" />
                            <p className="text-sm text-muted-foreground">
                                No messages yet.
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Start the conversation!
                            </p>
                        </div>
                    ) : (
                        <div className="flex-1 overflow-y-auto space-y-3 pr-1">
                            {/* Messages in reverse chronological order (newest at bottom) */}
                            {[...messages].reverse().map((message) => (
                                <MessageItem
                                    key={message.id}
                                    message={message}
                                    currentUserId={currentUserId}
                                    onEdit={handleEdit}
                                    onDelete={handleDelete}
                                />
                            ))}
                            <div ref={messagesEndRef} />
                        </div>
                    )}

                    {/* Message input */}
                    <MessageInput
                        threadableType={threadableType}
                        threadableId={threadableId}
                        onMessageSent={handleMessageSent}
                    />
                </div>
            </SheetContent>
        </Sheet>
    );
}
