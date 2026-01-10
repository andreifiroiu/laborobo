import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Clock, Users, CheckCircle2, AlertCircle } from 'lucide-react';
import type { SOPPlaybook } from '@/types/playbooks';

interface SOPDetailProps {
    playbook: SOPPlaybook;
}

export function SOPDetail({ playbook }: SOPDetailProps) {
    const content = playbook.content;

    return (
        <div className="space-y-6">
            {/* Trigger Conditions */}
            {content.triggerConditions && (
                <>
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <AlertCircle className="size-4 text-amber-500" />
                            <h3 className="text-sm font-semibold">Trigger Conditions</h3>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {content.triggerConditions}
                        </p>
                    </div>
                    <Separator />
                </>
            )}

            {/* Steps */}
            {content.steps && content.steps.length > 0 && (
                <>
                    <div>
                        <h3 className="mb-3 text-sm font-semibold">Steps</h3>
                        <div className="space-y-4">
                            {content.steps.map((step, index) => (
                                <div
                                    key={step.id}
                                    className="flex gap-3 rounded-lg border p-3"
                                >
                                    <div className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                        {index + 1}
                                    </div>
                                    <div className="flex-1 space-y-2">
                                        <p className="font-medium">{step.action}</p>
                                        {step.details && (
                                            <p className="text-sm text-muted-foreground">
                                                {step.details}
                                            </p>
                                        )}
                                        {step.evidence && step.evidence.length > 0 && (
                                            <div className="flex flex-wrap gap-1">
                                                {step.evidence.map((evidence, i) => (
                                                    <Badge
                                                        key={i}
                                                        variant="outline"
                                                        className="text-xs"
                                                    >
                                                        {evidence.type}
                                                    </Badge>
                                                ))}
                                            </div>
                                        )}
                                        {step.assignedRole && (
                                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                                                <Users className="size-3" />
                                                <span>{step.assignedRole}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                    <Separator />
                </>
            )}

            {/* Metadata */}
            <div className="grid gap-4 sm:grid-cols-2">
                {/* Roles Involved */}
                {content.rolesInvolved && content.rolesInvolved.length > 0 && (
                    <div className="rounded-lg border p-3">
                        <div className="mb-2 flex items-center gap-2 text-sm text-muted-foreground">
                            <Users className="size-4" />
                            <span>Roles Involved</span>
                        </div>
                        <div className="flex flex-wrap gap-1">
                            {content.rolesInvolved.map((role) => (
                                <Badge key={role} variant="secondary" className="text-xs">
                                    {role}
                                </Badge>
                            ))}
                        </div>
                    </div>
                )}

                {/* Estimated Time */}
                {content.estimatedTimeMinutes !== undefined &&
                    content.estimatedTimeMinutes > 0 && (
                        <div className="rounded-lg border p-3">
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <Clock className="size-4" />
                                <span>Estimated Time</span>
                            </div>
                            <p className="mt-1 text-lg font-semibold">
                                {content.estimatedTimeMinutes < 60
                                    ? `${content.estimatedTimeMinutes} min`
                                    : `${Math.floor(content.estimatedTimeMinutes / 60)}h ${content.estimatedTimeMinutes % 60}m`}
                            </p>
                        </div>
                    )}
            </div>

            {/* Definition of Done */}
            {content.definitionOfDone && (
                <>
                    <Separator />
                    <div>
                        <div className="mb-2 flex items-center gap-2">
                            <CheckCircle2 className="size-4 text-emerald-500" />
                            <h3 className="text-sm font-semibold">Definition of Done</h3>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {content.definitionOfDone}
                        </p>
                    </div>
                </>
            )}
        </div>
    );
}
