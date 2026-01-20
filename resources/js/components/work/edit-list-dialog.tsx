import { useForm } from '@inertiajs/react';
import { useEffect } from 'react';
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
import type { WorkOrderList } from '@/types/work';

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

interface EditListDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    list: WorkOrderList;
}

export function EditListDialog({ open, onOpenChange, list }: EditListDialogProps) {
    const form = useForm({
        name: list.name,
        description: list.description || '',
        color: list.color || '',
    });

    // Reset form when list changes
    useEffect(() => {
        form.setData({
            name: list.name,
            description: list.description || '',
            color: list.color || '',
        });
    }, [list.id]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.patch(`/work/work-order-lists/${list.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Edit List</DialogTitle>
                        <DialogDescription>
                            Update the list details
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Name</Label>
                            <Input
                                id="edit-name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="List name"
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-description">Description (optional)</Label>
                            <Textarea
                                id="edit-description"
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
                            Save Changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
