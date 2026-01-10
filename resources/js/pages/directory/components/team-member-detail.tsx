import { router } from '@inertiajs/react';
import {
    Mail,
    Globe2,
    Briefcase,
    Tag as TagIcon,
    Calendar,
    Edit,
    FolderKanban,
    Award,
} from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import type { TeamMember, Project } from '@/types/directory';

interface TeamMemberDetailProps {
    teamMember: TeamMember;
    projects: Project[];
    onEditSkills: (member: TeamMember) => void;
}

const proficiencyLabels: Record<number, string> = {
    1: 'Basic',
    2: 'Intermediate',
    3: 'Advanced',
};

const proficiencyColors: Record<number, string> = {
    1: 'bg-slate-500/10 text-slate-700 dark:text-slate-400 border-slate-500/20',
    2: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
    3: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/20',
};

function getCapacityColor(percentage: number): string {
    if (percentage < 75) return 'bg-emerald-500';
    if (percentage < 90) return 'bg-amber-500';
    return 'bg-red-500';
}

function getCapacityTextColor(percentage: number): string {
    if (percentage < 75) return 'text-emerald-700 dark:text-emerald-400';
    if (percentage < 90) return 'text-amber-700 dark:text-amber-400';
    return 'text-red-700 dark:text-red-400';
}

export function TeamMemberDetail({
    teamMember,
    projects,
    onEditSkills,
}: TeamMemberDetailProps) {
    const capacityPercentage =
        teamMember.capacityHoursPerWeek > 0
            ? (teamMember.currentWorkloadHours / teamMember.capacityHoursPerWeek) * 100
            : 0;

    const availableHours =
        teamMember.capacityHoursPerWeek - teamMember.currentWorkloadHours;

    return (
        <div className="space-y-6">
            {/* Header with Avatar */}
            <div>
                <div className="mb-4 flex items-start gap-4">
                    <Avatar className="h-20 w-20">
                        <AvatarImage src={teamMember.avatar} alt={teamMember.name} />
                        <AvatarFallback className="bg-primary/10 text-lg text-primary">
                            {teamMember.name
                                .split(' ')
                                .map((n) => n[0])
                                .join('')
                                .toUpperCase()
                                .slice(0, 2)}
                        </AvatarFallback>
                    </Avatar>

                    <div className="flex-1">
                        <h2 className="mb-1 text-2xl font-bold text-foreground">
                            {teamMember.name}
                        </h2>
                        {teamMember.role && (
                            <p className="mb-2 text-muted-foreground">{teamMember.role}</p>
                        )}
                        <Badge
                            variant={teamMember.status === 'active' ? 'default' : 'secondary'}
                        >
                            {teamMember.status}
                        </Badge>
                    </div>
                </div>

                <div className="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => onEditSkills(teamMember)}
                    >
                        <Edit className="mr-2 h-4 w-4" />
                        Edit Skills & Capacity
                    </Button>
                </div>
            </div>

            <Separator />

            {/* Capacity Overview */}
            <div>
                <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                    Capacity Overview
                </h3>
                <div className="space-y-3">
                    <div>
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-sm text-muted-foreground">
                                Current Workload
                            </span>
                            <span
                                className={`text-sm font-medium ${getCapacityTextColor(capacityPercentage)}`}
                            >
                                {teamMember.currentWorkloadHours}h /{' '}
                                {teamMember.capacityHoursPerWeek}h (
                                {capacityPercentage.toFixed(0)}%)
                            </span>
                        </div>
                        <div className="h-3 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                className={`h-full transition-all ${getCapacityColor(capacityPercentage)}`}
                                style={{ width: `${Math.min(capacityPercentage, 100)}%` }}
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4 rounded-lg border border-border bg-muted/50 p-4">
                        <div>
                            <p className="mb-1 text-xs text-muted-foreground">
                                Available Capacity
                            </p>
                            <p
                                className={`text-lg font-semibold ${
                                    availableHours > 0
                                        ? 'text-emerald-700 dark:text-emerald-400'
                                        : 'text-red-700 dark:text-red-400'
                                }`}
                            >
                                {Math.max(availableHours, 0)}h
                            </p>
                        </div>
                        <div>
                            <p className="mb-1 text-xs text-muted-foreground">Weekly Capacity</p>
                            <p className="text-lg font-semibold text-foreground">
                                {teamMember.capacityHoursPerWeek}h
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Contact Information */}
            <Separator />
            <div>
                <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                    Contact Information
                </h3>
                <div className="space-y-3">
                    <div className="flex items-start gap-3">
                        <Mail className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                        <div>
                            <p className="text-sm font-medium text-foreground">Email</p>
                            <a
                                href={`mailto:${teamMember.email}`}
                                className="text-sm text-primary hover:underline"
                            >
                                {teamMember.email}
                            </a>
                        </div>
                    </div>
                    {teamMember.timezone && (
                        <div className="flex items-start gap-3">
                            <Globe2 className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium text-foreground">Timezone</p>
                                <p className="text-sm text-muted-foreground">
                                    {teamMember.timezone}
                                </p>
                            </div>
                        </div>
                    )}
                    {teamMember.role && (
                        <div className="flex items-start gap-3">
                            <Briefcase className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
                            <div>
                                <p className="text-sm font-medium text-foreground">Role</p>
                                <p className="text-sm text-muted-foreground">{teamMember.role}</p>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Skills */}
            {teamMember.skills && teamMember.skills.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            <Award className="mr-2 inline-block h-4 w-4" />
                            Skills ({teamMember.skills.length})
                        </h3>
                        <div className="space-y-2">
                            {teamMember.skills.map((skill, index) => (
                                <div
                                    key={index}
                                    className="flex items-center justify-between rounded-lg border border-border bg-muted/50 p-3"
                                >
                                    <span className="font-medium text-foreground">
                                        {skill.name}
                                    </span>
                                    <div className="flex items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            className={proficiencyColors[skill.proficiency] || ''}
                                        >
                                            {proficiencyLabels[skill.proficiency] ||
                                                skill.proficiency}
                                        </Badge>
                                        <div className="flex gap-0.5">
                                            {[1, 2, 3].map((level) => (
                                                <div
                                                    key={level}
                                                    className={`h-2 w-2 rounded-full ${
                                                        level <= skill.proficiency
                                                            ? 'bg-primary'
                                                            : 'bg-muted'
                                                    }`}
                                                />
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </>
            )}

            {/* Assigned Projects */}
            {projects.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            <FolderKanban className="mr-2 inline-block h-4 w-4" />
                            Assigned Projects ({projects.length})
                        </h3>
                        <div className="space-y-2">
                            {projects.map((project) => (
                                <div
                                    key={project.id}
                                    className="cursor-pointer rounded-lg border border-border bg-muted/50 p-3 transition-colors hover:bg-muted"
                                    onClick={() => router.visit(`/work?project=${project.id}`)}
                                >
                                    <p className="font-medium text-foreground">{project.name}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </>
            )}

            {/* Tags */}
            {teamMember.tags && teamMember.tags.length > 0 && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                            <TagIcon className="mr-2 inline-block h-4 w-4" />
                            Tags
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {teamMember.tags.map((tag) => (
                                <Badge key={tag} variant="secondary">
                                    {tag}
                                </Badge>
                            ))}
                        </div>
                    </div>
                </>
            )}

            {/* Metadata */}
            <Separator />
            <div>
                <h3 className="mb-3 text-sm font-semibold uppercase text-muted-foreground">
                    Metadata
                </h3>
                <div className="space-y-2 text-sm">
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Calendar className="h-4 w-4 shrink-0" />
                        <span>Joined {new Date(teamMember.joinedAt).toLocaleDateString()}</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
