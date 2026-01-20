import { useForm } from '@inertiajs/react';
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
import InputError from '@/components/input-error';

const PRESET_COLORS = [
    '#ef4444', // red
    '#f97316', // orange
    '#eab308', // yellow
    '#22c55e', // green
    '#06b6d4', // cyan
    '#3b82f6', // blue
    '#8b5cf6', // violet
    '#ec4899', // pink
];

interface CreateListDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: string;
    projectName: string;
}

export function CreateListDialog({
    open,
    onOpenChange,
    projectId,
    projectName,
}: CreateListDialogProps) {
    const form = useForm({
        projectId,
        name: '',
        description: '',
        color: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/work/work-order-lists', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Create List</DialogTitle>
                        <DialogDescription>
                            Create a new list to organize work orders in {projectName}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="e.g., Sprint 1, Phase A, High Priority"
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">Description (optional)</Label>
                            <Textarea
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder="Brief description of this list"
                                rows={2}
                            />
                            <InputError message={form.errors.description} />
                        </div>
                        <div className="grid gap-2">
                            <Label>Color (optional)</Label>
                            <div className="flex items-center gap-2 flex-wrap">
                                {PRESET_COLORS.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        onClick={() =>
                                            form.setData(
                                                'color',
                                                form.data.color === color ? '' : color
                                            )
                                        }
                                        className={`w-8 h-8 rounded-full border-2 transition-transform hover:scale-110 ${
                                            form.data.color === color
                                                ? 'border-foreground scale-110'
                                                : 'border-transparent'
                                        }`}
                                        style={{ backgroundColor: color }}
                                    />
                                ))}
                                <button
                                    type="button"
                                    onClick={() => form.setData('color', '')}
                                    className={`w-8 h-8 rounded-full border-2 border-dashed transition-transform hover:scale-110 ${
                                        !form.data.color
                                            ? 'border-foreground'
                                            : 'border-muted-foreground'
                                    }`}
                                    title="No color"
                                >
                                    <span className="sr-only">No color</span>
                                </button>
                            </div>
                            <InputError message={form.errors.color} />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            Create List
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
