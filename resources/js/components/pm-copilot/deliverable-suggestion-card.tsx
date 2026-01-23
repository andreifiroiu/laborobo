import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Check, X, FileText, CheckCircle2 } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { DeliverableSuggestionCardProps, AIConfidence } from '@/types/pm-copilot.d';

const confidenceColors: Record<AIConfidence, string> = {
    high: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900',
    medium: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900',
    low: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
};

const typeIcons: Record<string, string> = {
    document: 'Document',
    design: 'Design',
    report: 'Report',
    code: 'Code',
    other: 'Other',
};

/**
 * Card displaying a single deliverable suggestion from PM Copilot.
 * Shows title, description, type, acceptance criteria, and confidence level.
 */
export function DeliverableSuggestionCard({
    suggestion,
    onApprove,
    onReject,
    isApproved = false,
    isRejected = false,
    disabled = false,
}: DeliverableSuggestionCardProps) {
    const isActioned = isApproved || isRejected;
    const isDisabled = disabled || isActioned;

    return (
        <Card
            className={cn(
                'transition-colors',
                isApproved && 'border-green-200 bg-green-50/50 dark:border-green-900 dark:bg-green-950/20',
                isRejected && 'border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-950/20 opacity-60'
            )}
        >
            <CardContent className="p-4">
                {/* Header */}
                <div className="flex items-start justify-between gap-3 mb-3">
                    <div className="flex items-center gap-2 min-w-0">
                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                            <FileText className="h-4 w-4" />
                        </div>
                        <div className="min-w-0">
                            <h4 className="font-medium truncate">{suggestion.title}</h4>
                            <div className="flex items-center gap-2 mt-0.5">
                                <Badge variant="secondary" className="text-xs capitalize">
                                    {typeIcons[suggestion.type] || suggestion.type}
                                </Badge>
                                <Badge
                                    variant="outline"
                                    className={cn('text-xs capitalize', confidenceColors[suggestion.confidence])}
                                >
                                    {suggestion.confidence}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    {isApproved && (
                        <Badge variant="outline" className="shrink-0 bg-green-100 text-green-700 border-green-200">
                            <Check className="mr-1 h-3 w-3" />
                            Approved
                        </Badge>
                    )}
                    {isRejected && (
                        <Badge variant="outline" className="shrink-0 bg-red-100 text-red-700 border-red-200">
                            <X className="mr-1 h-3 w-3" />
                            Rejected
                        </Badge>
                    )}
                </div>

                {/* Description */}
                <p className="text-sm text-muted-foreground mb-3">
                    {suggestion.description}
                </p>

                {/* Acceptance Criteria */}
                {suggestion.acceptanceCriteria.length > 0 && (
                    <div className="mb-4">
                        <h5 className="text-xs font-medium uppercase text-muted-foreground mb-2">
                            Acceptance Criteria
                        </h5>
                        <ul className="space-y-1">
                            {suggestion.acceptanceCriteria.map((criterion, index) => (
                                <li key={index} className="flex items-start gap-2 text-sm">
                                    <CheckCircle2 className="h-4 w-4 shrink-0 text-muted-foreground mt-0.5" />
                                    <span>{criterion}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Action Buttons */}
                {!isActioned && (
                    <div className="flex gap-2">
                        <Button
                            onClick={() => onApprove(suggestion.id)}
                            disabled={isDisabled}
                            size="sm"
                            className="flex-1"
                            aria-label={`Approve ${suggestion.title}`}
                        >
                            <Check className="mr-2 h-4 w-4" />
                            Approve
                        </Button>
                        <Button
                            onClick={() => onReject(suggestion.id, undefined)}
                            disabled={isDisabled}
                            size="sm"
                            variant="outline"
                            className="flex-1"
                            aria-label={`Reject ${suggestion.title}`}
                        >
                            <X className="mr-2 h-4 w-4" />
                            Reject
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
