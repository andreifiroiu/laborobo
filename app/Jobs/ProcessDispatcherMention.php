<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Agents\Workflows\DispatcherWorkflow;
use App\Enums\AuthorType;
use App\Enums\MessageType;
use App\Models\AgentConfiguration;
use App\Models\AIAgent;
use App\Models\CommunicationThread;
use App\Models\Message;
use App\Models\WorkOrder;
use App\Services\AgentOrchestrator;
use App\Services\ContextBuilder;
use App\Services\ThreadContextService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queue job for processing dispatcher agent mentions.
 *
 * Loads the full message thread context, builds the agent context
 * with work order data, and executes the DispatcherWorkflow.
 */
class ProcessDispatcherMention implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public readonly Message $message,
        public readonly CommunicationThread $thread,
        public readonly AIAgent $agent,
    ) {}

    /**
     * Execute the job.
     *
     * Processes the dispatcher mention by loading thread context,
     * building agent context, and executing the workflow.
     */
    public function handle(
        ThreadContextService $threadContextService,
        ContextBuilder $contextBuilder,
        AgentOrchestrator $orchestrator,
    ): void {
        Log::info('Processing dispatcher mention', [
            'message_id' => $this->message->id,
            'thread_id' => $this->thread->id,
            'agent_id' => $this->agent->id,
        ]);

        // Get the work order from the thread
        $workOrder = $this->getWorkOrderFromThread();

        if ($workOrder === null) {
            Log::warning('No work order found for thread', [
                'thread_id' => $this->thread->id,
            ]);

            return;
        }

        // Get agent configuration for the team
        $configuration = $this->getAgentConfiguration($workOrder->team_id);

        if ($configuration === null || ! $configuration->enabled) {
            Log::info('Dispatcher agent not configured or disabled for team', [
                'team_id' => $workOrder->team_id,
            ]);

            return;
        }

        // Load full thread context
        $threadContext = $threadContextService->getThreadContext($this->thread);
        $formattedThreadContext = $threadContextService->formatForSystemPrompt($this->thread);

        // Build agent context with work order data
        $agentContext = $contextBuilder->build($workOrder, $this->agent);

        // Add thread context to agent context
        $agentContext = $agentContext->withMetadata([
            'thread_id' => $this->thread->id,
            'thread_context' => $formattedThreadContext,
            'triggering_message_id' => $this->message->id,
        ]);

        // Execute the workflow
        $workflowState = $orchestrator->execute(
            DispatcherWorkflow::class,
            [
                'thread_id' => $this->thread->id,
                'work_order_id' => $workOrder->id,
                'message_count' => $this->thread->message_count,
                'thread_context' => $threadContext,
                'agent_context' => $agentContext->toArray(),
            ],
            $workOrder->team,
            $this->agent,
        );

        Log::info('Dispatcher workflow started', [
            'workflow_state_id' => $workflowState->id,
            'thread_id' => $this->thread->id,
            'work_order_id' => $workOrder->id,
        ]);

        // Post agent response to the thread
        $this->postAgentResponse($workflowState->state_data);
    }

    /**
     * Get the work order associated with the thread.
     */
    private function getWorkOrderFromThread(): ?WorkOrder
    {
        if ($this->thread->threadable_type !== WorkOrder::class) {
            return null;
        }

        return WorkOrder::find($this->thread->threadable_id);
    }

    /**
     * Get the agent configuration for a team.
     */
    private function getAgentConfiguration(int $teamId): ?AgentConfiguration
    {
        return AgentConfiguration::query()
            ->where('team_id', $teamId)
            ->where('ai_agent_id', $this->agent->id)
            ->first();
    }

    /**
     * Post the agent response to the thread.
     *
     * Creates a Message with author_type = 'ai_agent' and links
     * to the AIAgent via author_id.
     *
     * @param  array<string, mixed>  $stateData
     */
    private function postAgentResponse(array $stateData): void
    {
        $responseContent = [
            'type' => 'dispatcher_analysis',
            'status' => 'processing',
            'message' => 'Analyzing thread context and preparing routing recommendations...',
            'workflow_started_at' => $stateData['started_at'] ?? now()->toIso8601String(),
        ];

        // Add any extracted requirements if available
        if (isset($stateData['extracted_requirements'])) {
            $responseContent['extracted_requirements'] = $stateData['extracted_requirements'];
        }

        // Add routing candidates if available
        if (isset($stateData['routing_candidates'])) {
            $responseContent['routing_candidates'] = $stateData['routing_candidates'];
        }

        Message::create([
            'communication_thread_id' => $this->thread->id,
            'author_id' => $this->agent->id,
            'author_type' => AuthorType::AiAgent,
            'content' => json_encode($responseContent, JSON_PRETTY_PRINT),
            'type' => MessageType::Note,
        ]);

        $this->thread->increment('message_count');
        $this->thread->update(['last_activity' => now()]);

        Log::info('Agent response posted to thread', [
            'thread_id' => $this->thread->id,
            'agent_id' => $this->agent->id,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessDispatcherMention job failed', [
            'message_id' => $this->message->id,
            'thread_id' => $this->thread->id,
            'agent_id' => $this->agent->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
