import { useState } from 'react';
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
import { Separator } from '@/components/ui/separator';
import {
    CheckCircle,
    XCircle,
    Edit,
    Save,
    X,
    Bot,
    User,
    Mail,
    Calendar,
    Globe,
    FolderKanban,
    Briefcase,
} from 'lucide-react';
import ReactMarkdown from 'react-markdown';
import { cn } from '@/lib/utils';
import { getCommunicationTypeLabel } from './CommunicationTypeSelector';
import type { DraftPreviewModalProps } from '@/types/client-comms.d';

/**
 * Confidence badge color mapping
 */
const confidenceColors = {
    high: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/30',
    medium: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/30',
    low: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/30',
};

/**
 * Format ISO date string for display
 */
function formatDate(dateString: string): string {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    }).format(new Date(dateString));
}

/**
 * DraftPreviewModal displays a draft communication for review with approve/reject/edit actions.
 * Shows draft content, metadata (type, confidence, created at), and recipient info.
 */
export function DraftPreviewModal({
    draft,
    recipient,
    entity,
    isOpen,
    onClose,
    onApprove,
    onReject,
    onEdit,
    isApproving = false,
    isRejecting = false,
    isEditing = false,
}: DraftPreviewModalProps) {
    const [editMode, setEditMode] = useState(false);
    const [editedContent, setEditedContent] = useState('');
    const [rejectMode, setRejectMode] = useState(false);
    const [rejectReason, setRejectReason] = useState('');

    // Handle opening edit mode
    const handleStartEdit = () => {
        setEditedContent(draft?.content ?? '');
        setEditMode(true);
    };

    // Handle save edit
    const handleSaveEdit = () => {
        onEdit(editedContent);
        setEditMode(false);
    };

    // Handle cancel edit
    const handleCancelEdit = () => {
        setEditedContent('');
        setEditMode(false);
    };

    // Handle reject submission
    const handleRejectSubmit = () => {
        if (!rejectReason.trim()) return;
        onReject(rejectReason);
        setRejectMode(false);
        setRejectReason('');
    };

    // Handle dialog close
    const handleOpenChange = (open: boolean) => {
        if (!open) {
            setEditMode(false);
            setEditedContent('');
            setRejectMode(false);
            setRejectReason('');
            onClose();
        }
    };

    if (!draft) return null;

    return (
        <Dialog open={isOpen} onOpenChange={handleOpenChange}>
            <DialogContent className="sm:max-w-[700px] max-h-[90vh] flex flex-col">
                <DialogHeader>
                    <div className="flex items-center gap-2 flex-wrap mb-2">
                        <Badge variant="outline" className="text-xs bg-blue-500/10 text-blue-700 dark:text-blue-400">
                            AI Draft
                        </Badge>
                        {draft.communicationType && (
                            <Badge variant="outline" className="text-xs">
                                {getCommunicationTypeLabel(draft.communicationType)}
                            </Badge>
                        )}
                        <Badge
                            variant="outline"
                            className={cn('text-xs capitalize', confidenceColors[draft.confidence])}
                        >
                            {draft.confidence} confidence
                        </Badge>
                    </div>
                    <DialogTitle className="flex items-center gap-2">
                        <Bot className="h-5 w-5 text-blue-500" />
                        Draft Communication Preview
                    </DialogTitle>
                    <DialogDescription>
                        Review the AI-generated draft before sending to the client.
                    </DialogDescription>
                </DialogHeader>

                {/* Metadata Grid */}
                <div className="grid grid-cols-2 gap-x-6 gap-y-3 text-sm py-4">
                    {/* Recipient */}
                    {recipient && (
                        <div>
                            <span className="text-muted-foreground block mb-1">Recipient</span>
                            <div className="flex items-center gap-2 font-medium text-foreground">
                                <User className="h-4 w-4 text-slate-500" />
                                <span>{recipient.name}</span>
                            </div>
                        </div>
                    )}

                    {/* Email */}
                    {recipient?.email && (
                        <div>
                            <span className="text-muted-foreground block mb-1">Email</span>
                            <div className="flex items-center gap-2 font-medium text-foreground">
                                <Mail className="h-4 w-4 text-slate-500" />
                                <span className="truncate">{recipient.email}</span>
                            </div>
                        </div>
                    )}

                    {/* Related Entity */}
                    {entity && (
                        <div>
                            <span className="text-muted-foreground block mb-1">
                                {entity.type === 'Project' ? 'Project' : 'Work Order'}
                            </span>
                            <div className="flex items-center gap-2 font-medium text-foreground">
                                {entity.type === 'Project' ? (
                                    <FolderKanban className="h-4 w-4 text-slate-500" />
                                ) : (
                                    <Briefcase className="h-4 w-4 text-slate-500" />
                                )}
                                <span className="truncate">{entity.name}</span>
                            </div>
                        </div>
                    )}

                    {/* Created At */}
                    <div>
                        <span className="text-muted-foreground block mb-1">Created</span>
                        <div className="flex items-center gap-2 font-medium text-foreground">
                            <Calendar className="h-4 w-4 text-slate-500" />
                            <span>{formatDate(draft.createdAt)}</span>
                        </div>
                    </div>

                    {/* Language */}
                    <div>
                        <span className="text-muted-foreground block mb-1">Language</span>
                        <div className="flex items-center gap-2 font-medium text-foreground">
                            <Globe className="h-4 w-4 text-slate-500" />
                            <span className="uppercase">{draft.targetLanguage}</span>
                        </div>
                    </div>
                </div>

                <Separator />

                {/* Content Area */}
                <div className="flex-1 overflow-y-auto py-4">
                    {editMode ? (
                        <div className="space-y-2">
                            <Label htmlFor="draft-content">Edit Draft Content</Label>
                            <Textarea
                                id="draft-content"
                                value={editedContent}
                                onChange={(e) => setEditedContent(e.target.value)}
                                rows={12}
                                className="resize-none font-mono text-sm"
                                disabled={isEditing}
                            />
                        </div>
                    ) : rejectMode ? (
                        <div className="space-y-2">
                            <Label htmlFor="reject-reason">
                                Rejection Reason <span className="text-destructive">*</span>
                            </Label>
                            <Textarea
                                id="reject-reason"
                                value={rejectReason}
                                onChange={(e) => setRejectReason(e.target.value)}
                                placeholder="Please explain why this draft should be rejected..."
                                rows={4}
                                className="resize-none"
                                disabled={isRejecting}
                            />
                            <p className="text-xs text-muted-foreground">
                                This feedback will be logged for improving future drafts.
                            </p>
                        </div>
                    ) : (
                        <div className="border border-border rounded-lg p-4 bg-muted/30">
                            <div className="prose prose-sm dark:prose-invert max-w-none">
                                <ReactMarkdown>{draft.content}</ReactMarkdown>
                            </div>
                        </div>
                    )}
                </div>

                <DialogFooter className="flex-row gap-2 sm:justify-between">
                    {editMode ? (
                        <>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleCancelEdit}
                                disabled={isEditing}
                            >
                                <X className="h-4 w-4 mr-2" />
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                onClick={handleSaveEdit}
                                disabled={isEditing || !editedContent.trim()}
                            >
                                <Save className="h-4 w-4 mr-2" />
                                Save Changes
                            </Button>
                        </>
                    ) : rejectMode ? (
                        <>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setRejectMode(false);
                                    setRejectReason('');
                                }}
                                disabled={isRejecting}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={handleRejectSubmit}
                                disabled={isRejecting || !rejectReason.trim()}
                            >
                                {isRejecting ? 'Rejecting...' : 'Confirm Rejection'}
                            </Button>
                        </>
                    ) : (
                        <>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={handleStartEdit}
                                    disabled={isApproving || isRejecting}
                                >
                                    <Edit className="h-4 w-4 mr-2" />
                                    Edit
                                </Button>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="destructive"
                                    onClick={() => setRejectMode(true)}
                                    disabled={isApproving || isRejecting}
                                >
                                    <XCircle className="h-4 w-4 mr-2" />
                                    Reject
                                </Button>
                                <Button
                                    type="button"
                                    onClick={onApprove}
                                    disabled={isApproving || isRejecting}
                                    className="bg-green-600 hover:bg-green-700 text-white"
                                >
                                    {isApproving ? (
                                        'Approving...'
                                    ) : (
                                        <>
                                            <CheckCircle className="h-4 w-4 mr-2" />
                                            Approve
                                        </>
                                    )}
                                </Button>
                            </div>
                        </>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
