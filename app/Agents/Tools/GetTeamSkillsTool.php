<?php

declare(strict_types=1);

namespace App\Agents\Tools;

use App\Contracts\Tools\ToolInterface;
use App\Models\Team;
use App\Models\UserSkill;
use InvalidArgumentException;

/**
 * Tool for retrieving team member skills and proficiency levels.
 *
 * Queries the UserSkill model to get all team members with their skills
 * and proficiency levels for use in work routing decisions.
 */
class GetTeamSkillsTool implements ToolInterface
{
    /**
     * Get the unique identifier name for this tool.
     */
    public function name(): string
    {
        return 'get-team-skills';
    }

    /**
     * Get a human-readable description of what this tool does.
     */
    public function description(): string
    {
        return 'Retrieves skills and proficiency levels for all members of a team. Proficiency levels are: 1=Basic, 2=Intermediate, 3=Advanced.';
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
        $skillFilter = $params['skill_filter'] ?? null;

        if ($teamId === null) {
            throw new InvalidArgumentException('team_id is required');
        }

        $team = Team::find($teamId);

        if ($team === null) {
            throw new InvalidArgumentException("Team with ID {$teamId} not found");
        }

        // Get all team members (including owner)
        $teamMembers = $team->allUsers();

        $teamSkills = [];

        foreach ($teamMembers as $member) {
            $skillsQuery = UserSkill::where('user_id', $member->id);

            // Apply skill filter if provided
            if ($skillFilter !== null && is_array($skillFilter) && count($skillFilter) > 0) {
                $skillsQuery->whereIn('skill_name', $skillFilter);
            }

            $skills = $skillsQuery->get();

            if ($skills->isEmpty()) {
                continue;
            }

            $memberSkills = [
                'user_id' => $member->id,
                'user_name' => $member->name,
                'skills' => $skills->map(fn (UserSkill $skill) => [
                    'skill_name' => $skill->skill_name,
                    'proficiency' => $skill->proficiency,
                    'proficiency_label' => $skill->proficiency_label,
                ])->toArray(),
            ];

            $teamSkills[] = $memberSkills;
        }

        return [
            'team_id' => $teamId,
            'team_name' => $team->name,
            'team_skills' => $teamSkills,
            'total_members_with_skills' => count($teamSkills),
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
                'description' => 'The ID of the team to retrieve skills for',
                'required' => true,
            ],
            'skill_filter' => [
                'type' => 'array',
                'description' => 'Optional array of skill names to filter by',
                'required' => false,
            ],
        ];
    }
}
