# Spec Requirements: Dispatcher Agent

## Initial Description

Build a Dispatcher Agent that monitors communication channels (initially message threads linked to work orders when tagged by users), extracts work requirements from messages, and routes work to appropriate team members based on skills and capacity. The agent should provide detailed reasoning for its routing decisions and create work orders in draft status for human review.

## Requirements Discussion

### First Round Questions

**Q1:** Input Sources - I assume the Dispatcher Agent will monitor email inboxes, message threads, and potentially external integration webhooks. Is that correct, or should it focus on specific channels initially (e.g., just internal messages)?

**Answer:** For now it should monitor only the message channel linked to a work order when user tags it. Also, the user should be able to trigger the agent by a toggle in the creation form for a work order.

**Q2:** Extraction Scope - I'm thinking the agent should extract: title, description, scope, timeline, budget hints, priority indicators, and suggested deliverables. Should we also include automatic task breakdown, or leave that for manual refinement?

**Answer:** Deliverables and task breakdown will be handled by PM Copilot Agent. The Dispatcher should extract: title, description, scope, success criteria, estimated budget/hours, priority, and deadline.

**Q3:** Routing Logic - I assume routing should prioritize skill match first, then availability/capacity. Is that correct, or should capacity be the primary factor?

**Answer:** Prioritization should be done both by skills match and capacity. If a clear choice can't be made, then the agent should present multiple routing options ranked by confidence.

**Q4:** Project Context - I assume the Dispatcher works within a single project context (routing to team members of that project). Should it also consider cross-project availability?

**Answer:** Dispatcher will always work in a context of a project and work order.

**Q5:** Human Checkpoint Flow - I'm assuming extracted work orders go to a "Pending Review" inbox item before creation. Should there be different approval levels based on budget thresholds?

**Answer:** Draft status is enough.

**Q6:** Tool Access - Should the Dispatcher have read access to playbooks/SOPs to suggest relevant ones, or just focus on work extraction and routing?

**Answer:** Yes, it should have access to all including playbooks, SOPs, etc.

**Q7:** Transparency - For the reasoning output, should we show full detailed explanations, or just confidence scores with expandable details?

**Answer:** Detailed reasoning is good, let's have it.

**Q8:** Out of Scope - What should we explicitly exclude from this first version? (e.g., automated follow-up messages, deadline negotiation, budget approval workflows)

**Answer:** No specific exclusions mentioned.

### Existing Code to Reference

Based on codebase exploration, the following existing patterns and components should be referenced:

**Agent Infrastructure:**
- `app/Agents/BaseAgent.php` - Core agent implementation with context, tools, budget management
- `app/Agents/Workflows/BaseAgentWorkflow.php` - Workflow state management with pause/resume and approval integration
- `app/Agents/Tools/` - Existing tools (TaskListTool, WorkOrderInfoTool, CreateNoteTool)
- `app/Contracts/Tools/ToolInterface.php` - Tool contract for creating new tools
- `app/Providers/AgentServiceProvider.php` - Tool registration and service bindings

**Agent Services:**
- `app/Services/AgentRunner.php` - Agent execution service
- `app/Services/AgentOrchestrator.php` - Workflow orchestration
- `app/Services/AgentApprovalService.php` - Approval flow integration
- `app/Services/ContextBuilder.php` - Building agent context
- `app/Services/ToolGateway.php` - Tool execution with permissions
- `app/Services/AgentPermissionService.php` - Permission checks
- `app/Services/AgentBudgetService.php` - Cost management

**Relevant Models:**
- `app/Models/WorkOrder.php` - Work order with RACI roles, priority, status, budget, acceptance_criteria
- `app/Models/CommunicationThread.php` - Message threads linked to work orders (morphOne)
- `app/Models/Message.php` - Messages with author_type (human/AI), mentions
- `app/Models/User.php` - Skills relationship, capacity_hours_per_week, current_workload_hours, getAvailableCapacity()
- `app/Models/UserSkill.php` - Skills with proficiency levels (1-3)
- `app/Models/Playbook.php` - SOPs with content, tags, type
- `app/Models/InboxItem.php` - Approval queue items

**Enums:**
- `app/Enums/AgentType.php` - Includes WorkRouting type
- `app/Enums/Priority.php` - Priority levels
- `app/Enums/WorkOrderStatus.php` - Status values including Draft
- `app/Enums/AIConfidence.php` - Confidence levels for AI outputs

**Frontend Components:**
- `resources/js/components/work/promote-to-work-order-dialog.tsx` - Work order creation dialog (for toggle integration)
- `resources/js/components/work/work-order-list-item.tsx` - Work order display
- `resources/js/pages/work/work-orders/[id].tsx` - Work order detail page
- `resources/js/components/inbox/` - Approval inbox components

### Follow-up Questions

No follow-up questions needed. User answers were comprehensive and codebase exploration revealed sufficient existing patterns for implementation.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
N/A

## Requirements Summary

### Functional Requirements

**Input/Trigger Mechanisms:**
- Monitor message threads linked to work orders when user tags the agent (e.g., @dispatcher)
- Toggle option in work order creation form to invoke agent assistance
- Always operates within context of a specific project and work order

**Extraction Capabilities:**
- Extract from messages: title, description, scope, success criteria, estimated budget/hours, priority, deadline
- Access playbooks/SOPs to suggest relevant ones for the work
- Provide detailed reasoning for all extractions

**Routing Logic:**
- Match team members based on skills (using UserSkill model with proficiency levels)
- Consider capacity (User.capacity_hours_per_week vs current_workload_hours)
- Weight both factors equally when determining routing
- When no clear single choice: present multiple routing options ranked by confidence
- Include detailed reasoning for routing recommendations

**Output:**
- Create work orders in Draft status for human review
- Include AI reasoning/confidence in the output
- Suggest relevant playbooks/SOPs if applicable

### Reusability Opportunities

**Backend Patterns to Follow:**
- Extend `BaseAgent.php` for the Dispatcher agent class
- Extend `BaseAgentWorkflow.php` for the dispatcher workflow
- Implement `ToolInterface` for new tools (e.g., GetTeamSkillsTool, GetTeamCapacityTool, CreateDraftWorkOrderTool)
- Use `AgentType::WorkRouting` enum value
- Register tools in `AgentServiceProvider`

**Existing Tools to Extend/Reference:**
- `WorkOrderInfoTool` - Pattern for reading work order data
- `TaskListTool` - Pattern for listing related items
- `CreateNoteTool` - Pattern for creating records

**Frontend Patterns to Follow:**
- Add toggle to `promote-to-work-order-dialog.tsx` or work order creation forms
- Use inbox components pattern for displaying routing options
- Follow existing component patterns in `resources/js/components/work/`

### Scope Boundaries

**In Scope:**
- Message thread monitoring when agent is tagged
- Work order creation form toggle for agent assistance
- Extraction of: title, description, scope, success criteria, estimated budget/hours, priority, deadline
- Skill-based and capacity-based routing recommendations
- Multiple routing options with confidence ranking
- Draft work order creation
- Playbook/SOP access and suggestions
- Detailed reasoning output

**Out of Scope (Implicit - first version):**
- Automated follow-up messages to stakeholders
- Deadline negotiation capabilities
- Budget approval workflows
- Cross-project routing
- Deliverable extraction (handled by PM Copilot Agent)
- Task breakdown (handled by PM Copilot Agent)
- Email inbox monitoring
- External integration webhooks
- Different approval levels based on thresholds

### Technical Considerations

**Integration Points:**
- `CommunicationThread` morphOne relationship on WorkOrder for message monitoring
- `MessageMention` model for detecting @dispatcher tags
- `User.skills()` relationship for skill matching
- `User.getAvailableCapacity()` method for capacity calculations
- `WorkOrderStatus::Draft` for created work orders
- `AIConfidence` enum for confidence scoring

**Existing System Constraints:**
- Must work within team context (team_id scoping)
- Must work within project/work order context
- Use existing agent budget and permission services
- Follow existing tool registration patterns
- Use existing workflow state management for pause/resume

**Technology/Patterns:**
- Laravel for backend agent implementation
- React/Inertia for frontend toggle integration
- Existing agent infrastructure (neuron-ai package planned)
- JSON tool definitions in `config/agent-tools/`
