<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\RaciUpdateRequest;
use App\Models\AuditLog;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class ProjectRaciController extends Controller
{
    /**
     * Update RACI assignments for a project.
     */
    public function update(RaciUpdateRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validated();
        $user = $request->user();
        $team = $user->currentTeam;
        $isConfirmed = $validated['confirmed'] ?? false;

        // Build the update data from validated fields
        $updateData = $this->buildUpdateData($validated);

        // Check if any existing values would be overwritten
        $changes = $this->detectChanges($project, $updateData);
        $hasOverwrites = $this->hasExistingValueOverwrites($project, $updateData);

        // If there are overwrites and the request is not confirmed, return confirmation required
        if ($hasOverwrites && !$isConfirmed) {
            return response()->json([
                'confirmation_required' => true,
                'message' => 'This will overwrite existing RACI assignments. Please confirm to proceed.',
                'changes' => $changes,
            ]);
        }

        // Perform the update
        $project->update($updateData);

        // Log changes to AuditLog
        $this->logRaciChanges($team, $user, $project, $changes);

        // Reload to get fresh data
        $project->refresh();
        $project->load(['accountable', 'responsible']);

        return response()->json([
            'confirmation_required' => false,
            'message' => 'RACI assignments updated successfully.',
            'project' => $this->formatProjectResponse($project),
        ]);
    }

    /**
     * Build update data from validated request data.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function buildUpdateData(array $validated): array
    {
        $updateData = [];

        if (array_key_exists('accountable_id', $validated)) {
            $updateData['accountable_id'] = $validated['accountable_id'];
        }
        if (array_key_exists('responsible_id', $validated)) {
            $updateData['responsible_id'] = $validated['responsible_id'];
        }
        if (array_key_exists('consulted_ids', $validated)) {
            $updateData['consulted_ids'] = $validated['consulted_ids'];
        }
        if (array_key_exists('informed_ids', $validated)) {
            $updateData['informed_ids'] = $validated['informed_ids'];
        }

        return $updateData;
    }

    /**
     * Detect which fields are changing and their before/after values.
     *
     * @param array<string, mixed> $updateData
     * @return array<int, array{field: string, from: mixed, to: mixed}>
     */
    private function detectChanges(Project $project, array $updateData): array
    {
        $changes = [];

        foreach ($updateData as $field => $newValue) {
            $oldValue = $project->getAttribute($field);

            // Normalize arrays for comparison
            if (is_array($oldValue) || is_array($newValue)) {
                $oldNormalized = is_array($oldValue) ? $oldValue : [];
                $newNormalized = is_array($newValue) ? $newValue : [];

                if ($oldNormalized !== $newNormalized) {
                    $changes[] = [
                        'field' => $field,
                        'from' => $this->formatValueForDisplay($oldValue, $field),
                        'to' => $this->formatValueForDisplay($newValue, $field),
                    ];
                }
            } elseif ($oldValue !== $newValue) {
                $changes[] = [
                    'field' => $field,
                    'from' => $this->formatValueForDisplay($oldValue, $field),
                    'to' => $this->formatValueForDisplay($newValue, $field),
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if any existing non-null values would be overwritten.
     *
     * @param array<string, mixed> $updateData
     */
    private function hasExistingValueOverwrites(Project $project, array $updateData): bool
    {
        foreach ($updateData as $field => $newValue) {
            $oldValue = $project->getAttribute($field);

            // Skip if old value is null or empty array
            if ($oldValue === null || (is_array($oldValue) && empty($oldValue))) {
                continue;
            }

            // For arrays, compare contents
            if (is_array($oldValue) && is_array($newValue)) {
                if ($oldValue !== $newValue) {
                    return true;
                }
            } elseif ($oldValue !== $newValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format a value for display in the changes response.
     */
    private function formatValueForDisplay(mixed $value, string $field): mixed
    {
        if ($value === null) {
            return null;
        }

        // For user ID fields, resolve to user name
        if (in_array($field, ['accountable_id', 'responsible_id']) && is_int($value)) {
            $user = User::find($value);
            return $user ? $user->name : (string) $value;
        }

        // For array fields with user IDs
        if (in_array($field, ['consulted_ids', 'informed_ids']) && is_array($value)) {
            $users = User::whereIn('id', $value)->pluck('name')->toArray();
            return $users;
        }

        return $value;
    }

    /**
     * Log RACI changes to the audit log.
     *
     * @param array<int, array{field: string, from: mixed, to: mixed}> $changes
     */
    private function logRaciChanges(
        \App\Models\Team $team,
        \App\Models\User $user,
        Project $project,
        array $changes,
    ): void {
        if (empty($changes)) {
            return;
        }

        $changedFields = array_column($changes, 'field');
        $details = sprintf(
            'RACI fields updated: %s',
            implode(', ', $changedFields)
        );

        AuditLog::log(
            team: $team,
            actorType: 'user',
            actorId: (string) $user->id,
            actorName: $user->name,
            action: 'raci_updated',
            details: $details,
            target: 'Project',
            targetId: (string) $project->id,
            ipAddress: request()->ip(),
        );
    }

    /**
     * Format project data for JSON response.
     *
     * @return array<string, mixed>
     */
    private function formatProjectResponse(Project $project): array
    {
        return [
            'id' => (string) $project->id,
            'name' => $project->name,
            'accountable_id' => $project->accountable_id ? (string) $project->accountable_id : null,
            'accountable_name' => $project->accountable?->name,
            'responsible_id' => $project->responsible_id ? (string) $project->responsible_id : null,
            'responsible_name' => $project->responsible?->name,
            'consulted_ids' => $project->consulted_ids
                ? array_map(fn ($id) => (string) $id, $project->consulted_ids)
                : null,
            'informed_ids' => $project->informed_ids
                ? array_map(fn ($id) => (string) $id, $project->informed_ids)
                : null,
        ];
    }
}
