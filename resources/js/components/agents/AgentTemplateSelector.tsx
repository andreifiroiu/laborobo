import { useState } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Bot,
    Briefcase,
    FileCheck,
    DollarSign,
    MessageSquare,
    Code,
    Sparkles,
    ChevronRight,
    Search,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface AgentTemplate {
    id: number;
    code: string;
    name: string;
    type: string;
    description: string;
    defaultTools: string[];
    defaultPermissions: string[];
    isActive: boolean;
    defaultAiProvider?: string | null;
    defaultAiModel?: string | null;
}

interface AgentTemplateSelectorProps {
    templates: AgentTemplate[];
    isOpen: boolean;
    onClose: () => void;
    onSelectTemplate: (template: AgentTemplate | null, customName?: string, customDescription?: string) => void;
    usedTemplateIds?: number[];
}

// Map agent types to icons
const typeIcons: Record<string, React.ElementType> = {
    'project-management': Briefcase,
    'work-routing': Bot,
    'content-creation': MessageSquare,
    'quality-assurance': FileCheck,
    'data-analysis': Code,
    'finance': DollarSign,
    'dispatcher': Bot,
    'pm-copilot': Briefcase,
    'qa-compliance': FileCheck,
    'client-comms': MessageSquare,
    'domain-skills': Code,
};

// Map permission keys to human-readable labels
const permissionLabels: Record<string, string> = {
    can_create_work_orders: 'Work Orders',
    can_modify_tasks: 'Tasks',
    can_access_client_data: 'Client Data',
    can_send_emails: 'Email',
    can_modify_deliverables: 'Deliverables',
    can_access_financial_data: 'Financial',
    can_modify_playbooks: 'Playbooks',
};

export function AgentTemplateSelector({
    templates,
    isOpen,
    onClose,
    onSelectTemplate,
    usedTemplateIds = [],
}: AgentTemplateSelectorProps) {
    const [selectedTemplate, setSelectedTemplate] = useState<AgentTemplate | null>(null);
    const [isCustomMode, setIsCustomMode] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [customName, setCustomName] = useState('');
    const [customDescription, setCustomDescription] = useState('');

    const filteredTemplates = templates.filter((template) =>
        template.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        template.description.toLowerCase().includes(searchQuery.toLowerCase())
    );

    const handleSelect = () => {
        if (isCustomMode) {
            onSelectTemplate(null, customName, customDescription);
        } else if (selectedTemplate) {
            onSelectTemplate(selectedTemplate);
        }
        handleClose();
    };

    const handleClose = () => {
        setSelectedTemplate(null);
        setIsCustomMode(false);
        setSearchQuery('');
        setCustomName('');
        setCustomDescription('');
        onClose();
    };

    const getIcon = (template: AgentTemplate) => {
        const Icon = typeIcons[template.code] || typeIcons[template.type] || Bot;
        return Icon;
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="max-w-3xl max-h-[85vh] overflow-hidden flex flex-col">
                <DialogHeader>
                    <DialogTitle>Create New Agent</DialogTitle>
                    <DialogDescription>
                        Choose a template to get started quickly, or create a fully custom agent.
                    </DialogDescription>
                </DialogHeader>

                {/* Search */}
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                    <Input
                        placeholder="Search templates..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-9"
                    />
                </div>

                <div className="flex-1 overflow-y-auto py-4 space-y-4">
                    {/* Custom Agent Option */}
                    <Card
                        className={cn(
                            'cursor-pointer transition-all',
                            isCustomMode
                                ? 'ring-2 ring-primary'
                                : 'hover:bg-muted/50'
                        )}
                        onClick={() => {
                            setIsCustomMode(true);
                            setSelectedTemplate(null);
                        }}
                    >
                        <CardHeader className="pb-2">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-primary/10">
                                    <Sparkles className="w-5 h-5 text-primary" />
                                </div>
                                <div>
                                    <CardTitle className="text-base">Custom Agent</CardTitle>
                                    <CardDescription>
                                        Start from scratch with full control over capabilities
                                    </CardDescription>
                                </div>
                                <ChevronRight className="w-5 h-5 text-muted-foreground ml-auto" />
                            </div>
                        </CardHeader>
                    </Card>

                    {/* Custom Agent Form */}
                    {isCustomMode && (
                        <div className="space-y-4 p-4 bg-muted/30 rounded-lg">
                            <div className="space-y-2">
                                <Label htmlFor="customName">Agent Name</Label>
                                <Input
                                    id="customName"
                                    placeholder="Enter agent name..."
                                    value={customName}
                                    onChange={(e) => setCustomName(e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="customDescription">Description</Label>
                                <Textarea
                                    id="customDescription"
                                    placeholder="Describe what this agent does..."
                                    value={customDescription}
                                    onChange={(e) => setCustomDescription(e.target.value)}
                                    rows={3}
                                />
                            </div>
                        </div>
                    )}

                    {/* Template Cards */}
                    <div className="space-y-2">
                        <h4 className="text-sm font-medium text-muted-foreground">
                            Templates ({filteredTemplates.length})
                        </h4>
                        {filteredTemplates.map((template) => {
                            const Icon = getIcon(template);
                            const isSelected = selectedTemplate?.id === template.id;
                            const isUsed = usedTemplateIds.includes(template.id);

                            return (
                                <Card
                                    key={template.id}
                                    className={cn(
                                        'transition-all',
                                        isUsed
                                            ? 'opacity-50 cursor-not-allowed'
                                            : 'cursor-pointer',
                                        isSelected && !isUsed
                                            ? 'ring-2 ring-primary'
                                            : !isUsed && 'hover:bg-muted/50'
                                    )}
                                    onClick={() => {
                                        if (isUsed) return;
                                        setSelectedTemplate(template);
                                        setIsCustomMode(false);
                                    }}
                                >
                                    <CardHeader className="pb-2">
                                        <div className="flex items-start gap-3">
                                            <div className="p-2 rounded-lg bg-muted">
                                                <Icon className="w-5 h-5 text-muted-foreground" />
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <CardTitle className="text-base">
                                                        {template.name}
                                                    </CardTitle>
                                                    {template.defaultAiProvider && (
                                                        <Badge variant="outline" className="text-xs">
                                                            {template.defaultAiProvider}
                                                            {template.defaultAiModel ? ` / ${template.defaultAiModel}` : ''}
                                                        </Badge>
                                                    )}
                                                </div>
                                                <CardDescription className="mt-1">
                                                    {template.description}
                                                </CardDescription>
                                            </div>
                                            {isUsed ? (
                                                <Badge variant="secondary" className="flex-shrink-0 text-xs">
                                                    Already added
                                                </Badge>
                                            ) : (
                                                <ChevronRight className="w-5 h-5 text-muted-foreground flex-shrink-0" />
                                            )}
                                        </div>
                                    </CardHeader>
                                    {isSelected && (
                                        <CardContent className="pt-0">
                                            <div className="space-y-3">
                                                {/* Default Permissions */}
                                                {template.defaultPermissions.length > 0 && (
                                                    <div>
                                                        <p className="text-xs text-muted-foreground mb-1.5">
                                                            Default Permissions
                                                        </p>
                                                        <div className="flex flex-wrap gap-1">
                                                            {template.defaultPermissions.map((perm) => (
                                                                <Badge
                                                                    key={perm}
                                                                    variant="outline"
                                                                    className="text-xs"
                                                                >
                                                                    {permissionLabels[perm] || perm}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                                {/* Default Tools */}
                                                {template.defaultTools.length > 0 && (
                                                    <div>
                                                        <p className="text-xs text-muted-foreground mb-1.5">
                                                            Default Tools
                                                        </p>
                                                        <div className="flex flex-wrap gap-1">
                                                            {template.defaultTools.map((tool) => (
                                                                <Badge
                                                                    key={tool}
                                                                    variant="secondary"
                                                                    className="text-xs font-mono"
                                                                >
                                                                    {tool}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </CardContent>
                                    )}
                                </Card>
                            );
                        })}
                    </div>

                    {filteredTemplates.length === 0 && !isCustomMode && (
                        <div className="text-center py-8">
                            <p className="text-sm text-muted-foreground">
                                No templates match your search.
                            </p>
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={handleClose}>
                        Cancel
                    </Button>
                    <Button
                        onClick={handleSelect}
                        disabled={!selectedTemplate && !isCustomMode}
                    >
                        {isCustomMode ? 'Create Custom Agent' : 'Use Template'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
