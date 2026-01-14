import type { InboxListItemProps } from '@/types/inbox';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { Bot, User, Clock, Link as LinkIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export function InboxListItem({ item, isSelected, onSelect, onView }: InboxListItemProps) {
    // Determine type label and color
    const typeInfo = {
        agent_draft: { label: 'Agent Draft', color: 'bg-blue-500/10 text-blue-700 dark:text-blue-400' },
        approval: { label: 'Approval', color: 'bg-amber-500/10 text-amber-700 dark:text-amber-400' },
        flag: { label: 'Flagged', color: 'bg-red-500/10 text-red-700 dark:text-red-400' },
        mention: { label: 'Mention', color: 'bg-purple-500/10 text-purple-700 dark:text-purple-400' },
    };

    // Determine urgency color
    const urgencyColors = {
        urgent: 'border-l-red-500',
        high: 'border-l-orange-500',
        normal: 'border-l-gray-300 dark:border-l-gray-700',
    };

    // AI confidence indicator
    const confidenceColors = {
        high: 'text-green-600 dark:text-green-400',
        medium: 'text-yellow-600 dark:text-yellow-400',
        low: 'text-red-600 dark:text-red-400',
    };

    return (
        <div
            className={cn(
                'flex items-start gap-4 p-4 border-l-4 hover:bg-muted/50 transition-colors',
                urgencyColors[item.urgency],
                isSelected && 'bg-muted/50'
            )}
        >
            {/* Checkbox */}
            <div className="pt-1">
                <Checkbox
                    checked={isSelected}
                    onCheckedChange={onSelect}
                />
            </div>

            {/* Main Content */}
            <div className="flex-1 min-w-0 cursor-pointer" onClick={onView}>
                {/* Header */}
                <div className="flex items-start justify-between gap-4 mb-2">
                    <div className="flex-1 min-w-0">
                        <div className="flex items-center gap-2 mb-1">
                            <Badge variant="outline" className={cn("text-xs", typeInfo[item.type].color)}>
                                {typeInfo[item.type].label}
                            </Badge>
                            {item.urgency === 'urgent' && (
                                <Badge variant="destructive" className="text-xs">
                                    URGENT
                                </Badge>
                            )}
                            {item.urgency === 'high' && (
                                <Badge variant="outline" className="text-xs border-orange-500 text-orange-600 dark:text-orange-400">
                                    HIGH
                                </Badge>
                            )}
                        </div>
                        <h3 className="text-base font-semibold text-foreground truncate">
                            {item.title}
                        </h3>
                    </div>

                    {/* Waiting time */}
                    <div className="flex items-center gap-1 text-xs text-muted-foreground whitespace-nowrap">
                        <Clock className="w-3 h-3" />
                        <span>{item.waitingHours}h</span>
                    </div>
                </div>

                {/* Content Preview */}
                <p className="text-sm text-muted-foreground line-clamp-2 mb-3">
                    {item.contentPreview}
                </p>

                {/* Metadata Footer */}
                <div className="flex items-center gap-4 text-xs text-muted-foreground">
                    {/* Source */}
                    <div className="flex items-center gap-1">
                        {item.sourceType === 'ai_agent' ? (
                            <Bot className="w-3 h-3" />
                        ) : (
                            <User className="w-3 h-3" />
                        )}
                        <span>{item.sourceName}</span>
                    </div>

                    {/* Related items */}
                    {(item.relatedWorkOrderTitle || item.relatedProjectName) && (
                        <div className="flex items-center gap-1">
                            <LinkIcon className="w-3 h-3" />
                            <span>
                                {item.relatedWorkOrderTitle || item.relatedProjectName}
                            </span>
                        </div>
                    )}

                    {/* AI Confidence */}
                    {item.aiConfidence && (
                        <div className="flex items-center gap-1">
                            <span className={cn("font-medium", confidenceColors[item.aiConfidence])}>
                                {item.aiConfidence.toUpperCase()} confidence
                            </span>
                        </div>
                    )}

                    {/* QA Validation */}
                    {item.qaValidation && (
                        <Badge
                            variant={item.qaValidation === 'passed' ? 'outline' : 'destructive'}
                            className="text-xs"
                        >
                            QA {item.qaValidation}
                        </Badge>
                    )}
                </div>
            </div>
        </div>
    );
}
