# Specification: Agent Workflow Orchestration

## Goal

Enable multi-agent coordination through agent chaining, context passing between agents, and event-driven triggers that automatically initiate workflows based on entity state changes.

## User Stories

- As a team admin, I want to define chains of agents that execute in sequence so that complex workflows (e.g., Dispatcher > PM Copilot > Client Comms) run automatically without manual intervention.
- As a project manager, I want the system to automatically trigger relevant agent chains when work orders or deliverables change status so that follow-up actions happen without manual triggering.

## Specific Requirements

**Agent Chain Definition Model**
- Create `AgentChain` model to define reusable chain configurations with name, description, and team scope
- Store chain structure as JSON in `chain_definition` column containing ordered steps with agent references
- Each step includes: agent_id, execution_mode (sequential/parallel), conditions for execution, context filter rules
- Support conditional branching via `next_step_conditions` that evaluate previous agent output
- Reference existing `AIAgent` model via foreign key for each step in the chain

**Chain Execution State Model**
- Create `AgentChainExecution` model to track chain execution progress and cross-agent context
- Store chain_context as JSON containing accumulated outputs from each completed agent
- Track current_step_index, execution_status (pending, running, paused, completed, failed)
- Link to parent `AgentWorkflowState` records for each agent's individual workflow execution
- Support pause/resume at chain level (separate from individual workflow pauses)

**Chain Orchestration Service**
- Create `ChainOrchestrator` service extending patterns from existing `AgentOrchestrator`
- Execute chains by iterating through steps, invoking each agent's workflow via `AgentOrchestrator`
- Pass accumulated context to each subsequent agent via enhanced `ContextBuilder`
- Handle conditional branching by evaluating output conditions after each step completes
- Support parallel step execution using Laravel queues for agents in the same step group

**Context Passing Between Agents**
- Extend `ContextBuilder` with `buildFromChainContext()` method to aggregate prior agent outputs
- Create `ChainContext` value object (similar to `AgentContext`) for chain-level context accumulation
- Support selective context filtering per step via `context_include` and `context_exclude` arrays
- Store chain context in `AgentChainExecution.chain_context` JSON column
- Transform output formats between agents using configurable `output_transformers` per step

**State Change Trigger System**
- Create `AgentTrigger` model to define event-to-chain mappings with conditions
- Store trigger configuration: entity_type (work_order, task, deliverable), status_transition (from, to), chain_id
- Add `trigger_conditions` JSON column for additional filtering (e.g., only trigger if budget > X)
- Create `AgentTriggerListener` that listens to existing events (WorkOrderStatusChanged, DeliverableStatusChanged)
- Dispatch chain execution via `ChainOrchestrator` when trigger conditions match

**Chain Templates and Customization**
- Create `AgentChainTemplate` model for predefined chain patterns (similar to `AgentTemplate`)
- Teams can create chains from templates or define custom chains via `WorkflowCustomization`
- Store template chains in database with is_template flag for reuse across teams
- Support parameter overrides when instantiating chains from templates

**Cross-Agent Memory Scope**
- Add `chain` scope to `AgentMemoryScope` enum for chain-level memory sharing
- Extend `AgentMemoryService` to support chain-scoped memory using chain_execution_id
- Agents within a chain can read/write to shared chain memory scope
- Chain memory persists for duration of chain execution, cleared on completion

**Chain Monitoring and Logging**
- Log chain execution start/complete/fail events via existing logging patterns
- Track per-step timing and cost aggregation in `AgentChainExecution`
- Link `AgentActivityLog` records to chain execution for unified audit trail
- Support chain-level approval checkpoints using existing `AgentApprovalService`

## Visual Design

No visual assets provided.

## Existing Code to Leverage

**`app/Services/AgentOrchestrator.php`**
- Use as foundation for `ChainOrchestrator` service pattern
- Reuse execute/pause/resume/complete pattern for chain-level state management
- Leverage `loadCustomization()` pattern for chain customizations
- Extend `updateNode()` concept to chain step tracking

**`app/Agents/Workflows/BaseAgentWorkflow.php`**
- Follow step-based execution pattern for chain step iteration
- Reuse `pauseForApproval()` pattern for chain-level approval checkpoints
- Leverage hook methods (onStart, onComplete) pattern for chain lifecycle events
- Use `defineSteps()` pattern as model for chain step definitions

**`app/Services/ContextBuilder.php`**
- Extend to support chain context aggregation via new `buildFromChainContext()` method
- Reuse token estimation and truncation logic for combined chain context
- Leverage existing memory retrieval integration for chain-scoped memories
- Follow immutable `AgentContext` value object pattern for `ChainContext`

**`app/Listeners/TriggerPMCopilotOnWorkOrderCreated.php`**
- Use as pattern for `AgentTriggerListener` implementation
- Follow condition checking pattern (settings enabled, entity state validation)
- Reuse job dispatch pattern for async chain execution
- Leverage existing event classes (WorkOrderStatusChanged, DeliverableStatusChanged)

**`app/Models/AgentWorkflowState.php`**
- Use as pattern for `AgentChainExecution` model structure
- Reuse state_data JSON pattern for chain_context storage
- Follow scope patterns (forTeam, paused, completed) for chain execution queries
- Leverage pause/resume timestamp tracking pattern

## Out of Scope

- Creating new AI agents (PM Copilot enhancement, Domain Skill Agents, QA/Compliance, Finance agents)
- External API integrations for triggers (webhooks, third-party services)
- Real-time collaborative agent execution (agents working simultaneously on same entity)
- Agent training, fine-tuning, or model customization
- New tool development for agents
- UI/frontend components for chain management (admin interface)
- Chain execution visualization or progress dashboards
- Automated chain optimization or performance tuning
- Cross-team chain sharing or marketplace
- Rollback or undo functionality for completed chain actions
