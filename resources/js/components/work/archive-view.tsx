import { useState } from 'react';
import { Search, Filter, Archive, TrendingUp, TrendingDown, ArrowUpDown } from 'lucide-react';
import { router } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ArchiveProjectCard } from './archive-project-card';
import type { Project, WorkOrder, Task } from '@/types/work';

interface ArchiveViewProps {
    projects: Project[];
    workOrders: WorkOrder[];
    tasks: Task[];
}

export function ArchiveView({ projects, workOrders, tasks }: ArchiveViewProps) {
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<'all' | 'completed' | 'archived'>('all');
    const [sortBy, setSortBy] = useState<'date' | 'name' | 'hours'>('date');

    // Filter archived/completed projects
    const archivedProjects = projects.filter(
        (p) => p.status === 'archived' || p.status === 'completed'
    );

    // Apply filters
    const filteredProjects = archivedProjects
        .filter((p) => {
            const matchesSearch =
                p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                (p.description?.toLowerCase().includes(searchQuery.toLowerCase()) ?? false) ||
                p.partyName.toLowerCase().includes(searchQuery.toLowerCase());

            const matchesStatus =
                statusFilter === 'all' ||
                (statusFilter === 'completed' && p.status === 'completed') ||
                (statusFilter === 'archived' && p.status === 'archived');

            return matchesSearch && matchesStatus;
        })
        .sort((a, b) => {
            if (sortBy === 'date') {
                const dateA = a.targetEndDate ? new Date(a.targetEndDate).getTime() : 0;
                const dateB = b.targetEndDate ? new Date(b.targetEndDate).getTime() : 0;
                return dateB - dateA; // Most recent first
            }
            if (sortBy === 'name') {
                return a.name.localeCompare(b.name);
            }
            if (sortBy === 'hours') {
                return b.actualHours - a.actualHours;
            }
            return 0;
        });

    // Get work orders and tasks for each project
    const getProjectStats = (projectId: string) => {
        const projectWorkOrders = workOrders.filter((wo) => wo.projectId === projectId);
        const projectTasks = tasks.filter((t) => t.projectId === projectId);
        return {
            workOrderCount: projectWorkOrders.length,
            taskCount: projectTasks.length,
        };
    };

    // Calculate summary stats
    const totalProjects = archivedProjects.length;
    const completedCount = archivedProjects.filter((p) => p.status === 'completed').length;
    const archivedCount = archivedProjects.filter((p) => p.status === 'archived').length;
    const totalHours = archivedProjects.reduce((sum, p) => sum + p.actualHours, 0);

    // Calculate budget performance
    const projectsWithBudget = archivedProjects.filter((p) => p.budgetHours);
    const onBudgetCount = projectsWithBudget.filter(
        (p) => p.budgetHours && p.actualHours <= p.budgetHours
    ).length;
    const overBudgetCount = projectsWithBudget.filter(
        (p) => p.budgetHours && p.actualHours > p.budgetHours
    ).length;

    const handleRestore = (projectId: string) => {
        router.post(`/work/projects/${projectId}/restore`);
    };

    return (
        <div className="space-y-6">
            {/* Header Stats */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-card border border-border rounded-xl p-4">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-muted rounded-lg">
                            <Archive className="w-5 h-5 text-muted-foreground" />
                        </div>
                        <div>
                            <div className="text-2xl font-bold text-foreground">{totalProjects}</div>
                            <div className="text-xs text-muted-foreground">Total Projects</div>
                        </div>
                    </div>
                    <div className="flex items-center gap-3 text-xs">
                        <span className="text-emerald-600 dark:text-emerald-400">
                            {completedCount} completed
                        </span>
                        <span className="text-muted-foreground">•</span>
                        <span className="text-muted-foreground">{archivedCount} archived</span>
                    </div>
                </div>

                <div className="bg-card border border-border rounded-xl p-4">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-indigo-100 dark:bg-indigo-950/30 rounded-lg">
                            <TrendingUp className="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <div className="text-2xl font-bold text-foreground">{totalHours}h</div>
                            <div className="text-xs text-muted-foreground">Total Hours</div>
                        </div>
                    </div>
                    <div className="text-xs text-muted-foreground">Across all archived projects</div>
                </div>

                <div className="bg-card border border-border rounded-xl p-4">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-emerald-100 dark:bg-emerald-950/30 rounded-lg">
                            <TrendingUp className="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <div className="text-2xl font-bold text-foreground">{onBudgetCount}</div>
                            <div className="text-xs text-muted-foreground">On Budget</div>
                        </div>
                    </div>
                    <div className="text-xs text-emerald-600 dark:text-emerald-400">
                        At or under estimated hours
                    </div>
                </div>

                <div className="bg-card border border-border rounded-xl p-4">
                    <div className="flex items-center gap-3 mb-2">
                        <div className="p-2 bg-amber-100 dark:bg-amber-950/30 rounded-lg">
                            <TrendingDown className="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <div className="text-2xl font-bold text-foreground">{overBudgetCount}</div>
                            <div className="text-xs text-muted-foreground">Over Budget</div>
                        </div>
                    </div>
                    <div className="text-xs text-amber-600 dark:text-amber-400">
                        Exceeded estimated hours
                    </div>
                </div>
            </div>

            {/* Filters and Search */}
            <div className="bg-card border border-border rounded-xl p-4">
                <div className="flex flex-col sm:flex-row gap-4">
                    {/* Search */}
                    <div className="flex-1 relative">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
                        <Input
                            type="text"
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search by project name, description, or client..."
                            className="pl-10"
                        />
                    </div>

                    {/* Status Filter */}
                    <div className="flex items-center gap-2">
                        <Filter className="w-4 h-4 text-muted-foreground shrink-0" />
                        <Select
                            value={statusFilter}
                            onValueChange={(value) =>
                                setStatusFilter(value as typeof statusFilter)
                            }
                        >
                            <SelectTrigger className="w-[140px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Status</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                                <SelectItem value="archived">Archived</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    {/* Sort */}
                    <div className="flex items-center gap-2">
                        <ArrowUpDown className="w-4 h-4 text-muted-foreground shrink-0" />
                        <Select
                            value={sortBy}
                            onValueChange={(value) => setSortBy(value as typeof sortBy)}
                        >
                            <SelectTrigger className="w-[140px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="date">Sort by Date</SelectItem>
                                <SelectItem value="name">Sort by Name</SelectItem>
                                <SelectItem value="hours">Sort by Hours</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </div>

            {/* Project Cards */}
            {filteredProjects.length === 0 ? (
                <div className="bg-card border border-border rounded-xl p-12 text-center">
                    <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
                        <Archive className="w-8 h-8 text-muted-foreground" />
                    </div>
                    <h3 className="text-lg font-semibold text-foreground mb-2">
                        {searchQuery ? 'No projects found' : 'No archived projects'}
                    </h3>
                    <p className="text-sm text-muted-foreground">
                        {searchQuery
                            ? 'Try adjusting your search or filters'
                            : 'Completed and archived projects will appear here'}
                    </p>
                </div>
            ) : (
                <>
                    <div className="flex items-center justify-between mb-2">
                        <p className="text-sm text-muted-foreground">
                            Showing {filteredProjects.length} of {totalProjects} projects
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {filteredProjects.map((project) => {
                            const stats = getProjectStats(project.id);
                            return (
                                <ArchiveProjectCard
                                    key={project.id}
                                    project={project}
                                    workOrderCount={stats.workOrderCount}
                                    taskCount={stats.taskCount}
                                    onRestore={() => handleRestore(project.id)}
                                />
                            );
                        })}
                    </div>
                </>
            )}
        </div>
    );
}
