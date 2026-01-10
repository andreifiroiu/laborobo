import { useForm } from '@inertiajs/react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { Separator } from '@/components/ui/separator';
import type { PlaybookFormPanelProps, PlaybookType } from '@/types/playbooks';
import { SOPForm } from './sop-form';
import { ChecklistForm } from './checklist-form';
import { TemplateForm } from './template-form';
import { AcceptanceCriteriaForm } from './acceptance-criteria-form';

// Helper function to get default content structure based on playbook type
const getDefaultContent = (type: PlaybookType) => {
    switch (type) {
        case 'sop':
            return {
                triggerConditions: '',
                steps: [],
                rolesInvolved: [],
                estimatedTimeMinutes: 0,
                definitionOfDone: '',
            };
        case 'checklist':
            return {
                items: [],
            };
        case 'template':
            return {
                templateType: 'project',
                structure: {},
            };
        case 'acceptance_criteria':
            return {
                criteria: [],
            };
        default:
            return {};
    }
};

export function PlaybookFormPanel({
    open,
    playbook,
    type,
    onClose,
}: PlaybookFormPanelProps) {
    const playbookType = playbook?.type || type;
    const form = useForm({
        type: playbookType,
        name: playbook?.name || '',
        description: playbook?.description || '',
        content: playbook?.content || getDefaultContent(playbookType),
        tags: playbook?.tags || [],
        aiGenerated: playbook?.aiGenerated || false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (playbook) {
            form.patch(`/playbooks/${playbook.id}`, {
                preserveScroll: true,
                onSuccess: () => onClose(),
            });
        } else {
            form.post('/playbooks', {
                preserveScroll: true,
                onSuccess: () => onClose(),
            });
        }
    };

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'sop':
                return 'SOP';
            case 'checklist':
                return 'Checklist';
            case 'template':
                return 'Template';
            case 'acceptance_criteria':
                return 'Acceptance Criteria';
            default:
                return 'Playbook';
        }
    };

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <SheetTitle>
                            {playbook ? 'Edit' : 'Create'} {getTypeLabel(form.data.type)}
                        </SheetTitle>
                    </SheetHeader>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6 px-6 pb-6">
                    {/* Name */}
                    <div className="space-y-2">
                        <Label htmlFor="name">
                            Name <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            placeholder="Enter playbook name"
                            autoFocus
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    {/* Description */}
                    <div className="space-y-2">
                        <Label htmlFor="description">
                            Description <span className="text-red-500">*</span>
                        </Label>
                        <textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            placeholder="Describe what this playbook is for"
                            rows={4}
                            className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    {/* Tags */}
                    <div className="space-y-2">
                        <Label htmlFor="tags">Tags</Label>
                        <Input
                            id="tags"
                            value={form.data.tags.join(', ')}
                            onChange={(e) =>
                                form.setData(
                                    'tags',
                                    e.target.value
                                        .split(',')
                                        .map((t) => t.trim())
                                        .filter((t) => t.length > 0)
                                )
                            }
                            placeholder="Enter tags separated by commas"
                        />
                        <p className="text-xs text-muted-foreground">
                            Enter tags separated by commas
                        </p>
                        <InputError message={form.errors.tags} />
                    </div>

                    <Separator />

                    {/* Type-specific fields */}
                    <div>
                        <h3 className="mb-4 text-sm font-semibold">
                            {getTypeLabel(form.data.type)} Content
                        </h3>
                        {form.data.type === 'sop' && <SOPForm form={form} />}
                        {form.data.type === 'checklist' && <ChecklistForm form={form} />}
                        {form.data.type === 'template' && <TemplateForm form={form} />}
                        {form.data.type === 'acceptance_criteria' && (
                            <AcceptanceCriteriaForm form={form} />
                        )}
                    </div>

                    <Separator />

                    {/* Actions */}
                    <div className="flex gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing}>
                            {playbook ? 'Update' : 'Create'} {getTypeLabel(form.data.type)}
                        </Button>
                    </div>
                </form>
            </SheetContent>
        </Sheet>
    );
}
