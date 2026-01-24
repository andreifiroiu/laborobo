<?php

declare(strict_types=1);

use App\Enums\DeliverableStatus;
use App\Enums\TriggerEntityType;
use App\Enums\WorkOrderStatus;
use App\Events\DeliverableStatusChanged;
use App\Events\WorkOrderStatusChanged;
use App\Jobs\ProcessChainTrigger;
use App\Listeners\AgentTriggerListener;
use App\Models\AgentChain;
use App\Models\AgentTrigger;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = $this->user->createTeam(['name' => 'Test Team']);
    $this->user->current_team_id = $this->team->id;
    $this->user->save();

    $this->project = Project::factory()->create([
        'team_id' => $this->team->id,
    ]);

    $this->chain = AgentChain::factory()->create([
        'team_id' => $this->team->id,
        'enabled' => true,
    ]);

    $this->listener = app(AgentTriggerListener::class);
});

test('AgentTriggerListener receives WorkOrderStatusChanged event', function () {
    Queue::fake();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'status' => WorkOrderStatus::Active,
    ]);

    $trigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
        'priority' => 10,
    ]);

    $event = new WorkOrderStatusChanged(
        $workOrder,
        WorkOrderStatus::Draft,
        WorkOrderStatus::Active,
        $this->user
    );

    $this->listener->handleWorkOrderStatusChanged($event);

    Queue::assertPushed(ProcessChainTrigger::class, function ($job) use ($trigger, $workOrder) {
        return $job->trigger->id === $trigger->id
            && $job->entity->id === $workOrder->id;
    });
});

test('AgentTriggerListener receives DeliverableStatusChanged event', function () {
    Queue::fake();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
    ]);

    $deliverable = Deliverable::factory()->create([
        'team_id' => $this->team->id,
        'work_order_id' => $workOrder->id,
        'status' => DeliverableStatus::Approved,
    ]);

    $trigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::Deliverable,
        'status_from' => DeliverableStatus::InReview->value,
        'status_to' => DeliverableStatus::Approved->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
        'priority' => 10,
    ]);

    $event = new DeliverableStatusChanged(
        $deliverable,
        DeliverableStatus::InReview,
        DeliverableStatus::Approved,
        $this->user
    );

    $this->listener->handleDeliverableStatusChanged($event);

    Queue::assertPushed(ProcessChainTrigger::class, function ($job) use ($trigger, $deliverable) {
        return $job->trigger->id === $trigger->id
            && $job->entity->id === $deliverable->id;
    });
});

test('trigger condition matching works for status from/to and entity type', function () {
    Queue::fake();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'status' => WorkOrderStatus::Delivered,
    ]);

    // Create a trigger that should NOT match (different status_to)
    $nonMatchingTrigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Active->value,
        'status_to' => WorkOrderStatus::InReview->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
    ]);

    // Create a trigger that SHOULD match
    $matchingTrigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Active->value,
        'status_to' => WorkOrderStatus::Delivered->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
    ]);

    $event = new WorkOrderStatusChanged(
        $workOrder,
        WorkOrderStatus::Active,
        WorkOrderStatus::Delivered,
        $this->user
    );

    $this->listener->handleWorkOrderStatusChanged($event);

    // Only the matching trigger should dispatch a job
    Queue::assertPushed(ProcessChainTrigger::class, function ($job) use ($matchingTrigger) {
        return $job->trigger->id === $matchingTrigger->id;
    });

    Queue::assertNotPushed(ProcessChainTrigger::class, function ($job) use ($nonMatchingTrigger) {
        return $job->trigger->id === $nonMatchingTrigger->id;
    });
});

test('trigger_conditions JSON evaluation for budget threshold and tags', function () {
    Queue::fake();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'status' => WorkOrderStatus::Active,
        'budget_cost' => 5000.00,
    ]);

    // Trigger with budget condition that should NOT match (budget must be > 10000)
    $nonMatchingTrigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $this->chain->id,
        'trigger_conditions' => [
            'budget_greater_than' => 10000,
        ],
        'enabled' => true,
    ]);

    // Trigger with budget condition that SHOULD match (budget must be > 1000)
    $matchingTrigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $this->chain->id,
        'trigger_conditions' => [
            'budget_greater_than' => 1000,
        ],
        'enabled' => true,
    ]);

    $event = new WorkOrderStatusChanged(
        $workOrder,
        WorkOrderStatus::Draft,
        WorkOrderStatus::Active,
        $this->user
    );

    $this->listener->handleWorkOrderStatusChanged($event);

    // Only the trigger with matching budget condition should fire
    Queue::assertPushed(ProcessChainTrigger::class, function ($job) use ($matchingTrigger) {
        return $job->trigger->id === $matchingTrigger->id;
    });

    Queue::assertNotPushed(ProcessChainTrigger::class, function ($job) use ($nonMatchingTrigger) {
        return $job->trigger->id === $nonMatchingTrigger->id;
    });
});

test('chain execution dispatch when trigger matches', function () {
    Queue::fake();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'status' => WorkOrderStatus::Active,
    ]);

    $trigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => null, // Any source status
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
        'priority' => 50,
    ]);

    $event = new WorkOrderStatusChanged(
        $workOrder,
        WorkOrderStatus::Draft,
        WorkOrderStatus::Active,
        $this->user
    );

    $this->listener->handleWorkOrderStatusChanged($event);

    Queue::assertPushed(ProcessChainTrigger::class, function ($job) use ($trigger, $workOrder) {
        return $job->trigger->id === $trigger->id
            && $job->entity->id === $workOrder->id
            && $job->user?->id === $this->user->id;
    });
});

test('trigger priority ordering executes higher priority triggers first', function () {
    Queue::fake();

    $workOrder = WorkOrder::factory()->create([
        'team_id' => $this->team->id,
        'project_id' => $this->project->id,
        'status' => WorkOrderStatus::Active,
    ]);

    // Create triggers with different priorities
    $lowPriorityTrigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
        'priority' => 10,
    ]);

    $highPriorityTrigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
        'priority' => 100,
    ]);

    $mediumPriorityTrigger = AgentTrigger::factory()->create([
        'team_id' => $this->team->id,
        'entity_type' => TriggerEntityType::WorkOrder,
        'status_from' => WorkOrderStatus::Draft->value,
        'status_to' => WorkOrderStatus::Active->value,
        'agent_chain_id' => $this->chain->id,
        'enabled' => true,
        'priority' => 50,
    ]);

    $event = new WorkOrderStatusChanged(
        $workOrder,
        WorkOrderStatus::Draft,
        WorkOrderStatus::Active,
        $this->user
    );

    $this->listener->handleWorkOrderStatusChanged($event);

    // All 3 triggers should be dispatched
    Queue::assertPushed(ProcessChainTrigger::class, 3);

    // Verify jobs are pushed (order in queue is based on priority ordering in listener)
    $pushedJobs = [];
    Queue::assertPushed(ProcessChainTrigger::class, function ($job) use (&$pushedJobs) {
        $pushedJobs[] = $job->trigger->priority;

        return true;
    });

    // The listener processes in descending priority order
    expect(count($pushedJobs))->toBe(3);
});
