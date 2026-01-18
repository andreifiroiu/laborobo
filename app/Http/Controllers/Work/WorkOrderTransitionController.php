<?php

declare(strict_types=1);

namespace App\Http\Controllers\Work;

use App\Enums\WorkOrderStatus;
use App\Exceptions\InvalidTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionRequest;
use App\Models\WorkOrder;
use App\Services\WorkflowTransitionService;
use Illuminate\Http\JsonResponse;

class WorkOrderTransitionController extends Controller
{
    public function __construct(
        private readonly WorkflowTransitionService $transitionService,
    ) {}

    /**
     * Transition a work order to a new status.
     */
    public function transition(TransitionRequest $request, WorkOrder $workOrder): JsonResponse
    {
        $this->authorize('update', $workOrder);

        $toStatus = WorkOrderStatus::from($request->validated('status'));
        $comment = $request->validated('comment');

        try {
            $this->transitionService->transition(
                item: $workOrder,
                actor: $request->user(),
                toStatus: $toStatus,
                comment: $comment,
            );
        } catch (InvalidTransitionException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'reason' => $e->reason,
                'from_status' => $e->fromStatus,
                'to_status' => $e->toStatus,
            ], 422);
        }

        // Reload work order with transitions
        $workOrder->refresh();
        $workOrder->load(['statusTransitions' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }]);

        return response()->json([
            'message' => 'Work order status updated successfully.',
            'workOrder' => [
                'id' => (string) $workOrder->id,
                'title' => $workOrder->title,
                'status' => $workOrder->status->value,
                'statusLabel' => $workOrder->status->label(),
                'statusColor' => $workOrder->status->color(),
                'statusTransitions' => $workOrder->statusTransitions->map(fn ($transition) => [
                    'id' => (string) $transition->id,
                    'from_status' => $transition->from_status,
                    'to_status' => $transition->to_status,
                    'comment' => $transition->comment,
                    'created_at' => $transition->created_at->toIso8601String(),
                    'user_id' => $transition->user_id ? (string) $transition->user_id : null,
                ]),
            ],
        ]);
    }
}
