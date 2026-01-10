import { Button } from '@/components/ui/button';
import { FileText, ListChecks, FileStack, CheckSquare, Plus } from 'lucide-react';
import type { EmptyStateProps, PlaybookType } from '@/types/playbooks';

export function EmptyState({ tab, onCreatePlaybook }: EmptyStateProps) {
    const getEmptyStateConfig = (tab: string) => {
        switch (tab) {
            case 'sop':
                return {
                    icon: FileText,
                    title: 'No SOPs yet',
                    description:
                        'Create your first Standard Operating Procedure to document step-by-step processes.',
                    createType: 'sop' as PlaybookType,
                };
            case 'checklist':
                return {
                    icon: ListChecks,
                    title: 'No Checklists yet',
                    description:
                        'Create your first Checklist to track tasks and ensure nothing gets missed.',
                    createType: 'checklist' as PlaybookType,
                };
            case 'template':
                return {
                    icon: FileStack,
                    title: 'No Templates yet',
                    description:
                        'Create your first Template to standardize projects, work orders, or documents.',
                    createType: 'template' as PlaybookType,
                };
            case 'acceptance_criteria':
                return {
                    icon: CheckSquare,
                    title: 'No Acceptance Criteria yet',
                    description:
                        'Create your first Acceptance Criteria to define what "done" looks like.',
                    createType: 'acceptance_criteria' as PlaybookType,
                };
            default:
                return {
                    icon: FileText,
                    title: 'No Playbooks yet',
                    description:
                        'Create your first Playbook to start building your knowledge base.',
                    createType: 'sop' as PlaybookType,
                };
        }
    };

    const config = getEmptyStateConfig(tab);
    const Icon = config.icon;

    return (
        <div className="flex min-h-[400px] flex-col items-center justify-center rounded-lg border-2 border-dashed border-muted p-8 text-center">
            <div className="mx-auto flex size-12 items-center justify-center rounded-full bg-muted">
                <Icon className="size-6 text-muted-foreground" />
            </div>
            <h3 className="mt-4 text-lg font-semibold">{config.title}</h3>
            <p className="mb-4 mt-2 text-sm text-muted-foreground">
                {config.description}
            </p>
            <Button onClick={() => onCreatePlaybook(config.createType)}>
                <Plus className="mr-2 size-4" />
                Create {tab === 'all' ? 'Playbook' : config.title.replace('No ', '').replace(' yet', '')}
            </Button>
        </div>
    );
}
