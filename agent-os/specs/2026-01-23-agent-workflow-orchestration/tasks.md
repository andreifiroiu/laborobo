# Task Breakdown: Agent Workflow Orchestration

## Overview
Total Tasks: 38 (across 5 task groups)

This feature enables multi-agent coordination through agent chaining, context passing between agents, and event-driven triggers that automatically initiate workflows based on entity state changes.

## Task List

### Database Layer

#### Task Group 1: Chain and Trigger Data Models
**Dependencies:** None

- [x] 1.0 Complete database layer for chain orchestration
  - [x] 1.1 Write 6 focused tests for chain and trigger models
    - Test AgentChain creation with valid chain_definition JSON
    - Test AgentChainExecution state transitions (pending -> running -> completed)
    - Test AgentChainExecution relationship to AgentWorkflowState records
    - Test AgentTrigger condition matching for entity status transitions
    - Test AgentChainTemplate instantiation to AgentChain
    - Test AgentMemoryScope enum with new 'chain' value
  - [x] 1.2 Create AgentChain model with migrations
    - Fields: id, team_id, name, description, chain_definition (JSON), is_template, enabled, created_at, updated_at
    - chain_definition JSON structure: `{steps: [{agent_id, execution_mode, conditions, context_filter_rules, next_step_conditions, output_transformers}]}`
    - Validations: name required, chain_definition required and valid JSON structure
    - Foreign key: team_id references teams
    - Indexes: team_id, enabled, is_template
    - Follow `AgentWorkflowState` model pattern for structure and scopes
  - [x] 1.3 Create AgentChainExecution model with migrations
    - Fields: id, team_id, agent_chain_id, current_step_index, execution_status (enum), chain_context (JSON), paused_at, resumed_at, completed_at, failed_at, error_message, started_at, created_at, updated_at
    - execution_status enum values: pending, running, paused, completed, failed
    - Foreign keys: team_id, agent_chain_id
    - Indexes: team_id, agent_chain_id, execution_status, completed_at
    - Add morph relationship to triggering entity (work_order, task, deliverable)
    - Follow `AgentWorkflowState` model patterns for state tracking and scopes
  - [x] 1.4 Create AgentChainExecutionStep pivot model with migrations
    - Fields: id, agent_chain_execution_id, agent_workflow_state_id, step_index, status, started_at, completed_at, output_data (JSON), created_at, updated_at
    - Links chain execution to individual agent workflow states
    - Enables tracking of each step's progress and output within a chain
  - [x] 1.5 Create AgentTrigger model with migrations
    - Fields: id, team_id, name, entity_type (enum), status_from, status_to, agent_chain_id, trigger_conditions (JSON), enabled, priority, created_at, updated_at
    - entity_type enum values: work_order, task, deliverable
    - Foreign keys: team_id, agent_chain_id
    - Indexes: team_id, entity_type, status_from, status_to, enabled
    - trigger_conditions JSON for additional filtering (budget thresholds, tags, etc.)
  - [x] 1.6 Extend AgentMemoryScope enum with 'chain' scope
    - Add `Chain = 'chain'` case to `App\Enums\AgentMemoryScope`
    - Chain scope will use chain_execution_id as scope_id
  - [x] 1.7 Create AgentChainTemplate model with migrations
    - Fields: id, name, description, chain_definition (JSON), category, is_system (boolean for built-in templates), created_at, updated_at
    - Indexes: category, is_system
    - Seed with default template: "Dispatcher > PM Copilot > Client Comms"
  - [x] 1.8 Set up model associations
    - AgentChain: belongsTo Team, hasMany AgentChainExecution, belongsTo AgentChainTemplate (optional)
    - AgentChainExecution: belongsTo Team, belongsTo AgentChain, hasMany AgentChainExecutionStep, morphTo triggerable
    - AgentChainExecutionStep: belongsTo AgentChainExecution, belongsTo AgentWorkflowState
    - AgentTrigger: belongsTo Team, belongsTo AgentChain
    - AgentChainTemplate: hasMany AgentChain
  - [x] 1.9 Ensure database layer tests pass
    - Run ONLY the 6 tests written in 1.1
    - Verify migrations run successfully with `php artisan migrate`
    - Verify rollback works with `php artisan migrate:rollback`

**Acceptance Criteria:**
- All 6 tests from 1.1 pass
- AgentChain, AgentChainExecution, AgentChainExecutionStep, AgentTrigger, and AgentChainTemplate models created with proper validations
- Migrations run and rollback successfully
- AgentMemoryScope enum extended with 'chain' value
- All associations work correctly with eager loading

---

### Service Layer

#### Task Group 2: Chain Orchestration Service
**Dependencies:** Task Group 1

- [x] 2.0 Complete chain orchestration service layer
  - [x] 2.1 Write 6 focused tests for ChainOrchestrator service
    - Test sequential chain execution (3-step chain completes in order)
    - Test chain context accumulation across steps
    - Test conditional branching based on agent output
    - Test chain pause/resume at chain level
    - Test chain failure handling (step failure marks chain as failed)
    - Test parallel step execution within same step group
  - [x] 2.2 Create ChainContext value object
    - File: `app/ValueObjects/ChainContext.php`
    - Follow `AgentContext` value object pattern (immutable, readonly)
    - Properties: stepOutputs (array), accumulatedContext (array), metadata (array)
    - Methods: withStepOutput(), getOutputForStep(), toPromptString(), getTokenEstimate()
    - Include context filtering via include/exclude arrays
  - [x] 2.3 Create ChainOrchestrator service
    - File: `app/Services/ChainOrchestrator.php`
    - Extend patterns from `AgentOrchestrator` service
    - Methods: executeChain(), executeStep(), pause(), resume(), complete(), fail()
    - Use DB transactions for state changes (follow existing pattern)
    - Inject `AgentOrchestrator` for individual agent workflow execution
    - Inject `ContextBuilder` for context assembly
  - [x] 2.4 Implement sequential step execution in ChainOrchestrator
    - Iterate through chain_definition steps in order
    - Invoke each agent's workflow via `AgentOrchestrator->execute()`
    - Wait for agent workflow completion before proceeding to next step
    - Update AgentChainExecution.current_step_index after each step
    - Create AgentChainExecutionStep record for each executed step
  - [x] 2.5 Implement conditional branching logic
    - Evaluate `next_step_conditions` after each step completes
    - Conditions can reference previous step outputs (e.g., `steps.1.output.recommendation == 'approved'`)
    - Support skip, goto, and terminate branch actions
    - Log branching decisions for audit trail
  - [x] 2.6 Implement parallel step execution
    - Identify steps with same step_group in chain_definition
    - Dispatch parallel steps as separate queue jobs
    - Wait for all parallel steps to complete before proceeding
    - Aggregate outputs from parallel steps into chain context
    - Use Laravel queues with `Bus::batch()` for parallel execution
  - [x] 2.7 Implement chain-level pause and resume
    - Chain pause is separate from individual workflow pauses
    - Store pause_reason in AgentChainExecution
    - Resume continues from current_step_index
    - Support approval checkpoints at chain level using `AgentApprovalService`
  - [x] 2.8 Add chain execution logging and monitoring
    - Log chain start/step/complete/fail events via existing patterns
    - Track per-step timing in AgentChainExecutionStep
    - Aggregate cost data from individual workflow states
    - Link AgentActivityLog records to chain execution via chain_execution_id
  - [x] 2.9 Ensure chain orchestration service tests pass
    - Run ONLY the 6 tests written in 2.1
    - Verify service injection works correctly in Laravel container

**Acceptance Criteria:**
- All 6 tests from 2.1 pass
- ChainOrchestrator can execute multi-step chains sequentially
- Conditional branching evaluates correctly
- Parallel steps execute concurrently via queue jobs
- Chain-level pause/resume works independently of workflow pauses
- Activity logging captures chain execution events

---

#### Task Group 3: Context Passing and Memory Services
**Dependencies:** Task Group 2

- [x] 3.0 Complete context passing and cross-agent memory
  - [x] 3.1 Write 5 focused tests for context passing
    - Test ContextBuilder.buildFromChainContext() aggregates prior outputs
    - Test selective context filtering with context_include/context_exclude
    - Test output transformation between agents
    - Test chain-scoped memory storage and retrieval
    - Test memory cleanup on chain completion
  - [x] 3.2 Extend ContextBuilder with buildFromChainContext method
    - File: `app/Services/ContextBuilder.php`
    - New method: `buildFromChainContext(ChainContext $chainContext, AgentChainExecution $execution, AIAgent $agent, int $maxTokens): AgentContext`
    - Merge accumulated chain outputs with entity-based context
    - Apply context_filter_rules from current step configuration
    - Respect token limits using existing truncation logic
  - [x] 3.3 Implement context filtering rules
    - Support `context_include` array: only include specified keys from prior outputs
    - Support `context_exclude` array: exclude specified keys from prior outputs
    - Apply filters per-step based on chain_definition configuration
    - Handle nested key paths (e.g., `steps.1.output.recommendations`)
  - [x] 3.4 Implement output transformers
    - Create OutputTransformer interface: `transform(array $output, array $config): array`
    - Support configurable transformers per step in chain_definition
    - Built-in transformers: flatten, select_keys, rename_keys, summarize
    - Transformers prepare one agent's output format for next agent's input
  - [x] 3.5 Extend AgentMemoryService for chain scope
    - File: `app/Services/AgentMemoryService.php`
    - Support chain-scoped memory using chain_execution_id as scope_id
    - Methods: storeChainMemory(), getChainMemory(), clearChainMemory()
    - Chain memory persists for duration of execution
    - Auto-clear chain memory on chain completion or failure
  - [x] 3.6 Integrate chain context into ChainOrchestrator
    - Build chain context before each step execution
    - Pass chain context to ContextBuilder.buildFromChainContext()
    - Store step outputs in ChainContext after each step completes
    - Make chain context available to agents via enhanced AgentContext
  - [x] 3.7 Ensure context passing tests pass
    - Run ONLY the 5 tests written in 3.1
    - Verify context accumulates correctly across chain steps

**Acceptance Criteria:**
- All 5 tests from 3.1 pass
- ContextBuilder assembles chain context with prior agent outputs
- Context filtering respects include/exclude rules
- Output transformers convert formats between agents
- Chain-scoped memory works via AgentMemoryService
- Memory is cleaned up on chain completion

---

### Event System Layer

#### Task Group 4: State Change Trigger System
**Dependencies:** Task Group 3

- [x] 4.0 Complete state change trigger system
  - [x] 4.1 Write 6 focused tests for trigger system
    - Test AgentTriggerListener receives WorkOrderStatusChanged event
    - Test AgentTriggerListener receives DeliverableStatusChanged event
    - Test trigger condition matching (status from/to, entity type)
    - Test trigger_conditions JSON evaluation (budget threshold, tags)
    - Test chain execution dispatch when trigger matches
    - Test trigger priority ordering (higher priority triggers first)
  - [x] 4.2 Create AgentTriggerListener
    - File: `app/Listeners/AgentTriggerListener.php`
    - Follow `TriggerPMCopilotOnWorkOrderCreated` listener pattern
    - Subscribe to: WorkOrderStatusChanged, DeliverableStatusChanged, TaskStatusChanged
    - Query matching AgentTrigger records for entity type and status transition
    - Check additional trigger_conditions against entity state
  - [x] 4.3 Create ProcessChainTrigger job
    - File: `app/Jobs/ProcessChainTrigger.php`
    - Follow `ProcessPMCopilotTrigger` job pattern
    - Accept AgentTrigger and triggering entity as parameters
    - Invoke ChainOrchestrator.executeChain() with entity context
    - Handle job failures gracefully with logging
  - [x] 4.4 Implement trigger condition evaluation
    - Parse trigger_conditions JSON configuration
    - Support conditions: budget_greater_than, budget_less_than, has_tags, entity_field_equals
    - Use Laravel's collection filtering for condition matching
    - Return early if any condition fails (AND logic)
  - [x] 4.5 Implement trigger priority and deduplication
    - Execute triggers in priority order (higher priority first)
    - Prevent duplicate chain executions for same entity + chain within time window
    - Add `last_triggered_at` tracking on AgentTrigger model
    - Configurable deduplication window in trigger configuration
  - [x] 4.6 Register AgentTriggerListener in EventServiceProvider
    - File: `app/Providers/AppServiceProvider.php`
    - Map WorkOrderStatusChanged, DeliverableStatusChanged, TaskStatusChanged to AgentTriggerListener
    - Follow existing event registration patterns
  - [x] 4.7 Create status change events if not existing
    - Verify WorkOrderStatusChanged event exists or create
    - Verify DeliverableStatusChanged event exists or create
    - Verify TaskStatusChanged event exists or create
    - Events should include: entity, old_status, new_status, user (who made change)
  - [x] 4.8 Ensure trigger system tests pass
    - Run ONLY the 6 tests written in 4.1
    - Verify events dispatch correctly on status changes

**Acceptance Criteria:**
- All 6 tests from 4.1 pass
- AgentTriggerListener responds to status change events
- Trigger conditions evaluate correctly against entity state
- Matching triggers dispatch chain execution jobs
- Priority ordering and deduplication prevent conflicts
- Events are properly registered in EventServiceProvider

---

### Testing and Integration

#### Task Group 5: Integration Testing and Gap Analysis
**Dependencies:** Task Groups 1-4

- [x] 5.0 Review existing tests and fill critical gaps
  - [x] 5.1 Review tests from Task Groups 1-4
    - Review 6 tests from database-engineer (Task 1.1) - File: `tests/Feature/Agents/AgentChainModelsTest.php`
    - Review 6 tests from service layer - ChainOrchestrator (Task 2.1) - File: `tests/Feature/Agents/ChainOrchestratorTest.php`
    - Review 5 tests from service layer - ContextBuilder (Task 3.1) - File: `tests/Feature/Agents/ContextPassingTest.php`
    - Review 6 tests from event system (Task 4.1) - File: `tests/Feature/Agents/AgentTriggerListenerTest.php`
    - Total existing tests: 23 tests
  - [x] 5.2 Analyze test coverage gaps for chain orchestration feature
    - Identify critical end-to-end workflows lacking coverage
    - Focus ONLY on gaps related to this spec's requirements
    - Prioritize integration between components over unit test gaps
    - Check for missing edge cases in chain execution flow
  - [x] 5.3 Write up to 10 additional strategic tests to fill gaps
    - E2E test: Complete chain execution from trigger to completion
    - E2E test: Dispatcher > PM Copilot > Client Comms chain template execution
    - Integration test: Chain execution with real AgentOrchestrator invocation
    - Integration test: Context passing through 3-step chain
    - Integration test: Chain failure recovery and error handling
    - Integration test: Concurrent chain executions for same team
    - Test: Chain template instantiation to team-specific chain
    - Test: AgentActivityLog records link correctly to chain execution (covered by existing workflow state tests)
    - Test: Chain-scoped memory isolation between concurrent chains
    - Test: Trigger deduplication prevents duplicate chain executions
  - [x] 5.4 Run feature-specific tests only
    - Run ONLY tests related to agent workflow orchestration feature
    - Expected total: approximately 33 tests maximum
    - Verify all critical workflows pass
    - Do NOT run the entire application test suite
  - [x] 5.5 Verify integration with existing components
    - Verify ChainOrchestrator works with existing AgentOrchestrator
    - Verify ContextBuilder extension maintains backward compatibility
    - Verify AgentMemoryService extension maintains existing scopes
    - Verify new listeners do not conflict with existing event handlers

**Acceptance Criteria:**
- All feature-specific tests pass (approximately 33 tests total)
- Critical end-to-end chain workflows are covered
- No more than 10 additional tests added to fill gaps
- Integration with existing agent infrastructure verified
- No regressions in existing agent functionality

---

## Execution Order

Recommended implementation sequence:

1. **Database Layer** (Task Group 1)
   - Establishes data models for chains, executions, triggers, and templates
   - No dependencies on other task groups
   - Provides foundation for all subsequent work

2. **Chain Orchestration Service** (Task Group 2)
   - Implements core chain execution logic
   - Depends on models from Task Group 1
   - Critical path for feature functionality

3. **Context Passing and Memory** (Task Group 3)
   - Extends ContextBuilder for chain context aggregation
   - Depends on ChainOrchestrator from Task Group 2
   - Enables meaningful inter-agent communication

4. **State Change Trigger System** (Task Group 4)
   - Implements event-driven chain activation
   - Depends on ChainOrchestrator from Task Group 2
   - Enables automation without manual intervention

5. **Integration Testing** (Task Group 5)
   - Reviews and supplements tests from all groups
   - Depends on all previous task groups
   - Ensures feature works end-to-end

---

## Implementation Notes

### Key Files to Create
- `app/Models/AgentChain.php`
- `app/Models/AgentChainExecution.php`
- `app/Models/AgentChainExecutionStep.php`
- `app/Models/AgentTrigger.php`
- `app/Models/AgentChainTemplate.php`
- `app/Services/ChainOrchestrator.php`
- `app/ValueObjects/ChainContext.php`
- `app/Listeners/AgentTriggerListener.php`
- `app/Jobs/ProcessChainTrigger.php`
- `database/migrations/*_create_agent_chains_table.php`
- `database/migrations/*_create_agent_chain_executions_table.php`
- `database/migrations/*_create_agent_chain_execution_steps_table.php`
- `database/migrations/*_create_agent_triggers_table.php`
- `database/migrations/*_create_agent_chain_templates_table.php`

### Key Files to Modify
- `app/Services/ContextBuilder.php` (add buildFromChainContext method)
- `app/Services/AgentMemoryService.php` (add chain scope support)
- `app/Enums/AgentMemoryScope.php` (add Chain case)
- `app/Providers/EventServiceProvider.php` (register AgentTriggerListener)

### Patterns to Follow
- `AgentOrchestrator` for service orchestration pattern
- `AgentWorkflowState` for execution state tracking pattern
- `AgentContext` for immutable value object pattern
- `TriggerPMCopilotOnWorkOrderCreated` for event listener pattern
- `ProcessPMCopilotTrigger` for async job dispatch pattern
