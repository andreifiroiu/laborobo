import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Plus, X } from 'lucide-react';
import type { InertiaFormProps } from '@inertiajs/react';
import type { ChecklistContent, ChecklistItem } from '@/types/playbooks';

interface ChecklistFormProps {
    form: InertiaFormProps<{
        type: string;
        name: string;
        description: string;
        content: ChecklistContent;
        tags: string[];
        aiGenerated: boolean;
    }>;
}

export function ChecklistForm({ form }: ChecklistFormProps) {
    const content = form.data.content as ChecklistContent;

    const addItem = () => {
        const newItem: ChecklistItem = {
            id: `item-${Date.now()}`,
            label: '',
            description: '',
            evidence: [],
            assignedRole: '',
        };
        form.setData('content', {
            ...content,
            items: [...content.items, newItem],
        });
    };

    const removeItem = (index: number) => {
        const updatedItems = content.items.filter((_, i) => i !== index);
        form.setData('content', {
            ...content,
            items: updatedItems,
        });
    };

    const updateItem = (index: number, field: keyof ChecklistItem, value: string) => {
        const updatedItems = [...content.items];
        updatedItems[index] = {
            ...updatedItems[index],
            [field]: value,
        };
        form.setData('content', {
            ...content,
            items: updatedItems,
        });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <Label>Checklist Items</Label>
                <Button type="button" size="sm" variant="outline" onClick={addItem}>
                    <Plus className="mr-1 size-4" />
                    Add Item
                </Button>
            </div>

            {content.items.map((item, index) => (
                <div key={item.id} className="rounded-lg border p-3 space-y-3">
                    <div className="flex items-start gap-2">
                        <div className="flex size-5 shrink-0 items-center justify-center rounded border-2 border-primary mt-2">
                            <div className="size-2 rounded-sm bg-primary" />
                        </div>
                        <div className="flex-1 space-y-3">
                            <Input
                                placeholder="Item label (e.g., Verify all tests pass)"
                                value={item.label}
                                onChange={(e) => updateItem(index, 'label', e.target.value)}
                            />
                            <textarea
                                placeholder="Description (optional)"
                                value={item.description || ''}
                                onChange={(e) =>
                                    updateItem(index, 'description', e.target.value)
                                }
                                rows={2}
                                className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <Input
                                placeholder="Assigned Role (optional)"
                                value={item.assignedRole || ''}
                                onChange={(e) =>
                                    updateItem(index, 'assignedRole', e.target.value)
                                }
                            />
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => removeItem(index)}
                        >
                            <X className="size-4" />
                        </Button>
                    </div>
                </div>
            ))}

            {content.items.length === 0 && (
                <div className="rounded-lg border border-dashed p-8 text-center">
                    <p className="text-sm text-muted-foreground">
                        No checklist items added yet. Click "Add Item" to get started.
                    </p>
                </div>
            )}
        </div>
    );
}
