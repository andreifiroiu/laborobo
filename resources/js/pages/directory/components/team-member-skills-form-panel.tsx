import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import InputError from '@/components/input-error';
import type { TeamMember, Skill } from '@/types/directory';

interface TeamMemberSkillsFormPanelProps {
    open: boolean;
    teamMember: TeamMember;
    onClose: () => void;
}

export function TeamMemberSkillsFormPanel({
    open,
    teamMember,
    onClose,
}: TeamMemberSkillsFormPanelProps) {
    const [skills, setSkills] = useState<Skill[]>(teamMember.skills || []);

    const capacityForm = useForm({
        capacityHoursPerWeek: teamMember.capacityHoursPerWeek,
        currentWorkloadHours: teamMember.currentWorkloadHours,
        role: teamMember.role || '',
    });

    const skillsForm = useForm({
        skills: teamMember.skills || [],
    });

    const handleAddSkill = () => {
        setSkills([...skills, { name: '', proficiency: 1 }]);
    };

    const handleRemoveSkill = (index: number) => {
        setSkills(skills.filter((_, i) => i !== index));
    };

    const handleSkillChange = (index: number, field: keyof Skill, value: string | number) => {
        const newSkills = [...skills];
        newSkills[index] = { ...newSkills[index], [field]: value };
        setSkills(newSkills);
    };

    const handleSubmitCapacity = (e: React.FormEvent) => {
        e.preventDefault();
        capacityForm.patch(`/directory/team/${teamMember.id}/capacity`, {
            preserveScroll: true,
            onSuccess: () => {
                // Don't close yet, user might want to update skills too
            },
        });
    };

    const handleSubmitSkills = (e: React.FormEvent) => {
        e.preventDefault();

        // Filter out empty skills
        const validSkills = skills.filter((s) => s.name.trim().length > 0);

        skillsForm.patch(
            `/directory/team/${teamMember.id}/skills`,
            {
                data: { skills: validSkills },
                preserveScroll: true,
                onSuccess: () => onClose(),
            }
        );
    };

    return (
        <Sheet open={open} onOpenChange={(isOpen) => !isOpen && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <SheetTitle>Edit Skills & Capacity</SheetTitle>
                    </SheetHeader>
                </div>

                <div className="space-y-8 px-6 pb-6">
                    {/* Capacity Form */}
                    <form onSubmit={handleSubmitCapacity} className="space-y-6">
                        <div>
                            <h3 className="mb-4 text-lg font-semibold text-foreground">
                                Capacity Settings
                            </h3>

                            {/* Role */}
                            <div className="mb-4 space-y-2">
                                <Label htmlFor="role">Role</Label>
                                <Input
                                    id="role"
                                    value={capacityForm.data.role}
                                    onChange={(e) =>
                                        capacityForm.setData('role', e.target.value)
                                    }
                                    placeholder="e.g., Senior Developer"
                                />
                                <InputError message={capacityForm.errors.role} />
                            </div>

                            {/* Capacity Hours Per Week */}
                            <div className="mb-4 space-y-2">
                                <Label htmlFor="capacityHoursPerWeek">
                                    Capacity Hours Per Week
                                </Label>
                                <Input
                                    id="capacityHoursPerWeek"
                                    type="number"
                                    min="0"
                                    max="168"
                                    value={capacityForm.data.capacityHoursPerWeek}
                                    onChange={(e) =>
                                        capacityForm.setData(
                                            'capacityHoursPerWeek',
                                            parseInt(e.target.value) || 0
                                        )
                                    }
                                />
                                <InputError message={capacityForm.errors.capacityHoursPerWeek} />
                            </div>

                            {/* Current Workload Hours */}
                            <div className="mb-4 space-y-2">
                                <Label htmlFor="currentWorkloadHours">
                                    Current Workload Hours
                                </Label>
                                <Input
                                    id="currentWorkloadHours"
                                    type="number"
                                    min="0"
                                    max="168"
                                    value={capacityForm.data.currentWorkloadHours}
                                    onChange={(e) =>
                                        capacityForm.setData(
                                            'currentWorkloadHours',
                                            parseInt(e.target.value) || 0
                                        )
                                    }
                                />
                                <InputError message={capacityForm.errors.currentWorkloadHours} />
                            </div>

                            <Button
                                type="submit"
                                variant="outline"
                                disabled={capacityForm.processing}
                            >
                                Update Capacity
                            </Button>
                        </div>
                    </form>

                    <Separator />

                    {/* Skills Form */}
                    <form onSubmit={handleSubmitSkills} className="space-y-6">
                        <div>
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-foreground">Skills</h3>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleAddSkill}
                                >
                                    <Plus className="mr-2 h-4 w-4" />
                                    Add Skill
                                </Button>
                            </div>

                            {skills.length === 0 ? (
                                <div className="rounded-lg border border-dashed border-border p-8 text-center">
                                    <p className="text-sm text-muted-foreground">
                                        No skills added yet. Click "Add Skill" to get started.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-3">
                                    {skills.map((skill, index) => (
                                        <div
                                            key={index}
                                            className="flex items-end gap-2 rounded-lg border border-border bg-muted/50 p-3"
                                        >
                                            <div className="flex-1 space-y-1">
                                                <Label
                                                    htmlFor={`skill-name-${index}`}
                                                    className="text-xs"
                                                >
                                                    Skill Name
                                                </Label>
                                                <Input
                                                    id={`skill-name-${index}`}
                                                    value={skill.name}
                                                    onChange={(e) =>
                                                        handleSkillChange(
                                                            index,
                                                            'name',
                                                            e.target.value
                                                        )
                                                    }
                                                    placeholder="e.g., React, TypeScript"
                                                />
                                            </div>

                                            <div className="w-40 space-y-1">
                                                <Label
                                                    htmlFor={`skill-proficiency-${index}`}
                                                    className="text-xs"
                                                >
                                                    Proficiency
                                                </Label>
                                                <Select
                                                    value={skill.proficiency.toString()}
                                                    onValueChange={(value) =>
                                                        handleSkillChange(
                                                            index,
                                                            'proficiency',
                                                            parseInt(value) as 1 | 2 | 3
                                                        )
                                                    }
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="1">Basic</SelectItem>
                                                        <SelectItem value="2">
                                                            Intermediate
                                                        </SelectItem>
                                                        <SelectItem value="3">Advanced</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => handleRemoveSkill(index)}
                                                className="shrink-0"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            )}

                            <div className="mt-6 flex gap-2">
                                <Button type="button" variant="outline" onClick={onClose}>
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={skillsForm.processing}>
                                    Update Skills
                                </Button>
                            </div>
                        </div>
                    </form>
                </div>
            </SheetContent>
        </Sheet>
    );
}
