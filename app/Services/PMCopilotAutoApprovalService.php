<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AIConfidence;
use App\Models\GlobalAISettings;

/**
 * Service for evaluating whether PM Copilot suggestions can be auto-approved.
 *
 * Auto-approval is granted when:
 * 1. The suggestion confidence score meets or exceeds the team's threshold
 * 2. The suggestion has no budget impact (no budget_cost modifications)
 */
class PMCopilotAutoApprovalService
{
    /**
     * Default confidence scores for AIConfidence levels.
     */
    private const CONFIDENCE_SCORES = [
        'high' => 0.9,
        'medium' => 0.7,
        'low' => 0.5,
    ];

    /**
     * Determine if a suggestion should be auto-approved based on settings.
     *
     * @param  array{
     *     title?: string,
     *     confidence?: string,
     *     confidence_score?: float,
     *     has_budget_impact?: bool,
     *     budget_cost?: float|null
     * }  $suggestion  The suggestion data to evaluate
     * @param  GlobalAISettings  $settings  The team's AI settings
     * @return bool True if the suggestion can be auto-approved
     */
    public function shouldAutoApprove(array $suggestion, GlobalAISettings $settings): bool
    {
        // Check if suggestion has budget impact - if so, cannot auto-approve
        if ($this->hasBudgetImpact($suggestion)) {
            return false;
        }

        // Get the confidence score from the suggestion
        $confidenceScore = $this->getConfidenceScore($suggestion);

        // Check if confidence meets the threshold
        return $settings->meetsAutoApprovalThreshold($confidenceScore);
    }

    /**
     * Evaluate multiple suggestions and return auto-approval decisions.
     *
     * @param  array<int, array>  $suggestions  Array of suggestions to evaluate
     * @param  GlobalAISettings  $settings  The team's AI settings
     * @return array<int, bool>  Map of suggestion index to approval decision
     */
    public function evaluateSuggestions(array $suggestions, GlobalAISettings $settings): array
    {
        $results = [];

        foreach ($suggestions as $index => $suggestion) {
            $results[$index] = $this->shouldAutoApprove($suggestion, $settings);
        }

        return $results;
    }

    /**
     * Get the numeric confidence score from a suggestion.
     *
     * If a numeric confidence_score is provided, it is used directly.
     * Otherwise, the confidence level (high/medium/low) is mapped to a default score.
     */
    private function getConfidenceScore(array $suggestion): float
    {
        // Prefer explicit confidence score if provided
        if (isset($suggestion['confidence_score']) && is_numeric($suggestion['confidence_score'])) {
            return (float) $suggestion['confidence_score'];
        }

        // Fall back to mapping from confidence level
        $confidenceLevel = $suggestion['confidence'] ?? 'medium';

        // Handle AIConfidence enum or string
        if ($confidenceLevel instanceof AIConfidence) {
            $confidenceLevel = $confidenceLevel->value;
        }

        return self::CONFIDENCE_SCORES[strtolower($confidenceLevel)] ?? self::CONFIDENCE_SCORES['medium'];
    }

    /**
     * Check if a suggestion has budget impact.
     *
     * A suggestion has budget impact if:
     * - has_budget_impact is explicitly true
     * - budget_cost is set and greater than zero
     */
    private function hasBudgetImpact(array $suggestion): bool
    {
        // Check explicit budget impact flag
        if (isset($suggestion['has_budget_impact']) && $suggestion['has_budget_impact'] === true) {
            return true;
        }

        // Check if budget_cost is set and non-zero
        if (isset($suggestion['budget_cost']) && (float) $suggestion['budget_cost'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Get the minimum confidence score required for auto-approval.
     */
    public function getThresholdForSettings(GlobalAISettings $settings): float
    {
        return $settings->getPMCopilotAutoApprovalThreshold();
    }

    /**
     * Convert an AIConfidence enum to its default numeric score.
     */
    public function confidenceToScore(AIConfidence $confidence): float
    {
        return self::CONFIDENCE_SCORES[$confidence->value] ?? self::CONFIDENCE_SCORES['medium'];
    }
}
