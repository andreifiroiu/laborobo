import { UserCircle2, Tag as TagIcon } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import type { TeamMember } from '@/types/directory';

interface TeamListProps {
    teamMembers: TeamMember[];
    onTeamMemberClick: (memberId: string) => void;
    searchQuery: string;
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

export function TeamList({ teamMembers, onTeamMemberClick, searchQuery }: TeamListProps) {
    if (teamMembers.length === 0) {
        return (
            <div className="flex h-[50vh] items-center justify-center">
                <div className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
                        <UserCircle2 className="h-8 w-8 text-muted-foreground" />
                    </div>
                    <h3 className="mb-2 text-lg font-semibold text-foreground">
                        {searchQuery ? 'No team members found' : 'No team members yet'}
                    </h3>
                    <p className="mb-6 text-sm text-muted-foreground">
                        {searchQuery
                            ? 'Try adjusting your search query'
                            : 'Your team members will appear here'}
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {teamMembers.map((member) => {
                const capacityPercentage =
                    member.capacityHoursPerWeek > 0
                        ? (member.currentWorkloadHours / member.capacityHoursPerWeek) * 100
                        : 0;

                return (
                    <Card
                        key={member.id}
                        className="cursor-pointer p-4 transition-colors hover:bg-accent"
                        onClick={() => onTeamMemberClick(member.id)}
                    >
                        {/* Header with Avatar */}
                        <div className="mb-3 flex items-start gap-3">
                            <Avatar className="h-12 w-12">
                                <AvatarImage src={member.avatar} alt={member.name} />
                                <AvatarFallback className="bg-primary/10 text-primary">
                                    {member.name
                                        .split(' ')
                                        .map((n) => n[0])
                                        .join('')
                                        .toUpperCase()
                                        .slice(0, 2)}
                                </AvatarFallback>
                            </Avatar>

                            <div className="flex-1">
                                <h3 className="font-semibold text-foreground">{member.name}</h3>
                                {member.role && (
                                    <p className="text-sm text-muted-foreground">{member.role}</p>
                                )}
                                <div className="mt-1">
                                    <Badge
                                        variant={
                                            member.status === 'active' ? 'default' : 'secondary'
                                        }
                                    >
                                        {member.status}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        {/* Capacity Bar */}
                        <div className="mb-3">
                            <div className="mb-1 flex items-center justify-between text-sm">
                                <span className="text-muted-foreground">Capacity</span>
                                <span
                                    className={`font-medium ${getCapacityTextColor(capacityPercentage)}`}
                                >
                                    {member.currentWorkloadHours}h / {member.capacityHoursPerWeek}h
                                </span>
                            </div>
                            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    className={`h-full transition-all ${getCapacityColor(capacityPercentage)}`}
                                    style={{ width: `${Math.min(capacityPercentage, 100)}%` }}
                                />
                            </div>
                        </div>

                        {/* Skills */}
                        {member.skills && member.skills.length > 0 && (
                            <div className="mb-3">
                                <p className="mb-2 text-xs font-medium text-muted-foreground">
                                    SKILLS
                                </p>
                                <div className="flex flex-wrap gap-1">
                                    {member.skills.slice(0, 4).map((skill, index) => (
                                        <Badge
                                            key={index}
                                            variant="outline"
                                            className={proficiencyColors[skill.proficiency] || ''}
                                        >
                                            {skill.name}
                                            <span className="ml-1 text-[10px] opacity-70">
                                                •{' '}
                                                {proficiencyLabels[skill.proficiency] ||
                                                    skill.proficiency}
                                            </span>
                                        </Badge>
                                    ))}
                                    {member.skills.length > 4 && (
                                        <Badge variant="secondary" className="text-xs">
                                            +{member.skills.length - 4} more
                                        </Badge>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Tags */}
                        {member.tags && member.tags.length > 0 && (
                            <div className="flex items-center gap-2">
                                <TagIcon className="h-3 w-3 shrink-0 text-muted-foreground" />
                                <div className="flex flex-wrap gap-1">
                                    {member.tags.slice(0, 3).map((tag) => (
                                        <Badge key={tag} variant="secondary" className="text-xs">
                                            {tag}
                                        </Badge>
                                    ))}
                                    {member.tags.length > 3 && (
                                        <Badge variant="secondary" className="text-xs">
                                            +{member.tags.length - 3}
                                        </Badge>
                                    )}
                                </div>
                            </div>
                        )}
                    </Card>
                );
            })}
        </div>
    );
}
