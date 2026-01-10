import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FileText, ListChecks, FileStack, CheckSquare, TrendingUp } from 'lucide-react';
import type { PlaybookCardProps } from '@/types/playbooks';
import { formatDistanceToNow } from 'date-fns';

export function PlaybookCard({ playbook, onClick }: PlaybookCardProps) {
    const getTypeConfig = (type: string) => {
        switch (type) {
            case 'sop':
                return {
                    icon: FileText,
                    label: 'SOP',
                    color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-400',
                };
            case 'checklist':
                return {
                    icon: ListChecks,
                    label: 'Checklist',
                    color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400',
                };
            case 'template':
                return {
                    icon: FileStack,
                    label: 'Template',
                    color: 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-400',
                };
            case 'acceptance_criteria':
                return {
                    icon: CheckSquare,
                    label: 'Acceptance Criteria',
                    color: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
                };
            default:
                return {
                    icon: FileText,
                    label: 'Unknown',
                    color: 'bg-gray-100 text-gray-700 dark:bg-gray-950 dark:text-gray-400',
                };
        }
    };

    const typeConfig = getTypeConfig(playbook.type);
    const Icon = typeConfig.icon;

    const truncateText = (text: string, maxLength: number) => {
        if (text.length <= maxLength) return text;
        return text.slice(0, maxLength) + '...';
    };

    const timeAgo = playbook.lastUsed
        ? formatDistanceToNow(new Date(playbook.lastUsed), { addSuffix: true })
        : 'Never used';

    return (
        <Card
            className="cursor-pointer transition-all hover:border-primary hover:shadow-md"
            onClick={onClick}
        >
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between gap-2">
                    <div className={`rounded-md p-2 ${typeConfig.color}`}>
                        <Icon className="size-4" />
                    </div>
                    {playbook.aiGenerated && (
                        <Badge variant="secondary" className="text-xs">
                            AI
                        </Badge>
                    )}
                </div>
                <CardTitle className="mt-2 line-clamp-2">{playbook.name}</CardTitle>
                <CardDescription className="line-clamp-2">
                    {truncateText(playbook.description, 100)}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-2">
                {/* Tags */}
                {playbook.tags.length > 0 && (
                    <div className="flex flex-wrap gap-1">
                        {playbook.tags.slice(0, 3).map((tag) => (
                            <Badge key={tag} variant="outline" className="text-xs">
                                {tag}
                            </Badge>
                        ))}
                        {playbook.tags.length > 3 && (
                            <Badge variant="outline" className="text-xs">
                                +{playbook.tags.length - 3}
                            </Badge>
                        )}
                    </div>
                )}

                {/* Stats */}
                <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <div className="flex items-center gap-1">
                        <TrendingUp className="size-3" />
                        <span>{playbook.timesApplied} uses</span>
                    </div>
                    <span>{timeAgo}</span>
                </div>
            </CardContent>
        </Card>
    );
}
