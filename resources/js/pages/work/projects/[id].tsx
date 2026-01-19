import { Head, Link, useForm, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    Clock,
    User,
    Plus,
    MoreVertical,
    MessageSquare,
    FileText,
    Edit,
    Archive,
    Trash2,
    Upload,
    ExternalLink,
    Image,
    File,
    FileSpreadsheet,
    Package,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
import { StatusBadge, ProgressBar, ProjectTeamSection } from '@/components/work';
import { CommunicationsPanel } from '@/components/communications';
import { useState, useRef } from 'react';
import type { ProjectDetailProps } from '@/types/work';
import type { BreadcrumbItem } from '@/types';

export default function ProjectDetail({
    project,
    workOrders,
    documents,
    communicationThread,
    messages,
    parties,
    teamMembers,
}: ProjectDetailProps) {
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [createWorkOrderDialogOpen, setCreateWorkOrderDialogOpen] = useState(false);
    const [commsPanelOpen, setCommsPanelOpen] = useState(false);
    const [isUploading, setIsUploading] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Work', href: '/work' },
        { title: project.name, href: `/work/projects/${project.id}` },
    ];

    const editForm = useForm({
        name: project.name,
        description: project.description || '',
        party_id: project.partyId,
        status: project.status,
        target_end_date: project.targetEndDate || '',
        budget_hours: project.budgetHours?.toString() || '',
    });

    const workOrderForm = useForm({
        title: '',
        projectId: project.id,
        description: '',
        priority: 'medium' as const,
        dueDate: '',
    });

    const handleUpdateProject = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.patch(`/work/projects/${project.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditDialogOpen(false),
        });
    };

    const handleCreateWorkOrder = (e: React.FormEvent) => {
        e.preventDefault();
        workOrderForm.post('/work/work-orders', {
            preserveScroll: true,
            onSuccess: () => {
                workOrderForm.reset();
                setCreateWorkOrderDialogOpen(false);
            },
        });
    };

    const handleArchive = () => {
        router.post(`/work/projects/${project.id}/archive`);
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
            router.delete(`/work/projects/${project.id}`);
        }
    };

    // File upload handlers
    const handleFileSelect = () => {
        fileInputRef.current?.click();
    };

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsUploading(true);
        const formData = new FormData();
        formData.append('file', file);

        router.post(`/work/projects/${project.id}/files`, formData, {
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
            router.delete(`/work/projects/${project.id}/files/${documentId}`, {
                preserveScroll: true,
            });
        }
    };

    const getFileIcon = (type: string, fileName: string) => {
        const ext = fileName.split('.').pop()?.toLowerCase();
        if (ext === 'pdf') return <FileText className="h-8 w-8 text-red-500" />;
        if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext || '')) return <Image className="h-8 w-8 text-blue-500" />;
        if (['xls', 'xlsx'].includes(ext || '')) return <FileSpreadsheet className="h-8 w-8 text-green-500" />;
        if (['doc', 'docx'].includes(ext || '')) return <FileText className="h-8 w-8 text-blue-500" />;

        switch (type) {
            case 'reference':
                return <FileText className="h-8 w-8 text-blue-500" />;
            case 'artifact':
                return <Package className="h-8 w-8 text-purple-500" />;
            case 'evidence':
                return <FileText className="h-8 w-8 text-amber-500" />;
            case 'template':
                return <FileText className="h-8 w-8 text-green-500" />;
            default:
                return <File className="h-8 w-8 text-muted-foreground" />;
        }
    };

    const completedWorkOrders = workOrders.filter(
        (wo) => wo.status === 'delivered' || wo.status === 'approved'
    ).length;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={project.name} />

            <div className="flex h-full flex-1 flex-col">
                {/* Header */}
                <div className="px-6 py-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="flex items-center gap-4 mb-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/work">
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="flex-1">
                            <div className="flex items-center gap-3 mb-1">
                                <h1 className="text-2xl font-bold text-foreground">{project.name}</h1>
                                <StatusBadge status={project.status} type="project" />
                            </div>
                            {project.description && (
                                <p className="text-muted-foreground">{project.description}</p>
                            )}
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setCommsPanelOpen(true)}
                            >
                                <MessageSquare className="h-4 w-4 mr-2" />
                                {communicationThread?.messageCount || 0} Messages
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline" size="icon">
                                        <MoreVertical className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={() => setEditDialogOpen(true)}>
                                        <Edit className="h-4 w-4 mr-2" />
                                        Edit Project
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={handleArchive}>
                                        <Archive className="h-4 w-4 mr-2" />
                                        Archive
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onClick={handleDelete}
                                        className="text-destructive"
                                    >
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    {/* Project Stats */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <User className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Client</div>
                                <div className="font-medium">{project.partyName}</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <User className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Owner</div>
                                <div className="font-medium">{project.ownerName}</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Hours</div>
                                <div className="font-medium">
                                    {project.actualHours}
                                    {project.budgetHours && ` / ${project.budgetHours}`}h
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Calendar className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Target Date</div>
                                <div className="font-medium">
                                    {project.targetEndDate
                                        ? new Date(project.targetEndDate).toLocaleDateString()
                                        : 'Not set'}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Progress */}
                    <div className="mt-4">
                        <div className="flex items-center justify-between text-sm mb-2">
                            <span className="text-muted-foreground">Progress</span>
                            <span className="font-medium">{project.progress}%</span>
                        </div>
                        <ProgressBar progress={project.progress} />
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex-1 overflow-auto p-6">
                    {/* Team Members Section */}
                    <ProjectTeamSection
                        teamMembers={teamMembers}
                        projectId={project.id}
                    />

                    {/* Work Orders Section */}
                    <div className="mb-8">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-bold text-foreground">
                                Work Orders ({workOrders.length})
                            </h2>
                            <Button
                                size="sm"
                                onClick={() => setCreateWorkOrderDialogOpen(true)}
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Add Work Order
                            </Button>
                        </div>

                        {workOrders.length === 0 ? (
                            <div className="text-center py-12 bg-muted/50 rounded-xl">
                                <p className="text-muted-foreground mb-4">
                                    No work orders yet. Create one to get started.
                                </p>
                                <Button onClick={() => setCreateWorkOrderDialogOpen(true)}>
                                    Create Work Order
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {workOrders.map((wo) => (
                                    <Link
                                        key={wo.id}
                                        href={`/work/work-orders/${wo.id}`}
                                        className="block p-4 bg-card border border-border rounded-lg hover:border-primary/50 transition-colors"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2 mb-1">
                                                    <span className="font-medium">{wo.title}</span>
                                                    <Badge variant="outline">{wo.status}</Badge>
                                                    <Badge
                                                        variant={
                                                            wo.priority === 'urgent'
                                                                ? 'destructive'
                                                                : wo.priority === 'high'
                                                                  ? 'default'
                                                                  : 'secondary'
                                                        }
                                                    >
                                                        {wo.priority}
                                                    </Badge>
                                                </div>
                                                <div className="text-sm text-muted-foreground">
                                                    {wo.assignedToName} •{' '}
                                                    {wo.completedTasksCount}/{wo.tasksCount} tasks •
                                                    Due{' '}
                                                    {new Date(wo.dueDate).toLocaleDateString()}
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Documents Section */}
                    <div>
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-lg font-bold text-foreground">
                                Documents ({documents.length})
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
                                                {doc.fileSize} • {doc.uploadedDate}
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
            </div>

            {/* Communications Panel */}
            <CommunicationsPanel
                threadableType="projects"
                threadableId={project.id}
                open={commsPanelOpen}
                onOpenChange={setCommsPanelOpen}
            />

            {/* Edit Dialog */}
            <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                <DialogContent>
                    <form onSubmit={handleUpdateProject}>
                        <DialogHeader>
                            <DialogTitle>Edit Project</DialogTitle>
                            <DialogDescription>
                                Update project details
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Name</Label>
                                <Input
                                    value={editForm.data.name}
                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                />
                                <InputError message={editForm.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select
                                    value={editForm.data.status}
                                    onValueChange={(value) => editForm.setData('status', value as any)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="on_hold">On Hold</SelectItem>
                                        <SelectItem value="completed">Completed</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Client</Label>
                                <Select
                                    value={editForm.data.party_id}
                                    onValueChange={(value) => editForm.setData('party_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {parties.map((p) => (
                                            <SelectItem key={p.id} value={p.id}>
                                                {p.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Description</Label>
                                <Input
                                    value={editForm.data.description}
                                    onChange={(e) => editForm.setData('description', e.target.value)}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label>Target End Date</Label>
                                    <Input
                                        type="date"
                                        value={editForm.data.target_end_date}
                                        onChange={(e) =>
                                            editForm.setData('target_end_date', e.target.value)
                                        }
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Budget Hours</Label>
                                    <Input
                                        type="number"
                                        value={editForm.data.budget_hours}
                                        onChange={(e) =>
                                            editForm.setData('budget_hours', e.target.value)
                                        }
                                    />
                                </div>
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
                                Save Changes
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Create Work Order Dialog */}
            <Dialog open={createWorkOrderDialogOpen} onOpenChange={setCreateWorkOrderDialogOpen}>
                <DialogContent>
                    <form onSubmit={handleCreateWorkOrder}>
                        <DialogHeader>
                            <DialogTitle>Create Work Order</DialogTitle>
                            <DialogDescription>
                                Add a new work order to {project.name}
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Title</Label>
                                <Input
                                    value={workOrderForm.data.title}
                                    onChange={(e) => workOrderForm.setData('title', e.target.value)}
                                    placeholder="Work order title"
                                />
                                <InputError message={workOrderForm.errors.title} />
                                <InputError message={workOrderForm.errors.projectId} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Priority</Label>
                                <Select
                                    value={workOrderForm.data.priority}
                                    onValueChange={(value) =>
                                        workOrderForm.setData('priority', value as any)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="low">Low</SelectItem>
                                        <SelectItem value="medium">Medium</SelectItem>
                                        <SelectItem value="high">High</SelectItem>
                                        <SelectItem value="urgent">Urgent</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>Description</Label>
                                <Input
                                    value={workOrderForm.data.description}
                                    onChange={(e) =>
                                        workOrderForm.setData('description', e.target.value)
                                    }
                                    placeholder="Brief description"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Due Date</Label>
                                <Input
                                    type="date"
                                    value={workOrderForm.data.dueDate}
                                    onChange={(e) =>
                                        workOrderForm.setData('dueDate', e.target.value)
                                    }
                                />
                                <InputError message={workOrderForm.errors.dueDate} />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setCreateWorkOrderDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={workOrderForm.processing}>
                                Create
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
