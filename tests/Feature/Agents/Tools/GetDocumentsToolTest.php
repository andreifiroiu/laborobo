<?php

declare(strict_types=1);

use App\Agents\Tools\GetDocumentsTool;
use App\Enums\DocumentType;
use App\Enums\WorkOrderStatus;
use App\Models\Document;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->user = User::factory()->create();
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

    $this->workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'title' => 'Test Work Order',
        'status' => WorkOrderStatus::Active,
    ]);

    $this->tool = new GetDocumentsTool;
});

test('lists documents for a project', function () {
    Document::factory()->count(3)->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $result = $this->tool->execute([
        'entity_type' => 'project',
        'entity_id' => $this->project->id,
    ]);

    expect($result['entity_type'])->toBe('project');
    expect($result['entity_id'])->toBe($this->project->id);
    expect($result['total_found'])->toBe(3);
    expect($result['documents'])->toHaveCount(3);
    expect($result['documents'][0])->toHaveKeys(['id', 'name', 'type', 'file_size', 'file_url', 'uploaded_at', 'folder_id']);
});

test('lists documents for a work order', function () {
    Document::factory()->count(2)->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => WorkOrder::class,
        'documentable_id' => $this->workOrder->id,
    ]);

    $result = $this->tool->execute([
        'entity_type' => 'work_order',
        'entity_id' => $this->workOrder->id,
    ]);

    expect($result['entity_type'])->toBe('work_order');
    expect($result['total_found'])->toBe(2);
    expect($result['documents'])->toHaveCount(2);
});

test('filters by document type', function () {
    Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'type' => DocumentType::Reference,
    ]);

    Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'type' => DocumentType::Artifact,
    ]);

    $result = $this->tool->execute([
        'entity_type' => 'project',
        'entity_id' => $this->project->id,
        'document_type' => 'reference',
    ]);

    expect($result['total_found'])->toBe(1);
    expect($result['documents'][0]['type'])->toBe('reference');
});

test('searches by name', function () {
    Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'name' => 'Project Blueprint.pdf',
    ]);

    Document::factory()->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
        'name' => 'Invoice 2026.xlsx',
    ]);

    $result = $this->tool->execute([
        'entity_type' => 'project',
        'entity_id' => $this->project->id,
        'search' => 'Blueprint',
    ]);

    expect($result['total_found'])->toBe(1);
    expect($result['documents'][0]['name'])->toBe('Project Blueprint.pdf');
});

test('throws for invalid entity_type', function () {
    expect(fn () => $this->tool->execute([
        'entity_type' => 'task',
        'entity_id' => 1,
    ]))->toThrow(InvalidArgumentException::class, "Invalid entity_type 'task'");
});

test('throws for missing entity', function () {
    expect(fn () => $this->tool->execute([
        'entity_type' => 'project',
        'entity_id' => 99999,
    ]))->toThrow(InvalidArgumentException::class, 'project with ID 99999 not found');
});

test('throws when entity_type is missing', function () {
    expect(fn () => $this->tool->execute([
        'entity_id' => 1,
    ]))->toThrow(InvalidArgumentException::class, 'entity_type is required');
});

test('throws when entity_id is missing', function () {
    expect(fn () => $this->tool->execute([
        'entity_type' => 'project',
    ]))->toThrow(InvalidArgumentException::class, 'entity_id is required');
});

test('respects limit', function () {
    Document::factory()->count(5)->create([
        'team_id' => $this->team->id,
        'uploaded_by_id' => $this->user->id,
        'documentable_type' => Project::class,
        'documentable_id' => $this->project->id,
    ]);

    $result = $this->tool->execute([
        'entity_type' => 'project',
        'entity_id' => $this->project->id,
        'limit' => 2,
    ]);

    expect($result['total_found'])->toBe(2);
    expect($result['documents'])->toHaveCount(2);
});

test('returns empty array when no documents', function () {
    $result = $this->tool->execute([
        'entity_type' => 'project',
        'entity_id' => $this->project->id,
    ]);

    expect($result['total_found'])->toBe(0);
    expect($result['documents'])->toBeEmpty();
});
