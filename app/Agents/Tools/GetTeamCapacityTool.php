<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Models\Team;
use InvalidArgumentException;

/**
 * Tool for retrieving team member capacity and workload information.
 *
 * Queries team members to get their capacity hours per week, current workload,
 * and calculated available capacity for use in work routing decisions.
 */
class GetTeamCapacityTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'get-team-capacity';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Retrieves capacity and workload information for all members of a team, including available hours for new work assignments.';
    }

    /**
     * Get the category this tool belongs to.
     */
    public function category(): string
    {
        return 'work_routing';
    }

    /**
     * Execute the tool with the given parameters.
     *
     * @param  array<string, mixed>  $params  The parameters for tool execution
     * @return array<string, mixed> The result data from execution
     *
     * @throws InvalidArgumentException If team_id is not provided or team not found
     */
    public function execute(array $params): array
    {
        $teamId = $params['team_id'] ?? null;
        $minAvailableHours = $params['min_available_hours'] ?? null;

        if ($teamId === null) {
            throw new InvalidArgumentException('team_id is required');
        }

        $team = Team::find($teamId);

        if ($team === null) {
            throw new InvalidArgumentException("Team with ID {$teamId} not found");
        }

        // Get all team members (including owner)
        $teamMembers = $team->allUsers();

        $teamCapacity = [];

        foreach ($teamMembers as $member) {
            $capacityHoursPerWeek = $member->capacity_hours_per_week ?? 40;
            $currentWorkloadHours = $member->current_workload_hours ?? 0;
            $availableCapacity = $member->getAvailableCapacity();

            // Apply minimum available hours filter if provided
            if ($minAvailableHours !== null && $availableCapacity < $minAvailableHours) {
                continue;
            }

            // Calculate capacity utilization percentage
            $utilizationPercentage = $capacityHoursPerWeek > 0
                ? round(($currentWorkloadHours / $capacityHoursPerWeek) * 100, 1)
                : 0;

            // Flag if capacity is critically low (less than 20%)
            $hasLowCapacity = $capacityHoursPerWeek > 0
                && ($availableCapacity / $capacityHoursPerWeek) < 0.2;

            $teamCapacity[] = [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'capacity_hours_per_week' => $capacityHoursPerWeek,
                'current_workload_hours' => $currentWorkloadHours,
                'available_capacity' => $availableCapacity,
                'utilization_percentage' => $utilizationPercentage,
                'has_low_capacity' => $hasLowCapacity,
            ];
        }

        // Sort by available capacity descending
        usort($teamCapacity, fn ($a, $b) => $b['available_capacity'] <=> $a['available_capacity']);

        return [
            'team_id' => $teamId,
            'team_name' => $team->name,
            'team_capacity' => $teamCapacity,
            'total_members' => count($teamCapacity),
            'total_available_hours' => array_sum(array_column($teamCapacity, 'available_capacity')),
        ];
    }

    /**
     * Get the parameter definitions for this tool.
     *
     * @return array<string, array{type: string, description: string, required: bool}>
     */
    public function getParameters(): array
    {
        return [
            'team_id' => [
                'type' => 'integer',
                'description' => 'The ID of the team to retrieve capacity for',
                'required' => true,
            ],
            'min_available_hours' => [
                'type' => 'number',
                'description' => 'Optional minimum available hours to filter members by',
                'required' => false,
            ],
        ];
    }
}
