# Spec Requirements: Agent Workflow Orchestration

## Initial Description
Full agent chaining, context passing between agents, state change triggers.

Source: Product Roadmap - Next Feature (item #31)

## Requirements Discussion

### First Round Questions

**Q1:** Should the system integrate with the existing AIAgent and AgentConfiguration models, or is this a new agent architecture?
**Answer:** It should integrate with the existing AIAgent and AgentConfiguration models.

**Q2:** Are there existing patterns or similar features in the codebase that we should reference?
**Answer:** Yes, there are agents defined already and some additional logic. Check the code.

### Existing Code to Reference

Based on the user's direction and codebase exploration, the following existing code provides the foundation for this feature:

**Existing Agent Classes:**
- `app/Agents/BaseAgent.php` - Abstract base class providing provider configuration, system prompts, tools, budget checking, context management, and message history
- `app/Agents/PMCopilotAgent.php` - Project management agent with deliverable generation, task breakdown, and project insights
- `app/Agents/DispatcherAgent.php` - Work routing agent with requirement extraction, skill matching, and capacity assessment
- `app/Agents/ClientCommsAgent.php` - Client communication agent with status updates, deliverable notifications, and clarification requests

**Existing Workflow Classes:**
- `app/Agents/Workflows/BaseAgentWorkflow.php` - Abstract workflow base with state management, pause/resume, customization loading, and human checkpoints
- `app/Agents/Workflows/PMCopilotWorkflow.php` - 6-step workflow: gather_context > generate_deliverables > checkpoint_deliverables > generate_task_breakdown > generate_insights > present_results
- `app/Agents/Workflows/DispatcherWorkflow.php` - 4-step workflow: analyze_thread > extract_requirements > route_work > create_draft

**Existing Service Layer:**
- `app/Services/AgentOrchestrator.php` - Workflow execution, state persistence, pause/resume, customization loading
- `app/Services/AgentRunner.php` - Agent lifecycle: budget validation > context building > execution > logging
- `app/Services/AgentApprovalService.php` - Human approval requests via InboxItem, approval/rejection handling
- `app/Services/ToolGateway.php` - Single entry point for tool execution with permission enforcement and logging
- `app/Services/ToolRegistry.php` - Tool registration, discovery, and permission mapping
- `app/Services/ContextBuilder.php` - Builds context from work orders, projects, clients for agent consumption
- `app/Services/AgentBudgetService.php` - Cost tracking and budget enforcement
- `app/Services/AgentPermissionService.php` - Permission checking for tool access
- `app/Services/AgentMemoryService.php` - Agent memory storage and retrieval

**Existing Models:**
- `app/Models/AIAgent.php` - Agent definition with code, name, type, description, capabilities, template reference
- `app/Models/AgentConfiguration.php` - Per-team agent config with permissions, limits, budget caps, tool permissions
- `app/Models/AgentWorkflowState.php` - Workflow execution state with current_node, state_data, pause/resume timestamps, approval tracking
- `app/Models/AgentTemplate.php` - Template for creating agents with default instructions, tools, permissions
- `app/Models/AgentActivityLog.php` - Activity logging with input, output, tool calls, cost, duration
- `app/Models/AgentMemory.php` - Agent memory storage with scope (team, project, work order)
- `app/Models/WorkflowCustomization.php` - Team-specific workflow customizations (disabled steps, parameters, hooks)

**Existing Enums:**
- `app/Enums/AgentType.php` - ProjectManagement, WorkRouting, ContentCreation, QualityAssurance, DataAnalysis, ClientCommunication
- `app/Enums/AgentMemoryScope.php` - Memory scoping (team, project, work_order)

**Existing Tools:**
- `app/Agents/Tools/WorkOrderInfoTool.php`
- `app/Agents/Tools/GetPlaybooksTool.php`
- `app/Agents/Tools/TaskListTool.php`
- `app/Agents/Tools/GetTeamCapacityTool.php`
- `app/Agents/Tools/GetTeamSkillsTool.php`
- `app/Agents/Tools/CreateTaskTool.php`
- `app/Agents/Tools/CreateDeliverableTool.php`
- `app/Agents/Tools/CreateDraftWorkOrderTool.php`
- `app/Agents/Tools/CreateNoteTool.php`
- `app/Agents/Tools/GetProjectInsightsTool.php`

**Existing Contracts:**
- `app/Contracts/Tools/ToolInterface.php` - Tool contract with name(), description(), category(), execute(), getParameters()

### Follow-up Questions

No additional follow-up questions were asked.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
N/A

## Requirements Summary

### Functional Requirements

Based on the raw idea and existing architecture analysis, the Agent Workflow Orchestration feature needs to complete the following gaps in the current implementation:

**1. Agent Chaining**
- Ability to define sequences of agents that execute in order
- Chain definitions that specify which agents run in what order
- Conditional branching in chains based on agent output
- Support for parallel agent execution within a chain
- Chain templates that can be reused across similar workflows

**2. Context Passing Between Agents**
- Mechanism to pass output from one agent as input to the next
- Context accumulation across the chain (each agent sees previous agents' outputs)
- Selective context filtering (control what each agent sees)
- Context transformation between agents (output format != input format)
- Integration with existing AgentContext value object and ContextBuilder service

**3. State Change Triggers**
- Event-driven agent activation based on model state changes
- Trigger definitions tied to specific entity status transitions
- Support for work order, task, and deliverable status change triggers
- Trigger conditions (e.g., only trigger if certain criteria are met)
- Integration with existing WorkflowTransitionService for state changes

**4. Enhanced Workflow Orchestration**
- Extend AgentOrchestrator to support multi-agent workflows
- Workflow definition format for complex agent chains
- Dynamic workflow modification based on runtime conditions
- Workflow templates for common patterns (e.g., Dispatcher > PM Copilot > Client Comms)

**5. Cross-Agent Memory and Learning**
- Shared memory scope for agents within a chain
- Memory persistence across workflow executions
- Memory retrieval for context-aware decision making

### Reusability Opportunities

The existing architecture provides significant foundation:

- **BaseAgentWorkflow** already has step-based execution with pause/resume - extend for chaining
- **AgentOrchestrator** handles workflow state - enhance for multi-agent coordination
- **AgentWorkflowState.state_data** can store chain progress and inter-agent context
- **ContextBuilder** can be extended to aggregate context from multiple agents
- **WorkflowCustomization** can define chain configurations per team
- **ToolGateway** already logs all tool executions - no changes needed for chaining
- **AgentApprovalService** handles human checkpoints - reuse for chain checkpoints

### Scope Boundaries

**In Scope:**
- Agent chaining with sequential and conditional execution
- Context passing between agents within a chain
- State change triggers for automatic workflow initiation
- Workflow templates for common agent chains
- Integration with existing models (AIAgent, AgentConfiguration, AgentWorkflowState)
- Human approval checkpoints within chains
- Chain execution logging and monitoring

**Out of Scope:**
- New AI agents (PM Copilot enhancement, Domain Skill Agents, QA/Compliance, Finance, Client Comms are separate roadmap items)
- External API integrations for triggers
- Real-time collaborative agent execution
- Agent training or fine-tuning
- New tool development

### Technical Considerations

- **Integration Points:**
  - AgentOrchestrator service for chain execution
  - AgentWorkflowState for chain state persistence
  - WorkflowTransitionService for state change trigger integration
  - ContextBuilder for multi-agent context aggregation
  - AgentApprovalService for chain-level human checkpoints

- **Existing System Constraints:**
  - Must work with existing AIAgent and AgentConfiguration models
  - Must respect existing permission and budget systems
  - Must log activities via AgentActivityLog
  - Must support WorkflowCustomization for team-specific chains

- **Technology Preferences:**
  - Laravel queues for async agent execution
  - Event-driven architecture for state change triggers (Laravel Events/Listeners)
  - JSON state_data storage in AgentWorkflowState for chain context
  - Existing neuron-ai package integration when available

- **Architectural Patterns to Follow:**
  - BaseAgentWorkflow pattern for chain definition
  - ToolInterface pattern for chain step definitions
  - Service layer pattern (AgentOrchestrator) for coordination
  - Value object pattern for chain context (similar to AgentContext)
