<?php

declare(strict_types=1);

use App\Enums\AIConfidence;
use App\Enums\DeliverableType;
use App\Enums\PlaybookType;
use App\Enums\WorkOrderStatus;
use App\Models\Party;
use App\Models\Playbook;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\DeliverableGeneratorService;
use App\ValueObjects\DeliverableSuggestion;

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

    $this->service = new DeliverableGeneratorService;
});

test('service generates 2-3 alternatives from work order', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Build User Authentication System',
        'description' => 'Implement complete user authentication with login, registration, and password reset functionality.',
        'status' => WorkOrderStatus::Active,
        'acceptance_criteria' => [
            'Users can register with email and password',
            'Users can log in securely',
            'Password reset via email works',
        ],
    ]);

    $alternatives = $this->service->generateAlternatives($workOrder);

    expect($alternatives)->toBeArray();
    expect(count($alternatives))->toBeGreaterThanOrEqual(2);
    expect(count($alternatives))->toBeLessThanOrEqual(3);

    // Each alternative should be a DeliverableSuggestion
    foreach ($alternatives as $alternative) {
        expect($alternative)->toBeInstanceOf(DeliverableSuggestion::class);
        expect($alternative->title)->toBeString();
        expect($alternative->title)->not->toBeEmpty();
        expect($alternative->confidence)->toBeInstanceOf(AIConfidence::class);
    }
});

test('service incorporates playbook templates when available', function () {
    // Create a relevant playbook for authentication with required fields
    Playbook::factory()->create([
        'team_id' => $this->team->id,
        'created_by' => $this->user->id,
        'created_by_name' => $this->user->name,
        'name' => 'Authentication Implementation Playbook',
        'description' => 'Standard playbook for implementing authentication features',
        'type' => PlaybookType::Template,
        'tags' => ['authentication', 'security', 'login'],
        'content' => [
            'deliverables' => [
                ['title' => 'Authentication Module', 'type' => 'code'],
                ['title' => 'Security Documentation', 'type' => 'document'],
            ],
            'checklist' => [
                'Implement secure password hashing',
                'Add rate limiting',
                'Enable two-factor authentication',
            ],
        ],
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Implement User Authentication',
        'description' => 'Build login and registration system with secure authentication.',
        'status' => WorkOrderStatus::Active,
    ]);

    $alternatives = $this->service->generateAlternatives($workOrder);

    expect($alternatives)->toBeArray();
    expect(count($alternatives))->toBeGreaterThanOrEqual(2);

    // At least one alternative should have playbook influence indicated in reasoning
    $hasPlaybookInfluence = collect($alternatives)->contains(function (DeliverableSuggestion $suggestion) {
        return str_contains(strtolower($suggestion->reasoning ?? ''), 'playbook') ||
               $suggestion->playbookId !== null;
    });

    expect($hasPlaybookInfluence)->toBeTrue();
});

test('confidence levels assigned based on context clarity', function () {
    // Work order with high context clarity (detailed description, acceptance criteria)
    $highContextWorkOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Create API Documentation',
        'description' => 'Generate comprehensive API documentation including all endpoints, request/response schemas, authentication methods, and code examples. The documentation should follow OpenAPI 3.0 specification.',
        'status' => WorkOrderStatus::Active,
        'acceptance_criteria' => [
            'All public endpoints documented',
            'Request/response examples provided',
            'Authentication flow documented',
            'Error codes documented',
        ],
    ]);

    $highContextAlternatives = $this->service->generateAlternatives($highContextWorkOrder);

    // At least one alternative should have high or medium confidence
    $hasHighOrMediumConfidence = collect($highContextAlternatives)->contains(function (DeliverableSuggestion $suggestion) {
        return $suggestion->confidence === AIConfidence::High ||
               $suggestion->confidence === AIConfidence::Medium;
    });
    expect($hasHighOrMediumConfidence)->toBeTrue();

    // Work order with low context clarity (vague description, no criteria)
    $lowContextWorkOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Fix stuff',
        'description' => 'Need some fixes.',
        'status' => WorkOrderStatus::Active,
        'acceptance_criteria' => null,
    ]);

    $lowContextAlternatives = $this->service->generateAlternatives($lowContextWorkOrder);

    // For vague work orders, confidence should be lower overall
    $allLowConfidence = collect($lowContextAlternatives)->every(function (DeliverableSuggestion $suggestion) {
        return $suggestion->confidence === AIConfidence::Low ||
               $suggestion->confidence === AIConfidence::Medium;
    });
    expect($allLowConfidence)->toBeTrue();
});

test('deliverables linked to work order correctly', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Design New Dashboard',
        'description' => 'Create mockups and design specifications for the new analytics dashboard.',
        'status' => WorkOrderStatus::Active,
        'acceptance_criteria' => [
            'Responsive design for desktop and mobile',
            'Include data visualization components',
        ],
    ]);

    $alternatives = $this->service->generateAlternatives($workOrder);

    // Create a deliverable from one of the suggestions
    $suggestion = $alternatives[0];
    $deliverable = $suggestion->createDeliverable($workOrder);

    expect($deliverable->work_order_id)->toBe($workOrder->id);
    expect($deliverable->team_id)->toBe($workOrder->team_id);
    expect($deliverable->project_id)->toBe($workOrder->project_id);
    expect($deliverable->title)->toBe($suggestion->title);
    expect($deliverable->type)->toBe($suggestion->type);
});

test('value object toArray serializes correctly', function () {
    $suggestion = new DeliverableSuggestion(
        title: 'Project Report',
        description: 'A comprehensive project status report',
        type: DeliverableType::Report,
        acceptanceCriteria: ['Contains executive summary', 'Includes timeline'],
        confidence: AIConfidence::High,
        reasoning: 'Based on work order requirements and team playbook',
        playbookId: 1
    );

    $array = $suggestion->toArray();

    expect($array)->toBeArray();
    expect($array['title'])->toBe('Project Report');
    expect($array['description'])->toBe('A comprehensive project status report');
    expect($array['type'])->toBe('report');
    expect($array['acceptance_criteria'])->toBe(['Contains executive summary', 'Includes timeline']);
    expect($array['confidence'])->toBe('high');
    expect($array['reasoning'])->toBe('Based on work order requirements and team playbook');
    expect($array['playbook_id'])->toBe(1);
});
