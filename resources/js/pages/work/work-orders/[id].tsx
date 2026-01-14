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
    Trash2,
    CheckCircle2,
    ExternalLink,
    X,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
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
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import { StatusBadge, PriorityBadge, ProgressBar } from '@/components/work';
import { useState } from 'react';
import type { WorkOrderDetailProps } from '@/types/work';
import type { BreadcrumbItem } from '@/types';

export default function WorkOrderDetail({
    workOrder,
    tasks,
    deliverables,
    documents,
    communicationThread,
    messages,
    teamMembers,
}: WorkOrderDetailProps) {
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [createTaskDialogOpen, setCreateTaskDialogOpen] = useState(false);
    const [createDeliverableDialogOpen, setCreateDeliverableDialogOpen] = useState(false);
    const [editDeliverableDialogOpen, setEditDeliverableDialogOpen] = useState(false);
    const [deleteDeliverableDialogOpen, setDeleteDeliverableDialogOpen] = useState(false);
    const [selectedDeliverable, setSelectedDeliverable] = useState<typeof deliverables[0] | null>(null);
    const [newCriterion, setNewCriterion] = useState('');
    const [editCriterion, setEditCriterion] = useState('');
    const [commsPanelOpen, setCommsPanelOpen] = useState(false);
    const [newMessage, setNewMessage] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Work', href: '/work' },
        { title: workOrder.projectName, href: `/work/projects/${workOrder.projectId}` },
        { title: workOrder.title, href: `/work/work-orders/${workOrder.id}` },
    ];

    const editForm = useForm({
        title: workOrder.title,
        description: workOrder.description || '',
        status: workOrder.status,
        priority: workOrder.priority,
        assigned_to_id: workOrder.assignedToId || '',
        due_date: workOrder.dueDate,
        estimated_hours: workOrder.estimatedHours.toString(),
    });

    const taskForm = useForm({
        title: '',
        workOrderId: workOrder.id,
        description: '',
        dueDate: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    });

    const deliverableForm = useForm({
        title: '',
        workOrderId: workOrder.id,
        description: '',
        type: 'document' as const,
        fileUrl: '',
        acceptanceCriteria: [] as string[],
    });

    const editDeliverableForm = useForm({
        title: '',
        description: '',
        type: 'document' as const,
        status: 'draft' as const,
        version: '1.0',
        fileUrl: '',
        acceptanceCriteria: [] as string[],
    });

    const handleUpdateWorkOrder = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.patch(`/work/work-orders/${workOrder.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditDialogOpen(false),
        });
    };

    const handleCreateTask = (e: React.FormEvent) => {
        e.preventDefault();
        taskForm.post('/work/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                taskForm.reset();
                setCreateTaskDialogOpen(false);
            },
        });
    };

    const handleCreateDeliverable = (e: React.FormEvent) => {
        e.preventDefault();
        deliverableForm.post('/work/deliverables', {
            preserveScroll: true,
            onSuccess: () => {
                deliverableForm.reset();
                setNewCriterion('');
                setCreateDeliverableDialogOpen(false);
            },
        });
    };

    const handleEditDeliverable = (deliverable: typeof deliverables[0]) => {
        setSelectedDeliverable(deliverable);
        editDeliverableForm.setData({
            title: deliverable.title,
            description: deliverable.description || '',
            type: deliverable.type as 'document' | 'design' | 'report' | 'code' | 'other',
            status: deliverable.status as 'draft' | 'in_review' | 'approved' | 'delivered',
            version: deliverable.version,
            fileUrl: deliverable.fileUrl || '',
            acceptanceCriteria: deliverable.acceptanceCriteria || [],
        });
        setEditDeliverableDialogOpen(true);
    };

    const handleUpdateDeliverable = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedDeliverable) return;
        editDeliverableForm.patch(`/work/deliverables/${selectedDeliverable.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setEditDeliverableDialogOpen(false);
                setSelectedDeliverable(null);
                setEditCriterion('');
            },
        });
    };

    const handleDeleteDeliverable = () => {
        if (!selectedDeliverable) return;
        router.delete(`/work/deliverables/${selectedDeliverable.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteDeliverableDialogOpen(false);
                setSelectedDeliverable(null);
            },
        });
    };

    const handleDeliverableStatusChange = (deliverableId: string, newStatus: string) => {
        router.patch(`/work/deliverables/${deliverableId}`, { status: newStatus });
    };

    // Acceptance criteria helpers for create form
    const addCriterion = () => {
        if (newCriterion.trim()) {
            deliverableForm.setData('acceptanceCriteria', [
                ...deliverableForm.data.acceptanceCriteria,
                newCriterion.trim(),
            ]);
            setNewCriterion('');
        }
    };

    const removeCriterion = (index: number) => {
        deliverableForm.setData(
            'acceptanceCriteria',
            deliverableForm.data.acceptanceCriteria.filter((_, i) => i !== index)
        );
    };

    // Acceptance criteria helpers for edit form
    const addEditCriterion = () => {
        if (editCriterion.trim()) {
            editDeliverableForm.setData('acceptanceCriteria', [
                ...editDeliverableForm.data.acceptanceCriteria,
                editCriterion.trim(),
            ]);
            setEditCriterion('');
        }
    };

    const removeEditCriterion = (index: number) => {
        editDeliverableForm.setData(
            'acceptanceCriteria',
            editDeliverableForm.data.acceptanceCriteria.filter((_, i) => i !== index)
        );
    };

    const handleStatusChange = (status: string) => {
        router.patch(`/work/work-orders/${workOrder.id}/status`, { status });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this work order? This action cannot be undone.')) {
            router.delete(`/work/work-orders/${workOrder.id}`);
        }
    };

    const handleSendMessage = () => {
        if (!newMessage.trim()) return;
        router.post(
            `/work/work-order/${workOrder.id}/communications`,
            { content: newMessage, type: 'note' },
            {
                preserveScroll: true,
                onSuccess: () => setNewMessage(''),
            }
        );
    };

    const completedTasks = tasks.filter((t) => t.status === 'done').length;
    const progress = tasks.length > 0 ? Math.round((completedTasks / tasks.length) * 100) : 0;

    const dueDate = new Date(workOrder.dueDate);
    const now = new Date();
    const daysUntilDue = Math.ceil((dueDate.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    const isOverdue = daysUntilDue < 0;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={workOrder.title} />

            <div className="flex h-full flex-1 flex-col">
                {/* Header */}
                <div className="px-6 py-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="flex items-center gap-4 mb-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={`/work/projects/${workOrder.projectId}`}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="flex-1">
                            <div className="flex items-center gap-3 mb-1">
                                <h1 className="text-2xl font-bold text-foreground">{workOrder.title}</h1>
                                <StatusBadge status={workOrder.status} type="workOrder" />
                                <PriorityBadge priority={workOrder.priority} />
                            </div>
                            <p className="text-muted-foreground">
                                {workOrder.projectName}
                                {workOrder.description && ` • ${workOrder.description}`}
                            </p>
                        </div>
                        <div className="flex items-center gap-2">
                            <Sheet open={commsPanelOpen} onOpenChange={setCommsPanelOpen}>
                                <SheetTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <MessageSquare className="h-4 w-4 mr-2" />
                                        {communicationThread?.messageCount || 0} Messages
                                    </Button>
                                </SheetTrigger>
                                <SheetContent>
                                    <SheetHeader>
                                        <SheetTitle>Work Order Communications</SheetTitle>
                                        <SheetDescription>
                                            Discussion thread for this work order
                                        </SheetDescription>
                                    </SheetHeader>
                                    <div className="flex flex-col h-[calc(100vh-180px)] mt-4">
                                        <div className="flex-1 overflow-auto space-y-4">
                                            {messages.length === 0 ? (
                                                <p className="text-sm text-muted-foreground text-center py-8">
                                                    No messages yet
                                                </p>
                                            ) : (
                                                messages.map((msg) => (
                                                    <div key={msg.id} className="p-3 bg-muted rounded-lg">
                                                        <div className="flex items-center justify-between mb-1">
                                                            <span className="text-sm font-medium">
                                                                {msg.authorName}
                                                            </span>
                                                            <span className="text-xs text-muted-foreground">
                                                                {new Date(msg.timestamp).toLocaleDateString()}
                                                            </span>
                                                        </div>
                                                        <p className="text-sm">{msg.content}</p>
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                        <div className="flex gap-2 mt-4">
                                            <Input
                                                value={newMessage}
                                                onChange={(e) => setNewMessage(e.target.value)}
                                                placeholder="Type a message..."
                                                onKeyDown={(e) => e.key === 'Enter' && handleSendMessage()}
                                            />
                                            <Button onClick={handleSendMessage}>Send</Button>
                                        </div>
                                    </div>
                                </SheetContent>
                            </Sheet>
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
                                    <DropdownMenuItem onClick={handleDelete} className="text-destructive">
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>

                    {/* Work Order Stats */}
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <User className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Assigned To</div>
                                <div className="font-medium">{workOrder.assignedToName || 'Unassigned'}</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Hours</div>
                                <div className="font-medium">
                                    {workOrder.actualHours} / {workOrder.estimatedHours}h
                                </div>
                            </div>
                        </div>
                        <div
                            className={`flex items-center gap-3 p-3 rounded-lg ${
                                isOverdue ? 'bg-destructive/10' : 'bg-muted'
                            }`}
                        >
                            <Calendar
                                className={`h-5 w-5 ${isOverdue ? 'text-destructive' : 'text-muted-foreground'}`}
                            />
                            <div>
                                <div className="text-xs text-muted-foreground">Due Date</div>
                                <div className={`font-medium ${isOverdue ? 'text-destructive' : ''}`}>
                                    {dueDate.toLocaleDateString()}
                                    {isOverdue && ` (${Math.abs(daysUntilDue)}d overdue)`}
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <CheckCircle2 className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Tasks</div>
                                <div className="font-medium">
                                    {completedTasks} / {tasks.length}
                                </div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <FileText className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Deliverables</div>
                                <div className="font-medium">{deliverables.length}</div>
                            </div>
                        </div>
                    </div>

                    {/* Progress */}
                    <div className="mt-4">
                        <div className="flex items-center justify-between text-sm mb-2">
                            <span className="text-muted-foreground">Task Progress</span>
                            <span className="font-medium">{progress}%</span>
                        </div>
                        <ProgressBar progress={progress} />
                    </div>

                    {/* Status Actions */}
                    <div className="mt-4 flex gap-2">
                        {workOrder.status === 'draft' && (
                            <Button size="sm" onClick={() => handleStatusChange('active')}>
                                Start Work Order
                            </Button>
                        )}
                        {workOrder.status === 'active' && (
                            <Button size="sm" onClick={() => handleStatusChange('in_review')}>
                                Submit for Review
                            </Button>
                        )}
                        {workOrder.status === 'in_review' && (
                            <>
                                <Button size="sm" onClick={() => handleStatusChange('approved')}>
                                    Approve
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={() => handleStatusChange('active')}
                                >
                                    Request Changes
                                </Button>
                            </>
                        )}
                        {workOrder.status === 'approved' && (
                            <Button size="sm" onClick={() => handleStatusChange('delivered')}>
                                Mark as Delivered
                            </Button>
                        )}
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex-1 overflow-auto p-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Tasks Section */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-lg font-bold text-foreground">Tasks</h2>
                                <Button size="sm" onClick={() => setCreateTaskDialogOpen(true)}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Add Task
                                </Button>
                            </div>

                            {tasks.length === 0 ? (
                                <div className="text-center py-8 bg-muted/50 rounded-xl">
                                    <p className="text-muted-foreground mb-4">No tasks yet</p>
                                    <Button onClick={() => setCreateTaskDialogOpen(true)}>
                                        Create Task
                                    </Button>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {tasks.map((task) => (
                                        <Link
                                            key={task.id}
                                            href={`/work/tasks/${task.id}`}
                                            className="block p-4 bg-card border border-border rounded-lg hover:border-primary/50 transition-colors"
                                        >
                                            <div className="flex items-start gap-3">
                                                <div
                                                    className={`mt-1 w-4 h-4 rounded-full border-2 flex items-center justify-center ${
                                                        task.status === 'done'
                                                            ? 'bg-primary border-primary'
                                                            : 'border-muted-foreground'
                                                    }`}
                                                >
                                                    {task.status === 'done' && (
                                                        <CheckCircle2 className="h-3 w-3 text-primary-foreground" />
                                                    )}
                                                </div>
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <span
                                                            className={`font-medium ${
                                                                task.status === 'done'
                                                                    ? 'line-through text-muted-foreground'
                                                                    : ''
                                                            }`}
                                                        >
                                                            {task.title}
                                                        </span>
                                                        {task.isBlocked && (
                                                            <Badge variant="destructive">Blocked</Badge>
                                                        )}
                                                    </div>
                                                    <div className="text-sm text-muted-foreground">
                                                        {task.assignedToName} •{' '}
                                                        {task.checklistItems.filter((i) => i.completed).length}/
                                                        {task.checklistItems.length} items •{' '}
                                                        {task.actualHours}/{task.estimatedHours}h
                                                    </div>
                                                </div>
                                            </div>
                                        </Link>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Deliverables Section */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-lg font-bold text-foreground">Deliverables</h2>
                                <Button variant="outline" size="sm" onClick={() => setCreateDeliverableDialogOpen(true)}>
                                    <Plus className="h-4 w-4 mr-2" />
                                    Add
                                </Button>
                            </div>

                            {deliverables.length === 0 ? (
                                <div className="text-center py-8 bg-muted/50 rounded-xl">
                                    <FileText className="h-8 w-8 text-muted-foreground mx-auto mb-2" />
                                    <p className="text-muted-foreground">No deliverables yet</p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {deliverables.map((d) => (
                                        <div
                                            key={d.id}
                                            className="p-4 bg-card border border-border rounded-lg hover:border-primary/50 transition-colors"
                                        >
                                            <div className="flex items-start justify-between">
                                                <Link
                                                    href={`/work/deliverables/${d.id}`}
                                                    className="flex-1 min-w-0"
                                                >
                                                    <div className="flex items-center gap-2 mb-1 flex-wrap">
                                                        <span className="font-medium truncate">{d.title}</span>
                                                        <StatusBadge status={d.status} type="deliverable" />
                                                    </div>
                                                    <div className="text-sm text-muted-foreground flex items-center gap-2 flex-wrap">
                                                        <span className="capitalize">{d.type}</span>
                                                        <span>•</span>
                                                        <span>v{d.version}</span>
                                                        <span>•</span>
                                                        <span>Created {new Date(d.createdDate).toLocaleDateString()}</span>
                                                        {d.deliveredDate && (
                                                            <>
                                                                <span>•</span>
                                                                <span className="text-emerald-600 dark:text-emerald-400">
                                                                    Delivered {new Date(d.deliveredDate).toLocaleDateString()}
                                                                </span>
                                                            </>
                                                        )}
                                                        {d.acceptanceCriteria.length > 0 && (
                                                            <>
                                                                <span>•</span>
                                                                <span>{d.acceptanceCriteria.length} criteria</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </Link>
                                                <div className="flex items-center gap-1 ml-2">
                                                    {/* Status workflow buttons */}
                                                    {d.status === 'draft' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                handleDeliverableStatusChange(d.id, 'in_review');
                                                            }}
                                                        >
                                                            Submit
                                                        </Button>
                                                    )}
                                                    {d.status === 'in_review' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                handleDeliverableStatusChange(d.id, 'approved');
                                                            }}
                                                        >
                                                            Approve
                                                        </Button>
                                                    )}
                                                    {d.status === 'approved' && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                handleDeliverableStatusChange(d.id, 'delivered');
                                                            }}
                                                        >
                                                            Deliver
                                                        </Button>
                                                    )}
                                                    {d.fileUrl && (
                                                        <Button variant="ghost" size="icon" asChild>
                                                            <a href={d.fileUrl} target="_blank" rel="noreferrer" onClick={(e) => e.stopPropagation()}>
                                                                <ExternalLink className="h-4 w-4" />
                                                            </a>
                                                        </Button>
                                                    )}
                                                    <DropdownMenu>
                                                        <DropdownMenuTrigger asChild>
                                                            <Button variant="ghost" size="icon" onClick={(e) => e.preventDefault()}>
                                                                <MoreVertical className="h-4 w-4" />
                                                            </Button>
                                                        </DropdownMenuTrigger>
                                                        <DropdownMenuContent align="end">
                                                            <DropdownMenuItem onClick={() => handleEditDeliverable(d)}>
                                                                <Edit className="h-4 w-4 mr-2" />
                                                                Edit
                                                            </DropdownMenuItem>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuItem
                                                                onClick={() => {
                                                                    setSelectedDeliverable(d);
                                                                    setDeleteDeliverableDialogOpen(true);
                                                                }}
                                                                className="text-destructive"
                                                            >
                                                                <Trash2 className="h-4 w-4 mr-2" />
                                                                Delete
                                                            </DropdownMenuItem>
                                                        </DropdownMenuContent>
                                                    </DropdownMenu>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Acceptance Criteria */}
                            {workOrder.acceptanceCriteria.length > 0 && (
                                <div className="mt-6">
                                    <h3 className="text-sm font-bold text-foreground mb-3">
                                        Acceptance Criteria
                                    </h3>
                                    <ul className="space-y-2">
                                        {workOrder.acceptanceCriteria.map((criteria, i) => (
                                            <li key={i} className="flex items-start gap-2 text-sm">
                                                <Checkbox disabled />
                                                <span>{criteria}</span>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Edit Dialog */}
            <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                <DialogContent>
                    <form onSubmit={handleUpdateWorkOrder}>
                        <DialogHeader>
                            <DialogTitle>Edit Work Order</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
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
                                    <Label>Priority</Label>
                                    <Select
                                        value={editForm.data.priority}
                                        onValueChange={(v) => editForm.setData('priority', v as any)}
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
                                    <Label>Assigned To</Label>
                                    <Select
                                        value={editForm.data.assigned_to_id}
                                        onValueChange={(v) => editForm.setData('assigned_to_id', v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {teamMembers.map((m) => (
                                                <SelectItem key={m.id} value={m.id}>
                                                    {m.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label>Due Date</Label>
                                    <Input
                                        type="date"
                                        value={editForm.data.due_date}
                                        onChange={(e) => editForm.setData('due_date', e.target.value)}
                                    />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Estimated Hours</Label>
                                    <Input
                                        type="number"
                                        value={editForm.data.estimated_hours}
                                        onChange={(e) => editForm.setData('estimated_hours', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label>Description</Label>
                                <Input
                                    value={editForm.data.description}
                                    onChange={(e) => editForm.setData('description', e.target.value)}
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setEditDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editForm.processing}>
                                Save
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Create Task Dialog */}
            <Dialog open={createTaskDialogOpen} onOpenChange={setCreateTaskDialogOpen}>
                <DialogContent>
                    <form onSubmit={handleCreateTask}>
                        <DialogHeader>
                            <DialogTitle>Create Task</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label>Title</Label>
                                <Input
                                    value={taskForm.data.title}
                                    onChange={(e) => taskForm.setData('title', e.target.value)}
                                    placeholder="Task title"
                                />
                                <InputError message={taskForm.errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Due Date</Label>
                                <Input
                                    type="date"
                                    value={taskForm.data.dueDate}
                                    onChange={(e) => taskForm.setData('dueDate', e.target.value)}
                                />
                                <InputError message={taskForm.errors.dueDate} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Description</Label>
                                <Input
                                    value={taskForm.data.description}
                                    onChange={(e) => taskForm.setData('description', e.target.value)}
                                    placeholder="Brief description"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setCreateTaskDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={taskForm.processing}>
                                Create
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Create Deliverable Dialog */}
            <Dialog open={createDeliverableDialogOpen} onOpenChange={setCreateDeliverableDialogOpen}>
                <DialogContent className="max-w-lg">
                    <form onSubmit={handleCreateDeliverable}>
                        <DialogHeader>
                            <DialogTitle>Add Deliverable</DialogTitle>
                            <DialogDescription>
                                Add a new deliverable to this work order
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
                            <div className="grid gap-2">
                                <Label>Title *</Label>
                                <Input
                                    value={deliverableForm.data.title}
                                    onChange={(e) => deliverableForm.setData('title', e.target.value)}
                                    placeholder="Deliverable title"
                                />
                                <InputError message={deliverableForm.errors.title} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Type *</Label>
                                <Select
                                    value={deliverableForm.data.type}
                                    onValueChange={(value) =>
                                        deliverableForm.setData('type', value as any)
                                    }
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
                                <Label>Description</Label>
                                <Textarea
                                    value={deliverableForm.data.description}
                                    onChange={(e) =>
                                        deliverableForm.setData('description', e.target.value)
                                    }
                                    placeholder="Brief description of the deliverable"
                                    rows={3}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>File URL</Label>
                                <Input
                                    type="url"
                                    value={deliverableForm.data.fileUrl}
                                    onChange={(e) => deliverableForm.setData('fileUrl', e.target.value)}
                                    placeholder="https://..."
                                />
                                <InputError message={deliverableForm.errors.fileUrl} />
                            </div>

                            {/* Acceptance Criteria */}
                            <div className="grid gap-2">
                                <Label>Acceptance Criteria</Label>
                                <div className="flex gap-2">
                                    <Input
                                        value={newCriterion}
                                        onChange={(e) => setNewCriterion(e.target.value)}
                                        placeholder="Add acceptance criterion"
                                        onKeyDown={(e) => {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                addCriterion();
                                            }
                                        }}
                                    />
                                    <Button type="button" variant="outline" onClick={addCriterion}>
                                        <Plus className="h-4 w-4" />
                                    </Button>
                                </div>
                                {deliverableForm.data.acceptanceCriteria.length > 0 && (
                                    <ul className="space-y-2 mt-2">
                                        {deliverableForm.data.acceptanceCriteria.map((criterion, index) => (
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
                                                    onClick={() => removeCriterion(index)}
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
                                onClick={() => {
                                    setCreateDeliverableDialogOpen(false);
                                    deliverableForm.reset();
                                    setNewCriterion('');
                                }}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={deliverableForm.processing}>
                                Add
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Deliverable Dialog */}
            <Dialog open={editDeliverableDialogOpen} onOpenChange={setEditDeliverableDialogOpen}>
                <DialogContent className="max-w-lg">
                    <form onSubmit={handleUpdateDeliverable}>
                        <DialogHeader>
                            <DialogTitle>Edit Deliverable</DialogTitle>
                        </DialogHeader>
                        <div className="grid gap-4 py-4 max-h-[60vh] overflow-y-auto">
                            <div className="grid gap-2">
                                <Label>Title *</Label>
                                <Input
                                    value={editDeliverableForm.data.title}
                                    onChange={(e) => editDeliverableForm.setData('title', e.target.value)}
                                />
                                <InputError message={editDeliverableForm.errors.title} />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label>Type</Label>
                                    <Select
                                        value={editDeliverableForm.data.type}
                                        onValueChange={(value) => editDeliverableForm.setData('type', value as any)}
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
                                        value={editDeliverableForm.data.version}
                                        onChange={(e) => editDeliverableForm.setData('version', e.target.value)}
                                        placeholder="1.0"
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select
                                    value={editDeliverableForm.data.status}
                                    onValueChange={(value) => editDeliverableForm.setData('status', value as any)}
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
                                    value={editDeliverableForm.data.description}
                                    onChange={(e) => editDeliverableForm.setData('description', e.target.value)}
                                    rows={3}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>File URL</Label>
                                <Input
                                    type="url"
                                    value={editDeliverableForm.data.fileUrl}
                                    onChange={(e) => editDeliverableForm.setData('fileUrl', e.target.value)}
                                    placeholder="https://..."
                                />
                            </div>

                            {/* Acceptance Criteria for Edit */}
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
                                {editDeliverableForm.data.acceptanceCriteria.length > 0 && (
                                    <ul className="space-y-2 mt-2">
                                        {editDeliverableForm.data.acceptanceCriteria.map((criterion, index) => (
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
                                onClick={() => setEditDeliverableDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={editDeliverableForm.processing}>
                                Save
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Deliverable Confirmation Dialog */}
            <Dialog open={deleteDeliverableDialogOpen} onOpenChange={setDeleteDeliverableDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete Deliverable</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to delete "{selectedDeliverable?.title}"? This action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setDeleteDeliverableDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleDeleteDeliverable}>
                            Delete
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
