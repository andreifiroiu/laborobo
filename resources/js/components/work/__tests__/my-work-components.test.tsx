import { render, screen, fireEvent } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import { RaciBadge, RaciBadgeGroup } from '../raci-badge';
import { MyWorkSubtabs } from '../my-work-subtabs';
import { MyWorkFilters } from '../my-work-filters';
import { MyWorkTreeView } from '../my-work-tree-view';
import type { RaciRole } from '@/types/work';

// Mock Inertia router
vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href, ...props }: { children: React.ReactNode; href: string }) => (
        <a href={href} {...props}>{children}</a>
    ),
    router: {
        post: vi.fn(),
    },
}));

describe('RaciBadge', () => {
    it('renders correct color and label for Accountable role', () => {
        render(<RaciBadge role="accountable" showTooltip={false} />);

        const badge = screen.getByText('A');
        expect(badge).toBeInTheDocument();
        expect(badge).toHaveClass('bg-violet-100');
    });

    it('renders correct color and label for Responsible role', () => {
        render(<RaciBadge role="responsible" showTooltip={false} />);

        const badge = screen.getByText('R');
        expect(badge).toBeInTheDocument();
        expect(badge).toHaveClass('bg-indigo-100');
    });

    it('renders correct color and label for Consulted role', () => {
        render(<RaciBadge role="consulted" showTooltip={false} />);

        const badge = screen.getByText('C');
        expect(badge).toBeInTheDocument();
        expect(badge).toHaveClass('bg-amber-100');
    });

    it('renders correct color and label for Informed role', () => {
        render(<RaciBadge role="informed" showTooltip={false} />);

        const badge = screen.getByText('I');
        expect(badge).toBeInTheDocument();
        expect(badge).toHaveClass('bg-slate-100');
    });

    it('renders multiple badges in prominence order via RaciBadgeGroup', () => {
        const roles: RaciRole[] = ['informed', 'accountable', 'consulted'];
        render(<RaciBadgeGroup roles={roles} showTooltip={false} />);

        const badges = screen.getAllByTestId('raci-badge');
        expect(badges).toHaveLength(3);
        // Should be ordered: Accountable, Consulted, Informed (by prominence)
        expect(badges[0]).toHaveTextContent('A');
        expect(badges[1]).toHaveTextContent('C');
        expect(badges[2]).toHaveTextContent('I');
    });
});

describe('MyWorkSubtabs', () => {
    it('renders all four subtabs', () => {
        render(
            <MyWorkSubtabs
                activeTab="tasks"
                onTabChange={vi.fn()}
            />
        );

        expect(screen.getByRole('tab', { name: /tasks/i })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: /work orders/i })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: /projects/i })).toBeInTheDocument();
        expect(screen.getByRole('tab', { name: /all/i })).toBeInTheDocument();
    });

    it('handles tab switching and calls onTabChange', () => {
        const onTabChange = vi.fn();
        render(
            <MyWorkSubtabs
                activeTab="tasks"
                onTabChange={onTabChange}
            />
        );

        const workOrdersTab = screen.getByRole('tab', { name: /work orders/i });
        fireEvent.click(workOrdersTab);

        expect(onTabChange).toHaveBeenCalledWith('work_orders');
    });

    it('highlights the active tab', () => {
        render(
            <MyWorkSubtabs
                activeTab="projects"
                onTabChange={vi.fn()}
            />
        );

        const projectsTab = screen.getByRole('tab', { name: /projects/i });
        expect(projectsTab).toHaveAttribute('data-state', 'active');
    });
});

describe('MyWorkFilters', () => {
    it('renders sort controls for all subtabs', () => {
        render(
            <MyWorkFilters
                filters={{
                    raciRoles: [],
                    statuses: [],
                    dueDateRange: null,
                    sortBy: 'due_date',
                    sortDirection: 'asc',
                }}
                showInformed={false}
                activeSubtab="tasks"
                onFiltersChange={vi.fn()}
                onShowInformedChange={vi.fn()}
            />
        );

        // Find the sort by aria-label on the select
        expect(screen.getByRole('combobox', { name: /sort by/i })).toBeInTheDocument();
    });

    it('renders show informed toggle for work_orders subtab', () => {
        render(
            <MyWorkFilters
                filters={{
                    raciRoles: [],
                    statuses: [],
                    dueDateRange: null,
                    sortBy: 'due_date',
                    sortDirection: 'asc',
                }}
                showInformed={false}
                activeSubtab="work_orders"
                onFiltersChange={vi.fn()}
                onShowInformedChange={vi.fn()}
            />
        );

        // Check for the switch element (the show informed toggle)
        // Use specific ID for the toggle
        const toggle = screen.getByRole('switch');
        expect(toggle).toBeInTheDocument();
        expect(toggle).toHaveAttribute('id', 'show-informed-toggle');
    });

    it('calls onShowInformedChange when toggle is clicked', () => {
        const onShowInformedChange = vi.fn();
        render(
            <MyWorkFilters
                filters={{
                    raciRoles: [],
                    statuses: [],
                    dueDateRange: null,
                    sortBy: 'due_date',
                    sortDirection: 'asc',
                }}
                showInformed={false}
                activeSubtab="work_orders"
                onFiltersChange={vi.fn()}
                onShowInformedChange={onShowInformedChange}
            />
        );

        const toggle = screen.getByRole('switch');
        fireEvent.click(toggle);

        expect(onShowInformedChange).toHaveBeenCalledWith(true);
    });

    it('clears all filters when clear button is clicked', () => {
        const onFiltersChange = vi.fn();
        render(
            <MyWorkFilters
                filters={{
                    raciRoles: ['accountable'],
                    statuses: ['active'],
                    dueDateRange: 'this_week',
                    sortBy: 'due_date',
                    sortDirection: 'asc',
                }}
                showInformed={false}
                activeSubtab="work_orders"
                onFiltersChange={onFiltersChange}
                onShowInformedChange={vi.fn()}
            />
        );

        // The button contains "Clear all" on desktop and just "Clear" on mobile
        // Use getAllByRole and find the clear button
        const clearButton = screen.getByRole('button', { name: /clear/i });
        fireEvent.click(clearButton);

        expect(onFiltersChange).toHaveBeenCalledWith(expect.objectContaining({
            raciRoles: [],
            statuses: [],
            dueDateRange: null,
        }));
    });
});

describe('MyWorkTreeView', () => {
    const mockData = {
        projects: [
            {
                id: 'project-1',
                name: 'Test Project',
                status: 'active' as const,
                partyName: 'Test Client',
                progress: 50,
                userRaciRoles: ['accountable' as RaciRole],
                workOrders: [
                    {
                        id: 'wo-1',
                        title: 'Test Work Order',
                        status: 'active' as const,
                        priority: 'medium' as const,
                        dueDate: '2024-03-01',
                        userRaciRoles: ['responsible' as RaciRole],
                        tasks: [
                            {
                                id: 'task-1',
                                title: 'Test Task',
                                status: 'todo' as const,
                                dueDate: '2024-02-28',
                                assignedToName: 'John Doe',
                            },
                        ],
                    },
                ],
            },
        ],
    };

    it('renders hierarchical project structure', () => {
        render(<MyWorkTreeView data={mockData} />);

        expect(screen.getByText('Test Project')).toBeInTheDocument();
        expect(screen.getByText('Test Work Order')).toBeInTheDocument();
    });

    it('displays RACI badges at project and work order levels', () => {
        render(<MyWorkTreeView data={mockData} />);

        // Should show RACI badges (Accountable on project, Responsible on work order)
        const raciBadges = screen.getAllByTestId('raci-badge');
        expect(raciBadges.length).toBeGreaterThanOrEqual(1);
    });

    it('supports expand/collapse functionality on project node', () => {
        render(<MyWorkTreeView data={mockData} />);

        // Project should be expanded by default - work order should be visible
        expect(screen.getByText('Test Work Order')).toBeInTheDocument();

        // Click collapse button on project
        const collapseButtons = screen.getAllByRole('button', { name: /collapse/i });
        fireEvent.click(collapseButtons[0]);

        // Work order should no longer be in the document (removed from DOM when collapsed)
        expect(screen.queryByText('Test Work Order')).not.toBeInTheDocument();
    });
});
