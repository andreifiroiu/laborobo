<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkOrder;

class MentionParserService
{
    /**
     * Parse message content for mentions.
     *
     * @return array<int, array{type: string, class: class-string, id: int}>
     */
    public function parse(string $content): array
    {
        $mentions = [];

        // Parse @username patterns for user mentions
        $mentions = array_merge($mentions, $this->parseUserMentions($content));

        // Parse @P-{id} patterns for project mentions
        $mentions = array_merge($mentions, $this->parseProjectMentions($content));

        // Parse @WO-{id} patterns for work order mentions
        $mentions = array_merge($mentions, $this->parseWorkOrderMentions($content));

        // Parse @T-{id} patterns for task mentions
        $mentions = array_merge($mentions, $this->parseTaskMentions($content));

        return $mentions;
    }

    /**
     * Parse @username patterns.
     *
     * @return array<int, array{type: string, class: class-string, id: int}>
     */
    private function parseUserMentions(string $content): array
    {
        $mentions = [];

        // Match @username (alphanumeric characters, no spaces)
        if (preg_match_all('/@([a-zA-Z][a-zA-Z0-9_]*)(?![0-9-])/', $content, $matches)) {
            foreach ($matches[1] as $username) {
                // Skip patterns that look like work item mentions (P, WO, T followed by -)
                if (in_array(strtoupper($username), ['P', 'WO', 'T'], true)) {
                    continue;
                }

                $user = User::where('name', $username)->first();
                if ($user !== null) {
                    $mentions[] = [
                        'type' => 'user',
                        'class' => User::class,
                        'id' => $user->id,
                    ];
                }
            }
        }

        return $mentions;
    }

    /**
     * Parse @P-{id} patterns for projects.
     *
     * @return array<int, array{type: string, class: class-string, id: int}>
     */
    private function parseProjectMentions(string $content): array
    {
        $mentions = [];

        if (preg_match_all('/@P-(\d+)/', $content, $matches)) {
            foreach ($matches[1] as $id) {
                $project = Project::find((int) $id);
                if ($project !== null) {
                    $mentions[] = [
                        'type' => 'project',
                        'class' => Project::class,
                        'id' => $project->id,
                    ];
                }
            }
        }

        return $mentions;
    }

    /**
     * Parse @WO-{id} patterns for work orders.
     *
     * @return array<int, array{type: string, class: class-string, id: int}>
     */
    private function parseWorkOrderMentions(string $content): array
    {
        $mentions = [];

        if (preg_match_all('/@WO-(\d+)/', $content, $matches)) {
            foreach ($matches[1] as $id) {
                $workOrder = WorkOrder::find((int) $id);
                if ($workOrder !== null) {
                    $mentions[] = [
                        'type' => 'work_order',
                        'class' => WorkOrder::class,
                        'id' => $workOrder->id,
                    ];
                }
            }
        }

        return $mentions;
    }

    /**
     * Parse @T-{id} patterns for tasks.
     *
     * @return array<int, array{type: string, class: class-string, id: int}>
     */
    private function parseTaskMentions(string $content): array
    {
        $mentions = [];

        if (preg_match_all('/@T-(\d+)/', $content, $matches)) {
            foreach ($matches[1] as $id) {
                $task = Task::find((int) $id);
                if ($task !== null) {
                    $mentions[] = [
                        'type' => 'task',
                        'class' => Task::class,
                        'id' => $task->id,
                    ];
                }
            }
        }

        return $mentions;
    }
}
