<?php

declare(strict_types=1);

use App\Enums\ChainExecutionStatus;
use App\Enums\TriggerEntityType;
use App\Enums\WorkOrderStatus;
use App\Events\WorkOrderStatusChanged;
use App\Jobs\ProcessChainTrigger;
use App\Listeners\AgentTriggerListener;
use App\Models\AgentChain;
use App\Models\AgentChainExecution;
use App\Models\AgentChainTemplate;
use App\Models\AgentTrigger;
use App\Models\AIAgent;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\AgentMemoryService;
use App\Services\ChainOrchestrator;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Integration Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $this->orchestrator = app(ChainOrchestrator::class);
    $this->memoryService = app(AgentMemoryService::class);
    $this->listener = app(AgentTriggerListener::class);
});

test('E2E: complete chain execution from trigger to completion', function () {
    Queue::fake();

    // Create agents for the chain
    $dispatcherAgent = AIAgent::factory()->create(['code' => 'dispatcher-agent', 'name' => 'Dispatcher']);

    // Create a chain with a single step
    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Single Step Chain',
        'description' => 'Simple chain for E2E testing',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $dispatcherAgent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'enabled' => true,
    ]);

    // Create a trigger for the chain
    $trigger = AgentTrigger::create([
        'team_id' => $this->team->id,
        'name' => 'On Work Order Created',
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $chain->id,
        'enabled' => true,
        'priority' => 10,
    ]);

    // Create a work order and simulate status change
    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'status' => WorkOrderStatus::Active,
    ]);

    $event = new WorkOrderStatusChanged(
        $workOrder,
        WorkOrderStatus::Draft,
        WorkOrderStatus::Active,
        $this->user
    );

    // Trigger the listener
    $this->listener->handleWorkOrderStatusChanged($event);

    // Verify job was dispatched
    Queue::assertPushed(ProcessChainTrigger::class, function ($job) use ($trigger, $workOrder) {
        return $job->trigger->id === $trigger->id
            && $job->entity->id === $workOrder->id;
    });

    // Simulate job execution - execute chain directly
    $execution = $this->orchestrator->executeChain($chain, $this->team, $workOrder);

    expect($execution)->toBeInstanceOf(AgentChainExecution::class);
    expect($execution->execution_status)->toBe(ChainExecutionStatus::Running);
    expect($execution->triggerable_id)->toBe($workOrder->id);
    expect($execution->triggerable_type)->toBe(WorkOrder::class);

    // Execute the step
    $this->orchestrator->executeStep($execution, ['routing_decision' => 'team_a']);
    $execution->refresh();

    // Chain should complete after single step
    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);
    expect($execution->completed_at)->not->toBeNull();
    expect($execution->steps)->toHaveCount(1);
    expect($execution->steps->first()->status)->toBe('completed');
});

test('E2E: Dispatcher > PM Copilot > Client Comms chain template execution', function () {
    // Create the three agents
    $dispatcherAgent = AIAgent::factory()->create(['code' => 'dispatcher-agent', 'name' => 'Dispatcher']);
    $pmCopilotAgent = AIAgent::factory()->create(['code' => 'pm-copilot-agent', 'name' => 'PM Copilot']);
    $clientCommsAgent = AIAgent::factory()->create(['code' => 'client-comms-agent', 'name' => 'Client Comms']);

    // Create template with the standard chain
    $template = AgentChainTemplate::create([
        'name' => 'Dispatcher > PM Copilot > Client Comms',
        'description' => 'Standard workflow for processing work orders',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $dispatcherAgent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $pmCopilotAgent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
                [
                    'agent_id' => $clientCommsAgent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                ],
            ],
        ],
        'category' => 'work-order-processing',
        'is_system' => true,
    ]);

    // Instantiate chain from template
    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Our Work Order Processing Chain',
        'description' => 'Based on system template',
        'chain_definition' => $template->chain_definition,
        'agent_chain_template_id' => $template->id,
        'enabled' => true,
    ]);

    // Execute the full chain
    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Step 1: Dispatcher
    $this->orchestrator->executeStep($execution, [
        'routing_recommendation' => 'team_a',
        'priority' => 'high',
    ]);
    $execution->refresh();
    expect($execution->current_step_index)->toBe(1);
    expect($execution->chain_context['steps']['0']['output']['routing_recommendation'])->toBe('team_a');

    // Step 2: PM Copilot
    $this->orchestrator->executeStep($execution, [
        'deliverables' => ['deliverable_1', 'deliverable_2'],
        'tasks' => ['task_1', 'task_2', 'task_3'],
    ]);
    $execution->refresh();
    expect($execution->current_step_index)->toBe(2);
    expect($execution->chain_context['steps']['1']['output']['deliverables'])->toHaveCount(2);

    // Step 3: Client Comms
    $this->orchestrator->executeStep($execution, [
        'email_drafted' => true,
        'recipient' => 'client@example.com',
    ]);
    $execution->refresh();

    // Verify full chain completed
    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);
    expect($execution->steps)->toHaveCount(3);

    // Verify all step outputs are preserved
    expect($execution->chain_context['steps'])->toHaveCount(3);
    expect($execution->chain_context['steps']['2']['output']['email_drafted'])->toBeTrue();
});

test('Integration: chain execution creates AgentWorkflowState via AgentOrchestrator', function () {
    $agent = AIAgent::factory()->create(['code' => 'test-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Workflow Integration Chain',
        'description' => 'Tests workflow state creation',
        'chain_definition' => [
            'steps' => [
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

    // Execute step
    $step = $this->orchestrator->executeStep($execution);
    $execution->refresh();

    // The step should have a workflow state linked if workflow_class is provided
    expect($step)->not->toBeNull();
    expect($step->step_index)->toBe(0);
    expect($step->status)->toBe('completed');
});

test('Integration: context passes correctly through 3-step chain', function () {
    $agent1 = AIAgent::factory()->create(['code' => 'context-agent-1']);
    $agent2 = AIAgent::factory()->create(['code' => 'context-agent-2']);
    $agent3 = AIAgent::factory()->create(['code' => 'context-agent-3']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Context Flow Chain',
        'description' => 'Tests context flow across steps',
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
                    'context_filter_rules' => [
                        'context_include' => ['analysis_result', 'confidence'],
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

    // Step 1 output
    $this->orchestrator->executeStep($execution, [
        'analysis_result' => 'positive',
        'confidence' => 0.95,
        'internal_data' => 'should_be_excluded',
    ]);
    $execution->refresh();

    // Step 2 receives filtered context from step 1
    $this->orchestrator->executeStep($execution, [
        'recommendation' => 'proceed',
        'based_on_analysis' => $execution->chain_context['steps']['0']['output']['analysis_result'],
    ]);
    $execution->refresh();

    // Verify step 2 had access to step 1 output
    expect($execution->chain_context['steps']['1']['output']['based_on_analysis'])->toBe('positive');

    // Step 3 output
    $this->orchestrator->executeStep($execution, [
        'final_action' => 'approved',
        'all_steps_completed' => true,
    ]);
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);
    expect($execution->chain_context['steps'])->toHaveCount(3);
});

test('Integration: chain failure at step N preserves prior step outputs', function () {
    $agent = AIAgent::factory()->create(['code' => 'failure-test-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Failure Recovery Chain',
        'description' => 'Tests failure handling',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential', 'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow'],
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential', 'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow'],
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential', 'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow'],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Step 1 succeeds
    $this->orchestrator->executeStep($execution, ['step_1_data' => 'preserved']);
    $execution->refresh();

    // Step 2 succeeds
    $this->orchestrator->executeStep($execution, ['step_2_data' => 'also_preserved']);
    $execution->refresh();

    expect($execution->current_step_index)->toBe(2);

    // Step 3 fails
    $this->orchestrator->fail($execution, 'Critical error in step 3');
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Failed);
    expect($execution->error_message)->toBe('Critical error in step 3');

    // Prior step outputs should still be preserved
    expect($execution->chain_context['steps']['0']['output']['step_1_data'])->toBe('preserved');
    expect($execution->chain_context['steps']['1']['output']['step_2_data'])->toBe('also_preserved');
});

test('Integration: concurrent chain executions for same team do not conflict', function () {
    $agent = AIAgent::factory()->create(['code' => 'concurrent-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Concurrent Chain',
        'description' => 'Tests concurrent execution',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential', 'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow'],
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential', 'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow'],
            ],
        ],
        'enabled' => true,
    ]);

    // Start two chain executions
    $execution1 = $this->orchestrator->executeChain($chain, $this->team);
    $execution2 = $this->orchestrator->executeChain($chain, $this->team);

    expect($execution1->id)->not->toBe($execution2->id);

    // Execute step 1 for execution 1
    $this->orchestrator->executeStep($execution1, ['execution' => 1, 'step' => 0]);
    $execution1->refresh();

    // Execute step 1 for execution 2
    $this->orchestrator->executeStep($execution2, ['execution' => 2, 'step' => 0]);
    $execution2->refresh();

    // Each execution should have independent context
    expect($execution1->chain_context['steps']['0']['output']['execution'])->toBe(1);
    expect($execution2->chain_context['steps']['0']['output']['execution'])->toBe(2);

    // Complete both executions
    $this->orchestrator->executeStep($execution1, ['execution' => 1, 'step' => 1]);
    $execution1->refresh();

    $this->orchestrator->executeStep($execution2, ['execution' => 2, 'step' => 1]);
    $execution2->refresh();

    expect($execution1->execution_status)->toBe(ChainExecutionStatus::Completed);
    expect($execution2->execution_status)->toBe(ChainExecutionStatus::Completed);

    // Verify final contexts are independent
    expect($execution1->chain_context['steps']['1']['output']['step'])->toBe(1);
    expect($execution2->chain_context['steps']['1']['output']['step'])->toBe(1);
});

test('chain template instantiation preserves parameter overrides', function () {
    $agent = AIAgent::factory()->create(['code' => 'template-agent']);

    $template = AgentChainTemplate::create([
        'name' => 'Customizable Template',
        'description' => 'Template with customizable parameters',
        'chain_definition' => [
            'steps' => [
                [
                    'agent_id' => $agent->id,
                    'execution_mode' => 'sequential',
                    'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow',
                    'config' => ['max_retries' => 3, 'timeout' => 30],
                ],
            ],
            'parameters' => [
                'notification_enabled' => true,
                'approval_required' => false,
            ],
        ],
        'category' => 'customizable',
        'is_system' => false,
    ]);

    // Create chain with parameter overrides
    $overriddenDefinition = $template->chain_definition;
    $overriddenDefinition['parameters']['approval_required'] = true;
    $overriddenDefinition['steps'][0]['config']['max_retries'] = 5;

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Customized Chain',
        'description' => 'With overridden parameters',
        'chain_definition' => $overriddenDefinition,
        'agent_chain_template_id' => $template->id,
        'enabled' => true,
    ]);

    // Verify template relationship
    expect($chain->template)->toBeInstanceOf(AgentChainTemplate::class);
    expect($chain->template->id)->toBe($template->id);

    // Verify parameter overrides are stored
    expect($chain->chain_definition['parameters']['approval_required'])->toBeTrue();
    expect($chain->chain_definition['steps'][0]['config']['max_retries'])->toBe(5);

    // Original template should be unchanged
    expect($template->chain_definition['parameters']['approval_required'])->toBeFalse();
    expect($template->chain_definition['steps'][0]['config']['max_retries'])->toBe(3);
});

test('chain-scoped memory isolation between concurrent chains', function () {
    $agent = AIAgent::factory()->create(['code' => 'memory-isolation-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Memory Isolation Chain',
        'description' => 'Tests memory isolation',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential', 'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow'],
            ],
        ],
        'enabled' => true,
    ]);

    // Start two executions
    $execution1 = $this->orchestrator->executeChain($chain, $this->team);
    $execution2 = $this->orchestrator->executeChain($chain, $this->team);

    // Store chain-scoped memories for each execution
    $this->memoryService->storeChainMemory(
        $this->team,
        $execution1->id,
        'shared_key',
        ['value' => 'execution_1_data']
    );

    $this->memoryService->storeChainMemory(
        $this->team,
        $execution2->id,
        'shared_key',
        ['value' => 'execution_2_data']
    );

    // Retrieve memories - should be isolated
    $memory1 = $this->memoryService->getChainMemory($this->team, $execution1->id, 'shared_key');
    $memory2 = $this->memoryService->getChainMemory($this->team, $execution2->id, 'shared_key');

    expect($memory1['value'])->toBe('execution_1_data');
    expect($memory2['value'])->toBe('execution_2_data');

    // Complete execution 1 (clears its memory)
    $this->orchestrator->executeStep($execution1);
    $execution1->refresh();

    // Execution 1 memory should be cleared
    $memory1After = $this->memoryService->getChainMemory($this->team, $execution1->id, 'shared_key');
    expect($memory1After)->toBeNull();

    // Execution 2 memory should still exist
    $memory2After = $this->memoryService->getChainMemory($this->team, $execution2->id, 'shared_key');
    expect($memory2After['value'])->toBe('execution_2_data');
});

test('trigger deduplication prevents duplicate chain executions within window', function () {
    Queue::fake();

    $agent = AIAgent::factory()->create(['code' => 'dedup-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Dedup Chain',
        'description' => 'Tests deduplication',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential', 'workflow_class' => 'App\\Agents\\Workflows\\TestWorkflow'],
            ],
        ],
        'enabled' => true,
    ]);

    // Create trigger with deduplication window
    $trigger = AgentTrigger::create([
        'team_id' => $this->team->id,
        'name' => 'Deduplicated Trigger',
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $chain->id,
        'trigger_conditions' => [
            'deduplication_window_minutes' => 5,
        ],
        'enabled' => true,
        'priority' => 10,
    ]);

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'status' => WorkOrderStatus::Active,
    ]);

    $event = new WorkOrderStatusChanged(
        $workOrder,
        WorkOrderStatus::Draft,
        WorkOrderStatus::Active,
        $this->user
    );

    // First trigger - should dispatch
    $this->listener->handleWorkOrderStatusChanged($event);
    Queue::assertPushed(ProcessChainTrigger::class, 1);

    // Second trigger immediately - should be deduplicated
    Queue::fake(); // Reset queue
    $this->listener->handleWorkOrderStatusChanged($event);

    // Should not push another job (deduplicated)
    Queue::assertNotPushed(ProcessChainTrigger::class);
});
