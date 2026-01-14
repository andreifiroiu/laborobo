import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import type { InboxSidePanelProps } from '@/types/inbox';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetDescription,
    SheetFooter,
} from '@/components/ui/sheet';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { CheckCircle, XCircle, Clock, Archive, Bot, User, Link as LinkIcon } from 'lucide-react';
import { useInboxActions } from '@/hooks/use-inbox-actions';
import ReactMarkdown from 'react-markdown';
import { cn } from '@/lib/utils';
import InputError from '@/components/input-error';

const rejectSchema = z.object({
    feedback: z.string().min(1, 'Feedback is required').max(1000, 'Feedback must be less than 1000 characters'),
});

type RejectFormData = z.infer<typeof rejectSchema>;

export function InboxSidePanel({ item, onClose }: InboxSidePanelProps) {
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
    const { approveItem, rejectItem, deferItem, archiveItem } = useInboxActions();

    const {
        register,
        handleSubmit,
        formState: { errors },
        reset,
    } = useForm<RejectFormData>({
        resolver: zodResolver(rejectSchema),
    });

    if (!item) return null;

    const handleApprove = () => {
        approveItem(item.id);
        onClose();
    };

    const handleRejectSubmit = (data: RejectFormData) => {
        rejectItem(item.id, data.feedback);
        setRejectDialogOpen(false);
        reset();
        onClose();
    };

    const handleDefer = () => {
        deferItem(item.id);
        onClose();
    };

    const handleArchive = () => {
        archiveItem(item.id);
        onClose();
    };

    const typeInfo = {
        agent_draft: { label: 'Agent Draft', color: 'bg-blue-500/10 text-blue-700 dark:text-blue-400' },
        approval: { label: 'Approval', color: 'bg-amber-500/10 text-amber-700 dark:text-amber-400' },
        flag: { label: 'Flagged', color: 'bg-red-500/10 text-red-700 dark:text-red-400' },
        mention: { label: 'Mention', color: 'bg-purple-500/10 text-purple-700 dark:text-purple-400' },
    };

    const confidenceColors = {
        high: 'text-green-600 dark:text-green-400',
        medium: 'text-yellow-600 dark:text-yellow-400',
        low: 'text-red-600 dark:text-red-400',
    };

    return (
        <>
            <Sheet open={!!item} onOpenChange={onClose}>
                <SheetContent side="right" className="w-full sm:w-[600px] sm:max-w-[600px]">
                    <SheetHeader>
                        <div className="flex items-start gap-2 mb-2">
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
                        <SheetTitle>{item.title}</SheetTitle>
                        <SheetDescription>
                            <div className="flex flex-col gap-2 mt-2">
                                <div className="flex items-center gap-2 text-sm">
                                    {item.sourceType === 'ai_agent' ? (
                                        <Bot className="w-4 h-4" />
                                    ) : (
                                        <User className="w-4 h-4" />
                                    )}
                                    <span>From {item.sourceName}</span>
                                </div>
                                {(item.relatedWorkOrderTitle || item.relatedProjectName) && (
                                    <div className="flex items-center gap-2 text-sm">
                                        <LinkIcon className="w-4 h-4" />
                                        <span>
                                            {item.relatedWorkOrderTitle || item.relatedProjectName}
                                        </span>
                                    </div>
                                )}
                                <div className="flex items-center gap-2 text-sm">
                                    <Clock className="w-4 h-4" />
                                    <span>Waiting for {item.waitingHours} hours</span>
                                </div>
                            </div>
                        </SheetDescription>
                    </SheetHeader>

                    {/* Content */}
                    <div className="mt-6 space-y-4">
                        {/* AI Confidence & QA Validation */}
                        {(item.aiConfidence || item.qaValidation) && (
                            <div className="flex items-center gap-4 text-sm">
                                {item.aiConfidence && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-muted-foreground">AI Confidence:</span>
                                        <span className={cn("font-medium", confidenceColors[item.aiConfidence])}>
                                            {item.aiConfidence.toUpperCase()}
                                        </span>
                                    </div>
                                )}
                                {item.qaValidation && (
                                    <Badge
                                        variant={item.qaValidation === 'passed' ? 'outline' : 'destructive'}
                                        className="text-xs"
                                    >
                                        QA {item.qaValidation}
                                    </Badge>
                                )}
                            </div>
                        )}

                        {/* Full Content */}
                        <div className="border border-border rounded-lg p-4 bg-muted/30 max-h-[60vh] overflow-y-auto">
                            <div className="prose prose-sm dark:prose-invert max-w-none">
                                <ReactMarkdown>{item.fullContent}</ReactMarkdown>
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <SheetFooter className="mt-6 gap-2">
                        <Button
                            variant="outline"
                            onClick={handleArchive}
                        >
                            <Archive className="w-4 h-4 mr-2" />
                            Archive
                        </Button>
                        <Button
                            variant="outline"
                            onClick={handleDefer}
                        >
                            <Clock className="w-4 h-4 mr-2" />
                            Defer
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setRejectDialogOpen(true)}
                        >
                            <XCircle className="w-4 h-4 mr-2" />
                            Reject
                        </Button>
                        <Button
                            variant="default"
                            onClick={handleApprove}
                        >
                            <CheckCircle className="w-4 h-4 mr-2" />
                            Approve
                        </Button>
                    </SheetFooter>
                </SheetContent>
            </Sheet>

            {/* Reject Feedback Dialog */}
            <Dialog open={rejectDialogOpen} onOpenChange={setRejectDialogOpen}>
                <DialogContent>
                    <form onSubmit={handleSubmit(handleRejectSubmit)}>
                        <DialogHeader>
                            <DialogTitle>Reject Item</DialogTitle>
                            <DialogDescription>
                                Please provide feedback explaining why you're rejecting this item.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="feedback">Feedback</Label>
                                <Textarea
                                    id="feedback"
                                    {...register('feedback')}
                                    placeholder="Explain why you're rejecting this..."
                                    rows={4}
                                />
                                <InputError message={errors.feedback?.message} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setRejectDialogOpen(false);
                                    reset();
                                }}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" variant="destructive">
                                Reject with Feedback
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </>
    );
}
