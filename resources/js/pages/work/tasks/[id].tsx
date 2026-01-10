import { Head, Link, useForm, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Clock,
    User,
    MoreVertical,
    Edit,
    Trash2,
    Play,
    Pause,
    Plus,
    CheckCircle2,
    Circle,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
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
import { StatusBadge, ProgressBar } from '@/components/work';
import { useState, useEffect } from 'react';
import type { TaskDetailProps } from '@/types/work';
import type { BreadcrumbItem } from '@/types';

export default function TaskDetail({
    task,
    timeEntries,
    activeTimer,
    teamMembers,
}: TaskDetailProps) {
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [logTimeDialogOpen, setLogTimeDialogOpen] = useState(false);
    const [elapsedTime, setElapsedTime] = useState(0);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Work', href: '/work' },
        { title: task.workOrderTitle, href: `/work/work-orders/${task.workOrderId}` },
        { title: task.title, href: `/work/tasks/${task.id}` },
    ];

    const editForm = useForm({
        title: task.title,
        description: task.description || '',
        status: task.status,
        assigned_to_id: task.assignedToId || '',
        due_date: task.dueDate,
        estimated_hours: task.estimatedHours.toString(),
    });

    const timeForm = useForm({
        hours: '',
        date: new Date().toISOString().split('T')[0],
        note: '',
    });

    // Timer logic
    useEffect(() => {
        let interval: NodeJS.Timeout | null = null;
        if (activeTimer) {
            const startTime = new Date(activeTimer.startedAt).getTime();
            const updateElapsed = () => {
                const now = Date.now();
                setElapsedTime(Math.floor((now - startTime) / 1000));
            };
            updateElapsed();
            interval = setInterval(updateElapsed, 1000);
        } else {
            setElapsedTime(0);
        }
        return () => {
            if (interval) clearInterval(interval);
        };
    }, [activeTimer]);

    const formatTime = (seconds: number) => {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    };

    const handleUpdateTask = (e: React.FormEvent) => {
        e.preventDefault();
        editForm.patch(`/work/tasks/${task.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditDialogOpen(false),
        });
    };

    const handleStatusChange = (status: string) => {
        router.patch(`/work/tasks/${task.id}/status`, { status });
    };

    const handleToggleChecklist = (itemIndex: number) => {
        router.patch(`/work/tasks/${task.id}/checklist/${itemIndex}`);
    };

    const handleStartTimer = () => {
        router.post(`/work/tasks/${task.id}/timer/start`);
    };

    const handleStopTimer = () => {
        router.post(`/work/tasks/${task.id}/timer/stop`);
    };

    const handleLogTime = (e: React.FormEvent) => {
        e.preventDefault();
        timeForm.post('/work/time-entries', {
            data: {
                ...timeForm.data,
                task_id: task.id,
            },
            preserveScroll: true,
            onSuccess: () => {
                timeForm.reset();
                setLogTimeDialogOpen(false);
            },
        });
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this task?')) {
            router.delete(`/work/tasks/${task.id}`);
        }
    };

    const completedItems = task.checklistItems.filter((item) => item.completed).length;
    const totalItems = task.checklistItems.length;
    const progress = totalItems > 0 ? Math.round((completedItems / totalItems) * 100) : 0;

    const totalTimeLogged = timeEntries.reduce((sum, entry) => sum + entry.hours, 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={task.title} />

            <div className="flex h-full flex-1 flex-col">
                {/* Header */}
                <div className="px-6 py-6 border-b border-sidebar-border/70 dark:border-sidebar-border">
                    <div className="flex items-center gap-4 mb-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={`/work/work-orders/${task.workOrderId}`}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div className="flex-1">
                            <div className="flex items-center gap-3 mb-1">
                                <h1 className="text-2xl font-bold text-foreground">{task.title}</h1>
                                <StatusBadge status={task.status} type="task" />
                                {task.isBlocked && <Badge variant="destructive">Blocked</Badge>}
                            </div>
                            <p className="text-muted-foreground">
                                {task.workOrderTitle}
                                {task.description && ` • ${task.description}`}
                            </p>
                        </div>
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

                    {/* Task Stats */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <User className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Assigned To</div>
                                <div className="font-medium">{task.assignedToName || 'Unassigned'}</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Estimated</div>
                                <div className="font-medium">{task.estimatedHours}h</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <Clock className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Logged</div>
                                <div className="font-medium">{totalTimeLogged.toFixed(1)}h</div>
                            </div>
                        </div>
                        <div className="flex items-center gap-3 p-3 bg-muted rounded-lg">
                            <CheckCircle2 className="h-5 w-5 text-muted-foreground" />
                            <div>
                                <div className="text-xs text-muted-foreground">Checklist</div>
                                <div className="font-medium">
                                    {completedItems}/{totalItems}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Status Actions */}
                    <div className="mt-4 flex gap-2">
                        {task.status === 'todo' && (
                            <Button size="sm" onClick={() => handleStatusChange('in_progress')}>
                                Start Working
                            </Button>
                        )}
                        {task.status === 'in_progress' && (
                            <Button size="sm" onClick={() => handleStatusChange('done')}>
                                Mark as Done
                            </Button>
                        )}
                        {task.status === 'done' && (
                            <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleStatusChange('in_progress')}
                            >
                                Reopen
                            </Button>
                        )}
                    </div>
                </div>

                {/* Main Content */}
                <div className="flex-1 overflow-auto p-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Timer & Time Tracking */}
                        <div>
                            <h2 className="text-lg font-bold text-foreground mb-4">Time Tracking</h2>

                            {/* Timer Widget */}
                            <div className="p-6 bg-card border border-border rounded-xl mb-4">
                                <div className="text-center mb-4">
                                    <div className="text-4xl font-mono font-bold text-foreground mb-2">
                                        {formatTime(elapsedTime)}
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        {activeTimer ? 'Timer running...' : 'Timer stopped'}
                                    </p>
                                </div>
                                <div className="flex justify-center gap-2">
                                    {activeTimer ? (
                                        <Button onClick={handleStopTimer} variant="destructive">
                                            <Pause className="h-4 w-4 mr-2" />
                                            Stop Timer
                                        </Button>
                                    ) : (
                                        <Button onClick={handleStartTimer}>
                                            <Play className="h-4 w-4 mr-2" />
                                            Start Timer
                                        </Button>
                                    )}
                                    <Button variant="outline" onClick={() => setLogTimeDialogOpen(true)}>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Log Time
                                    </Button>
                                </div>
                            </div>

                            {/* Time Entries */}
                            <div className="space-y-2">
                                <h3 className="text-sm font-medium text-foreground">Time Entries</h3>
                                {timeEntries.length === 0 ? (
                                    <p className="text-sm text-muted-foreground py-4 text-center">
                                        No time logged yet
                                    </p>
                                ) : (
                                    timeEntries.map((entry) => (
                                        <div
                                            key={entry.id}
                                            className="flex items-center justify-between p-3 bg-muted rounded-lg"
                                        >
                                            <div>
                                                <div className="font-medium">{entry.hours}h</div>
                                                <div className="text-xs text-muted-foreground">
                                                    {entry.userName} •{' '}
                                                    {new Date(entry.date).toLocaleDateString()} •{' '}
                                                    {entry.mode}
                                                </div>
                                            </div>
                                            {entry.note && (
                                                <p className="text-sm text-muted-foreground">{entry.note}</p>
                                            )}
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>

                        {/* Checklist */}
                        <div>
                            <h2 className="text-lg font-bold text-foreground mb-4">Checklist</h2>

                            {totalItems > 0 && (
                                <div className="mb-4">
                                    <div className="flex items-center justify-between text-sm mb-2">
                                        <span className="text-muted-foreground">Progress</span>
                                        <span className="font-medium">{progress}%</span>
                                    </div>
                                    <ProgressBar progress={progress} />
                                </div>
                            )}

                            {totalItems === 0 ? (
                                <div className="text-center py-8 bg-muted/50 rounded-xl">
                                    <p className="text-muted-foreground">No checklist items</p>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {task.checklistItems.map((item, index) => (
                                        <div
                                            key={item.id}
                                            className="flex items-center gap-3 p-3 bg-card border border-border rounded-lg"
                                        >
                                            <Checkbox
                                                checked={item.completed}
                                                onCheckedChange={() => handleToggleChecklist(index)}
                                            />
                                            <span
                                                className={
                                                    item.completed ? 'line-through text-muted-foreground' : ''
                                                }
                                            >
                                                {item.text}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Dependencies */}
                            {task.dependencies.length > 0 && (
                                <div className="mt-6">
                                    <h3 className="text-sm font-medium text-foreground mb-3">
                                        Dependencies
                                    </h3>
                                    <div className="space-y-2">
                                        {task.dependencies.map((dep, i) => (
                                            <div
                                                key={i}
                                                className="flex items-center gap-2 p-3 bg-muted rounded-lg text-sm"
                                            >
                                                <Circle className="h-4 w-4 text-muted-foreground" />
                                                <span>{dep}</span>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Edit Dialog */}
            <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                <DialogContent>
                    <form onSubmit={handleUpdateTask}>
                        <DialogHeader>
                            <DialogTitle>Edit Task</DialogTitle>
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
                                <Label>Due Date</Label>
                                <Input
                                    type="date"
                                    value={editForm.data.due_date}
                                    onChange={(e) => editForm.setData('due_date', e.target.value)}
                                />
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

            {/* Log Time Dialog */}
            <Dialog open={logTimeDialogOpen} onOpenChange={setLogTimeDialogOpen}>
                <DialogContent>
                    <form onSubmit={handleLogTime}>
                        <DialogHeader>
                            <DialogTitle>Log Time</DialogTitle>
                            <DialogDescription>Manually log time spent on this task</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label>Hours</Label>
                                    <Input
                                        type="number"
                                        step="0.25"
                                        value={timeForm.data.hours}
                                        onChange={(e) => timeForm.setData('hours', e.target.value)}
                                        placeholder="0.0"
                                    />
                                    <InputError message={timeForm.errors.hours} />
                                </div>
                                <div className="grid gap-2">
                                    <Label>Date</Label>
                                    <Input
                                        type="date"
                                        value={timeForm.data.date}
                                        onChange={(e) => timeForm.setData('date', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label>Note (optional)</Label>
                                <Input
                                    value={timeForm.data.note}
                                    onChange={(e) => timeForm.setData('note', e.target.value)}
                                    placeholder="What did you work on?"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setLogTimeDialogOpen(false)}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={timeForm.processing}>
                                Log Time
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
