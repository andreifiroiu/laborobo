<?php

namespace Database\Seeders;

use App\Enums\DeliverableStatus;
use App\Enums\DeliverableType;
use App\Enums\PartyType;
use App\Enums\Priority;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\WorkOrderStatus;
use App\Models\CommunicationThread;
use App\Models\Deliverable;
use App\Models\Document;
use App\Models\Message;
use App\Models\Party;
use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

class WorkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing test user and their team (created by TeamSeeder)
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
            return; // TeamSeeder should run first
        }

        // Get the user's current team (should exist from TeamSeeder)
        $team = $user->currentTeam ?? $user->allTeams()->first();

        if (!$team) {
            return; // Team should exist from TeamSeeder
        }

        // Create parties
        $parties = $this->createParties($team);

        // Create projects
        $projects = $this->createProjects($team, $user, $parties);

        // Create work orders
        $workOrders = $this->createWorkOrders($team, $user, $projects, $parties);

        // Create tasks
        $tasks = $this->createTasks($team, $user, $workOrders, $projects);

        // Create deliverables
        $this->createDeliverables($team, $workOrders, $projects);

        // Create communication threads and messages
        $this->createCommunications($team, $user, $projects, $workOrders);

        // Create documents
        $this->createDocuments($team, $user, $projects, $workOrders, $tasks);
    }

    private function createParties(Team $team): array
    {
        $partiesData = [
            ['name' => 'Acme Corp', 'type' => PartyType::Client, 'contact_name' => 'Jennifer Martinez', 'contact_email' => 'jennifer@acmecorp.com'],
            ['name' => 'TechStart Inc', 'type' => PartyType::Client, 'contact_name' => 'Michael Chen', 'contact_email' => 'michael@techstart.io'],
            ['name' => 'BuildRight Construction', 'type' => PartyType::Client, 'contact_name' => 'Sarah Williams', 'contact_email' => 'sarah@buildright.com'],
            ['name' => 'Internal - Marketing', 'type' => PartyType::Department, 'contact_name' => null, 'contact_email' => null],
        ];

        $parties = [];
        foreach ($partiesData as $data) {
            $parties[] = Party::create([
                'team_id' => $team->id,
                'name' => $data['name'],
                'type' => $data['type'],
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
            ]);
        }

        return $parties;
    }

    private function createProjects(Team $team, User $user, array $parties): array
    {
        $projectsData = [
            [
                'name' => 'Acme Corp Rebrand',
                'description' => 'Complete brand refresh including logo, guidelines, and website redesign',
                'party_index' => 0,
                'status' => ProjectStatus::Active,
                'start_date' => '2024-01-08',
                'target_end_date' => '2024-03-15',
                'budget_hours' => 320,
                'actual_hours' => 156.5,
                'progress' => 65,
                'tags' => ['design', 'branding', 'web'],
            ],
            [
                'name' => 'TechStart Marketing Campaign',
                'description' => 'Q1 2024 marketing campaign across digital channels',
                'party_index' => 1,
                'status' => ProjectStatus::Active,
                'start_date' => '2024-01-02',
                'target_end_date' => '2024-03-31',
                'budget_hours' => 180,
                'actual_hours' => 62.0,
                'progress' => 35,
                'tags' => ['marketing', 'content', 'social'],
            ],
            [
                'name' => 'BuildRight Monthly Reporting',
                'description' => 'Ongoing monthly status reports and project documentation',
                'party_index' => 2,
                'status' => ProjectStatus::Active,
                'start_date' => '2023-11-01',
                'target_end_date' => null,
                'budget_hours' => null,
                'actual_hours' => 48.0,
                'progress' => 90,
                'tags' => ['reporting', 'documentation'],
            ],
            [
                'name' => 'Internal Website Redesign',
                'description' => 'Refresh company website with new content and improved UX',
                'party_index' => 3,
                'status' => ProjectStatus::OnHold,
                'start_date' => '2024-01-10',
                'target_end_date' => '2024-04-30',
                'budget_hours' => 240,
                'actual_hours' => 18.5,
                'progress' => 8,
                'tags' => ['web', 'internal', 'marketing'],
            ],
        ];

        $projects = [];
        foreach ($projectsData as $data) {
            $projects[] = Project::create([
                'team_id' => $team->id,
                'party_id' => $parties[$data['party_index']]->id,
                'owner_id' => $user->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'status' => $data['status'],
                'start_date' => $data['start_date'],
                'target_end_date' => $data['target_end_date'],
                'budget_hours' => $data['budget_hours'],
                'actual_hours' => $data['actual_hours'],
                'progress' => $data['progress'],
                'tags' => $data['tags'],
            ]);
        }

        return $projects;
    }

    private function createWorkOrders(Team $team, User $user, array $projects, array $parties): array
    {
        $workOrdersData = [
            [
                'title' => 'Website Redesign Phase 2',
                'description' => 'Design and implement new website based on approved brand guidelines',
                'project_index' => 0,
                'status' => WorkOrderStatus::InReview,
                'priority' => Priority::High,
                'due_date' => '2024-01-18',
                'estimated_hours' => 80,
                'actual_hours' => 65.5,
                'sop_attached' => true,
                'sop_name' => 'Website Design SOP',
            ],
            [
                'title' => 'Brand Guidelines Update',
                'description' => 'Finalize and document brand guidelines including logo usage, colors, typography',
                'project_index' => 0,
                'status' => WorkOrderStatus::Approved,
                'priority' => Priority::Medium,
                'due_date' => '2024-01-14',
                'estimated_hours' => 24,
                'actual_hours' => 28.0,
                'sop_attached' => false,
                'sop_name' => null,
            ],
            [
                'title' => 'Logo Design Variations',
                'description' => 'Create logo variations for different use cases',
                'project_index' => 0,
                'status' => WorkOrderStatus::Delivered,
                'priority' => Priority::High,
                'due_date' => '2024-01-10',
                'estimated_hours' => 16,
                'actual_hours' => 14.0,
                'sop_attached' => false,
                'sop_name' => null,
            ],
            [
                'title' => 'Q1 Marketing Campaign Strategy',
                'description' => 'Develop comprehensive marketing strategy for Q1',
                'project_index' => 1,
                'status' => WorkOrderStatus::Active,
                'priority' => Priority::High,
                'due_date' => '2024-01-20',
                'estimated_hours' => 40,
                'actual_hours' => 22.5,
                'sop_attached' => true,
                'sop_name' => 'Campaign Planning SOP',
            ],
            [
                'title' => 'Social Media Content Calendar',
                'description' => 'Create 3-month content calendar for LinkedIn, Twitter, and Instagram',
                'project_index' => 1,
                'status' => WorkOrderStatus::Draft,
                'priority' => Priority::Medium,
                'due_date' => '2024-01-25',
                'estimated_hours' => 32,
                'actual_hours' => 8.5,
                'sop_attached' => true,
                'sop_name' => 'Content Calendar Template',
            ],
            [
                'title' => 'Landing Page Design',
                'description' => 'Design high-converting landing page for Q1 campaign',
                'project_index' => 1,
                'status' => WorkOrderStatus::Draft,
                'priority' => Priority::Medium,
                'due_date' => '2024-02-01',
                'estimated_hours' => 24,
                'actual_hours' => 0,
                'sop_attached' => false,
                'sop_name' => null,
            ],
            [
                'title' => 'December Status Report',
                'description' => 'Compile and deliver monthly status report for December activities',
                'project_index' => 2,
                'status' => WorkOrderStatus::InReview,
                'priority' => Priority::Medium,
                'due_date' => '2024-01-16',
                'estimated_hours' => 8,
                'actual_hours' => 7.5,
                'sop_attached' => true,
                'sop_name' => 'Monthly Reporting Template',
            ],
            [
                'title' => 'Homepage Wireframes',
                'description' => 'Create wireframes for new homepage layout and structure',
                'project_index' => 3,
                'status' => WorkOrderStatus::Draft,
                'priority' => Priority::Low,
                'due_date' => '2024-02-15',
                'estimated_hours' => 16,
                'actual_hours' => 4.5,
                'sop_attached' => false,
                'sop_name' => null,
            ],
        ];

        $workOrders = [];
        foreach ($workOrdersData as $data) {
            $workOrders[] = WorkOrder::create([
                'team_id' => $team->id,
                'project_id' => $projects[$data['project_index']]->id,
                'assigned_to_id' => $user->id,
                'created_by_id' => $user->id,
                'party_contact_id' => $parties[$data['project_index']]->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'due_date' => $data['due_date'],
                'estimated_hours' => $data['estimated_hours'],
                'actual_hours' => $data['actual_hours'],
                'acceptance_criteria' => ['Meets design specifications', 'Client approval received', 'Quality reviewed'],
                'sop_attached' => $data['sop_attached'],
                'sop_name' => $data['sop_name'],
            ]);
        }

        return $workOrders;
    }

    private function createTasks(Team $team, User $user, array $workOrders, array $projects): array
    {
        $tasksData = [
            // Website Redesign Phase 2 (workOrders[0])
            [
                'title' => 'Design homepage hero section',
                'work_order_index' => 0,
                'project_index' => 0,
                'status' => TaskStatus::Done,
                'due_date' => '2024-01-12',
                'estimated_hours' => 8,
                'actual_hours' => 9.5,
                'checklist' => [
                    ['text' => 'Review brand guidelines', 'completed' => true],
                    ['text' => 'Create 3 design options', 'completed' => true],
                    ['text' => 'Get client feedback', 'completed' => true],
                ],
            ],
            [
                'title' => 'Build responsive navigation component',
                'work_order_index' => 0,
                'project_index' => 0,
                'status' => TaskStatus::InProgress,
                'due_date' => '2024-01-16',
                'estimated_hours' => 12,
                'actual_hours' => 8.0,
                'checklist' => [
                    ['text' => 'Desktop navigation', 'completed' => true],
                    ['text' => 'Mobile hamburger menu', 'completed' => true],
                    ['text' => 'Accessibility testing', 'completed' => false],
                ],
            ],
            [
                'title' => 'QA review of homepage',
                'work_order_index' => 0,
                'project_index' => 0,
                'status' => TaskStatus::InProgress,
                'due_date' => '2024-01-17',
                'estimated_hours' => 6,
                'actual_hours' => 2.5,
                'checklist' => [
                    ['text' => 'Cross-browser testing', 'completed' => false],
                    ['text' => 'Mobile device testing', 'completed' => false],
                    ['text' => 'Accessibility audit', 'completed' => false],
                ],
            ],
            // Brand Guidelines (workOrders[1])
            [
                'title' => 'Export brand assets package',
                'work_order_index' => 1,
                'project_index' => 0,
                'status' => TaskStatus::Done,
                'due_date' => '2024-01-13',
                'estimated_hours' => 4,
                'actual_hours' => 3.5,
                'checklist' => [
                    ['text' => 'Export logo in all formats', 'completed' => true],
                    ['text' => 'Create color palette files', 'completed' => true],
                    ['text' => 'Package font files', 'completed' => true],
                ],
            ],
            // Q1 Marketing (workOrders[3])
            [
                'title' => 'Research target audience segments',
                'work_order_index' => 3,
                'project_index' => 1,
                'status' => TaskStatus::Done,
                'due_date' => '2024-01-14',
                'estimated_hours' => 8,
                'actual_hours' => 9.5,
                'checklist' => [
                    ['text' => 'Review analytics data', 'completed' => true],
                    ['text' => 'Create audience personas', 'completed' => true],
                    ['text' => 'Document segment priorities', 'completed' => true],
                ],
            ],
            [
                'title' => 'Define channel strategy',
                'work_order_index' => 3,
                'project_index' => 1,
                'status' => TaskStatus::InProgress,
                'due_date' => '2024-01-18',
                'estimated_hours' => 12,
                'actual_hours' => 6.0,
                'checklist' => [
                    ['text' => 'Evaluate channel performance', 'completed' => true],
                    ['text' => 'Allocate budget by channel', 'completed' => false],
                    ['text' => 'Get client approval', 'completed' => false],
                ],
            ],
            // Social Media Content Calendar (workOrders[4])
            [
                'title' => 'Draft LinkedIn posts for January',
                'work_order_index' => 4,
                'project_index' => 1,
                'status' => TaskStatus::InProgress,
                'due_date' => '2024-01-22',
                'estimated_hours' => 10,
                'actual_hours' => 4.5,
                'checklist' => [
                    ['text' => 'Research trending topics', 'completed' => true],
                    ['text' => 'Write post copy', 'completed' => false],
                    ['text' => 'Select images', 'completed' => false],
                ],
            ],
            [
                'title' => 'Design social media templates',
                'work_order_index' => 4,
                'project_index' => 1,
                'status' => TaskStatus::Todo,
                'due_date' => '2024-01-24',
                'estimated_hours' => 8,
                'actual_hours' => 0,
                'checklist' => [
                    ['text' => 'Create Instagram template', 'completed' => false],
                    ['text' => 'Create LinkedIn template', 'completed' => false],
                    ['text' => 'Create Twitter template', 'completed' => false],
                ],
                'is_blocked' => true,
            ],
            // December Status Report (workOrders[6])
            [
                'title' => 'Compile project metrics',
                'work_order_index' => 6,
                'project_index' => 2,
                'status' => TaskStatus::Done,
                'due_date' => '2024-01-15',
                'estimated_hours' => 4,
                'actual_hours' => 3.5,
                'checklist' => [
                    ['text' => 'Export time tracking data', 'completed' => true],
                    ['text' => 'Calculate budget variance', 'completed' => true],
                    ['text' => 'Document milestone completion', 'completed' => true],
                ],
            ],
            // Homepage Wireframes (workOrders[7])
            [
                'title' => 'Sketch initial wireframe concepts',
                'work_order_index' => 7,
                'project_index' => 3,
                'status' => TaskStatus::InProgress,
                'due_date' => '2024-02-10',
                'estimated_hours' => 6,
                'actual_hours' => 4.5,
                'checklist' => [
                    ['text' => 'Sketch 5 layout options', 'completed' => true],
                    ['text' => 'Review with team', 'completed' => false],
                    ['text' => 'Select top 3 to refine', 'completed' => false],
                ],
            ],
        ];

        $tasks = [];
        foreach ($tasksData as $data) {
            $checklistItems = [];
            foreach ($data['checklist'] as $index => $item) {
                $checklistItems[] = [
                    'id' => 'c' . ($index + 1),
                    'text' => $item['text'],
                    'completed' => $item['completed'],
                ];
            }

            $tasks[] = Task::create([
                'team_id' => $team->id,
                'work_order_id' => $workOrders[$data['work_order_index']]->id,
                'project_id' => $projects[$data['project_index']]->id,
                'assigned_to_id' => $user->id,
                'title' => $data['title'],
                'description' => null,
                'status' => $data['status'],
                'due_date' => $data['due_date'],
                'estimated_hours' => $data['estimated_hours'],
                'actual_hours' => $data['actual_hours'],
                'checklist_items' => $checklistItems,
                'dependencies' => [],
                'is_blocked' => $data['is_blocked'] ?? false,
            ]);
        }

        return $tasks;
    }

    private function createDeliverables(Team $team, array $workOrders, array $projects): void
    {
        $deliverablesData = [
            [
                'title' => 'Brand Guidelines Document',
                'description' => 'Comprehensive PDF document covering logo usage, colors, typography, and brand voice',
                'work_order_index' => 1,
                'project_index' => 0,
                'type' => DeliverableType::Document,
                'status' => DeliverableStatus::Delivered,
                'version' => '2.0',
                'file_url' => '/files/acme-brand-guidelines-v2.pdf',
            ],
            [
                'title' => 'Logo Asset Package',
                'description' => 'Complete set of logo files in all formats (SVG, PNG, EPS)',
                'work_order_index' => 2,
                'project_index' => 0,
                'type' => DeliverableType::Design,
                'status' => DeliverableStatus::Delivered,
                'version' => '1.0',
                'file_url' => '/files/acme-logo-package.zip',
            ],
            [
                'title' => 'Website Mockups - Homepage',
                'description' => 'High-fidelity mockups of redesigned homepage in desktop and mobile views',
                'work_order_index' => 0,
                'project_index' => 0,
                'type' => DeliverableType::Design,
                'status' => DeliverableStatus::InReview,
                'version' => '3.1',
                'file_url' => '/files/acme-homepage-mockups-v3.1.fig',
            ],
            [
                'title' => 'Q1 Campaign Strategy Document',
                'description' => 'Comprehensive strategy document outlining channels, budget, timeline, and KPIs',
                'work_order_index' => 3,
                'project_index' => 1,
                'type' => DeliverableType::Document,
                'status' => DeliverableStatus::Draft,
                'version' => '0.8',
                'file_url' => '/files/techstart-q1-strategy-draft.docx',
            ],
        ];

        foreach ($deliverablesData as $data) {
            Deliverable::create([
                'team_id' => $team->id,
                'work_order_id' => $workOrders[$data['work_order_index']]->id,
                'project_id' => $projects[$data['project_index']]->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $data['type'],
                'status' => $data['status'],
                'version' => $data['version'],
                'created_date' => now()->subDays(rand(1, 14)),
                'delivered_date' => $data['status'] === DeliverableStatus::Delivered ? now()->subDays(rand(1, 7)) : null,
                'file_url' => $data['file_url'],
                'acceptance_criteria' => ['Meets specifications', 'Client approved'],
            ]);
        }
    }

    private function createCommunications(Team $team, User $user, array $projects, array $workOrders): void
    {
        // Thread for first project
        $thread1 = CommunicationThread::create([
            'team_id' => $team->id,
            'threadable_type' => Project::class,
            'threadable_id' => $projects[0]->id,
            'message_count' => 2,
            'last_activity' => now(),
        ]);

        Message::create([
            'communication_thread_id' => $thread1->id,
            'author_id' => $user->id,
            'author_type' => 'human',
            'content' => 'Jennifer from Acme just confirmed they love the logo variations. Moving forward with option 2.',
            'type' => 'note',
        ]);

        Message::create([
            'communication_thread_id' => $thread1->id,
            'author_id' => $user->id,
            'author_type' => 'human',
            'content' => "Great! I'll finalize the brand guidelines with option 2 and have them ready by EOD.",
            'type' => 'note',
        ]);

        // Thread for first work order
        $thread2 = CommunicationThread::create([
            'team_id' => $team->id,
            'threadable_type' => WorkOrder::class,
            'threadable_id' => $workOrders[0]->id,
            'message_count' => 2,
            'last_activity' => now(),
        ]);

        Message::create([
            'communication_thread_id' => $thread2->id,
            'author_id' => $user->id,
            'author_type' => 'ai_agent',
            'content' => "The homepage mockups are ready for client review. I've flagged this as needing approval before development. Should I send the review request to Jennifer?",
            'type' => 'suggestion',
        ]);

        Message::create([
            'communication_thread_id' => $thread2->id,
            'author_id' => $user->id,
            'author_type' => 'human',
            'content' => 'Yes, please send the review request. Include the Figma link and ask for feedback by Wednesday.',
            'type' => 'note',
        ]);
    }

    private function createDocuments(Team $team, User $user, array $projects, array $workOrders, array $tasks): void
    {
        Document::create([
            'team_id' => $team->id,
            'uploaded_by_id' => $user->id,
            'documentable_type' => Project::class,
            'documentable_id' => $projects[0]->id,
            'name' => 'Client Brief - Acme Rebrand',
            'type' => 'reference',
            'file_url' => '/files/acme-brief.pdf',
            'file_size' => '2.4 MB',
        ]);

        Document::create([
            'team_id' => $team->id,
            'uploaded_by_id' => $user->id,
            'documentable_type' => WorkOrder::class,
            'documentable_id' => $workOrders[0]->id,
            'name' => 'Homepage Wireframes v1',
            'type' => 'artifact',
            'file_url' => '/files/homepage-wireframes-v1.fig',
            'file_size' => '8.1 MB',
        ]);

        Document::create([
            'team_id' => $team->id,
            'uploaded_by_id' => $user->id,
            'documentable_type' => WorkOrder::class,
            'documentable_id' => $workOrders[3]->id,
            'name' => 'Competitor Analysis',
            'type' => 'reference',
            'file_url' => '/files/competitor-analysis.xlsx',
            'file_size' => '1.2 MB',
        ]);
    }
}
