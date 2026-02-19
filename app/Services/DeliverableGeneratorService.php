<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AIConfidence;
use App\Enums\DeliverableType;
use App\Models\Playbook;
use App\Models\WorkOrder;
use App\Services\AI\LLMService;
use App\ValueObjects\DeliverableSuggestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service for generating deliverable suggestions from work order context.
 *
 * Analyzes work order title, description, scope, and acceptance criteria
 * to generate 2-3 alternative deliverable structures. Incorporates relevant
 * playbook templates when available.
 *
 * When LLM integration is available (via neuron-ai), this service will
 * use the LLM to generate more sophisticated suggestions. Until then,
 * it uses rule-based heuristics to provide useful suggestions.
 */
class DeliverableGeneratorService
{
    /**
     * Number of alternatives to generate per work order.
     */
    private const MIN_ALTERNATIVES = 2;

    private const MAX_ALTERNATIVES = 3;

    public function __construct(
        private readonly ?LLMService $llmService = null,
    ) {}

    /**
     * Generate deliverable alternatives for a work order.
     *
     * @param  WorkOrder  $workOrder  The work order to analyze
     * @return array<DeliverableSuggestion> Array of 2-3 deliverable suggestions
     */
    public function generateAlternatives(WorkOrder $workOrder): array
    {
        // Query relevant playbooks
        $playbooks = $this->findRelevantPlaybooks($workOrder);

        // Build the prompt context
        $context = $this->buildPromptContext($workOrder, $playbooks);

        // Determine confidence based on context clarity
        $baseConfidence = $this->determineBaseConfidence($workOrder, $playbooks);

        // Generate alternatives using available information
        $alternatives = $this->generateSuggestions($workOrder, $playbooks, $context, $baseConfidence);

        // Ensure we have between MIN and MAX alternatives
        return array_slice($alternatives, 0, self::MAX_ALTERNATIVES);
    }

    /**
     * Build the LLM prompt with work order context.
     *
     * This method constructs a prompt suitable for LLM-based generation.
     * Currently returns context as a structured array, but will be used
     * with actual LLM calls when neuron-ai is integrated.
     *
     * @param  WorkOrder  $workOrder  The work order being analyzed
     * @param  Collection<int, Playbook>  $playbooks  Relevant playbooks
     * @return array{work_order: array, playbooks: array, prompt: string}
     */
    public function buildPromptContext(WorkOrder $workOrder, Collection $playbooks): array
    {
        $workOrderContext = [
            'id' => $workOrder->id,
            'title' => $workOrder->title,
            'description' => $workOrder->description,
            'acceptance_criteria' => $workOrder->acceptance_criteria,
            'estimated_hours' => $workOrder->estimated_hours,
            'priority' => $workOrder->priority?->value,
        ];

        $playbookContext = $playbooks->map(fn (Playbook $playbook) => [
            'id' => $playbook->id,
            'name' => $playbook->name,
            'description' => $playbook->description,
            'type' => $playbook->type?->value,
            'content' => $playbook->content,
            'tags' => $playbook->tags,
        ])->toArray();

        $prompt = $this->constructLLMPrompt($workOrderContext, $playbookContext);

        return [
            'work_order' => $workOrderContext,
            'playbooks' => $playbookContext,
            'prompt' => $prompt,
        ];
    }

    /**
     * Find playbooks relevant to the work order.
     *
     * @return Collection<int, Playbook>
     */
    private function findRelevantPlaybooks(WorkOrder $workOrder): Collection
    {
        $keywords = $this->extractKeywords($workOrder);

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
     * Extract keywords from work order for playbook matching.
     *
     * @return array<string>
     */
    private function extractKeywords(WorkOrder $workOrder): array
    {
        $text = collect([
            $workOrder->title,
            $workOrder->description,
        ])->filter()->implode(' ');

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
     */
    private function determineBaseConfidence(WorkOrder $workOrder, Collection $playbooks): AIConfidence
    {
        $score = 0;

        // Has meaningful description (not just a few words)
        if (! empty($workOrder->description) && strlen($workOrder->description) > 50) {
            $score += 2;
        }

        // Has acceptance criteria defined
        if (! empty($workOrder->acceptance_criteria) && is_array($workOrder->acceptance_criteria)) {
            $score += 2;
        }

        // Has relevant playbooks
        if ($playbooks->isNotEmpty()) {
            $score += 1;
        }

        // Has title that's descriptive enough
        if (! empty($workOrder->title) && strlen($workOrder->title) > 10) {
            $score += 1;
        }

        return match (true) {
            $score >= 5 => AIConfidence::High,
            $score >= 3 => AIConfidence::Medium,
            default => AIConfidence::Low,
        };
    }

    /**
     * Generate deliverable suggestions based on context.
     *
     * @param  Collection<int, Playbook>  $playbooks
     * @return array<DeliverableSuggestion>
     */
    private function generateSuggestions(
        WorkOrder $workOrder,
        Collection $playbooks,
        array $context,
        AIConfidence $baseConfidence
    ): array {
        // Try LLM-based generation first
        if ($this->llmService !== null) {
            $llmSuggestions = $this->generateViaLLM($workOrder, $context);
            if ($llmSuggestions !== null) {
                return $llmSuggestions;
            }
        }

        // Fall back to rule-based heuristics
        $suggestions = [];

        // Strategy 1: Playbook-based suggestion (if playbook available)
        if ($playbooks->isNotEmpty()) {
            $playbook = $playbooks->first();
            $playbookSuggestion = $this->generateFromPlaybook($workOrder, $playbook, $baseConfidence);
            if ($playbookSuggestion !== null) {
                $suggestions[] = $playbookSuggestion;
            }
        }

        // Strategy 2: Analysis-based primary deliverable
        $primarySuggestion = $this->generatePrimarySuggestion($workOrder, $baseConfidence);
        if ($primarySuggestion !== null) {
            $suggestions[] = $primarySuggestion;
        }

        // Strategy 3: Documentation-focused alternative
        $documentationSuggestion = $this->generateDocumentationSuggestion($workOrder, $baseConfidence);
        if ($documentationSuggestion !== null) {
            $suggestions[] = $documentationSuggestion;
        }

        // Ensure we have at least MIN_ALTERNATIVES
        if (count($suggestions) < self::MIN_ALTERNATIVES) {
            $fallbackSuggestion = $this->generateFallbackSuggestion($workOrder, AIConfidence::Low);
            $suggestions[] = $fallbackSuggestion;
        }

        // Remove duplicates by title
        return $this->deduplicateSuggestions($suggestions);
    }

    /**
     * Generate a suggestion based on playbook template.
     */
    private function generateFromPlaybook(
        WorkOrder $workOrder,
        Playbook $playbook,
        AIConfidence $baseConfidence
    ): ?DeliverableSuggestion {
        $content = $playbook->content ?? [];

        // Check if playbook has deliverable templates
        if (isset($content['deliverables']) && is_array($content['deliverables'])) {
            $template = $content['deliverables'][0] ?? null;

            if ($template !== null) {
                return new DeliverableSuggestion(
                    title: $template['title'] ?? "{$workOrder->title} - Primary Deliverable",
                    description: $template['description'] ?? "Deliverable based on {$playbook->name} template",
                    type: $this->parseDeliverableType($template['type'] ?? 'other'),
                    acceptanceCriteria: $template['acceptance_criteria'] ?? $this->extractCriteriaFromPlaybook($playbook),
                    confidence: $baseConfidence,
                    reasoning: "Generated from playbook: {$playbook->name}",
                    playbookId: $playbook->id,
                );
            }
        }

        // Generate from playbook metadata if no explicit templates
        return new DeliverableSuggestion(
            title: $this->generateTitleFromWorkOrder($workOrder),
            description: "Based on {$playbook->name}: {$playbook->description}",
            type: $this->inferDeliverableType($workOrder),
            acceptanceCriteria: $this->extractCriteriaFromPlaybook($playbook),
            confidence: $baseConfidence,
            reasoning: "Influenced by playbook: {$playbook->name}",
            playbookId: $playbook->id,
        );
    }

    /**
     * Extract acceptance criteria from playbook content.
     *
     * @return array<string>
     */
    private function extractCriteriaFromPlaybook(Playbook $playbook): array
    {
        $content = $playbook->content ?? [];

        // Check for checklist items
        if (isset($content['checklist']) && is_array($content['checklist'])) {
            return array_slice($content['checklist'], 0, 5);
        }

        // Check for requirements
        if (isset($content['requirements']) && is_array($content['requirements'])) {
            return array_slice($content['requirements'], 0, 5);
        }

        return [];
    }

    /**
     * Generate the primary deliverable suggestion.
     */
    private function generatePrimarySuggestion(
        WorkOrder $workOrder,
        AIConfidence $baseConfidence
    ): ?DeliverableSuggestion {
        $type = $this->inferDeliverableType($workOrder);
        $title = $this->generateTitleFromWorkOrder($workOrder);

        // Don't create duplicate of workOrder title
        if (Str::lower($title) === Str::lower($workOrder->title)) {
            $title = "{$title} - Primary Output";
        }

        return new DeliverableSuggestion(
            title: $title,
            description: $this->generateDescription($workOrder, $type),
            type: $type,
            acceptanceCriteria: $this->generateCriteriaFromWorkOrder($workOrder),
            confidence: $baseConfidence,
            reasoning: 'Primary deliverable inferred from work order title and description',
            playbookId: null,
        );
    }

    /**
     * Generate a documentation-focused suggestion.
     */
    private function generateDocumentationSuggestion(
        WorkOrder $workOrder,
        AIConfidence $baseConfidence
    ): DeliverableSuggestion {
        // Lower confidence for documentation alternatives
        $confidence = $baseConfidence === AIConfidence::High
            ? AIConfidence::Medium
            : $baseConfidence;

        return new DeliverableSuggestion(
            title: "{$workOrder->title} - Documentation",
            description: "Documentation covering requirements, implementation details, and usage instructions for {$workOrder->title}",
            type: DeliverableType::Document,
            acceptanceCriteria: [
                'Covers all requirements from work order',
                'Includes implementation details',
                'Contains usage examples',
            ],
            confidence: $confidence,
            reasoning: 'Documentation deliverable to complement the primary output',
            playbookId: null,
        );
    }

    /**
     * Generate a fallback suggestion when insufficient context.
     */
    private function generateFallbackSuggestion(
        WorkOrder $workOrder,
        AIConfidence $confidence
    ): DeliverableSuggestion {
        return new DeliverableSuggestion(
            title: "{$workOrder->title} - Output",
            description: "Primary deliverable for: {$workOrder->title}",
            type: DeliverableType::Other,
            acceptanceCriteria: [],
            confidence: $confidence,
            reasoning: 'Fallback suggestion due to limited context',
            playbookId: null,
        );
    }

    /**
     * Infer deliverable type from work order content.
     */
    private function inferDeliverableType(WorkOrder $workOrder): DeliverableType
    {
        $text = strtolower($workOrder->title.' '.($workOrder->description ?? ''));

        // Check for code-related keywords
        $codeKeywords = ['api', 'code', 'implement', 'build', 'develop', 'system', 'module', 'feature', 'integration', 'authentication', 'database'];
        foreach ($codeKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return DeliverableType::Code;
            }
        }

        // Check for design-related keywords
        $designKeywords = ['design', 'mockup', 'wireframe', 'ui', 'ux', 'prototype', 'layout', 'interface'];
        foreach ($designKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return DeliverableType::Design;
            }
        }

        // Check for report-related keywords
        $reportKeywords = ['report', 'analysis', 'audit', 'review', 'assessment', 'evaluation', 'summary'];
        foreach ($reportKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return DeliverableType::Report;
            }
        }

        // Check for document-related keywords
        $documentKeywords = ['document', 'documentation', 'guide', 'manual', 'specification', 'requirements', 'plan'];
        foreach ($documentKeywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return DeliverableType::Document;
            }
        }

        return DeliverableType::Other;
    }

    /**
     * Generate a deliverable title from work order.
     */
    private function generateTitleFromWorkOrder(WorkOrder $workOrder): string
    {
        $title = $workOrder->title;

        // Clean up common prefixes
        $prefixes = ['create', 'build', 'implement', 'develop', 'design', 'make', 'add', 'fix', 'update'];
        foreach ($prefixes as $prefix) {
            if (Str::startsWith(strtolower($title), $prefix.' ')) {
                $title = ucfirst(trim(substr($title, strlen($prefix))));
                break;
            }
        }

        return $title ?: 'Primary Deliverable';
    }

    /**
     * Generate description based on work order and type.
     */
    private function generateDescription(WorkOrder $workOrder, DeliverableType $type): string
    {
        $typeLabel = $type->label();

        if (! empty($workOrder->description)) {
            return Str::limit($workOrder->description, 200);
        }

        return "{$typeLabel} deliverable for: {$workOrder->title}";
    }

    /**
     * Extract or generate acceptance criteria from work order.
     *
     * @return array<string>
     */
    private function generateCriteriaFromWorkOrder(WorkOrder $workOrder): array
    {
        // Use existing acceptance criteria if available
        if (! empty($workOrder->acceptance_criteria) && is_array($workOrder->acceptance_criteria)) {
            return array_slice($workOrder->acceptance_criteria, 0, 5);
        }

        // Generate generic criteria based on type
        return [
            'Meets all work order requirements',
            'Reviewed and approved by stakeholders',
            'Documentation complete',
        ];
    }

    /**
     * Parse string to DeliverableType enum.
     */
    private function parseDeliverableType(string $type): DeliverableType
    {
        return match (strtolower($type)) {
            'document' => DeliverableType::Document,
            'design' => DeliverableType::Design,
            'report' => DeliverableType::Report,
            'code' => DeliverableType::Code,
            default => DeliverableType::Other,
        };
    }

    /**
     * Remove duplicate suggestions by title.
     *
     * @param  array<DeliverableSuggestion>  $suggestions
     * @return array<DeliverableSuggestion>
     */
    private function deduplicateSuggestions(array $suggestions): array
    {
        $seen = [];
        $unique = [];

        foreach ($suggestions as $suggestion) {
            $key = strtolower($suggestion->title);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $suggestion;
            }
        }

        return $unique;
    }

    /**
     * Construct the LLM prompt for deliverable generation.
     *
     * This prompt is designed for use with Claude or similar models.
     * When neuron-ai is integrated, this will be sent to the LLM.
     *
     * @param  array<string, mixed>  $workOrderContext
     * @param  array<int, array<string, mixed>>  $playbookContext
     */
    /**
     * Attempt to generate deliverable suggestions via LLM.
     *
     * @param  array{work_order: array, playbooks: array, prompt: string}  $context
     * @return array<DeliverableSuggestion>|null
     */
    private function generateViaLLM(WorkOrder $workOrder, array $context): ?array
    {
        try {
            $response = $this->llmService->complete(
                systemPrompt: 'You are a project management assistant specializing in deliverable planning. Always respond with valid JSON.',
                userPrompt: $context['prompt'],
                teamId: $workOrder->team_id,
            );

            if ($response === null) {
                return null;
            }

            $decoded = json_decode($response->content, true);
            if (! is_array($decoded)) {
                return null;
            }

            $suggestions = array_map(
                fn (array $d) => DeliverableSuggestion::fromArray($d),
                $decoded
            );

            return ! empty($suggestions) ? $suggestions : null;
        } catch (\Throwable $e) {
            Log::warning('LLM deliverable generation failed, falling back to heuristics', [
                'work_order_id' => $workOrder->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function constructLLMPrompt(array $workOrderContext, array $playbookContext): string
    {
        $prompt = <<<'PROMPT'
You are a project management assistant specializing in deliverable planning.

## Task
Analyze the following work order and generate 2-3 alternative deliverable structures. Each alternative should represent a different approach to organizing the work output.

## Work Order
Title: {title}
Description: {description}
Acceptance Criteria: {criteria}

{playbook_section}

## Instructions
1. Generate 2-3 distinct deliverable alternatives
2. Each deliverable should have:
   - A clear, specific title
   - A detailed description
   - Type (document, design, report, code, or other)
   - 3-5 acceptance criteria
   - Confidence level (high, medium, low) based on context clarity
3. Consider any relevant playbook templates
4. Provide reasoning for each suggestion

## Response Format
Return a JSON array with the following structure:
```json
[
  {
    "title": "Deliverable title",
    "description": "Detailed description",
    "type": "code|document|design|report|other",
    "acceptance_criteria": ["criterion 1", "criterion 2"],
    "confidence": "high|medium|low",
    "reasoning": "Why this structure is recommended",
    "playbook_id": null
  }
]
```
PROMPT;

        // Replace placeholders
        $prompt = str_replace('{title}', $workOrderContext['title'] ?? 'Untitled', $prompt);
        $prompt = str_replace('{description}', $workOrderContext['description'] ?? 'No description', $prompt);
        $prompt = str_replace(
            '{criteria}',
            ! empty($workOrderContext['acceptance_criteria'])
                ? implode(', ', $workOrderContext['acceptance_criteria'])
                : 'Not specified',
            $prompt
        );

        // Add playbook section if available
        if (! empty($playbookContext)) {
            $playbookSection = "## Relevant Playbooks\n";
            foreach ($playbookContext as $playbook) {
                $playbookSection .= "- {$playbook['name']}: {$playbook['description']}\n";
                if (! empty($playbook['tags'])) {
                    $playbookSection .= '  Tags: '.implode(', ', $playbook['tags'])."\n";
                }
            }
            $prompt = str_replace('{playbook_section}', $playbookSection, $prompt);
        } else {
            $prompt = str_replace('{playbook_section}', '', $prompt);
        }

        return $prompt;
    }
}
