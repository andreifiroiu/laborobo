import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Progress } from '@/components/ui/progress';
import { Bot, ChevronDown, ChevronUp, User, Clock, Zap, Check } from 'lucide-react';
import { cn } from '@/lib/utils';

/** Skill match information */
interface SkillMatch {
    skillName: string;
    proficiency: 1 | 2 | 3;
    weight: number;
}

/** Capacity analysis for a candidate */
interface CapacityAnalysis {
    availableHours: number;
    capacityPerWeek: number;
    currentWorkload: number;
    percentageAvailable: number;
}

/** Routing recommendation candidate */
export interface RoutingCandidate {
    userId: number;
    userName: string;
    overallScore: number;
    skillScore: number;
    capacityScore: number;
    confidence: 'high' | 'medium' | 'low';
    skillMatches: SkillMatch[];
    capacityAnalysis: CapacityAnalysis;
    reasoning: string;
}

interface RoutingRecommendationsProps {
    /** List of routing candidates with scores */
    candidates: RoutingCandidate[];
    /** Callback when a candidate is selected */
    onSelectCandidate: (userId: number) => void;
    /** Currently selected candidate ID */
    selectedCandidateId?: number | null;
    /** Whether the component is in a loading state */
    isLoading?: boolean;
    /** Whether selection is disabled */
    disabled?: boolean;
}

const proficiencyLabels: Record<number, string> = {
    1: 'Basic',
    2: 'Intermediate',
    3: 'Advanced',
};

const confidenceColors: Record<string, string> = {
    high: 'bg-green-100 text-green-700 border-green-200 dark:bg-green-950/30 dark:text-green-400 dark:border-green-900',
    medium: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/30 dark:text-amber-400 dark:border-amber-900',
    low: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
};

/**
 * Displays routing recommendations from the Dispatcher Agent.
 * Shows top 3+ candidates with scores, skill matches, proficiency levels,
 * capacity analysis, and confidence badges.
 */
export function RoutingRecommendations({
    candidates,
    onSelectCandidate,
    selectedCandidateId,
    isLoading = false,
    disabled = false,
}: RoutingRecommendationsProps) {
    const [expandedCandidates, setExpandedCandidates] = useState<Set<number>>(
        new Set([candidates[0]?.userId])
    );

    const toggleExpanded = (userId: number) => {
        setExpandedCandidates((prev) => {
            const next = new Set(prev);
            if (next.has(userId)) {
                next.delete(userId);
            } else {
                next.add(userId);
            }
            return next;
        });
    };

    if (candidates.length === 0) {
        return (
            <Card>
                <CardContent className="py-8 text-center">
                    <Bot className="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
                    <p className="text-muted-foreground">
                        No routing recommendations available
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader className="pb-3">
                <div className="flex items-center gap-2">
                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                        <Bot className="h-4 w-4" />
                    </div>
                    <CardTitle className="text-base">Routing Recommendations</CardTitle>
                </div>
            </CardHeader>
            <CardContent className="space-y-3">
                {candidates.map((candidate, index) => {
                    const isExpanded = expandedCandidates.has(candidate.userId);
                    const isSelected = selectedCandidateId === candidate.userId;

                    return (
                        <Collapsible
                            key={candidate.userId}
                            open={isExpanded}
                            onOpenChange={() => toggleExpanded(candidate.userId)}
                        >
                            <div
                                className={cn(
                                    'rounded-lg border transition-colors',
                                    isSelected
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border hover:border-primary/50'
                                )}
                            >
                                {/* Candidate Header */}
                                <div className="flex items-center gap-3 p-3">
                                    <div className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-medium">
                                        {index + 1}
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center gap-2">
                                            <User className="h-4 w-4 text-muted-foreground" />
                                            <span className="font-medium truncate">
                                                {candidate.userName}
                                            </span>
                                            <Badge
                                                variant="outline"
                                                className={cn(
                                                    'text-xs capitalize',
                                                    confidenceColors[candidate.confidence]
                                                )}
                                            >
                                                {candidate.confidence}
                                            </Badge>
                                        </div>
                                        <div className="flex items-center gap-3 mt-1 text-xs text-muted-foreground">
                                            <span className="flex items-center gap-1">
                                                <Zap className="h-3 w-3" />
                                                Skills: {candidate.skillScore}%
                                            </span>
                                            <span className="flex items-center gap-1">
                                                <Clock className="h-3 w-3" />
                                                {candidate.capacityAnalysis.availableHours}h available
                                            </span>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <div className="text-right">
                                            <div className="text-lg font-semibold">
                                                {candidate.overallScore}%
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {candidate.capacityAnalysis.percentageAvailable}% capacity
                                            </div>
                                        </div>

                                        <CollapsibleTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="h-8 w-8 p-0"
                                                aria-label={isExpanded ? 'Collapse details' : 'Expand details'}
                                            >
                                                {isExpanded ? (
                                                    <ChevronUp className="h-4 w-4" />
                                                ) : (
                                                    <ChevronDown className="h-4 w-4" />
                                                )}
                                            </Button>
                                        </CollapsibleTrigger>
                                    </div>
                                </div>

                                {/* Expanded Details */}
                                <CollapsibleContent>
                                    <div className="border-t border-border px-3 pb-3 pt-3">
                                        {/* Skill Matches */}
                                        <div className="mb-3">
                                            <h4 className="mb-2 text-xs font-medium uppercase text-muted-foreground">
                                                Skill Matches
                                            </h4>
                                            <div className="flex flex-wrap gap-2">
                                                {candidate.skillMatches.map((skill) => (
                                                    <div
                                                        key={skill.skillName}
                                                        className="flex items-center gap-1.5 rounded-md bg-muted px-2 py-1"
                                                    >
                                                        <span className="text-sm">
                                                            {skill.skillName}
                                                        </span>
                                                        <Badge
                                                            variant="secondary"
                                                            className="h-5 px-1.5 text-xs"
                                                        >
                                                            {proficiencyLabels[skill.proficiency]}
                                                        </Badge>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>

                                        {/* Capacity Analysis */}
                                        <div className="mb-3">
                                            <h4 className="mb-2 text-xs font-medium uppercase text-muted-foreground">
                                                Capacity Analysis
                                            </h4>
                                            <div className="space-y-2">
                                                <div className="flex items-center justify-between text-sm">
                                                    <span>Workload</span>
                                                    <span>
                                                        {candidate.capacityAnalysis.currentWorkload}h /{' '}
                                                        {candidate.capacityAnalysis.capacityPerWeek}h
                                                    </span>
                                                </div>
                                                <Progress
                                                    value={
                                                        100 -
                                                        candidate.capacityAnalysis.percentageAvailable
                                                    }
                                                    className="h-2"
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    {candidate.capacityAnalysis.availableHours}h available
                                                    this week ({candidate.capacityAnalysis.percentageAvailable}%)
                                                </p>
                                            </div>
                                        </div>

                                        {/* Reasoning */}
                                        <div className="mb-3">
                                            <h4 className="mb-2 text-xs font-medium uppercase text-muted-foreground">
                                                Reasoning
                                            </h4>
                                            <p className="text-sm text-muted-foreground">
                                                {candidate.reasoning}
                                            </p>
                                        </div>

                                        {/* Action Button */}
                                        <Button
                                            onClick={() => onSelectCandidate(candidate.userId)}
                                            disabled={disabled || isLoading || isSelected}
                                            className="w-full"
                                            variant={isSelected ? 'secondary' : 'default'}
                                            aria-label={`Select ${candidate.userName}`}
                                        >
                                            {isSelected ? (
                                                <>
                                                    <Check className="mr-2 h-4 w-4" />
                                                    Selected
                                                </>
                                            ) : (
                                                'Select'
                                            )}
                                        </Button>
                                    </div>
                                </CollapsibleContent>
                            </div>
                        </Collapsible>
                    );
                })}
            </CardContent>
        </Card>
    );
}
