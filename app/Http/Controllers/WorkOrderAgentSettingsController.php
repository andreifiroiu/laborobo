<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PMCopilotMode;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Controller for managing work order AI agent settings.
 *
 * Handles configuration of PM Copilot mode and other AI-related
 * settings at the work order level.
 */
class WorkOrderAgentSettingsController extends Controller
{
    /**
     * Update agent settings for a work order.
     *
     * Currently supports updating the PM Copilot mode (staged/full).
     */
    public function update(Request $request, WorkOrder $workOrder): JsonResponse
    {
        $this->authorize('update', $workOrder);

        $validated = $request->validate([
            'pm_copilot_mode' => [
                'sometimes',
                'required',
                'string',
                Rule::in(array_column(PMCopilotMode::cases(), 'value')),
            ],
        ]);

        try {
            $updateData = [];

            if (isset($validated['pm_copilot_mode'])) {
                $updateData['pm_copilot_mode'] = $validated['pm_copilot_mode'];
            }

            if (! empty($updateData)) {
                $workOrder->update($updateData);
            }

            Log::info('Work order agent settings updated', [
                'work_order_id' => $workOrder->id,
                'updated_settings' => $updateData,
                'user_id' => $request->user()->id,
            ]);

            $workOrder->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Agent settings updated',
                'work_order' => [
                    'id' => (string) $workOrder->id,
                    'pm_copilot_mode' => $workOrder->pm_copilot_mode->value,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Work order agent settings update failed', [
                'work_order_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update agent settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
