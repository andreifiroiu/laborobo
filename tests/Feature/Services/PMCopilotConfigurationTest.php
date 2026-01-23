<?php

declare(strict_types=1);

use App\Enums\AIConfidence;
use App\Enums\PMCopilotMode;
use App\Models\GlobalAISettings;
use App\Models\Party;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\PMCopilotAutoApprovalService;

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
});

test('work order pm_copilot_mode setting stores and retrieves correctly', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
        'pm_copilot_mode' => PMCopilotMode::Staged,
    ]);

    expect($workOrder->pm_copilot_mode)->toBe(PMCopilotMode::Staged);

    $workOrder->refresh();
    expect($workOrder->pm_copilot_mode)->toBe(PMCopilotMode::Staged);

    // Update to full mode
    $workOrder->update(['pm_copilot_mode' => PMCopilotMode::Full]);
    expect($workOrder->pm_copilot_mode)->toBe(PMCopilotMode::Full);
});

test('work order pm_copilot_mode defaults to full when not set', function () {
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'created_by_id' => $this->user->id,
    ]);

    // The accessor should return full as default
    expect($workOrder->pm_copilot_mode)->toBe(PMCopilotMode::Full);
});

test('GlobalAISettings pm_copilot_auto_approval_threshold stores float values', function () {
    $settings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'pm_copilot_auto_approval_threshold' => 0.85,
    ]);

    expect($settings->pm_copilot_auto_approval_threshold)->toBe(0.85);

    $settings->refresh();
    expect($settings->pm_copilot_auto_approval_threshold)->toBe(0.85);

    // Update threshold
    $settings->update(['pm_copilot_auto_approval_threshold' => 0.95]);
    expect($settings->pm_copilot_auto_approval_threshold)->toBe(0.95);
});

test('GlobalAISettings pm_copilot_auto_approval_threshold defaults to 0.8', function () {
    $settings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
    ]);

    expect($settings->pm_copilot_auto_approval_threshold)->toBe(0.8);
});

test('auto-approval service approves high confidence suggestions without budget impact', function () {
    $settings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'pm_copilot_auto_approval_threshold' => 0.8,
    ]);

    $service = new PMCopilotAutoApprovalService();

    // High confidence suggestion without budget impact should be auto-approved
    $suggestion = [
        'title' => 'Test Deliverable',
        'confidence' => AIConfidence::High->value,
        'confidence_score' => 0.9,
        'has_budget_impact' => false,
    ];

    expect($service->shouldAutoApprove($suggestion, $settings))->toBeTrue();
});

test('auto-approval service rejects suggestions with budget impact', function () {
    $settings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'pm_copilot_auto_approval_threshold' => 0.8,
    ]);

    $service = new PMCopilotAutoApprovalService();

    // High confidence suggestion WITH budget impact should NOT be auto-approved
    $suggestion = [
        'title' => 'Test Deliverable',
        'confidence' => AIConfidence::High->value,
        'confidence_score' => 0.95,
        'has_budget_impact' => true,
        'budget_cost' => 500.00,
    ];

    expect($service->shouldAutoApprove($suggestion, $settings))->toBeFalse();
});

test('auto-approval service rejects suggestions below confidence threshold', function () {
    $settings = GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'pm_copilot_auto_approval_threshold' => 0.8,
    ]);

    $service = new PMCopilotAutoApprovalService();

    // Low confidence suggestion without budget impact should NOT be auto-approved
    $suggestion = [
        'title' => 'Test Deliverable',
        'confidence' => AIConfidence::Low->value,
        'confidence_score' => 0.5,
        'has_budget_impact' => false,
    ];

    expect($service->shouldAutoApprove($suggestion, $settings))->toBeFalse();

    // Medium confidence below threshold
    $mediumSuggestion = [
        'title' => 'Test Deliverable',
        'confidence' => AIConfidence::Medium->value,
        'confidence_score' => 0.7,
        'has_budget_impact' => false,
    ];

    expect($service->shouldAutoApprove($mediumSuggestion, $settings))->toBeFalse();
});
