<?php

declare(strict_types=1);

use App\Enums\AIConfidence;
use App\Enums\AgentType;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\GlobalAISettings;
use App\Models\Party;
use App\Models\Playbook;
use App\Models\Project;
use App\Models\User;
use App\Models\UserSkill;
use App\Services\CapacityScoreService;
use App\Services\RoutingDecisionService;
use App\Services\SkillMatchingService;
use App\Services\WorkRequirementExtractor;

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

    $this->dispatcherAgent = AIAgent::factory()->create([
        'code' => 'dispatcher',
        'name' => 'Dispatcher Agent',
        'type' => AgentType::WorkRouting,
        'description' => 'Routes work to team members based on skills and capacity',
        'tools' => ['work_routing', 'skill_matching', 'capacity_analysis'],
    ]);

    AgentConfiguration::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $this->dispatcherAgent->id,
        'enabled' => true,
        'daily_run_limit' => 50,
        'monthly_budget_cap' => 100.00,
        'current_month_spend' => 0.00,
        'daily_spend' => 0.00,
        'can_create_work_orders' => true,
        'can_modify_tasks' => true,
        'can_access_client_data' => true,
        'can_send_emails' => false,
        'can_modify_deliverables' => true,
        'can_access_financial_data' => false,
        'can_modify_playbooks' => true,
    ]);

    GlobalAISettings::create([
        'team_id' => $this->team->id,
        'total_monthly_budget' => 1000.00,
        'current_month_spend' => 0.00,
        'require_approval_external_sends' => true,
        'require_approval_financial' => true,
        'require_approval_contracts' => true,
        'require_approval_scope_changes' => false,
    ]);
});

test('WorkRequirementExtractor extracts title, description, and scope from messages', function () {
    $extractor = app(WorkRequirementExtractor::class);

    $messageContent = <<<'EOT'
We need to build a new dashboard for the analytics team. The dashboard should display real-time metrics and KPIs.

The scope includes:
- User activity charts
- Revenue tracking
- Performance indicators

This is high priority and needs to be done by next Friday.
I estimate it will take about 16 hours.
EOT;

    $result = $extractor->extract($messageContent);

    expect($result)->toBeArray();
    expect($result)->toHaveKey('title');
    expect($result)->toHaveKey('description');
    expect($result)->toHaveKey('scope');
    expect($result)->toHaveKey('success_criteria');
    expect($result)->toHaveKey('estimated_hours');
    expect($result)->toHaveKey('priority');
    expect($result)->toHaveKey('deadline');

    // Each field should have value and confidence
    expect($result['title'])->toHaveKey('value');
    expect($result['title'])->toHaveKey('confidence');
    expect($result['description'])->toHaveKey('value');
    expect($result['description'])->toHaveKey('confidence');
});

test('WorkRequirementExtractor assigns AIConfidence enum to extracted fields', function () {
    $extractor = app(WorkRequirementExtractor::class);

    // Clear message with explicit requirements - should yield higher confidence
    $messageContent = <<<'EOT'
Title: Website Redesign Project
Description: Complete overhaul of the company website using modern React framework
Priority: High
Deadline: 2024-03-15
Estimated Hours: 40
EOT;

    $result = $extractor->extract($messageContent);

    // Verify confidence is an AIConfidence enum
    expect($result['title']['confidence'])->toBeInstanceOf(AIConfidence::class);
    expect($result['description']['confidence'])->toBeInstanceOf(AIConfidence::class);
    expect($result['priority']['confidence'])->toBeInstanceOf(AIConfidence::class);

    // Explicitly stated fields should have high confidence
    expect($result['title']['confidence'])->toBe(AIConfidence::High);
});

test('SkillMatchingService calculates skill match score with proficiency weighting', function () {
    // Create team members with skills
    $member1 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 10,
    ]);
    $member2 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 20,
    ]);

    $this->team->addUser($member1, 'member');
    $this->team->addUser($member2, 'member');

    // Member 1: Advanced Laravel (3), Intermediate React (2)
    UserSkill::create([
        'user_id' => $member1->id,
        'skill_name' => 'Laravel',
        'proficiency' => 3, // Advanced = 1.0 weight
    ]);
    UserSkill::create([
        'user_id' => $member1->id,
        'skill_name' => 'React',
        'proficiency' => 2, // Intermediate = 0.66 weight
    ]);

    // Member 2: Basic Laravel (1)
    UserSkill::create([
        'user_id' => $member2->id,
        'skill_name' => 'Laravel',
        'proficiency' => 1, // Basic = 0.33 weight
    ]);

    $service = app(SkillMatchingService::class);
    $requiredSkills = ['Laravel', 'React'];

    $scores = $service->calculateScores($this->team->id, $requiredSkills);

    expect($scores)->toBeArray();
    expect($scores)->toHaveKey($member1->id);
    expect($scores)->toHaveKey($member2->id);

    // Member 1 should have higher score (has both skills with higher proficiency)
    expect($scores[$member1->id]['score'])->toBeGreaterThan($scores[$member2->id]['score']);

    // Verify proficiency weights are applied correctly
    // Member 1: Laravel(3) = 1.0, React(2) = 0.66 -> (1.0 + 0.66) / 2 * 100 = 83
    // Member 2: Laravel(1) = 0.33, React = 0 -> (0.33 + 0) / 2 * 100 = 16.5
    expect($scores[$member1->id]['score'])->toBeGreaterThanOrEqual(80);
    expect($scores[$member2->id]['score'])->toBeLessThanOrEqual(20);
});

test('CapacityScoreService calculates capacity score and applies low capacity penalty', function () {
    // Create team members with different capacity levels
    $member1 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 10, // 75% available (30 hours)
    ]);
    $member2 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 35, // 12.5% available (5 hours) - below 20%
    ]);

    $this->team->addUser($member1, 'member');
    $this->team->addUser($member2, 'member');

    $service = app(CapacityScoreService::class);
    $estimatedHours = 8.0;

    $scores = $service->calculateScores($this->team->id, $estimatedHours);

    expect($scores)->toBeArray();
    expect($scores)->toHaveKey($member1->id);
    expect($scores)->toHaveKey($member2->id);

    // Member 1 should have high score (plenty of capacity)
    expect($scores[$member1->id]['score'])->toBeGreaterThan(70);

    // Member 2 should have penalized score (less than 20% available)
    // Base score then penalized by 50%
    expect($scores[$member2->id]['score'])->toBeLessThan($scores[$member1->id]['score']);
    expect($scores[$member2->id]['penalty_applied'])->toBeTrue();
});

test('RoutingDecisionService combines skill and capacity scores with 50/50 weighting', function () {
    // Create team members
    $member1 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 10, // High capacity
    ]);
    $member2 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 30, // Low capacity
    ]);

    $this->team->addUser($member1, 'member');
    $this->team->addUser($member2, 'member');

    // Member 1: Intermediate skills
    UserSkill::create([
        'user_id' => $member1->id,
        'skill_name' => 'PHP',
        'proficiency' => 2,
    ]);

    // Member 2: Advanced skills
    UserSkill::create([
        'user_id' => $member2->id,
        'skill_name' => 'PHP',
        'proficiency' => 3,
    ]);

    $service = app(RoutingDecisionService::class);
    $result = $service->calculateRouting(
        teamId: $this->team->id,
        requiredSkills: ['PHP'],
        estimatedHours: 8.0
    );

    expect($result)->toBeArray();
    expect($result)->toHaveKey('candidates');
    expect($result['candidates'])->not->toBeEmpty();

    // Verify combined score calculation (50% skill + 50% capacity)
    foreach ($result['candidates'] as $candidate) {
        expect($candidate)->toHaveKey('skill_score');
        expect($candidate)->toHaveKey('capacity_score');
        expect($candidate)->toHaveKey('combined_score');
        expect($candidate)->toHaveKey('confidence');
        expect($candidate)->toHaveKey('reasoning');

        // Verify 50/50 weighting
        $expectedCombined = ($candidate['skill_score'] * 0.5) + ($candidate['capacity_score'] * 0.5);
        expect($candidate['combined_score'])->toBe(round($expectedCombined, 2));
    }

    // Always return at least top 3 candidates when available
    expect(count($result['candidates']))->toBeGreaterThanOrEqual(2); // We only have 2 members
});

test('RoutingDecisionService presents multiple options when scores within 10% difference', function () {
    // Create team members with similar combined scores
    $member1 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 15,
    ]);
    $member2 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 12,
    ]);
    $member3 = User::factory()->create([
        'capacity_hours_per_week' => 40,
        'current_workload_hours' => 38, // Very low capacity
    ]);

    $this->team->addUser($member1, 'member');
    $this->team->addUser($member2, 'member');
    $this->team->addUser($member3, 'member');

    // Give similar skills to members 1 and 2
    UserSkill::create([
        'user_id' => $member1->id,
        'skill_name' => 'Laravel',
        'proficiency' => 3,
    ]);
    UserSkill::create([
        'user_id' => $member2->id,
        'skill_name' => 'Laravel',
        'proficiency' => 3,
    ]);
    UserSkill::create([
        'user_id' => $member3->id,
        'skill_name' => 'Laravel',
        'proficiency' => 1,
    ]);

    $service = app(RoutingDecisionService::class);
    $result = $service->calculateRouting(
        teamId: $this->team->id,
        requiredSkills: ['Laravel'],
        estimatedHours: 8.0
    );

    // Members 1 and 2 should both be flagged as top candidates since their scores are close
    $topCandidates = array_filter(
        $result['candidates'],
        fn ($c) => $c['is_top_candidate']
    );

    // At least 2 top candidates when scores are within 10%
    expect(count($topCandidates))->toBeGreaterThanOrEqual(2);

    // Verify reasoning is included for each candidate
    foreach ($result['candidates'] as $candidate) {
        expect($candidate['reasoning'])->toBeArray();
        expect($candidate['reasoning'])->toHaveKey('skill_matches');
        expect($candidate['reasoning'])->toHaveKey('capacity_analysis');
    }
});
