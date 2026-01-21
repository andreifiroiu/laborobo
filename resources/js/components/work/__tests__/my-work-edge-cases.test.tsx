import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi, beforeEach } from 'vitest';
import { MyWorkView } from '../my-work-view';
import { MyWorkProjectsList } from '../my-work-projects-list';
import type {
    WorkOrder,
    Task,
    Project,
    RaciRole,
    MyWorkMetrics,
    MyWorkData,
} from '@/types/work';

// Mock Inertia router
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>{children}</a>
    ),
    router: {
        post: vi.fn(),
    },
}));

describe('My Work Edge Cases', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('Empty state handling', () => {
        const emptyMyWorkData: MyWorkData = {
            projects: [],
            workOrders: [],
            tasks: [],
        };

        const emptyMetrics: MyWorkMetrics = {
            accountableCount: 0,
            responsibleCount: 0,
            awaitingReviewCount: 0,
            assignedTasksCount: 0,
        };

        it('displays empty state message when user has no work items', () => {
            render(
                <MyWorkView
                    workOrders={[]}
                    tasks={[]}
                    currentUserId="user-1"
                    myWorkData={emptyMyWorkData}
                    myWorkMetrics={emptyMetrics}
                    myWorkSubtab="tasks"
                    myWorkShowInformed={false}
                />
            );

            expect(screen.getByText('All caught up!')).toBeInTheDocument();
            expect(
                screen.getByText(/You don't have any projects, work orders, or tasks assigned/)
            ).toBeInTheDocument();
        });

        it('shows zero counts in all metrics when empty', () => {
            render(
                <MyWorkView
                    workOrders={[]}
                    tasks={[]}
                    currentUserId="user-1"
                    myWorkData={emptyMyWorkData}
                    myWorkMetrics={emptyMetrics}
                    myWorkSubtab="tasks"
                    myWorkShowInformed={false}
                />
            );

            // Find all metric values that show 0
            const zeroMetrics = screen.getAllByText('0');
            expect(zeroMetrics.length).toBeGreaterThanOrEqual(4);
        });
    });

    describe('Projects list with multiple RACI badges', () => {
        const projectsWithMultipleRoles: Array<Project & { userRaciRoles: RaciRole[] }> = [
            {
                id: 'project-1',
                name: 'Multi-Role Project',
                description: 'Project with multiple RACI roles',
                partyId: 'party-1',
                partyName: 'Test Client',
                ownerId: 'user-2',
                ownerName: 'Owner User',
                status: 'active',
                startDate: '2024-01-01',
                targetEndDate: '2024-12-31',
                budgetHours: 100,
                actualHours: 25,
                progress: 25,
                tags: [],
                workOrderLists: [],
                ungroupedWorkOrders: [],
                userRaciRoles: ['accountable', 'responsible', 'consulted'],
            },
        ];

        it('renders project with multiple RACI badges in correct order', () => {
            render(
                <MyWorkProjectsList
                    projects={projectsWithMultipleRoles}
                    filters={{
                        raciRoles: [],
                        statuses: [],
                        dueDateRange: null,
                        sortBy: 'due_date',
                        sortDirection: 'asc',
                    }}
                    showInformed={false}
                />
            );

            expect(screen.getByText('Multi-Role Project')).toBeInTheDocument();

            // Check RACI badges are present and in prominence order
            const raciBadges = screen.getAllByTestId('raci-badge');
            expect(raciBadges).toHaveLength(3);
            // Order should be: Accountable, Responsible, Consulted
            expect(raciBadges[0]).toHaveTextContent('A');
            expect(raciBadges[1]).toHaveTextContent('R');
            expect(raciBadges[2]).toHaveTextContent('C');
        });
    });

    describe('Filtering produces empty results', () => {
        const mockWorkOrders: Array<WorkOrder & { userRaciRoles: RaciRole[] }> = [
            {
                id: 'wo-1',
                title: 'Active Work Order',
                description: 'Test work order',
                projectId: 'project-1',
                projectName: 'Test Project',
                assignedToId: 'user-1',
                assignedToName: 'Test User',
                status: 'active',
                priority: 'medium',
                dueDate: '2024-03-15',
                estimatedHours: 20,
                actualHours: 5,
                acceptanceCriteria: [],
                sopAttached: false,
                sopName: null,
                partyContactId: null,
                createdBy: 'user-2',
                createdByName: 'Other User',
                userRaciRoles: ['responsible'],
            },
        ];

        const mockMyWorkData: MyWorkData = {
            projects: [],
            workOrders: mockWorkOrders,
            tasks: [],
        };

        const mockMetrics: MyWorkMetrics = {
            accountableCount: 0,
            responsibleCount: 1,
            awaitingReviewCount: 0,
            assignedTasksCount: 0,
        };

        it('shows no results message when filter produces empty set', () => {
            render(
                <MyWorkView
                    workOrders={mockWorkOrders}
                    tasks={[]}
                    currentUserId="user-1"
                    myWorkData={mockMyWorkData}
                    myWorkMetrics={mockMetrics}
                    myWorkSubtab="work_orders"
                    myWorkShowInformed={false}
                />
            );

            // Filter by Accountable role (user only has Responsible)
            const accountableBtn = screen.getByRole('button', { name: /filter by accountable/i });
            fireEvent.click(accountableBtn);

            // Should show no matching work orders message
            expect(screen.getByText(/no work orders match/i)).toBeInTheDocument();
        });
    });

    describe('Tree view with mixed RACI combinations', () => {
        const projectsWithMixedRoles: Array<Project & { userRaciRoles: RaciRole[] }> = [
            {
                id: 'project-1',
                name: 'Accountable Project',
                description: 'Test project',
                partyId: 'party-1',
                partyName: 'Test Client',
                ownerId: 'user-2',
                ownerName: 'Owner User',
                status: 'active',
                startDate: '2024-01-01',
                targetEndDate: '2024-12-31',
                budgetHours: 100,
                actualHours: 25,
                progress: 25,
                tags: [],
                workOrderLists: [],
                ungroupedWorkOrders: [],
                userRaciRoles: ['accountable'],
            },
        ];

        const workOrdersWithMixedRoles: Array<WorkOrder & { userRaciRoles: RaciRole[] }> = [
            {
                id: 'wo-1',
                title: 'Responsible Work Order',
                description: 'Test work order',
                projectId: 'project-1',
                projectName: 'Accountable Project',
                assignedToId: 'user-1',
                assignedToName: 'Test User',
                status: 'active',
                priority: 'medium',
                dueDate: '2024-03-15',
                estimatedHours: 20,
                actualHours: 5,
                acceptanceCriteria: [],
                sopAttached: false,
                sopName: null,
                partyContactId: null,
                createdBy: 'user-2',
                createdByName: 'Other User',
                userRaciRoles: ['responsible'],
            },
        ];

        const mockTasks: Task[] = [
            {
                id: 'task-1',
                title: 'Assigned Task',
                description: 'Test task',
                workOrderId: 'wo-1',
                workOrderTitle: 'Responsible Work Order',
                projectId: 'project-1',
                assignedToId: 'user-1',
                assignedToName: 'Test User',
                assignedAgentId: null,
                assignedAgentName: null,
                status: 'todo',
                dueDate: '2024-03-10',
                estimatedHours: 5,
                actualHours: 0,
                checklistItems: [],
                dependencies: [],
                isBlocked: false,
            },
        ];

        const mockMyWorkData: MyWorkData = {
            projects: projectsWithMixedRoles,
            workOrders: workOrdersWithMixedRoles,
            tasks: mockTasks,
        };

        const mockMetrics: MyWorkMetrics = {
            accountableCount: 1,
            responsibleCount: 1,
            awaitingReviewCount: 0,
            assignedTasksCount: 1,
        };

        it('tree view shows correct RACI badges at project and work order levels', () => {
            render(
                <MyWorkView
                    workOrders={workOrdersWithMixedRoles}
                    tasks={mockTasks}
                    currentUserId="user-1"
                    myWorkData={mockMyWorkData}
                    myWorkMetrics={mockMetrics}
                    myWorkSubtab="all"
                    myWorkShowInformed={false}
                />
            );

            // Verify tree structure is rendered - projects expanded by default, work orders collapsed
            expect(screen.getByText('Accountable Project')).toBeInTheDocument();
            expect(screen.getByText('Responsible Work Order')).toBeInTheDocument();

            // Tasks are inside collapsed work orders, so not visible by default
            // We verify the work order shows task count instead
            expect(screen.getByText('1 tasks')).toBeInTheDocument();

            // Check RACI badges are present (A for project, R for work order)
            const raciBadges = screen.getAllByTestId('raci-badge');
            expect(raciBadges.length).toBeGreaterThanOrEqual(2);

            // First badge should be Accountable on project
            expect(raciBadges[0]).toHaveTextContent('A');
            // Second badge should be Responsible on work order
            expect(raciBadges[1]).toHaveTextContent('R');
        });

        it('tree view expands work order to show tasks when clicked', () => {
            render(
                <MyWorkView
                    workOrders={workOrdersWithMixedRoles}
                    tasks={mockTasks}
                    currentUserId="user-1"
                    myWorkData={mockMyWorkData}
                    myWorkMetrics={mockMetrics}
                    myWorkSubtab="all"
                    myWorkShowInformed={false}
                />
            );

            // Task should not be visible initially (work order is collapsed)
            expect(screen.queryByText('Assigned Task')).not.toBeInTheDocument();

            // Find the expand button for the work order (second Expand button - first is for project)
            const expandButtons = screen.getAllByRole('button', { name: /expand/i });
            // The work order expand button (project is already expanded, so its button says "Collapse")
            const workOrderExpandBtn = expandButtons[0];
            fireEvent.click(workOrderExpandBtn);

            // Now the task should be visible
            expect(screen.getByText('Assigned Task')).toBeInTheDocument();
        });
    });
});
