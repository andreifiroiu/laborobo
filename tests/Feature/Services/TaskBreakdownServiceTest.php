<?php

declare(strict_types=1);

use App\Enums\AIConfidence;
use App\Enums\PlaybookType;
use App\Enums\WorkOrderStatus;
use App\Models\Deliverable;
use App\Models\Party;
use App\Models\Playbook;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\TaskBreakdownService;
use App\ValueObjects\TaskSuggestion;

beforeEach(function () {
    $this->user = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 20,
    ]);
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->party = Party::factory()->create(['team_id' => $this->team->id]);
    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
        'party_id' => $this->party->id,
        'owner_id' => $this->user->id,
        'name' => 'Test Project',
    ]);

    $this->service = new TaskBreakdownService;
});

test('service breaks deliverable into actionable tasks', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Build User Authentication System',
        'description' => 'Implement complete user authentication with login, registration, and password reset functionality.',
        'status' => WorkOrderStatus::Active,
        'estimated_hours' => 40,
    ]);

    $deliverables = [
        [
            'title' => 'Authentication Module',
            'description' => 'Core authentication functionality including login and registration',
            'type' => 'code',
        ],
    ];

    $alternatives = $this->service->generateBreakdown($workOrder, $deliverables);

    expect($alternatives)->toBeArray();
    expect(count($alternatives))->toBeGreaterThanOrEqual(2);
    expect(count($alternatives))->toBeLessThanOrEqual(3);

    // Each alternative should contain task suggestions
    foreach ($alternatives as $alternative) {
        expect($alternative)->toHaveKey('tasks');
        expect($alternative['tasks'])->toBeArray();

        foreach ($alternative['tasks'] as $task) {
            expect($task)->toBeInstanceOf(TaskSuggestion::class);
            expect($task->title)->toBeString();
            expect($task->title)->not->toBeEmpty();
        }
    }
});

test('LLM-based hour estimation populated for each task', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Create API Endpoints',
        'description' => 'Build REST API endpoints for CRUD operations on user resources.',
        'status' => WorkOrderStatus::Active,
        'estimated_hours' => 24,
    ]);

    $deliverables = [
        [
            'title' => 'User API Endpoints',
            'description' => 'CRUD endpoints for user management',
            'type' => 'code',
        ],
    ];

    $alternatives = $this->service->generateBreakdown($workOrder, $deliverables);

    expect($alternatives)->not->toBeEmpty();

    $firstAlternative = $alternatives[0];
    expect($firstAlternative['tasks'])->not->toBeEmpty();

    foreach ($firstAlternative['tasks'] as $task) {
        expect($task->estimatedHours)->toBeGreaterThan(0);
        expect($task->estimatedHours)->toBeFloat();
    }
});

test('position_in_work_order ordering generated correctly', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Design Dashboard',
        'description' => 'Create dashboard design with multiple components.',
        'status' => WorkOrderStatus::Active,
    ]);

    $deliverables = [
        [
            'title' => 'Dashboard Design',
            'description' => 'Complete dashboard mockups and specifications',
            'type' => 'design',
        ],
    ];

    $alternatives = $this->service->generateBreakdown($workOrder, $deliverables);

    expect($alternatives)->not->toBeEmpty();

    $firstAlternative = $alternatives[0];
    $tasks = $firstAlternative['tasks'];

    // Verify positions are sequential and unique
    $positions = collect($tasks)->pluck('position')->toArray();
    $expectedPositions = range(1, count($tasks));

    expect($positions)->toBe($expectedPositions);
});

test('checklist items from playbook included in tasks', function () {
    // Create a playbook with task templates and checklist items
    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'created_by_name' => $this->user->name,
        'name' => 'API Development Playbook',
        'description' => 'Standard playbook for API development tasks',
        'type' => PlaybookType::Template,
        'tags' => ['api', 'development', 'backend'],
        'content' => [
            'tasks' => [
                [
                    'title' => 'Implement endpoint',
                    'estimated_hours' => 4,
                    'checklist' => [
                        'Write endpoint handler',
                        'Add validation',
                        'Write unit tests',
                        'Document endpoint',
                    ],
                ],
            ],
            'checklist' => [
                'Code review completed',
                'Tests passing',
                'Documentation updated',
            ],
        ],
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Build API Endpoints',
        'description' => 'Create backend API endpoints for the application.',
        'status' => WorkOrderStatus::Active,
    ]);

    $deliverables = [
        [
            'title' => 'API Implementation',
            'description' => 'Backend API development',
            'type' => 'code',
        ],
    ];

    $alternatives = $this->service->generateBreakdown($workOrder, $deliverables);

    expect($alternatives)->not->toBeEmpty();

    // At least one task in the alternatives should have checklist items influenced by playbook
    $hasChecklistItems = false;
    foreach ($alternatives as $alternative) {
        foreach ($alternative['tasks'] as $task) {
            if (! empty($task->checklistItems)) {
                $hasChecklistItems = true;
                expect($task->checklistItems)->toBeArray();
                break 2;
            }
        }
    }

    expect($hasChecklistItems)->toBeTrue();
});

test('task suggestion value object serializes correctly', function () {
    $suggestion = new TaskSuggestion(
        title: 'Implement Login Form',
        description: 'Create the user login form with validation',
        estimatedHours: 4.5,
        position: 1,
        checklistItems: ['Add email field', 'Add password field', 'Add submit button'],
        dependencies: [1, 2],
        confidence: AIConfidence::High,
        reasoning: 'Based on standard authentication patterns',
        playbookId: 1
    );

    $array = $suggestion->toArray();

    expect($array)->toBeArray();
    expect($array['title'])->toBe('Implement Login Form');
    expect($array['description'])->toBe('Create the user login form with validation');
    expect($array['estimated_hours'])->toBe(4.5);
    expect($array['position'])->toBe(1);
    expect($array['checklist_items'])->toBe(['Add email field', 'Add password field', 'Add submit button']);
    expect($array['dependencies'])->toBe([1, 2]);
    expect($array['confidence'])->toBe('high');
    expect($array['reasoning'])->toBe('Based on standard authentication patterns');
    expect($array['playbook_id'])->toBe(1);
});

test('task suggestion creates task from work order', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Test Work Order',
        'status' => WorkOrderStatus::Active,
    ]);

    $suggestion = new TaskSuggestion(
        title: 'Test Task',
        description: 'A test task description',
        estimatedHours: 2.0,
        position: 1,
        checklistItems: ['Item 1', 'Item 2'],
        dependencies: [],
        confidence: AIConfidence::Medium
    );

    $task = $suggestion->createTask($workOrder);

    expect($task->work_order_id)->toBe($workOrder->id);
    expect($task->team_id)->toBe($workOrder->team_id);
    expect($task->project_id)->toBe($workOrder->project_id);
    expect($task->title)->toBe($suggestion->title);
    expect($task->estimated_hours)->toBe('2.00');
    expect($task->position_in_work_order)->toBe(1);
});
