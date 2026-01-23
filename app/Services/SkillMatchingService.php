<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Team;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Service for matching team members based on skills.
 *
 * Queries UserSkill for team members with matching skills,
 * weights proficiency levels (1=Basic: 0.33, 2=Intermediate: 0.66, 3=Advanced: 1.0),
 * and calculates skill match scores (0-100) per team member.
 */
class SkillMatchingService
{
    /**
     * Proficiency level weights.
     */
    private const PROFICIENCY_WEIGHTS = [
        1 => 0.33, // Basic
        2 => 0.66, // Intermediate
        3 => 1.0,  // Advanced
    ];

    /**
     * Calculate skill match scores for all team members.
     *
     * @param  int  $teamId  The team ID to query members from
     * @param  array<string>  $requiredSkills  List of required skill names
     * @return array<int, array{
     *     user_id: int,
     *     user_name: string,
     *     score: float,
     *     matched_skills: array<array{skill_name: string, proficiency: int, proficiency_label: string, weight: float}>,
     *     missing_skills: array<string>
     * }>
     */
    public function calculateScores(int $teamId, array $requiredSkills): array
    {
        if (empty($requiredSkills)) {
            return [];
        }

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
            $memberScore = $this->calculateMemberScore($member, $requiredSkills);
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
        $members = $team->users()->with('skills')->get();

        // Include team owner if not already in the list
        $owner = $team->owner()->with('skills')->first();
        if ($owner !== null && ! $members->contains('id', $owner->id)) {
            $members->push($owner);
        }

        return $members;
    }

    /**
     * Calculate skill match score for a single team member.
     *
     * @param  array<string>  $requiredSkills
     * @return array{
     *     user_id: int,
     *     user_name: string,
     *     score: float,
     *     matched_skills: array<array{skill_name: string, proficiency: int, proficiency_label: string, weight: float}>,
     *     missing_skills: array<string>
     * }
     */
    private function calculateMemberScore(User $member, array $requiredSkills): array
    {
        $memberSkills = $member->skills;
        $matchedSkills = [];
        $missingSkills = [];
        $totalWeight = 0.0;

        foreach ($requiredSkills as $requiredSkill) {
            $match = $this->findSkillMatch($memberSkills, $requiredSkill);

            if ($match !== null) {
                $weight = self::PROFICIENCY_WEIGHTS[$match->proficiency] ?? 0.33;
                $totalWeight += $weight;

                $matchedSkills[] = [
                    'skill_name' => $match->skill_name,
                    'proficiency' => $match->proficiency,
                    'proficiency_label' => $match->proficiency_label,
                    'weight' => $weight,
                ];
            } else {
                $missingSkills[] = $requiredSkill;
            }
        }

        // Calculate score: (total weighted matches / number of required skills) * 100
        $score = count($requiredSkills) > 0
            ? ($totalWeight / count($requiredSkills)) * 100
            : 0.0;

        return [
            'user_id' => $member->id,
            'user_name' => $member->name,
            'score' => round($score, 2),
            'matched_skills' => $matchedSkills,
            'missing_skills' => $missingSkills,
        ];
    }

    /**
     * Find a matching skill using exact match first, then semantic similarity.
     *
     * @param  Collection<int, UserSkill>  $memberSkills
     */
    private function findSkillMatch(Collection $memberSkills, string $requiredSkill): ?UserSkill
    {
        $requiredSkillLower = strtolower(trim($requiredSkill));

        // First: exact match (case-insensitive)
        $exactMatch = $memberSkills->first(function (UserSkill $skill) use ($requiredSkillLower) {
            return strtolower($skill->skill_name) === $requiredSkillLower;
        });

        if ($exactMatch !== null) {
            return $exactMatch;
        }

        // Second: partial match (skill contains required or vice versa)
        $partialMatch = $memberSkills->first(function (UserSkill $skill) use ($requiredSkillLower) {
            $skillLower = strtolower($skill->skill_name);

            return Str::contains($skillLower, $requiredSkillLower) ||
                   Str::contains($requiredSkillLower, $skillLower);
        });

        if ($partialMatch !== null) {
            return $partialMatch;
        }

        // Third: semantic similarity match
        return $this->findSemanticMatch($memberSkills, $requiredSkillLower);
    }

    /**
     * Find a skill match using semantic similarity.
     *
     * @param  Collection<int, UserSkill>  $memberSkills
     */
    private function findSemanticMatch(Collection $memberSkills, string $requiredSkill): ?UserSkill
    {
        // Common skill synonyms and related terms
        $skillGroups = [
            'php' => ['laravel', 'symfony', 'wordpress', 'drupal'],
            'laravel' => ['php', 'eloquent', 'artisan'],
            'javascript' => ['js', 'typescript', 'ts', 'node', 'nodejs'],
            'react' => ['reactjs', 'react.js', 'jsx'],
            'vue' => ['vuejs', 'vue.js'],
            'angular' => ['angularjs', 'angular.js'],
            'css' => ['scss', 'sass', 'less', 'tailwind', 'bootstrap'],
            'html' => ['html5', 'markup'],
            'python' => ['django', 'flask', 'fastapi'],
            'ruby' => ['rails', 'ruby on rails'],
            'database' => ['sql', 'mysql', 'postgresql', 'postgres', 'mongodb', 'redis'],
            'mysql' => ['sql', 'database', 'mariadb'],
            'postgresql' => ['postgres', 'sql', 'database'],
            'mongodb' => ['mongo', 'nosql', 'database'],
            'devops' => ['docker', 'kubernetes', 'k8s', 'aws', 'azure', 'gcp', 'ci/cd'],
            'docker' => ['containers', 'devops', 'kubernetes'],
            'aws' => ['amazon web services', 'cloud', 'ec2', 's3'],
            'api' => ['rest', 'restful', 'graphql', 'backend'],
            'testing' => ['test', 'qa', 'phpunit', 'jest', 'cypress'],
            'frontend' => ['ui', 'ux', 'client-side', 'web'],
            'backend' => ['server-side', 'api', 'server'],
            'mobile' => ['ios', 'android', 'react native', 'flutter'],
            'design' => ['ui', 'ux', 'figma', 'sketch', 'adobe'],
        ];

        // Flatten the groups for quick lookup
        $relatedSkills = [];
        foreach ($skillGroups as $primary => $related) {
            $relatedSkills[$primary] = $related;
            foreach ($related as $relatedSkill) {
                if (! isset($relatedSkills[$relatedSkill])) {
                    $relatedSkills[$relatedSkill] = [];
                }
                $relatedSkills[$relatedSkill][] = $primary;
            }
        }

        // Get related skills for the required skill
        $searchTerms = $relatedSkills[$requiredSkill] ?? [];
        $searchTerms[] = $requiredSkill;

        // Find a match among member skills
        foreach ($memberSkills as $memberSkill) {
            $skillLower = strtolower($memberSkill->skill_name);

            foreach ($searchTerms as $term) {
                if ($skillLower === $term || Str::contains($skillLower, $term) || Str::contains($term, $skillLower)) {
                    return $memberSkill;
                }
            }
        }

        return null;
    }

    /**
     * Get all unique skills from team members.
     *
     * @return array<array{skill_name: string, users_count: int, avg_proficiency: float}>
     */
    public function getTeamSkillsSummary(int $teamId): array
    {
        $team = Team::find($teamId);
        if ($team === null) {
            return [];
        }

        $teamMembers = $this->getTeamMembers($team);
        $skillsData = [];

        foreach ($teamMembers as $member) {
            foreach ($member->skills as $skill) {
                $skillName = $skill->skill_name;
                if (! isset($skillsData[$skillName])) {
                    $skillsData[$skillName] = [
                        'skill_name' => $skillName,
                        'proficiencies' => [],
                    ];
                }
                $skillsData[$skillName]['proficiencies'][] = $skill->proficiency;
            }
        }

        return collect($skillsData)
            ->map(fn ($data) => [
                'skill_name' => $data['skill_name'],
                'users_count' => count($data['proficiencies']),
                'avg_proficiency' => round(array_sum($data['proficiencies']) / count($data['proficiencies']), 2),
            ])
            ->sortByDesc('users_count')
            ->values()
            ->toArray();
    }
}
