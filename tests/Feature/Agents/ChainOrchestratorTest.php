<?php

declare(strict_types=1);

use App\Enums\ChainExecutionStatus;
use App\Models\AgentChain;
use App\Models\AgentChainExecution;
use App\Models\AIAgent;
use App\Models\User;
use App\Services\ChainOrchestrator;
use App\ValueObjects\ChainContext;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->orchestrator = app(ChainOrchestrator::class);
});

test('sequential chain execution completes 3-step chain in order', function () {
    // Create 3 agents for the chain
    $agent1 = AIAgent::factory()->create(['code' => 'dispatcher-agent', 'name' => 'Dispatcher']);
    $agent2 = AIAgent::factory()->create(['code' => 'pm-copilot-agent', 'name' => 'PM Copilot']);
    $agent3 = AIAgent::factory()->create(['code' => 'client-comms-agent', 'name' => 'Client Comms']);

    // Create a 3-step chain
    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Full Workflow Chain',
        'description' => 'Dispatcher > PM Copilot > Client Comms',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $agent1->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $agent2->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $agent3->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'is_template' => false,
        'enabled' => true,
    ]);

    // Start the chain execution
    $execution = $this->orchestrator->executeChain($chain, $this->team);

    expect($execution)->toBeInstanceOf(AgentChainExecution::class);
    expect($execution->execution_status)->toBe(ChainExecutionStatus::Running);
    expect($execution->current_step_index)->toBe(0);
    expect($execution->started_at)->not->toBeNull();

    // Execute first step
    $this->orchestrator->executeStep($execution);
    $execution->refresh();

    expect($execution->current_step_index)->toBe(1);
    expect($execution->steps)->toHaveCount(1);
    expect($execution->steps->first()->step_index)->toBe(0);
    expect($execution->steps->first()->status)->toBe('completed');

    // Execute second step
    $this->orchestrator->executeStep($execution);
    $execution->refresh();

    expect($execution->current_step_index)->toBe(2);
    expect($execution->steps)->toHaveCount(2);

    // Execute third step
    $this->orchestrator->executeStep($execution);
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);
    expect($execution->completed_at)->not->toBeNull();
    expect($execution->steps)->toHaveCount(3);

    // Verify steps executed in order
    $stepIndices = $execution->steps->pluck('step_index')->toArray();
    expect($stepIndices)->toBe([0, 1, 2]);
});

test('chain context accumulates outputs across steps', function () {
    $agent1 = AIAgent::factory()->create(['code' => 'agent-1']);
    $agent2 = AIAgent::factory()->create(['code' => 'agent-2']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Context Accumulation Chain',
        'description' => 'Tests context passing between steps',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $agent1->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $agent2->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Execute first step with output
    $this->orchestrator->executeStep($execution, ['routing_recommendation' => 'team_a']);
    $execution->refresh();

    // Check that chain context has first step output
    expect($execution->chain_context)->toHaveKey('steps');
    expect($execution->chain_context['steps'])->toHaveKey('0');
    expect($execution->chain_context['steps']['0']['output'])->toHaveKey('routing_recommendation');

    // Execute second step with additional output
    $this->orchestrator->executeStep($execution, ['deliverables_created' => 3]);
    $execution->refresh();

    // Check accumulated context
    expect($execution->chain_context['steps'])->toHaveKey('1');
    expect($execution->chain_context['steps']['0']['output']['routing_recommendation'])->toBe('team_a');
    expect($execution->chain_context['steps']['1']['output']['deliverables_created'])->toBe(3);

    // Verify ChainContext value object
    $chainContext = ChainContext::fromArray($execution->chain_context);
    expect($chainContext->getOutputForStep(0))->toBe(['routing_recommendation' => 'team_a']);
    expect($chainContext->getOutputForStep(1))->toBe(['deliverables_created' => 3]);
});

test('conditional branching evaluates based on agent output', function () {
    $agent1 = AIAgent::factory()->create(['code' => 'decision-agent']);
    $agent2 = AIAgent::factory()->create(['code' => 'approved-path-agent']);
    $agent3 = AIAgent::factory()->create(['code' => 'rejected-path-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Branching Chain',
        'description' => 'Tests conditional branching',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $agent1->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                    'next_step_conditions' => [
                        [
                            'condition' => 'steps.0.output.recommendation == "approved"',
                            'action' => 'goto',
                            'target_step' => 1,
                        ],
                        [
                            'condition' => 'steps.0.output.recommendation == "rejected"',
                            'action' => 'goto',
                            'target_step' => 2,
                        ],
                    ],
                ],
                [
                    'agent_id' => $agent2->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                    'next_step_conditions' => [
                        [
                            'action' => 'terminate',
                        ],
                    ],
                ],
                [
                    'agent_id' => $agent3->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Execute first step with "approved" output
    $this->orchestrator->executeStep($execution, ['recommendation' => 'approved']);
    $execution->refresh();

    // Should branch to step 1 (approved path)
    expect($execution->current_step_index)->toBe(1);

    // Execute approved path step
    $this->orchestrator->executeStep($execution, ['processed' => true]);
    $execution->refresh();

    // Should terminate after approved path
    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);

    // Now test with "rejected" output
    $execution2 = $this->orchestrator->executeChain($chain, $this->team);
    $this->orchestrator->executeStep($execution2, ['recommendation' => 'rejected']);
    $execution2->refresh();

    // Should branch to step 2 (rejected path)
    expect($execution2->current_step_index)->toBe(2);
});

test('chain level pause and resume works independently of workflow pauses', function () {
    $agent = AIAgent::factory()->create(['code' => 'pausable-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Pausable Chain',
        'description' => 'Tests pause/resume at chain level',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $agent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $agent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Execute first step
    $this->orchestrator->executeStep($execution);
    $execution->refresh();

    expect($execution->current_step_index)->toBe(1);

    // Pause the chain
    $this->orchestrator->pause($execution, 'Awaiting manager review');
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Paused);
    expect($execution->paused_at)->not->toBeNull();
    expect($execution->chain_context['pause_reason'])->toBe('Awaiting manager review');

    // Resume the chain
    $this->orchestrator->resume($execution, ['approved_by' => 'manager@example.com']);
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Running);
    expect($execution->resumed_at)->not->toBeNull();
    expect($execution->chain_context['resume_data']['approved_by'])->toBe('manager@example.com');

    // Continue execution
    $this->orchestrator->executeStep($execution);
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);
});

test('chain failure handling marks chain as failed when step fails', function () {
    $agent = AIAgent::factory()->create(['code' => 'failing-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Failure Test Chain',
        'description' => 'Tests failure handling',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $agent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $agent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Execute first step successfully
    $this->orchestrator->executeStep($execution);
    $execution->refresh();

    expect($execution->current_step_index)->toBe(1);

    // Fail the chain during second step
    $this->orchestrator->fail($execution, 'Agent encountered an unrecoverable error');
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Failed);
    expect($execution->failed_at)->not->toBeNull();
    expect($execution->error_message)->toBe('Agent encountered an unrecoverable error');

    // Verify the step is marked as failed
    $failedStep = $execution->steps()->where('step_index', 1)->first();
    expect($failedStep)->not->toBeNull();
    expect($failedStep->status)->toBe('failed');
});

test('parallel step execution within same step group', function () {
    Queue::fake();

    $agent1 = AIAgent::factory()->create(['code' => 'parallel-agent-1']);
    $agent2 = AIAgent::factory()->create(['code' => 'parallel-agent-2']);
    $agent3 = AIAgent::factory()->create(['code' => 'sequential-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Parallel Execution Chain',
        'description' => 'Tests parallel step execution',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $agent1->id,
                    'execution_mode' => 'parallel',
                    'step_group' => 'parallel_group_1',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $agent2->id,
                    'execution_mode' => 'parallel',
                    'step_group' => 'parallel_group_1',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $agent3->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Execute parallel step group
    $this->orchestrator->executeParallelStepGroup($execution);
    $execution->refresh();

    // Both parallel steps should be created
    expect($execution->steps)->toHaveCount(2);
    expect($execution->steps->where('step_index', 0)->first())->not->toBeNull();
    expect($execution->steps->where('step_index', 1)->first())->not->toBeNull();

    // Mark parallel steps as completed
    $execution->steps->each(function ($step) {
        $step->update(['status' => 'completed', 'completed_at' => now()]);
    });

    // Complete parallel group and move to next step
    $this->orchestrator->completeParallelGroup($execution, 'parallel_group_1');
    $execution->refresh();

    // Should now be on the sequential step
    expect($execution->current_step_index)->toBe(2);
});
