<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Stateless utility for extracting meaningful search keywords from text.
 *
 * Combines multiple input texts, strips noise words and short tokens,
 * and returns a deduplicated list of keywords suitable for database queries.
 */
class KeywordExtractor
{
    /**
     * Common stopwords merged from all previous implementations
     * (PMCopilotWorkflow, DeliverableGeneratorService, TaskBreakdownService).
     *
     * @var array<int, string>
     */
    private const STOPWORDS = [
        // Articles & conjunctions
        'a', 'an', 'the', 'and', 'or', 'but', 'nor', 'not', 'no', 'so',
        // Prepositions
        'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as',
        'into', 'through', 'during', 'before', 'after', 'above', 'below',
        'between', 'out', 'off', 'over', 'under', 'up', 'about',
        // Be / have / do
        'is', 'are', 'was', 'were', 'be', 'been', 'being',
        'have', 'has', 'had', 'do', 'does', 'did',
        // Modals
        'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need',
        // Pronouns & determiners
        'this', 'that', 'these', 'those', 'it', 'its', 'we', 'our', 'you',
        'your', 'they', 'their', 'them', 'i', 'me', 'my',
        // Adverbs & misc
        'again', 'further', 'then', 'once', 'here', 'there', 'when', 'where',
        'why', 'how', 'all', 'each', 'every', 'both', 'few', 'more', 'most',
        'other', 'some', 'such', 'only', 'own', 'same', 'than', 'too', 'very',
        'just', 'because', 'also', 'which', 'what',
        // Action verbs (from DeliverableGeneratorService / TaskBreakdownService)
        'new', 'create', 'build', 'implement', 'develop', 'make', 'add',
        'fix', 'update', 'change',
    ];

    /**
     * Extract unique, meaningful keywords from one or more text inputs.
     *
     * @return array<int, string>
     */
    public static function extract(string ...$texts): array
    {
        $combined = mb_strtolower(implode(' ', array_filter($texts)));

        if (trim($combined) === '') {
            return [];
        }

        // Strip non-alphanumeric characters (keep spaces)
        $combined = (string) preg_replace('/[^a-z0-9\s]/', ' ', $combined);

        $words = preg_split('/\s+/', $combined, -1, PREG_SPLIT_NO_EMPTY);

        if (! is_array($words)) {
            return [];
        }

        $seen = [];
        $result = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 3) {
                continue;
            }
            if (in_array($word, self::STOPWORDS, true)) {
                continue;
            }
            if (isset($seen[$word])) {
                continue;
            }
            $seen[$word] = true;
            $result[] = $word;
        }

        return $result;
    }
}
