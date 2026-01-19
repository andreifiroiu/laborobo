import { useState, useCallback, useRef, useEffect } from 'react';
import { Textarea } from '@/components/ui/textarea';
import { Popover, PopoverContent, PopoverAnchor } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { User, FolderKanban, FileText, CheckSquare, Loader2 } from 'lucide-react';
import type { MentionInputProps, MentionSuggestion } from '@/types/communications';

const MENTION_TRIGGER = '@';
const DEBOUNCE_MS = 200;
const MIN_QUERY_LENGTH = 1;

export function MentionInput({
    value,
    onChange,
    placeholder,
    disabled = false,
    className,
}: MentionInputProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [suggestions, setSuggestions] = useState<MentionSuggestion[]>([]);
    const [selectedIndex, setSelectedIndex] = useState(0);
    const [isLoading, setIsLoading] = useState(false);
    const [mentionQuery, setMentionQuery] = useState('');
    const [mentionStartPos, setMentionStartPos] = useState<number | null>(null);

    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const debounceRef = useRef<NodeJS.Timeout>();

    const searchMentions = useCallback(async (query: string) => {
        if (query.length < MIN_QUERY_LENGTH) {
            setSuggestions([]);
            setIsOpen(false);
            return;
        }

        setIsLoading(true);

        try {
            const response = await fetch(
                `/api/mentions/search?q=${encodeURIComponent(query)}&type=all`,
                { credentials: 'same-origin' }
            );

            if (!response.ok) {
                throw new Error('Failed to fetch mentions');
            }

            const data = await response.json();
            setSuggestions(data.results || []);
            setIsOpen(data.results?.length > 0);
            setSelectedIndex(0);
        } catch {
            setSuggestions([]);
            setIsOpen(false);
        } finally {
            setIsLoading(false);
        }
    }, []);

    const handleInputChange = useCallback(
        (e: React.ChangeEvent<HTMLTextAreaElement>) => {
            const newValue = e.target.value;
            const cursorPos = e.target.selectionStart || 0;

            onChange(newValue);

            // Check for mention trigger
            const textBeforeCursor = newValue.slice(0, cursorPos);
            const lastAtIndex = textBeforeCursor.lastIndexOf(MENTION_TRIGGER);

            if (lastAtIndex !== -1) {
                const textAfterAt = textBeforeCursor.slice(lastAtIndex + 1);
                // Check if there's a space before the @ (or it's at the start)
                const charBeforeAt = lastAtIndex > 0 ? newValue[lastAtIndex - 1] : ' ';

                if ((charBeforeAt === ' ' || charBeforeAt === '\n' || lastAtIndex === 0) &&
                    !textAfterAt.includes(' ') && !textAfterAt.includes('\n')) {
                    setMentionQuery(textAfterAt);
                    setMentionStartPos(lastAtIndex);

                    // Debounce the search
                    if (debounceRef.current) {
                        clearTimeout(debounceRef.current);
                    }
                    debounceRef.current = setTimeout(() => {
                        searchMentions(textAfterAt);
                    }, DEBOUNCE_MS);
                } else {
                    closeMentions();
                }
            } else {
                closeMentions();
            }
        },
        [onChange, searchMentions]
    );

    const closeMentions = useCallback(() => {
        setIsOpen(false);
        setSuggestions([]);
        setMentionQuery('');
        setMentionStartPos(null);
    }, []);

    const insertMention = useCallback(
        (suggestion: MentionSuggestion) => {
            if (mentionStartPos === null) return;

            const beforeMention = value.slice(0, mentionStartPos);
            const afterMention = value.slice(mentionStartPos + mentionQuery.length + 1);

            // Format mention based on type
            let mentionText: string;
            switch (suggestion.type) {
                case 'user':
                    mentionText = `@${suggestion.username || suggestion.name}`;
                    break;
                case 'project':
                    mentionText = `@P-${suggestion.id}`;
                    break;
                case 'work_order':
                    mentionText = `@WO-${suggestion.id}`;
                    break;
                case 'task':
                    mentionText = `@T-${suggestion.id}`;
                    break;
                default:
                    mentionText = `@${suggestion.name}`;
            }

            const newValue = `${beforeMention}${mentionText} ${afterMention}`;
            onChange(newValue);
            closeMentions();

            // Focus back on textarea
            setTimeout(() => {
                if (textareaRef.current) {
                    const newCursorPos = beforeMention.length + mentionText.length + 1;
                    textareaRef.current.focus();
                    textareaRef.current.setSelectionRange(newCursorPos, newCursorPos);
                }
            }, 0);
        },
        [value, mentionStartPos, mentionQuery, onChange, closeMentions]
    );

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
            if (!isOpen || suggestions.length === 0) return;

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    setSelectedIndex((prev) =>
                        prev < suggestions.length - 1 ? prev + 1 : 0
                    );
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    setSelectedIndex((prev) =>
                        prev > 0 ? prev - 1 : suggestions.length - 1
                    );
                    break;
                case 'Enter':
                    if (suggestions[selectedIndex]) {
                        e.preventDefault();
                        insertMention(suggestions[selectedIndex]);
                    }
                    break;
                case 'Escape':
                    e.preventDefault();
                    closeMentions();
                    break;
                case 'Tab':
                    if (suggestions[selectedIndex]) {
                        e.preventDefault();
                        insertMention(suggestions[selectedIndex]);
                    }
                    break;
            }
        },
        [isOpen, suggestions, selectedIndex, insertMention, closeMentions]
    );

    // Cleanup debounce on unmount
    useEffect(() => {
        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, []);

    const getIconForType = (type: MentionSuggestion['type']) => {
        switch (type) {
            case 'user':
                return <User className="h-4 w-4" />;
            case 'project':
                return <FolderKanban className="h-4 w-4" />;
            case 'work_order':
                return <FileText className="h-4 w-4" />;
            case 'task':
                return <CheckSquare className="h-4 w-4" />;
            default:
                return <User className="h-4 w-4" />;
        }
    };

    const getTypeLabel = (type: MentionSuggestion['type']) => {
        switch (type) {
            case 'user':
                return 'User';
            case 'project':
                return 'Project';
            case 'work_order':
                return 'Work Order';
            case 'task':
                return 'Task';
            default:
                return '';
        }
    };

    return (
        <Popover open={isOpen} onOpenChange={setIsOpen}>
            <PopoverAnchor asChild>
                <Textarea
                    ref={textareaRef}
                    value={value}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder}
                    disabled={disabled}
                    className={cn('min-h-[60px] resize-none', className)}
                    onBlur={() => {
                        // Delay close to allow click on suggestion
                        setTimeout(() => closeMentions(), 150);
                    }}
                />
            </PopoverAnchor>
            <PopoverContent
                className="w-64 p-1"
                align="start"
                side="bottom"
                onOpenAutoFocus={(e) => e.preventDefault()}
            >
                {isLoading ? (
                    <div className="flex items-center justify-center py-4">
                        <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                    </div>
                ) : suggestions.length === 0 ? (
                    <div className="py-3 px-2 text-sm text-muted-foreground text-center">
                        No results found
                    </div>
                ) : (
                    <ul className="space-y-0.5" role="listbox">
                        {suggestions.map((suggestion, index) => (
                            <li
                                key={`${suggestion.type}-${suggestion.id}`}
                                role="option"
                                aria-selected={index === selectedIndex}
                                className={cn(
                                    'flex items-center gap-2 px-2 py-1.5 rounded-sm cursor-pointer text-sm',
                                    index === selectedIndex
                                        ? 'bg-accent text-accent-foreground'
                                        : 'hover:bg-muted'
                                )}
                                onClick={() => insertMention(suggestion)}
                                onMouseEnter={() => setSelectedIndex(index)}
                            >
                                <span className="text-muted-foreground">
                                    {getIconForType(suggestion.type)}
                                </span>
                                <div className="flex-1 min-w-0">
                                    <div className="truncate font-medium">
                                        {suggestion.name}
                                    </div>
                                    {suggestion.username && (
                                        <div className="truncate text-xs text-muted-foreground">
                                            @{suggestion.username}
                                        </div>
                                    )}
                                </div>
                                <span className="text-xs text-muted-foreground shrink-0">
                                    {getTypeLabel(suggestion.type)}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
            </PopoverContent>
        </Popover>
    );
}
