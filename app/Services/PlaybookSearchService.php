<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Playbook;
use Illuminate\Support\Collection;

/**
 * Shared service for finding playbooks relevant to a set of keywords.
 *
 * Uses a two-strategy approach:
 * 1. Per-keyword Eloquent query against name, description, and tags.
 * 2. Fallback to the team's most-used playbooks when keyword results are sparse.
 */
class PlaybookSearchService
{
    /**
     * Find playbooks relevant to the given keywords for a team.
     *
     * @param  array<int, string>  $keywords
     * @return Collection<int, Playbook>
     */
    public function findRelevantPlaybooks(int $teamId, array $keywords, int $limit = 5): Collection
    {
        $collected = collect();

        // Strategy 1: Per-keyword search against name, description, and tags
        if (! empty($keywords)) {
            $collected = Playbook::query()
                ->forTeam($teamId)
                ->where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere('name', 'like', "%{$keyword}%")
                            ->orWhere('description', 'like', "%{$keyword}%")
                            ->orWhereJsonContains('tags', $keyword);
                    }
                })
                ->orderByDesc('times_applied')
                ->limit($limit)
                ->get()
                ->keyBy('id');
        }

        // Strategy 2: Fallback to most-used playbooks when keyword results are sparse
        if ($collected->count() < $limit) {
            $remaining = $limit - $collected->count();
            $excludeIds = $collected->keys()->all();

            $fallback = Playbook::query()
                ->forTeam($teamId)
                ->when(! empty($excludeIds), fn ($q) => $q->whereNotIn('id', $excludeIds))
                ->orderByDesc('times_applied')
                ->limit($remaining)
                ->get()
                ->keyBy('id');

            $collected = $collected->union($fallback);
        }

        return $collected->values()->take($limit);
    }
}
