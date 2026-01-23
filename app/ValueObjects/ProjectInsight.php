<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\AIConfidence;

/**
 * Immutable value object representing a project insight from PM Copilot.
 *
 * Contains structured information about project issues like overdue items,
 * bottlenecks, resource imbalances, and scope creep risks.
 */
final readonly class ProjectInsight
{
    /**
     * Insight type constants.
     */
    public const TYPE_OVERDUE = 'overdue';

    public const TYPE_BOTTLENECK = 'bottleneck';

    public const TYPE_RESOURCE = 'resource';

    public const TYPE_SCOPE_CREEP = 'scope_creep';

    /**
     * Severity level constants.
     */
    public const SEVERITY_CRITICAL = 'critical';

    public const SEVERITY_HIGH = 'high';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_LOW = 'low';

    /**
     * Create a new project insight.
     *
     * @param  string  $type  The type of insight (overdue, bottleneck, resource, scope_creep)
     * @param  string  $severity  The severity level (critical, high, medium, low)
     * @param  string  $title  A brief title describing the insight
     * @param  string  $description  Detailed description of the insight
     * @param  array<int, array{id: int, type: string, title: string}>  $affectedItems  List of affected items
     * @param  string|null  $suggestion  Suggested action to address the insight
     * @param  AIConfidence  $confidence  Confidence level of this insight
     */
    public function __construct(
        public string $type,
        public string $severity,
        public string $title,
        public string $description,
        public array $affectedItems = [],
        public ?string $suggestion = null,
        public AIConfidence $confidence = AIConfidence::Medium,
    ) {}

    /**
     * Convert the insight to an array for serialization.
     *
     * @return array{
     *     type: string,
     *     severity: string,
     *     title: string,
     *     description: string,
     *     affected_items: array<int, array{id: int, type: string, title: string}>,
     *     affected_items_count: int,
     *     suggestion: string|null,
     *     confidence: string
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'severity' => $this->severity,
            'title' => $this->title,
            'description' => $this->description,
            'affected_items' => $this->affectedItems,
            'affected_items_count' => count($this->affectedItems),
            'suggestion' => $this->suggestion,
            'confidence' => $this->confidence->value,
        ];
    }

    /**
     * Create a ProjectInsight from an array.
     *
     * @param  array{
     *     type: string,
     *     severity: string,
     *     title: string,
     *     description: string,
     *     affected_items?: array<int, array{id: int, type: string, title: string}>,
     *     suggestion?: string|null,
     *     confidence?: string
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            severity: $data['severity'],
            title: $data['title'],
            description: $data['description'],
            affectedItems: $data['affected_items'] ?? [],
            suggestion: $data['suggestion'] ?? null,
            confidence: self::parseConfidence($data['confidence'] ?? 'medium'),
        );
    }

    /**
     * Check if this insight is of critical or high severity.
     */
    public function isUrgent(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL
            || $this->severity === self::SEVERITY_HIGH;
    }

    /**
     * Check if this insight has actionable suggestions.
     */
    public function hasActionableSuggestion(): bool
    {
        return $this->suggestion !== null && trim($this->suggestion) !== '';
    }

    /**
     * Get the icon name for this insight type.
     */
    public function getIconName(): string
    {
        return match ($this->type) {
            self::TYPE_OVERDUE => 'clock',
            self::TYPE_BOTTLENECK => 'alert-triangle',
            self::TYPE_RESOURCE => 'users',
            self::TYPE_SCOPE_CREEP => 'trending-up',
            default => 'info',
        };
    }

    /**
     * Get a human-readable label for the insight type.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_OVERDUE => 'Overdue Items',
            self::TYPE_BOTTLENECK => 'Bottleneck',
            self::TYPE_RESOURCE => 'Resource Imbalance',
            self::TYPE_SCOPE_CREEP => 'Scope Creep Risk',
            default => 'Insight',
        };
    }

    /**
     * Parse a string to AIConfidence enum.
     */
    private static function parseConfidence(string $confidence): AIConfidence
    {
        return match (strtolower($confidence)) {
            'high' => AIConfidence::High,
            'low' => AIConfidence::Low,
            default => AIConfidence::Medium,
        };
    }
}
