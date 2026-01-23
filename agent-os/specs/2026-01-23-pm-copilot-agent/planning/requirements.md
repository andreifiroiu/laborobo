# Spec Requirements: PM Copilot Agent

## Initial Description
An AI agent that assists with project management tasks like creating deliverables, breaking down work orders into tasks, and providing project insights.

Source: Item 24 from the product roadmap (agent-os/product/roadmap.md)

## Requirements Discussion

### First Round Questions

**Q1:** For triggering the PM Copilot Agent, I assume it will be manually triggered (e.g., via a button like "Generate Plan" on a work order). Should it also auto-suggest plans for newly created work orders, or only run on-demand?
**Answer:** Should have the possibility to be triggered manually AND auto-suggest plans for newly created work orders. It will also be triggered by the Dispatcher Agent.

**Q2:** When creating deliverables and tasks, should the agent consider existing Playbooks and SOPs to suggest standard task structures, or generate everything fresh based on the work order description?
**Answer:** Should also consider playbooks and SOPs where applicable.

**Q3:** For "project insights," should the agent automatically flag issues (overdue items, bottlenecks), or only respond when asked? I'm thinking a proactive approach with configurable alerts would be most useful.
**Answer:** There should be a configurable option for auto-approval of low-risk suggestions.

**Q4:** I assume the agent will use LLM-based estimation for task durations and effort. Should it also learn from historical data (completed similar tasks), or focus purely on LLM estimation initially?
**Answer:** Focus purely on LLM-based estimation initially. Historical data use will be implemented later.

**Q5:** For project insights, what scope should we cover initially: just flagging overdue items, or a broader set (identifying bottlenecks, suggesting resource reallocation, highlighting scope creep risks)?
**Answer:** Implement a broad set initially (flagging overdue items, identifying bottlenecks, suggesting resource reallocation, highlighting scope creep risks).

**Q6:** When the agent generates a task breakdown, should it present a single recommended plan, or offer multiple options for the user to choose from?
**Answer:** Present the top 2-3 alternatives with confidence levels.

**Q7:** Should there be a "review checkpoint" workflow where the agent pauses after deliverable creation to get approval before proceeding to task breakdown, or should it generate the full plan in one pass?
**Answer:** Put a setting option in place for this at work order level.

**Q8:** Are there any specific PM workflows or task types you want to explicitly exclude from the agent's scope (e.g., budget modifications, team reassignments, external communications)?
**Answer:** Out of scope: Modifying budgets, changing assignments, interacting with external parties.

### Existing Code to Reference

**Similar Features Identified:**
- Feature: DispatcherAgent - Path: `app/Agents/DispatcherAgent.php`
- Feature: BaseAgent - Path: `app/Agents/BaseAgent.php`
- Feature: BaseAgentWorkflow - Path: `app/Agents/Workflows/BaseAgentWorkflow.php`
- Feature: DispatcherAgent Spec - Path: `agent-os/specs/2026-01-23-dispatcher-agent/spec.md`

**Existing Tools to Potentially Reuse:**
- `app/Agents/Tools/WorkOrderInfoTool.php` - Get work order details
- `app/Agents/Tools/TaskListTool.php` - Work with task lists
- `app/Agents/Tools/GetPlaybooksTool.php` - Search playbooks by tags/keywords
- `app/Agents/Tools/GetTeamCapacityTool.php` - Query team capacity
- `app/Agents/Tools/GetTeamSkillsTool.php` - Query team skills
- `app/Agents/Tools/CreateDraftWorkOrderTool.php` - Create draft work orders
- `app/Agents/Tools/CreateNoteTool.php` - Create notes

**Backend Patterns to Reference:**
- DispatcherAgent follows BaseAgent extension pattern with tool registration
- Uses AgentType enum for agent classification
- Implements workflow state management via BaseAgentWorkflow
- Uses AIConfidence enum for confidence levels on recommendations
- Integrates with ContextBuilder service for project/client context

### Follow-up Questions

No follow-up questions were needed - the user provided comprehensive answers to all clarifying questions.

## Visual Assets

### Files Provided:
No visual assets provided.

### Visual Insights:
Not applicable - follow existing UI patterns from work order and agent components in the codebase.

## Requirements Summary

### Functional Requirements

**Trigger Mechanisms:**
- Manual trigger via UI button on work order views
- Auto-suggestion for newly created work orders (configurable)
- Invocation by Dispatcher Agent after work order creation/routing

**Deliverable Creation:**
- Analyze work order description, scope, and acceptance criteria
- Generate appropriate deliverables with titles, descriptions, and success metrics
- Reference applicable Playbooks and SOPs for standard deliverable structures
- Present 2-3 alternative deliverable structures with confidence levels

**Task Breakdown:**
- Break down work orders into actionable tasks
- Consider playbooks and SOPs for standard task patterns
- Generate task sequences with dependencies where applicable
- Provide LLM-based duration and effort estimates
- Present 2-3 alternative task breakdown options with confidence levels

**Project Insights (Broad Scope):**
- Flag overdue items (tasks, deliverables, work orders)
- Identify bottlenecks in project workflow
- Suggest resource reallocation opportunities
- Highlight scope creep risks
- Configurable auto-approval for low-risk suggestions

**Workflow Control:**
- Work order level setting for review checkpoints (pause after deliverables for approval vs. full plan generation)
- Configurable auto-approval threshold for low-risk suggestions

### Reusability Opportunities

**Agent Infrastructure:**
- Extend BaseAgent class (app/Agents/BaseAgent.php)
- Create PMCopilotWorkflow extending BaseAgentWorkflow
- Follow DispatcherAgent pattern for tool registration and context management
- Use existing ToolGateway for permission checks and execution logging

**Existing Tools to Leverage:**
- WorkOrderInfoTool for work order context
- TaskListTool for task operations
- GetPlaybooksTool for SOP/playbook suggestions
- CreateNoteTool for documenting insights

**New Tools Required:**
- CreateDeliverableTool: Create deliverables linked to work orders
- CreateTaskTool: Create tasks with estimates and dependencies
- GetProjectInsightsTool: Analyze project status and generate insights
- UpdateWorkOrderSettingsTool: Manage work order level agent settings

**Models and Services:**
- Use AIConfidence enum for confidence levels
- Use ContextBuilder service for full project context
- Reference Playbook model for SOP suggestions
- Work with Deliverable and Task models for creation

### Scope Boundaries

**In Scope:**
- Creating deliverables from work order descriptions
- Breaking down work orders into tasks
- LLM-based estimation for task durations
- Referencing playbooks and SOPs for standard structures
- Providing 2-3 plan alternatives with confidence levels
- Flagging overdue items across projects
- Identifying workflow bottlenecks
- Suggesting resource reallocation
- Highlighting scope creep risks
- Configurable review checkpoints at work order level
- Auto-approval settings for low-risk suggestions
- Integration with Dispatcher Agent workflow

**Out of Scope:**
- Modifying budgets or financial data
- Changing team member assignments
- Interacting with external parties (clients, vendors)
- Historical data-based estimation (future enhancement)
- Cross-project optimization (works within project context)
- Automated stakeholder notifications
- Budget approval workflows

### Technical Considerations

**Integration Points:**
- Dispatcher Agent can trigger PM Copilot after routing decisions
- Work order creation flow triggers auto-suggestion
- Inbox/approval queue for human review of suggestions
- Work order settings for checkpoint and auto-approval configuration

**Agent Infrastructure:**
- Create PMCopilotAgent class extending BaseAgent
- Create PMCopilotWorkflow class extending BaseAgentWorkflow
- Use AgentType enum (likely AgentType::ProjectManagement or similar)
- Register tools in AgentServiceProvider

**Estimation Approach:**
- Pure LLM-based estimation initially
- Structure estimation output for future historical data integration
- Include confidence levels on all estimates

**UI Patterns:**
- Follow existing work order component patterns
- Add agent trigger button to work order views
- Display plan alternatives with confidence indicators
- Work order settings panel for agent configuration
