import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Pencil, Copy, TrendingUp, FileText, ListChecks, FileStack, CheckSquare } from 'lucide-react';
import type { PlaybookDetailPanelProps, SOPPlaybook, ChecklistPlaybook, TemplatePlaybook, AcceptanceCriteriaPlaybook } from '@/types/playbooks';
import { formatDistanceToNow } from 'date-fns';
import { SOPDetail } from './sop-detail';
import { ChecklistDetail } from './checklist-detail';
import { TemplateDetail } from './template-detail';
import { AcceptanceCriteriaDetail } from './acceptance-criteria-detail';

export function PlaybookDetailPanel({
    playbook,
    workOrders,
    onClose,
    onEdit,
}: PlaybookDetailPanelProps) {
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
                    color: 'bg-gray-100 text-gray-700',
                };
        }
    };

    const typeConfig = getTypeConfig(playbook.type);
    const Icon = typeConfig.icon;

    const linkedWorkOrders = workOrders.filter((wo) =>
        playbook.usedByWorkOrders.includes(wo.id)
    );

    const timeAgo = playbook.lastUsed
        ? formatDistanceToNow(new Date(playbook.lastUsed), { addSuffix: true })
        : 'Never used';

    return (
        <Sheet open={true} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-3">
                                <div className={`rounded-md p-2 ${typeConfig.color}`}>
                                    <Icon className="size-5" />
                                </div>
                                <div>
                                    <SheetTitle>{playbook.name}</SheetTitle>
                                    <div className="mt-1 flex items-center gap-2">
                                        <Badge variant="outline">{typeConfig.label}</Badge>
                                        {playbook.aiGenerated && (
                                            <Badge variant="secondary">AI Generated</Badge>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => onEdit(playbook)}
                                >
                                    <Pencil className="size-4" />
                                </Button>
                                <Button variant="outline" size="sm">
                                    <Copy className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </SheetHeader>
                </div>

                <div className="space-y-6 px-6 pb-6">
                    {/* Description */}
                    <div>
                        <h3 className="mb-2 text-sm font-semibold">Description</h3>
                        <p className="text-sm text-muted-foreground">{playbook.description}</p>
                    </div>

                    <Separator />

                    {/* Type-specific content */}
                    <div>
                        <h3 className="mb-3 text-sm font-semibold">Content</h3>
                        {playbook.type === 'sop' && (
                            <SOPDetail playbook={playbook as SOPPlaybook} />
                        )}
                        {playbook.type === 'checklist' && (
                            <ChecklistDetail playbook={playbook as ChecklistPlaybook} />
                        )}
                        {playbook.type === 'template' && (
                            <TemplateDetail playbook={playbook as TemplatePlaybook} />
                        )}
                        {playbook.type === 'acceptance_criteria' && (
                            <AcceptanceCriteriaDetail
                                playbook={playbook as AcceptanceCriteriaPlaybook}
                            />
                        )}
                    </div>

                    <Separator />

                    {/* Usage Stats */}
                    <div>
                        <h3 className="mb-3 text-sm font-semibold">Usage Statistics</h3>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="rounded-lg border p-3">
                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <TrendingUp className="size-4" />
                                    <span>Times Applied</span>
                                </div>
                                <p className="mt-1 text-2xl font-semibold">
                                    {playbook.timesApplied}
                                </p>
                            </div>
                            <div className="rounded-lg border p-3">
                                <div className="text-sm text-muted-foreground">Last Used</div>
                                <p className="mt-1 text-sm font-semibold">{timeAgo}</p>
                            </div>
                        </div>
                    </div>

                    {/* Tags */}
                    {playbook.tags.length > 0 && (
                        <>
                            <Separator />
                            <div>
                                <h3 className="mb-2 text-sm font-semibold">Tags</h3>
                                <div className="flex flex-wrap gap-2">
                                    {playbook.tags.map((tag) => (
                                        <Badge key={tag} variant="outline">
                                            {tag}
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        </>
                    )}

                    {/* Linked Work Orders */}
                    {linkedWorkOrders.length > 0 && (
                        <>
                            <Separator />
                            <div>
                                <h3 className="mb-2 text-sm font-semibold">
                                    Used in Work Orders ({linkedWorkOrders.length})
                                </h3>
                                <div className="space-y-2">
                                    {linkedWorkOrders.slice(0, 5).map((wo) => (
                                        <div
                                            key={wo.id}
                                            className="rounded-lg border p-2 text-sm"
                                        >
                                            <div className="font-medium">{wo.title}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {wo.projectName}
                                            </div>
                                        </div>
                                    ))}
                                    {linkedWorkOrders.length > 5 && (
                                        <p className="text-xs text-muted-foreground">
                                            +{linkedWorkOrders.length - 5} more
                                        </p>
                                    )}
                                </div>
                            </div>
                        </>
                    )}

                    {/* Metadata */}
                    <Separator />
                    <div className="text-xs text-muted-foreground">
                        <div>Created by {playbook.createdByName}</div>
                        <div>
                            Last modified{' '}
                            {formatDistanceToNow(new Date(playbook.lastModified), {
                                addSuffix: true,
                            })}
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    );
}
