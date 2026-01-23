<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AIConfidence;
use App\Models\Playbook;
use App\Models\WorkOrder;
use App\ValueObjects\TaskSuggestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Service for generating task breakdown suggestions from work order and deliverables.
 *
 * Analyzes work order context and deliverables to generate 2-3 alternative
 * task breakdown structures. Incorporates playbook task templates and
 * provides LLM-based hour estimates for each task.
 *
 * When LLM integration is available (via neuron-ai), this service will
 * use the LLM to generate more sophisticated breakdowns. Until then,
 * it uses rule-based heuristics to provide useful suggestions.
 */
class TaskBreakdownService
{
    /**
     * Number of alternatives to generate per work order.
     */
    private const MIN_ALTERNATIVES = 2;

    private const MAX_ALTERNATIVES = 3;

    /**
     * Default estimated hours when no context available.
     */
    private const DEFAULT_TASK_HOURS = 2.0;

    /**
     * Generate task breakdown alternatives for a work order.
     *
     * @param  WorkOrder  $workOrder  The work order to analyze
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables  Array of deliverable data
     * @return array<int, array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}> Array of 2-3 breakdown alternatives
     */
    public function generateBreakdown(WorkOrder $workOrder, array $deliverables): array
    {
        // Query relevant playbooks
        $playbooks = $this->findRelevantPlaybooks($workOrder, $deliverables);

        // Build the prompt context
        $context = $this->buildPromptContext($workOrder, $deliverables, $playbooks);

        // Determine confidence based on context clarity
        $baseConfidence = $this->determineBaseConfidence($workOrder, $deliverables, $playbooks);

        // Generate alternatives using available information
        $alternatives = $this->generateAlternatives($workOrder, $deliverables, $playbooks, $context, $baseConfidence);

        // Ensure we have between MIN and MAX alternatives
        return array_slice($alternatives, 0, self::MAX_ALTERNATIVES);
    }

    /**
     * Build the LLM prompt with work order and deliverable context.
     *
     * @param  WorkOrder  $workOrder  The work order being analyzed
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @param  Collection<int, Playbook>  $playbooks  Relevant playbooks
     * @return array{work_order: array, deliverables: array, playbooks: array, prompt: string}
     */
    public function buildPromptContext(WorkOrder $workOrder, array $deliverables, Collection $playbooks): array
    {
        $workOrderContext = [
            'id' => $workOrder->id,
            'title' => $workOrder->title,
            'description' => $workOrder->description,
            'acceptance_criteria' => $workOrder->acceptance_criteria,
            'estimated_hours' => $workOrder->estimated_hours,
            'priority' => $workOrder->priority?->value,
        ];

        $deliverablesContext = array_map(fn (array $d) => [
            'title' => $d['title'],
            'description' => $d['description'] ?? null,
            'type' => $d['type'] ?? 'other',
        ], $deliverables);

        $playbookContext = $playbooks->map(fn (Playbook $playbook) => [
            'id' => $playbook->id,
            'name' => $playbook->name,
            'description' => $playbook->description,
            'type' => $playbook->type?->value,
            'content' => $playbook->content,
            'tags' => $playbook->tags,
        ])->toArray();

        $prompt = $this->constructLLMPrompt($workOrderContext, $deliverablesContext, $playbookContext);

        return [
            'work_order' => $workOrderContext,
            'deliverables' => $deliverablesContext,
            'playbooks' => $playbookContext,
            'prompt' => $prompt,
        ];
    }

    /**
     * Find playbooks relevant to the work order and deliverables.
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @return Collection<int, Playbook>
     */
    private function findRelevantPlaybooks(WorkOrder $workOrder, array $deliverables): Collection
    {
        $keywords = $this->extractKeywords($workOrder, $deliverables);

        if (empty($keywords)) {
            return collect();
        }

        return Playbook::query()
            ->forTeam($workOrder->team_id)
            ->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhereJsonContains('tags', $keyword);
                }
            })
            ->orderByDesc('times_applied')
            ->limit(5)
            ->get();
    }

    /**
     * Extract keywords from work order and deliverables for playbook matching.
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @return array<string>
     */
    private function extractKeywords(WorkOrder $workOrder, array $deliverables): array
    {
        $textParts = [
            $workOrder->title,
            $workOrder->description,
        ];

        foreach ($deliverables as $deliverable) {
            $textParts[] = $deliverable['title'];
            $textParts[] = $deliverable['description'] ?? '';
        }

        $text = implode(' ', array_filter($textParts));

        $stopWords = [
            'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
            'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
            'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would',
            'could', 'should', 'may', 'might', 'must', 'shall', 'can', 'need',
            'this', 'that', 'these', 'those', 'it', 'its', 'we', 'our', 'you',
            'your', 'they', 'their', 'i', 'me', 'my', 'new', 'create', 'build',
            'implement', 'develop', 'make', 'add', 'fix', 'update', 'change',
        ];

        $words = preg_split('/[\s\-_.,;:!?\'"()\[\]{}]+/', strtolower($text));

        return array_values(array_unique(array_filter(
            $words ?? [],
            fn ($word) => strlen($word) >= 3 && ! in_array($word, $stopWords, true)
        )));
    }

    /**
     * Determine base confidence level from context clarity.
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @param  Collection<int, Playbook>  $playbooks
     */
    private function determineBaseConfidence(WorkOrder $workOrder, array $deliverables, Collection $playbooks): AIConfidence
    {
        $score = 0;

        // Has meaningful description
        if (! empty($workOrder->description) && strlen($workOrder->description) > 50) {
            $score += 2;
        }

        // Has acceptance criteria defined
        if (! empty($workOrder->acceptance_criteria) && is_array($workOrder->acceptance_criteria)) {
            $score += 2;
        }

        // Has estimated hours
        if (! empty($workOrder->estimated_hours) && $workOrder->estimated_hours > 0) {
            $score += 1;
        }

        // Has relevant playbooks with task templates
        if ($playbooks->isNotEmpty()) {
            $hasTaskTemplates = $playbooks->contains(function (Playbook $p) {
                $content = $p->content ?? [];

                return isset($content['tasks']) && is_array($content['tasks']);
            });
            $score += $hasTaskTemplates ? 2 : 1;
        }

        // Has multiple deliverables with descriptions
        $describedDeliverables = collect($deliverables)->filter(fn ($d) => ! empty($d['description']))->count();
        if ($describedDeliverables >= 1) {
            $score += 1;
        }

        return match (true) {
            $score >= 6 => AIConfidence::High,
            $score >= 3 => AIConfidence::Medium,
            default => AIConfidence::Low,
        };
    }

    /**
     * Generate task breakdown alternatives based on context.
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @param  Collection<int, Playbook>  $playbooks
     * @return array<int, array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}>
     */
    private function generateAlternatives(
        WorkOrder $workOrder,
        array $deliverables,
        Collection $playbooks,
        array $_context,
        AIConfidence $baseConfidence
    ): array {
        $alternatives = [];

        // Strategy 1: Playbook-based breakdown (if playbook with tasks available)
        $playbookWithTasks = $playbooks->first(function (Playbook $p) {
            $content = $p->content ?? [];

            return isset($content['tasks']) && is_array($content['tasks']);
        });

        if ($playbookWithTasks !== null) {
            $playbookAlternative = $this->generateFromPlaybook($workOrder, $deliverables, $playbookWithTasks, $baseConfidence);
            if ($playbookAlternative !== null) {
                $alternatives[] = $playbookAlternative;
            }
        }

        // Strategy 2: Deliverable-focused breakdown
        $deliverableFocused = $this->generateDeliverableFocusedBreakdown($workOrder, $deliverables, $playbooks, $baseConfidence);
        if ($deliverableFocused !== null) {
            $alternatives[] = $deliverableFocused;
        }

        // Strategy 3: Phase-based breakdown (setup, implementation, testing)
        $phaseBased = $this->generatePhaseBasedBreakdown($workOrder, $deliverables, $baseConfidence);
        if ($phaseBased !== null) {
            $alternatives[] = $phaseBased;
        }

        // Ensure we have at least MIN_ALTERNATIVES
        if (count($alternatives) < self::MIN_ALTERNATIVES) {
            $fallback = $this->generateFallbackBreakdown($workOrder, $deliverables, AIConfidence::Low);
            $alternatives[] = $fallback;
        }

        // Apply dependency detection to all alternatives
        return array_map(fn ($alt) => $this->applyDependencyDetection($alt), $alternatives);
    }

    /**
     * Generate a breakdown based on playbook task templates.
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @return array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}|null
     */
    private function generateFromPlaybook(
        WorkOrder $workOrder,
        array $_deliverables,
        Playbook $playbook,
        AIConfidence $baseConfidence
    ): ?array {
        $content = $playbook->content ?? [];
        $taskTemplates = $content['tasks'] ?? [];

        if (empty($taskTemplates)) {
            return null;
        }

        $tasks = [];
        $position = 1;
        $totalEstimatedHours = (float) ($workOrder->estimated_hours ?? 0);

        foreach ($taskTemplates as $template) {
            $estimatedHours = $this->estimateTaskHours($template, $totalEstimatedHours, count($taskTemplates));

            $checklistItems = [];
            if (isset($template['checklist']) && is_array($template['checklist'])) {
                $checklistItems = $template['checklist'];
            } elseif (isset($content['checklist']) && is_array($content['checklist'])) {
                // Use global playbook checklist if no task-specific one
                $checklistItems = $content['checklist'];
            }

            $tasks[] = new TaskSuggestion(
                title: $template['title'] ?? 'Task '.$position,
                description: $template['description'] ?? null,
                estimatedHours: $estimatedHours,
                position: $position,
                checklistItems: $checklistItems,
                dependencies: [],
                confidence: $baseConfidence,
                reasoning: "Based on playbook: {$playbook->name}",
                playbookId: $playbook->id,
            );

            $position++;
        }

        return [
            'tasks' => $tasks,
            'confidence' => $baseConfidence,
            'reasoning' => "Task breakdown generated from playbook: {$playbook->name}",
        ];
    }

    /**
     * Generate a breakdown focused on each deliverable.
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @param  Collection<int, Playbook>  $playbooks
     * @return array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}|null
     */
    private function generateDeliverableFocusedBreakdown(
        WorkOrder $workOrder,
        array $deliverables,
        Collection $playbooks,
        AIConfidence $baseConfidence
    ): ?array {
        if (empty($deliverables)) {
            return null;
        }

        $tasks = [];
        $position = 1;
        $totalEstimatedHours = (float) ($workOrder->estimated_hours ?? 0);
        $hoursPerDeliverable = $totalEstimatedHours > 0
            ? $totalEstimatedHours / count($deliverables)
            : self::DEFAULT_TASK_HOURS * 3;

        // Get checklist from any available playbook
        $checklistFromPlaybook = $this->extractChecklistFromPlaybooks($playbooks);

        foreach ($deliverables as $deliverable) {
            $type = $deliverable['type'] ?? 'other';
            $tasksForDeliverable = $this->generateTasksForDeliverable($deliverable, $type, $hoursPerDeliverable, $position, $checklistFromPlaybook);

            foreach ($tasksForDeliverable as $task) {
                $tasks[] = $task;
                $position++;
            }
        }

        return [
            'tasks' => $tasks,
            'confidence' => $baseConfidence,
            'reasoning' => 'Task breakdown organized by deliverable with standard phases for each',
        ];
    }

    /**
     * Generate tasks for a specific deliverable.
     *
     * @param  array{title: string, description?: string, type?: string}  $deliverable
     * @param  array<string>  $checklistFromPlaybook
     * @return array<TaskSuggestion>
     */
    private function generateTasksForDeliverable(
        array $deliverable,
        string $type,
        float $hoursForDeliverable,
        int $startPosition,
        array $checklistFromPlaybook
    ): array {
        $tasks = [];
        $title = $deliverable['title'];

        // Different task patterns based on deliverable type
        $taskPatterns = match (strtolower($type)) {
            'code' => [
                ['suffix' => 'Setup', 'percent' => 0.15, 'checklist' => ['Environment setup', 'Dependencies installed']],
                ['suffix' => 'Implementation', 'percent' => 0.50, 'checklist' => $checklistFromPlaybook ?: ['Write code', 'Follow patterns']],
                ['suffix' => 'Testing', 'percent' => 0.20, 'checklist' => ['Unit tests', 'Integration tests']],
                ['suffix' => 'Review', 'percent' => 0.15, 'checklist' => ['Code review', 'Documentation']],
            ],
            'document' => [
                ['suffix' => 'Research', 'percent' => 0.20, 'checklist' => ['Gather requirements', 'Review sources']],
                ['suffix' => 'Draft', 'percent' => 0.50, 'checklist' => $checklistFromPlaybook ?: ['Create outline', 'Write content']],
                ['suffix' => 'Review', 'percent' => 0.30, 'checklist' => ['Proofread', 'Get feedback', 'Finalize']],
            ],
            'design' => [
                ['suffix' => 'Research', 'percent' => 0.20, 'checklist' => ['Analyze requirements', 'Review references']],
                ['suffix' => 'Wireframes', 'percent' => 0.30, 'checklist' => ['Create wireframes', 'Get feedback']],
                ['suffix' => 'Design', 'percent' => 0.35, 'checklist' => $checklistFromPlaybook ?: ['Create mockups', 'Iterate design']],
                ['suffix' => 'Handoff', 'percent' => 0.15, 'checklist' => ['Prepare assets', 'Document specs']],
            ],
            'report' => [
                ['suffix' => 'Data Collection', 'percent' => 0.30, 'checklist' => ['Gather data', 'Verify sources']],
                ['suffix' => 'Analysis', 'percent' => 0.40, 'checklist' => $checklistFromPlaybook ?: ['Analyze data', 'Draw conclusions']],
                ['suffix' => 'Compile Report', 'percent' => 0.30, 'checklist' => ['Write report', 'Add visuals', 'Review']],
            ],
            default => [
                ['suffix' => 'Planning', 'percent' => 0.20, 'checklist' => ['Define scope', 'Create plan']],
                ['suffix' => 'Execution', 'percent' => 0.60, 'checklist' => $checklistFromPlaybook ?: ['Execute work', 'Track progress']],
                ['suffix' => 'Completion', 'percent' => 0.20, 'checklist' => ['Verify completion', 'Handoff']],
            ],
        };

        $position = $startPosition;
        foreach ($taskPatterns as $pattern) {
            $tasks[] = new TaskSuggestion(
                title: "{$title} - {$pattern['suffix']}",
                description: "Handle {$pattern['suffix']} phase for: {$title}",
                estimatedHours: round($hoursForDeliverable * $pattern['percent'], 1),
                position: $position,
                checklistItems: $pattern['checklist'],
                dependencies: [],
                confidence: AIConfidence::Medium,
            );
            $position++;
        }

        return $tasks;
    }

    /**
     * Generate a phase-based breakdown (setup, implementation, testing).
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @return array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}
     */
    private function generatePhaseBasedBreakdown(
        WorkOrder $workOrder,
        array $deliverables,
        AIConfidence $baseConfidence
    ): array {
        $totalEstimatedHours = (float) ($workOrder->estimated_hours ?? 0);
        if ($totalEstimatedHours <= 0) {
            $totalEstimatedHours = self::DEFAULT_TASK_HOURS * 5;
        }

        // Lower confidence for phase-based as it's more generic
        $confidence = $baseConfidence === AIConfidence::High
            ? AIConfidence::Medium
            : $baseConfidence;

        $phases = [
            ['name' => 'Planning & Setup', 'percent' => 0.15, 'checklist' => ['Review requirements', 'Setup environment', 'Create plan']],
            ['name' => 'Implementation', 'percent' => 0.50, 'checklist' => ['Execute main work', 'Follow standards', 'Track progress']],
            ['name' => 'Testing & Validation', 'percent' => 0.20, 'checklist' => ['Test functionality', 'Validate requirements', 'Fix issues']],
            ['name' => 'Review & Handoff', 'percent' => 0.15, 'checklist' => ['Documentation', 'Code review', 'Stakeholder approval']],
        ];

        $tasks = [];
        $position = 1;

        foreach ($phases as $phase) {
            $deliverableContext = ! empty($deliverables) ? " for: {$deliverables[0]['title']}" : '';

            $tasks[] = new TaskSuggestion(
                title: "{$phase['name']}{$deliverableContext}",
                description: "Handle {$phase['name']} phase for the work order: {$workOrder->title}",
                estimatedHours: round($totalEstimatedHours * $phase['percent'], 1),
                position: $position,
                checklistItems: $phase['checklist'],
                dependencies: [],
                confidence: $confidence,
            );
            $position++;
        }

        return [
            'tasks' => $tasks,
            'confidence' => $confidence,
            'reasoning' => 'Phase-based breakdown following standard project lifecycle (planning, implementation, testing, review)',
        ];
    }

    /**
     * Generate a fallback breakdown when insufficient context.
     *
     * @param  array<int, array{title: string, description?: string, type?: string}>  $deliverables
     * @return array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}
     */
    private function generateFallbackBreakdown(
        WorkOrder $workOrder,
        array $deliverables,
        AIConfidence $confidence
    ): array {
        $deliverableTitle = ! empty($deliverables) ? $deliverables[0]['title'] : $workOrder->title;

        return [
            'tasks' => [
                new TaskSuggestion(
                    title: "Complete: {$deliverableTitle}",
                    description: "Primary task for completing: {$deliverableTitle}",
                    estimatedHours: self::DEFAULT_TASK_HOURS * 2,
                    position: 1,
                    checklistItems: ['Start work', 'Complete work', 'Verify completion'],
                    dependencies: [],
                    confidence: $confidence,
                    reasoning: 'Fallback task due to limited context',
                ),
                new TaskSuggestion(
                    title: "Review: {$deliverableTitle}",
                    description: "Review and finalize: {$deliverableTitle}",
                    estimatedHours: self::DEFAULT_TASK_HOURS,
                    position: 2,
                    checklistItems: ['Review work', 'Get approval'],
                    dependencies: [],
                    confidence: $confidence,
                    reasoning: 'Fallback review task due to limited context',
                ),
            ],
            'confidence' => $confidence,
            'reasoning' => 'Fallback task breakdown due to limited context information',
        ];
    }

    /**
     * Apply dependency detection to task breakdown.
     *
     * Analyzes task descriptions and titles for implicit dependencies
     * and sets dependencies array with task references.
     *
     * @param  array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}  $alternative
     * @return array{tasks: array<TaskSuggestion>, confidence: AIConfidence, reasoning: string}
     */
    private function applyDependencyDetection(array $alternative): array
    {
        $tasks = $alternative['tasks'];
        $updatedTasks = [];

        foreach ($tasks as $index => $task) {
            $dependencies = $this->detectDependencies($task, $tasks, $index);

            if (! empty($dependencies)) {
                // Create new TaskSuggestion with detected dependencies
                $updatedTasks[] = new TaskSuggestion(
                    title: $task->title,
                    description: $task->description,
                    estimatedHours: $task->estimatedHours,
                    position: $task->position,
                    checklistItems: $task->checklistItems,
                    dependencies: $dependencies,
                    confidence: $task->confidence,
                    reasoning: $task->reasoning,
                    playbookId: $task->playbookId,
                );
            } else {
                $updatedTasks[] = $task;
            }
        }

        return [
            'tasks' => $updatedTasks,
            'confidence' => $alternative['confidence'],
            'reasoning' => $alternative['reasoning'],
        ];
    }

    /**
     * Detect dependencies for a task based on task patterns and keywords.
     *
     * @param  TaskSuggestion  $task
     * @param  array<TaskSuggestion>  $allTasks
     * @param  int  $currentIndex
     * @return array<int>
     */
    private function detectDependencies(TaskSuggestion $task, array $allTasks, int $currentIndex): array
    {
        $dependencies = [];
        $titleLower = strtolower($task->title);
        $descLower = strtolower($task->description ?? '');

        // Keywords that indicate a task should follow another
        $afterKeywords = ['review', 'testing', 'validation', 'handoff', 'finalize', 'complete', 'approval'];
        $beforeKeywords = ['setup', 'planning', 'research', 'wireframe', 'draft', 'data collection'];

        // Check if current task should depend on previous tasks
        foreach ($afterKeywords as $keyword) {
            if (Str::contains($titleLower, $keyword) || Str::contains($descLower, $keyword)) {
                // This task likely depends on implementation/execution tasks before it
                for ($i = 0; $i < $currentIndex; $i++) {
                    $prevTitle = strtolower($allTasks[$i]->title);
                    if (Str::contains($prevTitle, 'implementation') ||
                        Str::contains($prevTitle, 'execution') ||
                        Str::contains($prevTitle, 'develop') ||
                        Str::contains($prevTitle, 'design') ||
                        Str::contains($prevTitle, 'draft')) {
                        $dependencies[] = $allTasks[$i]->position;
                    }
                }
            }
        }

        // If current task is implementation-like, it should depend on setup tasks
        $isImplementation = Str::contains($titleLower, 'implementation') ||
                            Str::contains($titleLower, 'execution') ||
                            Str::contains($titleLower, 'develop');

        if ($isImplementation) {
            for ($i = 0; $i < $currentIndex; $i++) {
                $prevTitle = strtolower($allTasks[$i]->title);
                foreach ($beforeKeywords as $keyword) {
                    if (Str::contains($prevTitle, $keyword)) {
                        $dependencies[] = $allTasks[$i]->position;
                        break;
                    }
                }
            }
        }

        return array_unique($dependencies);
    }

    /**
     * Estimate hours for a task based on template and work order context.
     */
    private function estimateTaskHours(array $template, float $totalWorkOrderHours, int $totalTasks): float
    {
        // If template has explicit hours, use them
        if (isset($template['estimated_hours']) && $template['estimated_hours'] > 0) {
            return (float) $template['estimated_hours'];
        }

        // If work order has total hours, distribute proportionally
        if ($totalWorkOrderHours > 0 && $totalTasks > 0) {
            return round($totalWorkOrderHours / $totalTasks, 1);
        }

        // Default estimate
        return self::DEFAULT_TASK_HOURS;
    }

    /**
     * Extract checklist items from relevant playbooks.
     *
     * @param  Collection<int, Playbook>  $playbooks
     * @return array<string>
     */
    private function extractChecklistFromPlaybooks(Collection $playbooks): array
    {
        foreach ($playbooks as $playbook) {
            $content = $playbook->content ?? [];

            if (isset($content['checklist']) && is_array($content['checklist'])) {
                return array_slice($content['checklist'], 0, 5);
            }

            if (isset($content['requirements']) && is_array($content['requirements'])) {
                return array_slice($content['requirements'], 0, 5);
            }
        }

        return [];
    }

    /**
     * Construct the LLM prompt for task breakdown generation.
     *
     * @param  array<string, mixed>  $workOrderContext
     * @param  array<int, array<string, mixed>>  $deliverablesContext
     * @param  array<int, array<string, mixed>>  $playbookContext
     */
    private function constructLLMPrompt(array $workOrderContext, array $deliverablesContext, array $playbookContext): string
    {
        $prompt = <<<'PROMPT'
You are a project management assistant specializing in task breakdown and estimation.

## Task
Analyze the following work order and deliverables to generate 2-3 alternative task breakdown structures. Each alternative should represent a different approach to organizing the work.

## Work Order
Title: {title}
Description: {description}
Estimated Hours: {estimated_hours}
Acceptance Criteria: {criteria}

## Deliverables
{deliverables_section}

{playbook_section}

## Instructions
1. Generate 2-3 distinct task breakdown alternatives
2. Each task should have:
   - A clear, actionable title
   - A detailed description
   - Estimated hours (must be greater than 0)
   - Position in the work order sequence
   - Relevant checklist items (3-5 items)
   - Dependencies on other tasks (by position number)
   - Confidence level (high, medium, low) based on context clarity
3. Consider any relevant playbook templates for task patterns
4. Order tasks by their dependency chain
5. Distribute estimated hours proportionally across tasks

## Response Format
Return a JSON array with the following structure:
```json
[
  {
    "reasoning": "Why this breakdown approach is recommended",
    "confidence": "high|medium|low",
    "tasks": [
      {
        "title": "Task title",
        "description": "Detailed description",
        "estimated_hours": 4.0,
        "position": 1,
        "checklist_items": ["item 1", "item 2"],
        "dependencies": [],
        "confidence": "high|medium|low",
        "playbook_id": null
      }
    ]
  }
]
```
PROMPT;

        // Replace placeholders
        $prompt = str_replace('{title}', $workOrderContext['title'] ?? 'Untitled', $prompt);
        $prompt = str_replace('{description}', $workOrderContext['description'] ?? 'No description', $prompt);
        $prompt = str_replace('{estimated_hours}', (string) ($workOrderContext['estimated_hours'] ?? 'Not specified'), $prompt);
        $prompt = str_replace(
            '{criteria}',
            ! empty($workOrderContext['acceptance_criteria'])
                ? implode(', ', $workOrderContext['acceptance_criteria'])
                : 'Not specified',
            $prompt
        );

        // Add deliverables section
        if (! empty($deliverablesContext)) {
            $deliverablesSection = '';
            foreach ($deliverablesContext as $index => $deliverable) {
                $num = $index + 1;
                $deliverablesSection .= "{$num}. {$deliverable['title']}\n";
                if (! empty($deliverable['description'])) {
                    $deliverablesSection .= "   Description: {$deliverable['description']}\n";
                }
                $deliverablesSection .= "   Type: {$deliverable['type']}\n";
            }
            $prompt = str_replace('{deliverables_section}', $deliverablesSection, $prompt);
        } else {
            $prompt = str_replace('{deliverables_section}', 'No deliverables specified', $prompt);
        }

        // Add playbook section if available
        if (! empty($playbookContext)) {
            $playbookSection = "## Relevant Playbooks\n";
            foreach ($playbookContext as $playbook) {
                $playbookSection .= "- {$playbook['name']}: {$playbook['description']}\n";
                if (! empty($playbook['tags'])) {
                    $playbookSection .= '  Tags: '.implode(', ', $playbook['tags'])."\n";
                }
                if (isset($playbook['content']['tasks']) && is_array($playbook['content']['tasks'])) {
                    $playbookSection .= '  Task templates available: '.count($playbook['content']['tasks'])."\n";
                }
            }
            $prompt = str_replace('{playbook_section}', $playbookSection, $prompt);
        } else {
            $prompt = str_replace('{playbook_section}', '', $prompt);
        }

        return $prompt;
    }
}
