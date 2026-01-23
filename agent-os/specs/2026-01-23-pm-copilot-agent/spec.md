# Specification: PM Copilot Agent

## Goal
Build an AI agent that assists with project management tasks including creating deliverables from work order descriptions, breaking down work orders into tasks with LLM-based estimates, and providing project insights such as bottleneck identification, overdue flagging, and scope creep risk detection.

## User Stories
- As a project manager, I want to auto-generate deliverables and task breakdowns for new work orders so that I can quickly plan work with consistent structures
- As a team lead, I want to receive proactive project insights (bottlenecks, overdue items, scope creep risks) so that I can address issues before they escalate

## Specific Requirements

**PMCopilotAgent Class**
- Extend `BaseAgent` class following the pattern established by `DispatcherAgent`
- Use `AgentType::ProjectManagement` enum for agent classification
- Override `instructions()` method to return PM-specific system prompt
- Override `tools()` method to filter to PM-relevant tools only
- Include methods for deliverable generation, task breakdown, and project insights

**Trigger Mechanisms**
- Manual trigger via UI button on work order detail views
- Auto-suggestion for newly created work orders (configurable per team in `GlobalAISettings`)
- Invocation by Dispatcher Agent after work order creation/routing via `AgentOrchestrator`
- Use event-driven architecture (e.g., `WorkOrderCreated` event listener)

**Deliverable Generation**
- Analyze work order title, description, scope, and acceptance criteria
- Query playbooks via `GetPlaybooksTool` to find relevant templates and SOPs
- Generate deliverables with title, description, type, and acceptance criteria
- Present 2-3 alternative deliverable structures with `AIConfidence` levels
- Link deliverables to work order via `work_order_id` relationship

**Task Breakdown**
- Break work orders into actionable tasks with dependencies where applicable
- Reference playbooks for standard task patterns (e.g., checklist-based templates)
- Generate position ordering via `position_in_work_order` field
- Provide LLM-based duration estimates via `estimated_hours` field
- Include checklist items from playbook content when applicable
- Present 2-3 task breakdown alternatives with confidence levels

**Project Insights Generation**
- Flag overdue tasks, deliverables, and work orders based on `due_date` fields
- Identify bottlenecks by analyzing blocked tasks (`is_blocked`, `blocker_reason`)
- Suggest resource reallocation by comparing team capacity vs. workload
- Highlight scope creep risks by comparing original estimates to actual progress
- Use `ContextBuilder` service to assemble project/client/org context

**Workflow Checkpoints**
- Add work order level setting for review checkpoint behavior (stored in work order metadata or related settings table)
- Support two modes: "Full plan" (deliverables + tasks in one pass) and "Staged" (pause after deliverables for approval)
- Use `pauseForApproval()` from `BaseAgentWorkflow` to create `InboxItem` for human review

**Auto-Approval Configuration**
- Configurable auto-approval threshold for low-risk suggestions at team level
- Low-risk defined as: high confidence suggestions with no budget impact
- Store configuration in `GlobalAISettings` or dedicated agent settings

**PMCopilotWorkflow Class**
- Extend `BaseAgentWorkflow` following existing workflow patterns
- Define workflow steps: context gathering, deliverable generation, (optional pause), task breakdown, insights generation
- Implement `defineSteps()` returning step handlers as callable array
- Support resume from checkpoint via `onResume()` hook

## Visual Design
No visual assets provided. Follow existing UI patterns from work order and agent components in the codebase.

## Existing Code to Leverage

**DispatcherAgent Pattern (`app/Agents/DispatcherAgent.php`)**
- Follow same structure for tool filtering and custom instructions
- Replicate confidence scoring pattern from `determineRoutingConfidence()`
- Use similar JSON response format for structured recommendations

**BaseAgent (`app/Agents/BaseAgent.php`)**
- Extend this class for provider configuration, budget checking, and tool gateway integration
- Use `setContext()` and `buildSystemPrompt()` for context management
- Leverage `executeTool()` method for tool calls through gateway

**BaseAgentWorkflow (`app/Agents/Workflows/BaseAgentWorkflow.php`)**
- Extend for PMCopilotWorkflow with step-based execution
- Use `pauseForApproval()` for human checkpoint integration
- Leverage `getParameter()` for customization support

**Existing Tools (`app/Agents/Tools/`)**
- `WorkOrderInfoTool`: Retrieve work order context including tasks and deliverables
- `GetPlaybooksTool`: Query playbooks by tags, type, and search terms
- `TaskListTool`: Work with task list operations
- `GetTeamCapacityTool`: Query team member availability for resource insights

**ContextBuilder Service (`app/Services/ContextBuilder.php`)**
- Use `build()` method to assemble project, client, and org context
- Leverage `buildProjectContext()` for pending tasks and work order summaries

## Out of Scope
- Modifying budget values (`budget_cost`, `actual_cost`) on any entity
- Changing team member assignments (`assigned_to_id`, `responsible_id`, `accountable_id`)
- Interacting with external parties (clients, vendors) via communications
- Historical data-based estimation (future enhancement - structure output for later integration)
- Cross-project optimization (agent works within single project context only)
- Automated stakeholder notifications
- Budget approval workflows
- Creating or modifying playbooks (read-only access)
- Direct database modifications without going through model layer
- Bypassing ToolGateway permission checks
