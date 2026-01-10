import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Plus, X, Bot, User } from 'lucide-react';
import type { InertiaFormProps } from '@inertiajs/react';
import type {
    AcceptanceCriteriaContent,
    AcceptanceCriterion,
    ValidationType,
} from '@/types/playbooks';

interface AcceptanceCriteriaFormProps {
    form: InertiaFormProps<{
        type: string;
        name: string;
        description: string;
        content: AcceptanceCriteriaContent;
        tags: string[];
        aiGenerated: boolean;
    }>;
}

export function AcceptanceCriteriaForm({ form }: AcceptanceCriteriaFormProps) {
    const content = form.data.content as AcceptanceCriteriaContent;

    const addCriterion = () => {
        const newCriterion: AcceptanceCriterion = {
            id: `criterion-${Date.now()}`,
            rule: '',
            validationType: 'manual',
            validationDetails: '',
        };
        form.setData('content', {
            ...content,
            criteria: [...content.criteria, newCriterion],
        });
    };

    const removeCriterion = (index: number) => {
        const updatedCriteria = content.criteria.filter((_, i) => i !== index);
        form.setData('content', {
            ...content,
            criteria: updatedCriteria,
        });
    };

    const updateCriterion = (
        index: number,
        field: keyof AcceptanceCriterion,
        value: string
    ) => {
        const updatedCriteria = [...content.criteria];
        updatedCriteria[index] = {
            ...updatedCriteria[index],
            [field]: value,
        };
        form.setData('content', {
            ...content,
            criteria: updatedCriteria,
        });
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <Label>Acceptance Criteria</Label>
                <Button type="button" size="sm" variant="outline" onClick={addCriterion}>
                    <Plus className="mr-1 size-4" />
                    Add Criterion
                </Button>
            </div>

            {content.criteria.map((criterion, index) => (
                <div key={criterion.id} className="rounded-lg border p-4 space-y-3">
                    <div className="flex items-start gap-2">
                        <div className="flex-1 space-y-3">
                            {/* Rule */}
                            <div className="space-y-2">
                                <Label>Criterion Rule</Label>
                                <Input
                                    placeholder="e.g., All unit tests must pass"
                                    value={criterion.rule}
                                    onChange={(e) =>
                                        updateCriterion(index, 'rule', e.target.value)
                                    }
                                />
                            </div>

                            {/* Validation Type */}
                            <div className="space-y-2">
                                <Label>Validation Type</Label>
                                <Select
                                    value={criterion.validationType}
                                    onValueChange={(value: ValidationType) =>
                                        updateCriterion(index, 'validationType', value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="automated">
                                            <div className="flex items-center gap-2">
                                                <Bot className="size-4" />
                                                <span>Automated</span>
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="manual">
                                            <div className="flex items-center gap-2">
                                                <User className="size-4" />
                                                <span>Manual</span>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Validation Details */}
                            <div className="space-y-2">
                                <Label>Validation Details (optional)</Label>
                                <textarea
                                    placeholder="How will this criterion be validated?"
                                    value={criterion.validationDetails || ''}
                                    onChange={(e) =>
                                        updateCriterion(
                                            index,
                                            'validationDetails',
                                            e.target.value
                                        )
                                    }
                                    rows={2}
                                    className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                            </div>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="ghost"
                            onClick={() => removeCriterion(index)}
                        >
                            <X className="size-4" />
                        </Button>
                    </div>
                </div>
            ))}

            {content.criteria.length === 0 && (
                <div className="rounded-lg border border-dashed p-8 text-center">
                    <p className="text-sm text-muted-foreground">
                        No acceptance criteria added yet. Click "Add Criterion" to get
                        started.
                    </p>
                </div>
            )}

            {/* Help Text */}
            <div className="rounded-lg bg-muted/50 p-4">
                <h4 className="mb-2 text-sm font-semibold">Tips</h4>
                <ul className="list-disc list-inside space-y-1 text-xs text-muted-foreground">
                    <li>
                        <strong>Automated</strong> criteria can be verified by scripts or tools
                    </li>
                    <li>
                        <strong>Manual</strong> criteria require human review and approval
                    </li>
                    <li>Be specific and measurable in your criteria</li>
                </ul>
            </div>
        </div>
    );
}
