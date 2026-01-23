<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AIConfidence;

/**
 * Service for making routing decisions based on skill and capacity scores.
 *
 * Combines skill score (50%) and capacity score (50%), presents multiple
 * options when top candidates are within 10% score difference, and always
 * returns at least top 3 candidates when available.
 */
class RoutingDecisionService
{
    /**
     * Weight for skill score in combined calculation.
     */
    private const SKILL_WEIGHT = 0.50;

    /**
     * Weight for capacity score in combined calculation.
     */
    private const CAPACITY_WEIGHT = 0.50;

    /**
     * Threshold for considering candidates as "close" to top score.
     * Candidates within 10% of the top score are flagged as top candidates.
     */
    private const TOP_CANDIDATE_THRESHOLD = 0.10;

    /**
     * Minimum number of candidates to return when available.
     */
    private const MIN_CANDIDATES = 3;

    public function __construct(
        private readonly SkillMatchingService $skillMatchingService,
        private readonly CapacityScoreService $capacityScoreService
    ) {}

    /**
     * Calculate routing recommendations for team members.
     *
     * @param  int  $teamId  The team ID
     * @param  array<string>  $requiredSkills  List of required skill names
     * @param  float  $estimatedHours  Estimated hours for the work
     * @return array{
     *     candidates: array<array{
     *         user_id: int,
     *         user_name: string,
     *         skill_score: float,
     *         capacity_score: float,
     *         combined_score: float,
     *         confidence: AIConfidence,
     *         is_top_candidate: bool,
     *         reasoning: array{
     *             skill_matches: array,
     *             missing_skills: array,
     *             capacity_analysis: array,
     *             confidence_rationale: string
     *         }
     *     }>,
     *     top_score: float,
     *     threshold_score: float,
     *     recommendation_summary: string
     * }
     */
    public function calculateRouting(int $teamId, array $requiredSkills, float $estimatedHours): array
    {
        // Get skill scores
        $skillScores = $this->skillMatchingService->calculateScores($teamId, $requiredSkills);

        // Get capacity scores
        $capacityScores = $this->capacityScoreService->calculateScores($teamId, $estimatedHours);

        // Combine scores for each team member
        $candidates = $this->combineScores($skillScores, $capacityScores, $estimatedHours);

        // Sort by combined score descending
        usort($candidates, fn ($a, $b) => $b['combined_score'] <=> $a['combined_score']);

        // Determine top score and threshold
        $topScore = ! empty($candidates) ? $candidates[0]['combined_score'] : 0;
        $thresholdScore = $topScore * (1 - self::TOP_CANDIDATE_THRESHOLD);

        // Mark top candidates (within 10% of top score or in top 3)
        $candidates = $this->markTopCandidates($candidates, $thresholdScore);

        // Ensure we return at least MIN_CANDIDATES if available
        $candidates = $this->ensureMinimumCandidates($candidates);

        // Generate recommendation summary
        $summary = $this->generateSummary($candidates, $topScore);

        return [
            'candidates' => $candidates,
            'top_score' => round($topScore, 2),
            'threshold_score' => round($thresholdScore, 2),
            'recommendation_summary' => $summary,
        ];
    }

    /**
     * Combine skill and capacity scores for each team member.
     *
     * @param  array<int, array>  $skillScores
     * @param  array<int, array>  $capacityScores
     * @return array<array>
     */
    private function combineScores(
        array $skillScores,
        array $capacityScores,
        float $estimatedHours
    ): array {
        $candidates = [];

        // Get all unique user IDs from both score sets
        $userIds = array_unique(array_merge(
            array_keys($skillScores),
            array_keys($capacityScores)
        ));

        foreach ($userIds as $userId) {
            $skillData = $skillScores[$userId] ?? null;
            $capacityData = $capacityScores[$userId] ?? null;

            // Skip if we don't have both data sets
            if ($skillData === null || $capacityData === null) {
                continue;
            }

            $skillScore = $skillData['score'];
            $capacityScore = $capacityData['score'];

            // Calculate combined score with 50/50 weighting
            $combinedScore = ($skillScore * self::SKILL_WEIGHT) + ($capacityScore * self::CAPACITY_WEIGHT);

            // Determine confidence level
            $confidence = $this->determineConfidence(
                $combinedScore,
                count($skillData['matched_skills']),
                $capacityData['available_capacity'],
                $estimatedHours
            );

            // Generate reasoning
            $reasoning = $this->generateReasoning(
                $skillData,
                $capacityData,
                $estimatedHours,
                $confidence
            );

            $candidates[] = [
                'user_id' => $userId,
                'user_name' => $skillData['user_name'],
                'skill_score' => round($skillScore, 2),
                'capacity_score' => round($capacityScore, 2),
                'combined_score' => round($combinedScore, 2),
                'confidence' => $confidence,
                'is_top_candidate' => false, // Will be set later
                'reasoning' => $reasoning,
            ];
        }

        return $candidates;
    }

    /**
     * Mark candidates that are within the top score threshold.
     *
     * @param  array<array>  $candidates
     * @return array<array>
     */
    private function markTopCandidates(array $candidates, float $thresholdScore): array
    {
        foreach ($candidates as &$candidate) {
            // Mark as top candidate if score is within threshold
            $candidate['is_top_candidate'] = $candidate['combined_score'] >= $thresholdScore;
        }

        return $candidates;
    }

    /**
     * Ensure we return at least the minimum number of candidates.
     *
     * @param  array<array>  $candidates
     * @return array<array>
     */
    private function ensureMinimumCandidates(array $candidates): array
    {
        $topCount = 0;
        foreach ($candidates as $candidate) {
            if ($candidate['is_top_candidate']) {
                $topCount++;
            }
        }

        // If we have fewer than MIN_CANDIDATES marked as top, mark more
        if ($topCount < self::MIN_CANDIDATES) {
            $remaining = self::MIN_CANDIDATES - $topCount;
            foreach ($candidates as &$candidate) {
                if (! $candidate['is_top_candidate'] && $remaining > 0) {
                    $candidate['is_top_candidate'] = true;
                    $remaining--;
                }
            }
        }

        return $candidates;
    }

    /**
     * Determine confidence level for a routing recommendation.
     */
    private function determineConfidence(
        float $score,
        int $skillMatchCount,
        int $availableCapacity,
        float $requiredHours
    ): AIConfidence {
        // High confidence: score >= 80, multiple skill matches, sufficient capacity
        if ($score >= 80 && $skillMatchCount >= 2 && $availableCapacity >= $requiredHours) {
            return AIConfidence::High;
        }

        // Medium confidence: score >= 50, at least one skill match, some capacity
        if ($score >= 50 && $skillMatchCount >= 1 && $availableCapacity > 0) {
            return AIConfidence::Medium;
        }

        // Low confidence: low score, no skill matches, or insufficient capacity
        return AIConfidence::Low;
    }

    /**
     * Generate detailed reasoning for a routing recommendation.
     *
     * @return array{
     *     skill_matches: array,
     *     missing_skills: array<string>,
     *     capacity_analysis: array,
     *     confidence_rationale: string
     * }
     */
    private function generateReasoning(
        array $skillData,
        array $capacityData,
        float $estimatedHours,
        AIConfidence $confidence
    ): array {
        // Skill analysis
        $skillMatches = array_map(function ($match) {
            return [
                'skill' => $match['skill_name'],
                'proficiency' => $match['proficiency_label'],
                'weight' => $match['weight'],
            ];
        }, $skillData['matched_skills']);

        // Capacity analysis
        $capacityAnalysis = [
            'available_hours' => $capacityData['available_capacity'],
            'required_hours' => $estimatedHours,
            'utilization' => 100 - $capacityData['capacity_percentage'],
            'can_fit_work' => $capacityData['can_fit_work'],
            'penalty_applied' => $capacityData['penalty_applied'],
        ];

        // Generate confidence rationale
        $rationale = $this->generateConfidenceRationale(
            $confidence,
            count($skillMatches),
            count($skillData['missing_skills']),
            $capacityData['can_fit_work'],
            $capacityData['penalty_applied']
        );

        return [
            'skill_matches' => $skillMatches,
            'missing_skills' => $skillData['missing_skills'],
            'capacity_analysis' => $capacityAnalysis,
            'confidence_rationale' => $rationale,
        ];
    }

    /**
     * Generate a human-readable confidence rationale.
     */
    private function generateConfidenceRationale(
        AIConfidence $confidence,
        int $matchedSkillCount,
        int $missingSkillCount,
        bool $canFitWork,
        bool $penaltyApplied
    ): string {
        $parts = [];

        // Skill assessment
        if ($matchedSkillCount === 0) {
            $parts[] = 'No matching skills found';
        } elseif ($missingSkillCount === 0) {
            $parts[] = "All {$matchedSkillCount} required skills matched";
        } else {
            $parts[] = "{$matchedSkillCount} skills matched, {$missingSkillCount} missing";
        }

        // Capacity assessment
        if ($canFitWork) {
            $parts[] = 'sufficient capacity available';
        } else {
            $parts[] = 'limited capacity for this work';
        }

        if ($penaltyApplied) {
            $parts[] = 'score penalized due to low availability (<20%)';
        }

        $assessment = implode('; ', $parts);

        return match ($confidence) {
            AIConfidence::High => "High confidence: {$assessment}",
            AIConfidence::Medium => "Medium confidence: {$assessment}",
            AIConfidence::Low => "Low confidence: {$assessment}",
        };
    }

    /**
     * Generate a summary of the routing recommendation.
     *
     * @param  array<array>  $candidates
     */
    private function generateSummary(array $candidates, float $topScore): string
    {
        if (empty($candidates)) {
            return 'No candidates available for routing.';
        }

        $topCandidates = array_filter($candidates, fn ($c) => $c['is_top_candidate']);
        $topCount = count($topCandidates);

        if ($topCount === 0) {
            return 'No suitable candidates found.';
        }

        $topNames = array_map(fn ($c) => $c['user_name'], array_slice($topCandidates, 0, 3));
        $namesStr = implode(', ', $topNames);

        if ($topCount === 1) {
            return "Recommended: {$namesStr} with score {$topScore}";
        }

        return "{$topCount} candidates within 10% of top score ({$topScore}): {$namesStr}";
    }

    /**
     * Get the top recommended candidate.
     *
     * @return array|null The top candidate or null if no candidates
     */
    public function getTopRecommendation(int $teamId, array $requiredSkills, float $estimatedHours): ?array
    {
        $result = $this->calculateRouting($teamId, $requiredSkills, $estimatedHours);

        return $result['candidates'][0] ?? null;
    }
}
