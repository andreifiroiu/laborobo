<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AIConfidence;
use App\Models\Playbook;
use Illuminate\Support\Str;

/**
 * Service for extracting work requirements from unstructured message content.
 *
 * Parses messages to extract: title, description, scope, success_criteria,
 * estimated_hours, priority, and deadline. Applies AIConfidence levels to
 * each extracted field based on how explicitly the information was stated.
 */
class WorkRequirementExtractor
{
    /**
     * Extract work requirements from message content.
     *
     * @param  string  $messageContent  The message content to parse
     * @return array{
     *     title: array{value: string|null, confidence: AIConfidence},
     *     description: array{value: string|null, confidence: AIConfidence},
     *     scope: array{value: string|null, confidence: AIConfidence},
     *     success_criteria: array{value: array<string>|null, confidence: AIConfidence},
     *     estimated_hours: array{value: float|null, confidence: AIConfidence},
     *     priority: array{value: string|null, confidence: AIConfidence},
     *     deadline: array{value: string|null, confidence: AIConfidence}
     * }
     */
    public function extract(string $messageContent): array
    {
        $content = $this->normalizeContent($messageContent);

        return [
            'title' => $this->extractTitle($content),
            'description' => $this->extractDescription($content),
            'scope' => $this->extractScope($content),
            'success_criteria' => $this->extractSuccessCriteria($content),
            'estimated_hours' => $this->extractEstimatedHours($content),
            'priority' => $this->extractPriority($content),
            'deadline' => $this->extractDeadline($content),
        ];
    }

    /**
     * Extract requirements and suggest relevant playbooks.
     *
     * @param  string  $messageContent  The message content to parse
     * @param  int  $teamId  The team ID to filter playbooks
     * @return array{
     *     requirements: array,
     *     suggested_playbooks: array<array{id: int, name: string, relevance_score: float}>
     * }
     */
    public function extractWithPlaybooks(string $messageContent, int $teamId): array
    {
        $requirements = $this->extract($messageContent);
        $suggestedPlaybooks = $this->suggestPlaybooks($requirements, $teamId);

        return [
            'requirements' => $requirements,
            'suggested_playbooks' => $suggestedPlaybooks,
        ];
    }

    /**
     * Suggest relevant playbooks based on extracted requirements.
     *
     * @param  array  $requirements  The extracted requirements
     * @param  int  $teamId  The team ID to filter playbooks
     * @return array<array{id: int, name: string, description: string|null, relevance_score: float, matched_tags: array<string>}>
     */
    public function suggestPlaybooks(array $requirements, int $teamId): array
    {
        $keywords = $this->extractKeywords($requirements);

        if (empty($keywords)) {
            return [];
        }

        $playbooks = Playbook::forTeam($teamId)->get();

        $scoredPlaybooks = $playbooks->map(function (Playbook $playbook) use ($keywords) {
            $matchedTags = [];
            $score = 0.0;
            $playbookTags = $playbook->tags ?? [];
            $playbookName = strtolower($playbook->name);
            $playbookDescription = strtolower($playbook->description ?? '');

            foreach ($keywords as $keyword) {
                $keyword = strtolower($keyword);

                // Check tag matches (highest weight)
                foreach ($playbookTags as $tag) {
                    if ($this->isSemanticMatch($keyword, strtolower($tag))) {
                        $score += 30;
                        $matchedTags[] = $tag;
                    }
                }

                // Check name matches (medium weight)
                if (Str::contains($playbookName, $keyword)) {
                    $score += 20;
                }

                // Check description matches (lower weight)
                if (Str::contains($playbookDescription, $keyword)) {
                    $score += 10;
                }
            }

            return [
                'id' => $playbook->id,
                'name' => $playbook->name,
                'description' => $playbook->description,
                'relevance_score' => min($score, 100), // Cap at 100
                'matched_tags' => array_unique($matchedTags),
            ];
        })
            ->filter(fn ($p) => $p['relevance_score'] > 0)
            ->sortByDesc('relevance_score')
            ->values()
            ->take(5)
            ->toArray();

        return $scoredPlaybooks;
    }

    /**
     * Extract title from content.
     *
     * @return array{value: string|null, confidence: AIConfidence}
     */
    private function extractTitle(string $content): array
    {
        // Check for explicit title pattern
        if (preg_match('/^title:\s*(.+)$/mi', $content, $matches)) {
            return [
                'value' => trim($matches[1]),
                'confidence' => AIConfidence::High,
            ];
        }

        // Check for "need to" or "want to" patterns to infer title
        if (preg_match('/(?:need|want)\s+to\s+(?:build|create|develop|design|implement)\s+(?:a\s+)?(.+?)(?:\.|,|$)/i', $content, $matches)) {
            $inferred = Str::title(trim($matches[1]));

            return [
                'value' => Str::limit($inferred, 100, ''),
                'confidence' => AIConfidence::Medium,
            ];
        }

        // Extract first sentence as potential title
        $firstSentence = $this->extractFirstSentence($content);
        if ($firstSentence !== null && strlen($firstSentence) <= 100) {
            return [
                'value' => $firstSentence,
                'confidence' => AIConfidence::Low,
            ];
        }

        return ['value' => null, 'confidence' => AIConfidence::Low];
    }

    /**
     * Extract description from content.
     *
     * @return array{value: string|null, confidence: AIConfidence}
     */
    private function extractDescription(string $content): array
    {
        // Check for explicit description pattern
        if (preg_match('/^description:\s*(.+?)(?=\n(?:priority|deadline|scope|title|estimated):|$)/msi', $content, $matches)) {
            return [
                'value' => trim($matches[1]),
                'confidence' => AIConfidence::High,
            ];
        }

        // Use the full content as description (excluding explicit fields)
        $cleaned = preg_replace('/^(title|priority|deadline|estimated\s*hours?):\s*.+$/mi', '', $content);
        $cleaned = trim($cleaned);

        if (! empty($cleaned)) {
            return [
                'value' => Str::limit($cleaned, 1000),
                'confidence' => AIConfidence::Medium,
            ];
        }

        return ['value' => null, 'confidence' => AIConfidence::Low];
    }

    /**
     * Extract scope from content.
     *
     * @return array{value: string|null, confidence: AIConfidence}
     */
    private function extractScope(string $content): array
    {
        // Check for explicit scope section
        if (preg_match('/(?:scope|includes?):\s*\n?((?:[\s\S]*?)(?=\n\n|priority:|deadline:|$))/i', $content, $matches)) {
            $scope = trim($matches[1]);

            // Extract bullet points if present (use proper regex with escaped characters)
            if (preg_match_all('/^[\s\-\*]+(.+)$/m', $scope, $bullets)) {
                $scope = implode("\n", array_map('trim', $bullets[1]));
            }

            return [
                'value' => $scope,
                'confidence' => AIConfidence::High,
            ];
        }

        // Check for bullet list pattern that might indicate scope
        if (preg_match_all('/^[\s\-\*]+(.+)$/m', $content, $matches)) {
            $bullets = array_map('trim', $matches[1]);

            return [
                'value' => implode("\n", $bullets),
                'confidence' => AIConfidence::Medium,
            ];
        }

        return ['value' => null, 'confidence' => AIConfidence::Low];
    }

    /**
     * Extract success criteria from content.
     *
     * @return array{value: array<string>|null, confidence: AIConfidence}
     */
    private function extractSuccessCriteria(string $content): array
    {
        // Check for explicit success criteria section
        $patterns = [
            '/(?:success\s*criteria|acceptance\s*criteria|definition\s*of\s*done|requirements?):\s*\n?((?:[\s\S]*?)(?=\n\n|priority:|deadline:|$))/i',
            '/(?:should|must|needs?\s+to)(?:\s+be\s+able\s+to)?\s+(.+?)(?:\.|$)/mi',
        ];

        if (preg_match($patterns[0], $content, $matches)) {
            $criteria = trim($matches[1]);
            $items = preg_split('/[\n,;]+/', $criteria);
            $items = array_filter(array_map('trim', $items));

            if (! empty($items)) {
                return [
                    'value' => array_values($items),
                    'confidence' => AIConfidence::High,
                ];
            }
        }

        // Extract "should" statements as inferred criteria
        if (preg_match_all($patterns[1], $content, $matches)) {
            $criteria = array_unique(array_map('trim', $matches[1]));

            return [
                'value' => array_values($criteria),
                'confidence' => AIConfidence::Medium,
            ];
        }

        return ['value' => null, 'confidence' => AIConfidence::Low];
    }

    /**
     * Extract estimated hours from content.
     *
     * @return array{value: float|null, confidence: AIConfidence}
     */
    private function extractEstimatedHours(string $content): array
    {
        // Check for explicit hours patterns
        $patterns = [
            '/(?:estimated?\s*(?:hours?|time|effort)|estimate):\s*(\d+(?:\.\d+)?)\s*(?:hours?)?/i',
            '/(?:take|takes|requires?|need)\s*(?:about|approximately|around)?\s*(\d+(?:\.\d+)?)\s*hours?/i',
            '/(\d+(?:\.\d+)?)\s*hours?\s*(?:of\s+work|effort|estimate)/i',
        ];

        foreach ($patterns as $index => $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $hours = (float) $matches[1];

                // First pattern is explicit, others are inferred
                $confidence = $index === 0 ? AIConfidence::High : AIConfidence::Medium;

                return [
                    'value' => $hours,
                    'confidence' => $confidence,
                ];
            }
        }

        // Check for day estimates and convert
        if (preg_match('/(\d+(?:\.\d+)?)\s*days?/i', $content, $matches)) {
            $days = (float) $matches[1];

            return [
                'value' => $days * 8, // Assume 8 hours per day
                'confidence' => AIConfidence::Low,
            ];
        }

        return ['value' => null, 'confidence' => AIConfidence::Low];
    }

    /**
     * Extract priority from content.
     *
     * @return array{value: string|null, confidence: AIConfidence}
     */
    private function extractPriority(string $content): array
    {
        // Check for explicit priority
        if (preg_match('/priority:\s*(high|medium|low|urgent|critical|normal)/i', $content, $matches)) {
            return [
                'value' => $this->normalizePriority($matches[1]),
                'confidence' => AIConfidence::High,
            ];
        }

        // Check for priority keywords in context
        $urgentPatterns = [
            '/\b(?:urgent|asap|critical|immediately|emergency)\b/i',
            '/\b(?:high\s*priority)\b/i',
        ];

        foreach ($urgentPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return [
                    'value' => 'high',
                    'confidence' => AIConfidence::Medium,
                ];
            }
        }

        // Check for low priority indicators
        if (preg_match('/\b(?:low\s*priority|when\s*(?:you\s*)?(?:get\s*)?(?:a\s*)?chance|not\s*urgent|nice\s*to\s*have)\b/i', $content)) {
            return [
                'value' => 'low',
                'confidence' => AIConfidence::Medium,
            ];
        }

        return ['value' => null, 'confidence' => AIConfidence::Low];
    }

    /**
     * Extract deadline from content.
     *
     * @return array{value: string|null, confidence: AIConfidence}
     */
    private function extractDeadline(string $content): array
    {
        // Check for explicit deadline
        if (preg_match('/deadline:\s*(\d{4}-\d{2}-\d{2})/i', $content, $matches)) {
            return [
                'value' => $matches[1],
                'confidence' => AIConfidence::High,
            ];
        }

        // Check for "by" date patterns
        $datePatterns = [
            '/(?:by|due|deadline|before)\s+(?:the\s+)?(\d{4}-\d{2}-\d{2})/i',
            '/(?:by|due|deadline|before)\s+(?:the\s+)?(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/(?:by|due|before)\s+(?:next\s+)?(monday|tuesday|wednesday|thursday|friday|saturday|sunday)/i',
            '/(?:by|due|before)\s+(?:the\s+)?end\s+of\s+(?:this\s+)?(week|month)/i',
        ];

        if (preg_match($datePatterns[0], $content, $matches)) {
            return [
                'value' => $matches[1],
                'confidence' => AIConfidence::High,
            ];
        }

        if (preg_match($datePatterns[1], $content, $matches)) {
            $parsed = $this->parseRelativeDate($matches[1]);

            return [
                'value' => $parsed,
                'confidence' => AIConfidence::Medium,
            ];
        }

        if (preg_match($datePatterns[2], $content, $matches)) {
            $day = strtolower($matches[1]);
            $date = $this->getNextWeekday($day);

            return [
                'value' => $date,
                'confidence' => AIConfidence::Medium,
            ];
        }

        if (preg_match($datePatterns[3], $content, $matches)) {
            $period = strtolower($matches[1]);
            $date = $period === 'week'
                ? now()->endOfWeek()->format('Y-m-d')
                : now()->endOfMonth()->format('Y-m-d');

            return [
                'value' => $date,
                'confidence' => AIConfidence::Medium,
            ];
        }

        return ['value' => null, 'confidence' => AIConfidence::Low];
    }

    /**
     * Normalize message content for parsing.
     */
    private function normalizeContent(string $content): string
    {
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);

        // Remove excessive whitespace while preserving paragraph breaks
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
    }

    /**
     * Extract the first sentence from content.
     */
    private function extractFirstSentence(string $content): ?string
    {
        $lines = explode("\n", $content);
        $firstLine = trim($lines[0] ?? '');

        if (empty($firstLine)) {
            return null;
        }

        // Check if first line ends with sentence punctuation
        if (preg_match('/^(.+?[.!?])/', $firstLine, $matches)) {
            return trim($matches[1]);
        }

        return $firstLine;
    }

    /**
     * Normalize priority value to standard format.
     */
    private function normalizePriority(string $priority): string
    {
        $priority = strtolower(trim($priority));

        return match ($priority) {
            'urgent', 'critical' => 'high',
            'normal' => 'medium',
            default => $priority,
        };
    }

    /**
     * Parse relative date formats.
     */
    private function parseRelativeDate(string $date): string
    {
        $date = trim($date);

        // Try to parse common formats
        $formats = ['m/d/Y', 'm-d-Y', 'd/m/Y', 'd-m-Y', 'm/d/y', 'm-d-y'];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $date);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        // Fall back to strtotime
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return $date;
    }

    /**
     * Get the next occurrence of a weekday.
     */
    private function getNextWeekday(string $day): string
    {
        $date = now();
        $targetDay = match ($day) {
            'monday' => 1, 'tuesday' => 2, 'wednesday' => 3,
            'thursday' => 4, 'friday' => 5, 'saturday' => 6, 'sunday' => 0,
            default => 1,
        };

        $currentDay = (int) $date->format('w');
        $daysUntil = ($targetDay - $currentDay + 7) % 7;

        // If today, get next week's occurrence
        if ($daysUntil === 0) {
            $daysUntil = 7;
        }

        return $date->addDays($daysUntil)->format('Y-m-d');
    }

    /**
     * Extract keywords from requirements for playbook matching.
     *
     * @return array<string>
     */
    private function extractKeywords(array $requirements): array
    {
        $keywords = [];

        // Extract from title
        if (! empty($requirements['title']['value'])) {
            $titleWords = $this->extractSignificantWords($requirements['title']['value']);
            $keywords = array_merge($keywords, $titleWords);
        }

        // Extract from description
        if (! empty($requirements['description']['value'])) {
            $descWords = $this->extractSignificantWords($requirements['description']['value']);
            $keywords = array_merge($keywords, $descWords);
        }

        // Extract from scope
        if (! empty($requirements['scope']['value'])) {
            $scopeWords = $this->extractSignificantWords($requirements['scope']['value']);
            $keywords = array_merge($keywords, $scopeWords);
        }

        return array_unique($keywords);
    }

    /**
     * Extract significant words from text (removing stop words).
     *
     * @return array<string>
     */
    private function extractSignificantWords(string $text): array
    {
        $stopWords = [
            'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
            'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need',
            'dare', 'ought', 'used', 'this', 'that', 'these', 'those', 'it',
            'its', 'we', 'our', 'you', 'your', 'they', 'their', 'i', 'me', 'my',
        ];

        $words = preg_split('/[\s\-_.,;:!?\'"()\[\]{}]+/', strtolower($text));
        $words = array_filter($words, function ($word) use ($stopWords) {
            return strlen($word) >= 3 && ! in_array($word, $stopWords, true);
        });

        return array_values($words);
    }

    /**
     * Check for semantic match between two strings.
     */
    private function isSemanticMatch(string $keyword, string $target): bool
    {
        // Exact match
        if ($keyword === $target) {
            return true;
        }

        // Substring match
        if (Str::contains($target, $keyword) || Str::contains($keyword, $target)) {
            return true;
        }

        // Common variations
        $synonyms = [
            'develop' => ['build', 'create', 'implement', 'code'],
            'design' => ['ui', 'ux', 'visual', 'interface'],
            'web' => ['website', 'frontend', 'frontend'],
            'api' => ['backend', 'rest', 'endpoint'],
            'database' => ['db', 'sql', 'data'],
            'test' => ['testing', 'qa', 'quality'],
            'deploy' => ['deployment', 'release', 'launch'],
        ];

        foreach ($synonyms as $base => $related) {
            if (($keyword === $base || in_array($keyword, $related, true)) &&
                ($target === $base || in_array($target, $related, true))) {
                return true;
            }
        }

        return false;
    }
}
