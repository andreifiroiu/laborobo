<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service for calculating capacity scores for team members.
 *
 * Uses User.getAvailableCapacity() for remaining hours, compares available
 * capacity against estimated hours, and penalizes score by 50% for users
 * with less than 20% available capacity.
 */
class CapacityScoreService
{
    /**
     * Capacity threshold below which a penalty is applied.
     * Users with less than 20% available capacity receive a score penalty.
     */
    private const LOW_CAPACITY_THRESHOLD = 0.20;

    /**
     * Penalty multiplier applied when capacity is below threshold.
     */
    private const LOW_CAPACITY_PENALTY = 0.50;

    /**
     * Calculate capacity scores for all team members.
     *
     * @param  int  $teamId  The team ID to query members from
     * @param  float  $estimatedHours  Estimated hours for the work
     * @return array<int, array{
     *     user_id: int,
     *     user_name: string,
     *     score: float,
     *     capacity_hours_per_week: int,
     *     current_workload_hours: int,
     *     available_capacity: int,
     *     capacity_percentage: float,
     *     penalty_applied: bool,
     *     can_fit_work: bool
     * }>
     */
    public function calculateScores(int $teamId, float $estimatedHours): array
    {
        $team = Team::find($teamId);
        if ($team === null) {
            return [];
        }

        $teamMembers = $this->getTeamMembers($team);

        if ($teamMembers->isEmpty()) {
            return [];
        }

        $scores = [];

        foreach ($teamMembers as $member) {
            $memberScore = $this->calculateMemberCapacityScore($member, $estimatedHours);
            $scores[$member->id] = $memberScore;
        }

        return $scores;
    }

    /**
     * Get all team members including owner.
     *
     * @return Collection<int, User>
     */
    private function getTeamMembers(Team $team): Collection
    {
        // Get team members via the pivot table
        $members = $team->users()->get();

        // Include team owner if not already in the list
        $owner = $team->owner()->first();
        if ($owner !== null && ! $members->contains('id', $owner->id)) {
            $members->push($owner);
        }

        return $members;
    }

    /**
     * Calculate capacity score for a single team member.
     *
     * @return array{
     *     user_id: int,
     *     user_name: string,
     *     score: float,
     *     capacity_hours_per_week: int,
     *     current_workload_hours: int,
     *     available_capacity: int,
     *     capacity_percentage: float,
     *     penalty_applied: bool,
     *     can_fit_work: bool
     * }
     */
    private function calculateMemberCapacityScore(User $member, float $estimatedHours): array
    {
        $capacityPerWeek = $member->capacity_hours_per_week ?? 40;
        $currentWorkload = $member->current_workload_hours ?? 0;
        $availableCapacity = $member->getAvailableCapacity();

        // Calculate capacity percentage
        $capacityPercentage = $capacityPerWeek > 0
            ? ($availableCapacity / $capacityPerWeek)
            : 0.0;

        // Determine if work fits within available capacity
        $canFitWork = $availableCapacity >= $estimatedHours;

        // Calculate base score based on how well the work fits
        $baseScore = $this->calculateBaseScore($availableCapacity, $estimatedHours);

        // Check if penalty should be applied
        $penaltyApplied = $capacityPercentage < self::LOW_CAPACITY_THRESHOLD;

        // Apply penalty if user has less than 20% available capacity
        $finalScore = $penaltyApplied
            ? $baseScore * self::LOW_CAPACITY_PENALTY
            : $baseScore;

        return [
            'user_id' => $member->id,
            'user_name' => $member->name,
            'score' => round($finalScore, 2),
            'capacity_hours_per_week' => $capacityPerWeek,
            'current_workload_hours' => $currentWorkload,
            'available_capacity' => $availableCapacity,
            'capacity_percentage' => round($capacityPercentage * 100, 2),
            'penalty_applied' => $penaltyApplied,
            'can_fit_work' => $canFitWork,
        ];
    }

    /**
     * Calculate base capacity score.
     *
     * Score is based on:
     * - 100 if available capacity >= 2x estimated hours (plenty of room)
     * - Proportionally lower if available capacity is tight
     * - 0 if no available capacity
     */
    private function calculateBaseScore(int $availableCapacity, float $estimatedHours): float
    {
        if ($availableCapacity <= 0) {
            return 0.0;
        }

        if ($estimatedHours <= 0) {
            // If no work estimate, score based purely on available capacity
            // Assume 40 hours per week as baseline
            return min(($availableCapacity / 40) * 100, 100);
        }

        // Calculate ratio of available capacity to estimated hours
        $ratio = $availableCapacity / $estimatedHours;

        if ($ratio >= 2.0) {
            // Plenty of capacity (2x or more than needed)
            return 100.0;
        }

        if ($ratio >= 1.5) {
            // Comfortable capacity (1.5x to 2x)
            return 90.0 + (($ratio - 1.5) / 0.5) * 10;
        }

        if ($ratio >= 1.0) {
            // Tight but workable (1x to 1.5x)
            return 70.0 + (($ratio - 1.0) / 0.5) * 20;
        }

        // Not enough capacity (less than 1x)
        // Scale from 0 to 70 based on how close to fitting
        return max($ratio * 70, 0);
    }

    /**
     * Get capacity summary for a team.
     *
     * @return array{
     *     total_capacity: int,
     *     total_workload: int,
     *     total_available: int,
     *     utilization_percentage: float,
     *     members_with_capacity: int,
     *     members_overloaded: int
     * }
     */
    public function getTeamCapacitySummary(int $teamId): array
    {
        $team = Team::find($teamId);
        if ($team === null) {
            return [
                'total_capacity' => 0,
                'total_workload' => 0,
                'total_available' => 0,
                'utilization_percentage' => 0.0,
                'members_with_capacity' => 0,
                'members_overloaded' => 0,
            ];
        }

        $teamMembers = $this->getTeamMembers($team);

        $totalCapacity = 0;
        $totalWorkload = 0;
        $membersWithCapacity = 0;
        $membersOverloaded = 0;

        foreach ($teamMembers as $member) {
            $capacity = $member->capacity_hours_per_week ?? 40;
            $workload = $member->current_workload_hours ?? 0;
            $available = $member->getAvailableCapacity();

            $totalCapacity += $capacity;
            $totalWorkload += $workload;

            if ($available > 0) {
                $membersWithCapacity++;
            }

            if ($workload > $capacity) {
                $membersOverloaded++;
            }
        }

        $utilizationPercentage = $totalCapacity > 0
            ? ($totalWorkload / $totalCapacity) * 100
            : 0.0;

        return [
            'total_capacity' => $totalCapacity,
            'total_workload' => $totalWorkload,
            'total_available' => $totalCapacity - $totalWorkload,
            'utilization_percentage' => round($utilizationPercentage, 2),
            'members_with_capacity' => $membersWithCapacity,
            'members_overloaded' => $membersOverloaded,
        ];
    }

    /**
     * Find members with sufficient capacity for a given work estimate.
     *
     * @return Collection<int, User>
     */
    public function findMembersWithCapacity(int $teamId, float $estimatedHours, float $minimumBuffer = 1.0): Collection
    {
        $team = Team::find($teamId);
        if ($team === null) {
            return collect();
        }

        $teamMembers = $this->getTeamMembers($team);
        $requiredCapacity = $estimatedHours * $minimumBuffer;

        return $teamMembers->filter(function (User $member) use ($requiredCapacity) {
            return $member->getAvailableCapacity() >= $requiredCapacity;
        });
    }
}
