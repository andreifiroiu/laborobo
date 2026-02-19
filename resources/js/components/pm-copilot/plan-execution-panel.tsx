import { AlertCircle, Bot, Clock, Sparkles, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { PlanExecutionPanelProps } from '@/types/pm-copilot.d';

/**
 * Post-approval panel for assigning tasks to team members or AI agents.
 * Supports AI-powered delegation suggestions and manual assignment.
 */
export function PlanExecutionPanel({
    tasks,
    teamMembers,
    availableAgents,
    onAssign,
    onDelegateAll,
    isDelegating,
    isAssigning,
    aiSuggestions,
    delegationError,
}: PlanExecutionPanelProps) {
    const getSuggestionForTask = (taskId: string) =>
        aiSuggestions.find((s) => s.taskId === taskId);

    const handleAssigneeChange = (taskId: string, value: string) => {
        // value format: "user:id" or "agent:id"
        const [type, id] = value.split(':') as ['user' | 'agent', string];
        onAssign(taskId, type, id);
    };

    const getCurrentValue = (task: PlanExecutionPanelProps['tasks'][0]) => {
        if (task.assignedToId) {
            return `user:${task.assignedToId}`;
        }
        return '';
    };

    if (tasks.length === 0) {
        return null;
    }

    return (
        <Card>
            <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Bot className="text-muted-foreground h-5 w-5" />
                        <CardTitle className="text-base">Plan Execution</CardTitle>
                    </div>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={onDelegateAll}
                        disabled={isDelegating || isAssigning}
                    >
                        <Sparkles className="mr-1.5 h-4 w-4" />
                        {isDelegating ? 'Analyzing...' : 'Delegate to AI'}
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="space-y-3">
                {delegationError && (
                    <div className="text-destructive flex items-center gap-2 text-sm">
                        <AlertCircle className="h-4 w-4 shrink-0" />
                        <span>{delegationError}</span>
                    </div>
                )}
                <TooltipProvider>
                    {tasks.map((task) => {
                        const suggestion = getSuggestionForTask(task.id);

                        return (
                            <div
                                key={task.id}
                                className="border-border flex items-center gap-3 rounded-lg border p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="text-foreground truncate text-sm font-medium">
                                            {task.title}
                                        </span>
                                        {task.estimatedHours > 0 && (
                                            <span className="text-muted-foreground flex shrink-0 items-center gap-1 text-xs">
                                                <Clock className="h-3 w-3" />
                                                {task.estimatedHours}h
                                            </span>
                                        )}
                                    </div>
                                    {suggestion && (
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Badge
                                                    variant="secondary"
                                                    className="mt-1 cursor-help text-xs"
                                                >
                                                    <Sparkles className="mr-1 h-3 w-3" />
                                                    AI: {suggestion.assigneeName}
                                                </Badge>
                                            </TooltipTrigger>
                                            <TooltipContent side="bottom" className="max-w-xs">
                                                <p className="text-xs">{suggestion.reasoning}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    )}
                                </div>
                                <div className="w-44 shrink-0">
                                    <Select
                                        value={getCurrentValue(task)}
                                        onValueChange={(value) =>
                                            handleAssigneeChange(task.id, value)
                                        }
                                        disabled={isAssigning}
                                    >
                                        <SelectTrigger className="h-8 text-xs">
                                            <SelectValue placeholder="Assign to..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {teamMembers.length > 0 && (
                                                <SelectGroup>
                                                    <SelectLabel>Team Members</SelectLabel>
                                                    {teamMembers.map((member) => (
                                                        <SelectItem
                                                            key={`user:${member.id}`}
                                                            value={`user:${member.id}`}
                                                        >
                                                            <span className="flex items-center gap-1.5">
                                                                <User className="h-3 w-3" />
                                                                {member.name}
                                                            </span>
                                                        </SelectItem>
                                                    ))}
                                                </SelectGroup>
                                            )}
                                            {availableAgents.length > 0 && (
                                                <SelectGroup>
                                                    <SelectLabel>AI Agents</SelectLabel>
                                                    {availableAgents.map((agent) => (
                                                        <SelectItem
                                                            key={`agent:${agent.id}`}
                                                            value={`agent:${agent.id}`}
                                                        >
                                                            <span className="flex items-center gap-1.5">
                                                                <Bot className="h-3 w-3" />
                                                                {agent.name}
                                                            </span>
                                                        </SelectItem>
                                                    ))}
                                                </SelectGroup>
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        );
                    })}
                </TooltipProvider>
            </CardContent>
        </Card>
    );
}
