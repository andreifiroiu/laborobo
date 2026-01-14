import { useState } from 'react';
import { Calendar, Clock, User, Briefcase, AlertCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import type { TodayApproval } from '@/types/today';

interface ApprovalSheetProps {
    approval: TodayApproval | null;
    onClose: () => void;
    onApprove?: (id: string) => void;
    onReject?: (id: string, reason?: string) => void;
}

const priorityColors: Record<string, string> = {
    high: 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-400',
    medium: 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
    low: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
};

const typeLabels: Record<string, string> = {
    deliverable: 'Deliverable',
    estimate: 'Estimate',
    draft: 'Draft',
};

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function ApprovalSheet({ approval, onClose, onApprove, onReject }: ApprovalSheetProps) {
    const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
    const [rejectReason, setRejectReason] = useState('');

    const handleApprove = () => {
        if (approval) {
            onApprove?.(approval.id);
            onClose();
        }
    };

    const handleReject = () => {
        if (approval) {
            onReject?.(approval.id, rejectReason || undefined);
            setRejectReason('');
            setRejectDialogOpen(false);
            onClose();
        }
    };

    return (
        <>
            <Sheet open={!!approval} onOpenChange={(open) => !open && onClose()}>
                <SheetContent side="right" className="w-full sm:w-[500px]">
                    {approval && (
                        <>
                            <SheetHeader>
                                <div className="mb-2 flex flex-wrap items-center gap-2">
                                    <span
                                        className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ${priorityColors[approval.priority]}`}
                                    >
                                        {typeLabels[approval.type]}
                                    </span>
                                    <span
                                        className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium capitalize ${priorityColors[approval.priority]}`}
                                    >
                                        {approval.priority} priority
                                    </span>
                                </div>
                                <SheetTitle>{approval.title}</SheetTitle>
                                <SheetDescription>{approval.description}</SheetDescription>
                            </SheetHeader>

                            <div className="mt-6 space-y-4">
                                <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                                    <h4 className="mb-3 text-sm font-medium text-slate-900 dark:text-white">Details</h4>
                                    <div className="space-y-3">
                                        <div className="flex items-center gap-3 text-sm">
                                            <User className="h-4 w-4 text-slate-400" />
                                            <span className="text-slate-600 dark:text-slate-400">Created by:</span>
                                            <span className="font-medium text-slate-900 dark:text-white">
                                                {approval.createdBy}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-3 text-sm">
                                            <Clock className="h-4 w-4 text-slate-400" />
                                            <span className="text-slate-600 dark:text-slate-400">Created:</span>
                                            <span className="font-medium text-slate-900 dark:text-white">
                                                {formatDate(approval.createdAt)}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-3 text-sm">
                                            <Calendar className="h-4 w-4 text-slate-400" />
                                            <span className="text-slate-600 dark:text-slate-400">Due:</span>
                                            <span className="font-medium text-slate-900 dark:text-white">
                                                {formatDate(approval.dueDate)}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-3 text-sm">
                                            <Briefcase className="h-4 w-4 text-slate-400" />
                                            <span className="text-slate-600 dark:text-slate-400">Project:</span>
                                            <span className="font-medium text-slate-900 dark:text-white">
                                                {approval.projectTitle}
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-3 text-sm">
                                            <AlertCircle className="h-4 w-4 text-slate-400" />
                                            <span className="text-slate-600 dark:text-slate-400">Work Order:</span>
                                            <span className="font-medium text-slate-900 dark:text-white">
                                                {approval.workOrderTitle}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <SheetFooter className="mt-6 flex gap-2">
                                <Button variant="outline" onClick={() => setRejectDialogOpen(true)} className="flex-1">
                                    Reject
                                </Button>
                                <Button onClick={handleApprove} className="flex-1">
                                    Approve
                                </Button>
                            </SheetFooter>
                        </>
                    )}
                </SheetContent>
            </Sheet>

            <Dialog open={rejectDialogOpen} onOpenChange={setRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reject Approval</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to reject this approval? You can optionally provide a reason.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-4">
                        <Label htmlFor="reject-reason">Reason (optional)</Label>
                        <Textarea
                            id="reject-reason"
                            value={rejectReason}
                            onChange={(e) => setRejectReason(e.target.value)}
                            placeholder="Enter reason for rejection..."
                            className="mt-2"
                        />
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRejectDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleReject}>
                            Reject
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
