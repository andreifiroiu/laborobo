<?php

declare(strict_types=1);

use App\Enums\AgentMemoryScope;
use App\Enums\AgentType;
use App\Enums\ChainExecutionStatus;
use App\Enums\TriggerEntityType;
use App\Models\AgentChain;
use App\Models\AgentChainExecution;
use App\Models\AgentChainExecutionStep;
use App\Models\AgentChainTemplate;
use App\Models\AgentTrigger;
use App\Models\AgentWorkflowState;
use App\Models\AIAgent;
use App\Models\User;
use App\Models\WorkOrder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();
});

test('agent chain can be created with valid chain_definition JSON', function () {
    $chainDefinition = [
        'steps' => [
            [
                'agent_id' => 1,
                'execution_mode' => 'sequential',
                'conditions' => [],
                'context_filter_rules' => [
                    'context_include' => ['work_order', 'project'],
                    'context_exclude' => [],
                ],
                'next_step_conditions' => [],
                'output_transformers' => [],
            ],
            [
                'agent_id' => 2,
                'execution_mode' => 'sequential',
                'conditions' => ['previous_step_completed' => true],
                'context_filter_rules' => [],
                'next_step_conditions' => [
                    'if_output_contains' => 'approved',
                    'goto_step' => 3,
                ],
                'output_transformers' => ['flatten'],
            ],
        ],
    ];

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Dispatcher > PM Copilot Chain',
        'description' => 'Routes work and then creates deliverables',
        'chain_definition' => $chainDefinition,
        'is_template' => false,
        'enabled' => true,
    ]);

    expect($chain->id)->toBeInt();
    expect($chain->name)->toBe('Dispatcher > PM Copilot Chain');
    expect($chain->chain_definition)->toBeArray();
    expect($chain->chain_definition['steps'])->toHaveCount(2);
    expect($chain->chain_definition['steps'][0]['agent_id'])->toBe(1);
    expect($chain->chain_definition['steps'][1]['execution_mode'])->toBe('sequential');
    expect($chain->is_template)->toBeFalse();
    expect($chain->enabled)->toBeTrue();
    expect($chain->team)->toBeInstanceOf(\App\Models\Team::class);
});

test('agent chain execution state transitions from pending to running to completed', function () {
    $agent = AIAgent::factory()->create();

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Test Chain',
        'description' => 'A test chain',
        'chain_definition' => ['steps' => []],
        'is_template' => false,
        'enabled' => true,
    ]);

    // Create execution in pending state
    $execution = AgentChainExecution::create([
        'team_id' => $this->team->id,
        'agent_chain_id' => $chain->id,
        'current_step_index' => 0,
        'execution_status' => ChainExecutionStatus::Pending,
        'chain_context' => [],
    ]);

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Pending);
    expect($execution->isPending())->toBeTrue();
    expect($execution->started_at)->toBeNull();

    // Transition to running
    $execution->update([
        'execution_status' => ChainExecutionStatus::Running,
        'started_at' => now(),
    ]);
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Running);
    expect($execution->isRunning())->toBeTrue();
    expect($execution->started_at)->not->toBeNull();

    // Transition to completed
    $execution->update([
        'execution_status' => ChainExecutionStatus::Completed,
        'completed_at' => now(),
    ]);
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);
    expect($execution->isCompleted())->toBeTrue();
    expect($execution->completed_at)->not->toBeNull();
});

test('agent chain execution has relationship to agent workflow state records', function () {
    $agent = AIAgent::factory()->create();

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Multi-Step Chain',
        'description' => 'Chain with multiple steps',
        'chain_definition' => ['steps' => [['agent_id' => $agent->id]]],
        'is_template' => false,
        'enabled' => true,
    ]);

    $execution = AgentChainExecution::create([
        'team_id' => $this->team->id,
        'agent_chain_id' => $chain->id,
        'current_step_index' => 0,
        'execution_status' => ChainExecutionStatus::Running,
        'chain_context' => ['accumulated_output' => []],
        'started_at' => now(),
    ]);

    // Create workflow state for the agent
    $workflowState = AgentWorkflowState::create([
        'team_id' => $this->team->id,
        'ai_agent_id' => $agent->id,
        'workflow_class' => 'App\\Agents\\Workflows\\DispatcherWorkflow',
        'current_node' => 'start',
        'state_data' => [],
        'approval_required' => false,
    ]);

    // Create execution step linking them
    $step = AgentChainExecutionStep::create([
        'agent_chain_execution_id' => $execution->id,
        'agent_workflow_state_id' => $workflowState->id,
        'step_index' => 0,
        'status' => 'running',
        'started_at' => now(),
        'output_data' => [],
    ]);

    expect($execution->steps)->toHaveCount(1);
    expect($execution->steps->first()->workflowState)->toBeInstanceOf(AgentWorkflowState::class);
    expect($step->chainExecution)->toBeInstanceOf(AgentChainExecution::class);
    expect($step->workflowState->workflow_class)->toBe('App\\Agents\\Workflows\\DispatcherWorkflow');
});

test('agent trigger condition matching for entity status transitions', function () {
    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Work Order Processing Chain',
        'description' => 'Triggered on work order status changes',
        'chain_definition' => ['steps' => []],
        'is_template' => false,
        'enabled' => true,
    ]);

    $trigger = AgentTrigger::create([
        'team_id' => $this->team->id,
        'name' => 'On Work Order Created',
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => null,
        'status_to' => 'active',
        'agent_chain_id' => $chain->id,
        'trigger_conditions' => [
            'budget_greater_than' => 1000,
            'has_tags' => ['priority', 'urgent'],
        ],
        'enabled' => true,
        'priority' => 10,
    ]);

    expect($trigger->id)->toBeInt();
    expect($trigger->entity_type)->toBe(TriggerEntityType::WorkOrder);
    expect($trigger->status_from)->toBeNull();
    expect($trigger->status_to)->toBe('active');
    expect($trigger->trigger_conditions)->toBeArray();
    expect($trigger->trigger_conditions['budget_greater_than'])->toBe(1000);
    expect($trigger->chain)->toBeInstanceOf(AgentChain::class);
    expect($trigger->enabled)->toBeTrue();
    expect($trigger->priority)->toBe(10);

    // Test scope for finding matching triggers
    $matchingTriggers = AgentTrigger::forTeam($this->team->id)
        ->enabled()
        ->forEntityType(TriggerEntityType::WorkOrder)
        ->forStatusTransition(null, 'active')
        ->get();

    expect($matchingTriggers)->toHaveCount(1);
    expect($matchingTriggers->first()->name)->toBe('On Work Order Created');
});

test('agent chain template can be instantiated to agent chain', function () {
    $template = AgentChainTemplate::create([
        'name' => 'Dispatcher > PM Copilot > Client Comms',
        'description' => 'Standard chain for processing new work orders',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => 1, 'execution_mode' => 'sequential'],
                ['agent_id' => 2, 'execution_mode' => 'sequential'],
                ['agent_id' => 3, 'execution_mode' => 'sequential'],
            ],
        ],
        'category' => 'work-order-processing',
        'is_system' => true,
    ]);

    expect($template->id)->toBeInt();
    expect($template->name)->toBe('Dispatcher > PM Copilot > Client Comms');
    expect($template->is_system)->toBeTrue();
    expect($template->chain_definition['steps'])->toHaveCount(3);

    // Instantiate a chain from the template
    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'My Custom Chain',
        'description' => 'Based on standard template',
        'chain_definition' => $template->chain_definition,
        'is_template' => false,
        'enabled' => true,
        'agent_chain_template_id' => $template->id,
    ]);

    expect($chain->template)->toBeInstanceOf(AgentChainTemplate::class);
    expect($chain->template->name)->toBe('Dispatcher > PM Copilot > Client Comms');
    expect($template->chains)->toHaveCount(1);
    expect($template->chains->first()->id)->toBe($chain->id);
});

test('agent memory scope enum includes chain value', function () {
    expect(AgentMemoryScope::cases())->toContain(AgentMemoryScope::Chain);
    expect(AgentMemoryScope::Chain->value)->toBe('chain');

    // Verify chain scope can be used alongside existing scopes
    expect(AgentMemoryScope::Project->value)->toBe('project');
    expect(AgentMemoryScope::Client->value)->toBe('client');
    expect(AgentMemoryScope::Org->value)->toBe('org');
    expect(AgentMemoryScope::Chain->value)->toBe('chain');
});
