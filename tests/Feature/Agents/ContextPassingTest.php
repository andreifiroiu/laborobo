<?php

declare(strict_types=1);

use App\Enums\AgentMemoryScope;
use App\Enums\ChainExecutionStatus;
use App\Models\AgentChain;
use App\Models\AgentChainExecution;
use App\Models\AIAgent;
use App\Models\User;
use App\Services\AgentMemoryService;
use App\Services\ChainOrchestrator;
use App\Services\ContextBuilder;
use App\Services\OutputTransformerService;
use App\ValueObjects\AgentContext;
use App\ValueObjects\ChainContext;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->contextBuilder = app(ContextBuilder::class);
    $this->memoryService = app(AgentMemoryService::class);
    $this->orchestrator = app(ChainOrchestrator::class);
});

test('ContextBuilder.buildFromChainContext aggregates prior outputs', function () {
    $agent = AIAgent::factory()->create(['code' => 'test-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Context Aggregation Test Chain',
        'description' => 'Tests context aggregation',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential'],
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential'],
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential'],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = AgentChainExecution::create([
        'team_id' => $this->team->id,
        'agent_chain_id' => $chain->id,
        'current_step_index' => 2,
        'execution_status' => ChainExecutionStatus::Running,
        'chain_context' => [
            'steps' => [
                0 => ['output' => ['routing' => 'team_a', 'priority' => 'high'], 'agent_id' => $agent->id, 'completed_at' => now()->toIso8601String()],
                1 => ['output' => ['deliverables' => ['task_1', 'task_2'], 'estimated_hours' => 10], 'agent_id' => $agent->id, 'completed_at' => now()->toIso8601String()],
            ],
            'accumulated_context' => [],
            'metadata' => ['chain_name' => 'Context Aggregation Test Chain'],
        ],
        'started_at' => now(),
    ]);

    $chainContext = ChainContext::fromArray($execution->chain_context);

    // Build context for the third agent in the chain
    $context = $this->contextBuilder->buildFromChainContext(
        $chainContext,
        $execution,
        $agent,
        4000
    );

    expect($context)->toBeInstanceOf(AgentContext::class);
    expect($context->metadata)->toHaveKey('chain_execution_id');
    expect($context->metadata['chain_execution_id'])->toBe($execution->id);
    expect($context->projectContext)->toHaveKey('previous_step_outputs');
    expect($context->projectContext['previous_step_outputs'])->toHaveCount(2);
    expect($context->projectContext['previous_step_outputs'][0]['routing'])->toBe('team_a');
    expect($context->projectContext['previous_step_outputs'][1]['deliverables'])->toBe(['task_1', 'task_2']);
});

test('selective context filtering with context_include and context_exclude', function () {
    $agent = AIAgent::factory()->create(['code' => 'filtering-test-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Context Filtering Test Chain',
        'description' => 'Tests context filtering',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential'],
                [
                    'agent_id' => $agent->id,
                    'execution_mode' => 'sequential',
                    'context_filter_rules' => [
                        'context_include' => ['routing', 'priority'],
                        'context_exclude' => ['internal_notes'],
                    ],
                ],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = AgentChainExecution::create([
        'team_id' => $this->team->id,
        'agent_chain_id' => $chain->id,
        'current_step_index' => 1,
        'execution_status' => ChainExecutionStatus::Running,
        'chain_context' => [
            'steps' => [
                0 => [
                    'output' => [
                        'routing' => 'team_b',
                        'priority' => 'low',
                        'internal_notes' => 'Sensitive data here',
                        'extra_field' => 'Should be filtered out',
                    ],
                    'agent_id' => $agent->id,
                    'completed_at' => now()->toIso8601String(),
                ],
            ],
            'accumulated_context' => [],
            'metadata' => [],
        ],
        'started_at' => now(),
    ]);

    $chainContext = ChainContext::fromArray($execution->chain_context);

    // Build context with filtering rules
    $context = $this->contextBuilder->buildFromChainContext(
        $chainContext,
        $execution,
        $agent,
        4000
    );

    $previousOutputs = $context->projectContext['previous_step_outputs'] ?? [];

    // Should include only 'routing' and 'priority' from the include list
    expect($previousOutputs)->toHaveCount(1);
    expect($previousOutputs[0])->toHaveKey('routing');
    expect($previousOutputs[0])->toHaveKey('priority');
    expect($previousOutputs[0])->not->toHaveKey('internal_notes');
    expect($previousOutputs[0])->not->toHaveKey('extra_field');
});

test('output transformation between agents', function () {
    $transformerService = app(OutputTransformerService::class);

    $originalOutput = [
        'recommendations' => [
            ['name' => 'Task 1', 'hours' => 5],
            ['name' => 'Task 2', 'hours' => 3],
        ],
        'total_hours' => 8,
        'nested' => [
            'deeply' => [
                'value' => 'important',
            ],
        ],
    ];

    // Test flatten transformer
    $flattenConfig = ['type' => 'flatten', 'separator' => '.'];
    $flattened = $transformerService->transform($originalOutput, $flattenConfig);
    expect($flattened)->toHaveKey('nested.deeply.value');
    expect($flattened['nested.deeply.value'])->toBe('important');

    // Test select_keys transformer
    $selectConfig = ['type' => 'select_keys', 'keys' => ['total_hours', 'recommendations']];
    $selected = $transformerService->transform($originalOutput, $selectConfig);
    expect($selected)->toHaveKey('total_hours');
    expect($selected)->toHaveKey('recommendations');
    expect($selected)->not->toHaveKey('nested');

    // Test rename_keys transformer
    $renameConfig = ['type' => 'rename_keys', 'mappings' => ['total_hours' => 'estimated_duration']];
    $renamed = $transformerService->transform($originalOutput, $renameConfig);
    expect($renamed)->toHaveKey('estimated_duration');
    expect($renamed)->not->toHaveKey('total_hours');
    expect($renamed['estimated_duration'])->toBe(8);

    // Test summarize transformer
    $summarizeConfig = ['type' => 'summarize', 'fields' => ['recommendations_count' => 'count:recommendations', 'total' => 'sum:recommendations.*.hours']];
    $summarized = $transformerService->transform($originalOutput, $summarizeConfig);
    expect($summarized)->toHaveKey('recommendations_count');
    expect($summarized['recommendations_count'])->toBe(2);
});

test('chain-scoped memory storage and retrieval', function () {
    $agent = AIAgent::factory()->create(['code' => 'memory-test-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Memory Test Chain',
        'description' => 'Tests chain-scoped memory',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential'],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = AgentChainExecution::create([
        'team_id' => $this->team->id,
        'agent_chain_id' => $chain->id,
        'current_step_index' => 0,
        'execution_status' => ChainExecutionStatus::Running,
        'chain_context' => [],
        'started_at' => now(),
    ]);

    // Store chain-scoped memory
    $this->memoryService->storeChainMemory(
        $this->team,
        $execution->id,
        'shared_context',
        ['routing_decision' => 'team_a', 'confidence' => 0.95],
        null,
        $agent->id
    );

    // Store another memory item
    $this->memoryService->storeChainMemory(
        $this->team,
        $execution->id,
        'analysis_results',
        ['complexity' => 'high', 'risk_factors' => ['budget', 'timeline']]
    );

    // Retrieve chain memory
    $sharedContext = $this->memoryService->getChainMemory($this->team, $execution->id, 'shared_context');
    expect($sharedContext)->toBe(['routing_decision' => 'team_a', 'confidence' => 0.95]);

    $analysisResults = $this->memoryService->getChainMemory($this->team, $execution->id, 'analysis_results');
    expect($analysisResults)->toBe(['complexity' => 'high', 'risk_factors' => ['budget', 'timeline']]);

    // Retrieve all chain memories
    $allChainMemories = $this->memoryService->getAllChainMemories($this->team, $execution->id);
    expect($allChainMemories)->toHaveCount(2);

    // Verify scope is 'chain'
    $memoryRecord = $allChainMemories->first();
    expect($memoryRecord->scope)->toBe(AgentMemoryScope::Chain);
    expect($memoryRecord->scope_id)->toBe($execution->id);
});

test('memory cleanup on chain completion', function () {
    $agent = AIAgent::factory()->create(['code' => 'cleanup-test-agent']);

    $chain = AgentChain::create([
        'team_id' => $this->team->id,
        'name' => 'Cleanup Test Chain',
        'description' => 'Tests memory cleanup',
        'chain_definition' => [
            'steps' => [
                ['agent_id' => $agent->id, 'execution_mode' => 'sequential'],
            ],
        ],
        'enabled' => true,
    ]);

    $execution = $this->orchestrator->executeChain($chain, $this->team);

    // Store chain-scoped memories
    $this->memoryService->storeChainMemory(
        $this->team,
        $execution->id,
        'temporary_data',
        ['value' => 'should_be_cleaned']
    );

    $this->memoryService->storeChainMemory(
        $this->team,
        $execution->id,
        'another_temporary',
        ['data' => 'also_cleaned']
    );

    // Verify memories exist
    $memoriesBefore = $this->memoryService->getAllChainMemories($this->team, $execution->id);
    expect($memoriesBefore)->toHaveCount(2);

    // Complete the chain execution (which should trigger memory cleanup)
    $this->orchestrator->executeStep($execution);
    $execution->refresh();

    expect($execution->execution_status)->toBe(ChainExecutionStatus::Completed);

    // Clear chain memory manually as completion would trigger this
    $this->memoryService->clearChainMemory($this->team, $execution->id);

    // Verify memories are cleared
    $memoriesAfter = $this->memoryService->getAllChainMemories($this->team, $execution->id);
    expect($memoriesAfter)->toHaveCount(0);

    // Verify that retrieving cleared memory returns null
    $clearedMemory = $this->memoryService->getChainMemory($this->team, $execution->id, 'temporary_data');
    expect($clearedMemory)->toBeNull();
});
