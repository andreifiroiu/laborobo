import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Plus, X } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import { DispatcherToggle } from '@/components/agents';

interface ChecklistItem {
    id: string;
    text: string;
    completed: boolean;
}

interface TeamMember {
    id: string;
    name: string;
}

interface PromoteToWorkOrderDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    taskId: string;
    taskTitle: string;
    taskDescription: string | null;
    taskDueDate: string | null;
    taskEstimatedHours: number;
    taskAssignedToId: string | null;
    taskChecklistItems: ChecklistItem[];
    teamMembers: TeamMember[];
}

type OriginalTaskAction = 'cancel' | 'delete' | 'keep';

export function PromoteToWorkOrderDialog({
    open,
    onOpenChange,
    taskId,
    taskTitle,
    taskDescription,
    taskDueDate,
    taskEstimatedHours,
    taskAssignedToId,
    taskChecklistItems,
    teamMembers,
}: PromoteToWorkOrderDialogProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    // Form state
    const [title, setTitle] = useState(taskTitle);
    const [description, setDescription] = useState(taskDescription || '');
    const [priority, setPriority] = useState<string>('medium');
    const [dueDate, setDueDate] = useState(taskDueDate || '');
    const [estimatedHours, setEstimatedHours] = useState(taskEstimatedHours.toString());
    const [assignedToId, setAssignedToId] = useState(taskAssignedToId || '');
    const [acceptanceCriteria, setAcceptanceCriteria] = useState<string[]>([]);
    const [newCriterion, setNewCriterion] = useState('');
    const [convertChecklistToTasks, setConvertChecklistToTasks] = useState(false);
    const [originalTaskAction, setOriginalTaskAction] = useState<OriginalTaskAction>('cancel');
    const [dispatcherEnabled, setDispatcherEnabled] = useState(false);

    const hasChecklist = taskChecklistItems.length > 0;

    const handleAddCriterion = () => {
        if (newCriterion.trim()) {
            setAcceptanceCriteria([...acceptanceCriteria, newCriterion.trim()]);
            setNewCriterion('');
        }
    };

    const handleRemoveCriterion = (index: number) => {
        setAcceptanceCriteria(acceptanceCriteria.filter((_, i) => i !== index));
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAddCriterion();
        }
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        router.post(`/work/tasks/${taskId}/promote`, {
            title,
            description: description || null,
            priority,
            dueDate: dueDate || null,
            estimatedHours: estimatedHours ? parseFloat(estimatedHours) : null,
            assignedToId: assignedToId || null,
            acceptanceCriteria,
            convertChecklistToTasks,
            originalTaskAction,
            dispatcherEnabled,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setIsSubmitting(false);
                onOpenChange(false);
            },
            onError: (formErrors) => {
                setIsSubmitting(false);
                setErrors(formErrors as Record<string, string>);
            },
        });
    };

    const handleOpenChange = (newOpen: boolean) => {
        if (!isSubmitting) {
            if (!newOpen) {
                // Reset form state when closing
                setTitle(taskTitle);
                setDescription(taskDescription || '');
                setPriority('medium');
                setDueDate(taskDueDate || '');
                setEstimatedHours(taskEstimatedHours.toString());
                setAssignedToId(taskAssignedToId || '');
                setAcceptanceCriteria([]);
                setNewCriterion('');
                setConvertChecklistToTasks(false);
                setOriginalTaskAction('cancel');
                setDispatcherEnabled(false);
                setErrors({});
            }
            onOpenChange(newOpen);
        }
    };

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Promote to Work Order</DialogTitle>
                        <DialogDescription>
                            Create a new work order from this task. The work order will be created
                            with draft status.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 py-4">
                        {/* Title */}
                        <div className="grid gap-2">
                            <Label htmlFor="title">Title *</Label>
                            <Input
                                id="title"
                                value={title}
                                onChange={(e) => setTitle(e.target.value)}
                                placeholder="Work order title"
                            />
                            <InputError message={errors.title} />
                        </div>

                        {/* Priority */}
                        <div className="grid gap-2">
                            <Label htmlFor="priority">Priority *</Label>
                            <Select value={priority} onValueChange={setPriority}>
                                <SelectTrigger id="priority">
                                    <SelectValue placeholder="Select priority" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="low">Low</SelectItem>
                                    <SelectItem value="medium">Medium</SelectItem>
                                    <SelectItem value="high">High</SelectItem>
                                    <SelectItem value="urgent">Urgent</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.priority} />
                        </div>

                        {/* Description */}
                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={description}
                                onChange={(e) => setDescription(e.target.value)}
                                placeholder="Work order description"
                                rows={3}
                            />
                            <InputError message={errors.description} />
                        </div>

                        {/* Due Date and Estimated Hours */}
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="dueDate">Due Date</Label>
                                <Input
                                    id="dueDate"
                                    type="date"
                                    value={dueDate}
                                    onChange={(e) => setDueDate(e.target.value)}
                                />
                                <InputError message={errors.dueDate} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="estimatedHours">Estimated Hours</Label>
                                <Input
                                    id="estimatedHours"
                                    type="number"
                                    step="0.5"
                                    min="0"
                                    value={estimatedHours}
                                    onChange={(e) => setEstimatedHours(e.target.value)}
                                    placeholder="0"
                                />
                                <InputError message={errors.estimatedHours} />
                            </div>
                        </div>

                        {/* Assigned To */}
                        <div className="grid gap-2">
                            <Label htmlFor="assignedTo">Assigned To</Label>
                            <Select value={assignedToId} onValueChange={setAssignedToId}>
                                <SelectTrigger id="assignedTo">
                                    <SelectValue placeholder="Select team member" />
                                </SelectTrigger>
                                <SelectContent>
                                    {teamMembers.map((member) => (
                                        <SelectItem key={member.id} value={member.id}>
                                            {member.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.assignedToId} />
                        </div>

                        {/* Acceptance Criteria */}
                        <div className="grid gap-2">
                            <Label>Acceptance Criteria</Label>
                            <div className="flex gap-2">
                                <Input
                                    value={newCriterion}
                                    onChange={(e) => setNewCriterion(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                    placeholder="Add acceptance criterion"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={handleAddCriterion}
                                >
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </div>
                            {acceptanceCriteria.length > 0 && (
                                <ul className="mt-2 space-y-1">
                                    {acceptanceCriteria.map((criterion, index) => (
                                        <li
                                            key={index}
                                            className="bg-muted flex items-center justify-between rounded-md px-3 py-2 text-sm"
                                        >
                                            <span>{criterion}</span>
                                            <button
                                                type="button"
                                                onClick={() => handleRemoveCriterion(index)}
                                                className="text-muted-foreground hover:text-foreground"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            <InputError message={errors.acceptanceCriteria} />
                        </div>

                        {/* Convert Checklist to Tasks */}
                        {hasChecklist && (
                            <div className="flex items-center space-x-2">
                                <Checkbox
                                    id="convertChecklist"
                                    checked={convertChecklistToTasks}
                                    onCheckedChange={(checked) =>
                                        setConvertChecklistToTasks(checked === true)
                                    }
                                />
                                <Label htmlFor="convertChecklist" className="cursor-pointer">
                                    Convert {taskChecklistItems.length} checklist item
                                    {taskChecklistItems.length !== 1 ? 's' : ''} to tasks
                                </Label>
                            </div>
                        )}

                        {/* Dispatcher Agent Toggle */}
                        <DispatcherToggle
                            checked={dispatcherEnabled}
                            onCheckedChange={setDispatcherEnabled}
                        />

                        {/* Original Task Action */}
                        <div className="grid gap-3">
                            <Label>What should happen to the original task?</Label>
                            <div className="space-y-2">
                                <label className="flex cursor-pointer items-center space-x-3 rounded-md border p-3 transition-colors hover:bg-muted/50">
                                    <input
                                        type="radio"
                                        name="originalTaskAction"
                                        value="cancel"
                                        checked={originalTaskAction === 'cancel'}
                                        onChange={() => setOriginalTaskAction('cancel')}
                                        className="h-4 w-4"
                                    />
                                    <div className="flex-1">
                                        <div className="font-medium">Cancel (Recommended)</div>
                                        <div className="text-muted-foreground text-sm">
                                            Mark the task as cancelled for reference
                                        </div>
                                    </div>
                                </label>
                                <label className="flex cursor-pointer items-center space-x-3 rounded-md border p-3 transition-colors hover:bg-muted/50">
                                    <input
                                        type="radio"
                                        name="originalTaskAction"
                                        value="delete"
                                        checked={originalTaskAction === 'delete'}
                                        onChange={() => setOriginalTaskAction('delete')}
                                        className="h-4 w-4"
                                    />
                                    <div className="flex-1">
                                        <div className="font-medium">Delete</div>
                                        <div className="text-muted-foreground text-sm">
                                            Remove the task completely
                                        </div>
                                    </div>
                                </label>
                                <label className="flex cursor-pointer items-center space-x-3 rounded-md border p-3 transition-colors hover:bg-muted/50">
                                    <input
                                        type="radio"
                                        name="originalTaskAction"
                                        value="keep"
                                        checked={originalTaskAction === 'keep'}
                                        onChange={() => setOriginalTaskAction('keep')}
                                        className="h-4 w-4"
                                    />
                                    <div className="flex-1">
                                        <div className="font-medium">Keep</div>
                                        <div className="text-muted-foreground text-sm">
                                            Leave the task unchanged
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <InputError message={errors.originalTaskAction} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                            disabled={isSubmitting}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? 'Creating...' : 'Create Work Order'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
