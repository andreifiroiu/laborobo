import { Head, Link, useForm, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    FileText,
    MoreVertical,
    Edit,
    Trash2,
    CheckCircle2,
    ExternalLink,
    Package,
    Clock,
    Plus,
    X,
    Upload,
    File,
    Image,
    FileSpreadsheet,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import { StatusBadge } from '@/components/work';
import { useState, useRef } from 'react';
import type { Deliverable } from '@/types/work';
import type { BreadcrumbItem } from '@/types';

interface DocumentItem {
    id: string;
    name: string;
    type: string;
    fileUrl: string;
    fileSize: string;
    uploadedAt: string;
}

interface DeliverableDetailProps {
    deliverable: Deliverable & {
        projectName: string;
    };
    documents: DocumentItem[];
}

export default function DeliverableDetail({ deliverable, documents }: DeliverableDetailProps) {
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [editCriterion, setEditCriterion] = useState('');
    const [newCriterion, setNewCriterion] = useState('');
    const [isUploading, setIsUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Work', href: '/work' },
        { title: deliverable.workOrderTitle, href: `/work/work-orders/${deliverable.workOrderId}` },
        { title: deliverable.title, href: `/work/deliverables/${deliverable.id}` },
    ];

    const editForm = useForm({
        title: deliverable.title,
        description: deliverable.description || '',
        type: deliverable.type,
        status: deliverable.status,
        version: deliverable.version,
        fileUrl: deliverable.fileUrl || '',
        acceptanceCriteria: deliverable.acceptanceCriteria || [],
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.patch(`/work/deliverables/${deliverable.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setEditDialogOpen(false);
                setEditCriterion('');
            },
        });
    };

    const handleStatusChange = (status: string) => {
        router.patch(`/work/deliverables/${deliverable.id}`, { status });
    };

    const handleDelete = () => {
        router.delete(`/work/deliverables/${deliverable.id}`);
    };

    // Inline acceptance criteria management
    const handleAddCriterion = () => {
        if (!newCriterion.trim()) return;
        const updatedCriteria = [...deliverable.acceptanceCriteria, newCriterion.trim()];
        router.patch(`/work/deliverables/${deliverable.id}`, {
            acceptanceCriteria: updatedCriteria,
        }, {
            preserveScroll: true,
            onSuccess: () => setNewCriterion(''),
        });
    };

    const handleRemoveCriterion = (index: number) => {
        const updatedCriteria = deliverable.acceptanceCriteria.filter((_, i) => i !== index);
        router.patch(`/work/deliverables/${deliverable.id}`, {
            acceptanceCriteria: updatedCriteria,
        }, {
            preserveScroll: true,
        });
    };

    // Edit form acceptance criteria helpers
    const addEditCriterion = () => {
        if (editCriterion.trim()) {
            editForm.setData('acceptanceCriteria', [
                ...editForm.data.acceptanceCriteria,
                editCriterion.trim(),
            ]);
            setEditCriterion('');
        }
    };

    const removeEditCriterion = (index: number) => {
        editForm.setData(
            'acceptanceCriteria',
            editForm.data.acceptanceCriteria.filter((_, i) => i !== index)
        );
    };

    // File upload handling
    const handleFileSelect = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsUploading(true);
        const formData = new FormData();
        formData.append('file', file);

        router.post(`/work/deliverables/${deliverable.id}/files`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => {
                setIsUploading(false);
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    };

    const handleDeleteFile = (documentId: string) => {
        if (confirm('Are you sure you want to delete this file?')) {
            router.delete(`/work/deliverables/${deliverable.id}/files/${documentId}`, {
                preserveScroll: true,
            });
        }
    };

    const getFileIcon = (type: string, fileName: string) => {
        // Check file extension for more specific icons
        const ext = fileName.split('.').pop()?.toLowerCase();
        if (ext === 'pdf') return <FileText className="h-5 w-5 text-red-500" />;
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext || '')) return <Image className="h-5 w-5 text-blue-500" />;
        if (['xls', 'xlsx'].includes(ext || '')) return <FileSpreadsheet className="h-5 w-5 text-green-500" />;
        if (['doc', 'docx'].includes(ext || '')) return <FileText className="h-5 w-5 text-blue-500" />;

        // Fallback to type-based icons
        switch (type) {
            case 'reference':
                return <FileText className="h-5 w-5 text-blue-500" />;
            case 'artifact':
                return <Package className="h-5 w-5 text-purple-500" />;
            case 'evidence':
                return <FileText className="h-5 w-5 text-amber-500" />;
            case 'template':
                return <FileText className="h-5 w-5 text-green-500" />;
            default:
                return <File className="h-5 w-5 text-muted-foreground" />;
        }
    };

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'document':
                return <FileText className="h-5 w-5" />;
            case 'design':
                return <Package className="h-5 w-5" />;
            case 'code':
                return <FileText className="h-5 w-5" />;
            default:
                return <FileText className="h-5 w-5" />;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={deliverable.title} />

            <div className="flex h-full flex-1 flex-col">
                {/* Header */}
                <div className="px-6 py-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="flex items-center gap-4 mb-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={`/work/work-orders/${deliverable.workOrderId}`}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="flex-1">
                            <div className="flex items-center gap-3 mb-1">
                                <h1 className="text-2xl font-bold text-foreground">{deliverable.title}</h1>
                                <StatusBadge status={deliverable.status} type="deliverable" />
                                <Badge variant="outline" className="capitalize">{deliverable.type}</Badge>
                            </div>
                            <p className="text-muted-foreground">
                                {deliverable.workOrderTitle}
                                {deliverable.description && ` • ${deliverable.description}`}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            {deliverable.fileUrl && (
                                <Button variant="outline" size="sm" asChild>
                                    <a href={deliverable.fileUrl} target="_blank" rel="noreferrer">
                                        <ExternalLink className="h-4 w-4 mr-2" />
                                        View File
                                    </a>
                                </Button>
                            )}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="icon">
                                        <MoreVertical className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={() => setEditDialogOpen(true)}>
                                        <Edit className="h-4 w-4 mr-2" />
                                        Edit
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onClick={() => setDeleteDialogOpen(true)}
                                        className="text-destructive"
                                    >
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    {/* Deliverable Stats */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            {getTypeIcon(deliverable.type)}
                            <div>
                                <div className="text-xs text-muted-foreground">Type</div>
                                <div className="font-medium capitalize">{deliverable.type}</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Package className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Version</div>
                                <div className="font-medium">v{deliverable.version}</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Calendar className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Created</div>
                                <div className="font-medium">
                                    {new Date(deliverable.createdDate).toLocaleDateString()}
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Delivered</div>
                                <div className="font-medium">
                                    {deliverable.deliveredDate
                                        ? new Date(deliverable.deliveredDate).toLocaleDateString()
                                        : 'Not yet'}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Status Actions */}
                    <div className="mt-4 flex gap-2">
                        {deliverable.status === 'draft' && (
                            <Button size="sm" onClick={() => handleStatusChange('in_review')}>
                                Submit for Review
                            </Button>
                        )}
                        {deliverable.status === 'in_review' && (
                            <>
                                <Button size="sm" onClick={() => handleStatusChange('approved')}>
                                    Approve
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleStatusChange('draft')}
                                >
                                    Return to Draft
                                </Button>
                            </>
                        )}
                        {deliverable.status === 'approved' && (
                            <Button size="sm" onClick={() => handleStatusChange('delivered')}>
                                Mark as Delivered
                            </Button>
                        )}
                        {deliverable.status === 'delivered' && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleStatusChange('approved')}
                            >
                                Revert to Approved
                            </Button>
                        )}
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex-1 overflow-auto p-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Left Column - Description & Files */}
                        <div className="space-y-6">
                            {/* Description */}
                            <div>
                                <h2 className="text-lg font-bold text-foreground mb-4">Description</h2>
                                <div className="p-4 bg-card border border-border rounded-xl">
                                    {deliverable.description ? (
                                        <p className="text-foreground whitespace-pre-wrap">
                                            {deliverable.description}
                                        </p>
                                    ) : (
                                        <p className="text-muted-foreground italic">No description provided</p>
                                    )}
                                </div>
                            </div>

                            {/* File URL */}
                            {deliverable.fileUrl && (
                                <div>
                                    <h3 className="text-sm font-bold text-foreground mb-3">External Link</h3>
                                    <a
                                        href={deliverable.fileUrl}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="flex items-center gap-2 p-3 bg-muted rounded-lg hover:bg-muted/80 transition-colors"
                                    >
                                        <ExternalLink className="h-4 w-4 text-muted-foreground" />
                                        <span className="text-sm text-primary truncate">
                                            {deliverable.fileUrl}
                                        </span>
                                    </a>
                                </div>
                            )}

                            {/* Files Section */}
                            <div>
                                <div className="flex items-center justify-between mb-4">
                                    <h2 className="text-lg font-bold text-foreground">
                                        Files ({documents.length})
                                    </h2>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={handleFileSelect}
                                        disabled={isUploading}
                                    >
                                        <Upload className="h-4 w-4 mr-2" />
                                        {isUploading ? 'Uploading...' : 'Upload'}
                                    </Button>
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        className="hidden"
                                        onChange={handleFileChange}
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp,.zip,.txt"
                                    />
                                </div>

                                {documents.length === 0 ? (
                                    <div
                                        className="text-center py-8 bg-muted/50 rounded-xl border-2 border-dashed border-border cursor-pointer hover:bg-muted/70 transition-colors"
                                        onClick={handleFileSelect}
                                    >
                                        <Upload className="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                                        <p className="text-muted-foreground">
                                            Click to upload files or drag and drop
                                        </p>
                                        <p className="text-xs text-muted-foreground mt-1">
                                            PDF, DOC, XLS, Images up to 10MB
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {documents.map((doc) => (
                                            <div
                                                key={doc.id}
                                                className="flex items-center gap-3 p-3 bg-card border border-border rounded-lg group"
                                            >
                                                {getFileIcon(doc.type, doc.name)}
                                                <div className="flex-1 min-w-0">
                                                    <a
                                                        href={doc.fileUrl}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="font-medium text-sm truncate block hover:text-primary"
                                                    >
                                                        {doc.name}
                                                    </a>
                                                    <div className="text-xs text-muted-foreground">
                                                        {doc.fileSize} • {doc.uploadedAt}
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                    <Button variant="ghost" size="icon" asChild>
                                                        <a href={doc.fileUrl} target="_blank" rel="noreferrer">
                                                            <ExternalLink className="h-4 w-4" />
                                                        </a>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => handleDeleteFile(doc.id)}
                                                        className="text-destructive hover:text-destructive"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}

                                        {/* Add more files button */}
                                        <button
                                            onClick={handleFileSelect}
                                            disabled={isUploading}
                                            className="w-full p-3 border-2 border-dashed border-border rounded-lg text-muted-foreground hover:bg-muted/50 hover:text-foreground transition-colors flex items-center justify-center gap-2"
                                        >
                                            <Plus className="h-4 w-4" />
                                            Add more files
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Right Column - Acceptance Criteria */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-lg font-bold text-foreground">
                                    Acceptance Criteria ({deliverable.acceptanceCriteria.length})
                                </h2>
                            </div>

                            {/* Add new criterion inline */}
                            <div className="flex gap-2 mb-4">
                                <Input
                                    value={newCriterion}
                                    onChange={(e) => setNewCriterion(e.target.value)}
                                    placeholder="Add acceptance criterion..."
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter') {
                                            e.preventDefault();
                                            handleAddCriterion();
                                        }
                                    }}
                                />
                                <Button onClick={handleAddCriterion} disabled={!newCriterion.trim()}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </div>

                            {deliverable.acceptanceCriteria.length === 0 ? (
                                <div className="text-center py-8 bg-muted/50 rounded-xl">
                                    <CheckCircle2 className="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                                    <p className="text-muted-foreground">No acceptance criteria defined</p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Add criteria to track deliverable requirements
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {deliverable.acceptanceCriteria.map((criterion, index) => (
                                        <div
                                            key={index}
                                            className="flex items-start gap-3 p-3 bg-card border border-border rounded-lg group"
                                        >
                                            <CheckCircle2 className="h-5 w-5 text-emerald-500 mt-0.5 shrink-0" />
                                            <span className="text-foreground flex-1">{criterion}</span>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity text-destructive hover:text-destructive"
                                                onClick={() => handleRemoveCriterion(index)}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Edit Dialog */}
            <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                <DialogContent className="max-w-lg">
                    <form onSubmit={handleUpdate}>
                        <DialogHeader>
                            <DialogTitle>Edit Deliverable</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
                            <div className="grid gap-2">
                                <Label>Title</Label>
                                <Input
                                    value={editForm.data.title}
                                    onChange={(e) => editForm.setData('title', e.target.value)}
                                />
                                <InputError message={editForm.errors.title} />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label>Type</Label>
                                    <Select
                                        value={editForm.data.type}
                                        onValueChange={(v) => editForm.setData('type', v as any)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="document">Document</SelectItem>
                                            <SelectItem value="design">Design</SelectItem>
                                            <SelectItem value="report">Report</SelectItem>
                                            <SelectItem value="code">Code</SelectItem>
                                            <SelectItem value="other">Other</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label>Version</Label>
                                    <Input
                                        value={editForm.data.version}
                                        onChange={(e) => editForm.setData('version', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select
                                    value={editForm.data.status}
                                    onValueChange={(v) => editForm.setData('status', v as any)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="in_review">In Review</SelectItem>
                                        <SelectItem value="approved">Approved</SelectItem>
                                        <SelectItem value="delivered">Delivered</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Description</Label>
                                <Textarea
                                    value={editForm.data.description}
                                    onChange={(e) => editForm.setData('description', e.target.value)}
                                    rows={3}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>External File URL</Label>
                                <Input
                                    type="url"
                                    value={editForm.data.fileUrl}
                                    onChange={(e) => editForm.setData('fileUrl', e.target.value)}
                                    placeholder="https://..."
                                />
                            </div>

                            {/* Acceptance Criteria */}
                            <div className="grid gap-2">
                                <Label>Acceptance Criteria</Label>
                                <div className="flex gap-2">
                                    <Input
                                        value={editCriterion}
                                        onChange={(e) => setEditCriterion(e.target.value)}
                                        placeholder="Add acceptance criterion"
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                addEditCriterion();
                                            }
                                        }}
                                    />
                                    <Button type="button" variant="outline" onClick={addEditCriterion}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                                {editForm.data.acceptanceCriteria.length > 0 && (
                                    <ul className="space-y-2 mt-2">
                                        {editForm.data.acceptanceCriteria.map((criterion, index) => (
                                            <li
                                                key={index}
                                                className="flex items-center gap-2 p-2 bg-muted rounded-md text-sm"
                                            >
                                                <CheckCircle2 className="h-4 w-4 text-muted-foreground shrink-0" />
                                                <span className="flex-1">{criterion}</span>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    className="h-6 w-6"
                                                    onClick={() => removeEditCriterion(index)}
                                                >
                                                    <X className="h-4 w-4" />
                                                </Button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                Save
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Deliverable</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "{deliverable.title}"? This action cannot be
                            undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
