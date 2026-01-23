# Specification: Dispatcher Agent

## Goal
Build a Dispatcher Agent that monitors message threads linked to work orders when tagged by users, extracts work requirements from messages, and routes work to appropriate team members based on skills and capacity, creating draft work orders for human review with detailed routing reasoning.

## User Stories
- As a project manager, I want to tag @dispatcher in a work order message thread so that the agent extracts requirements and suggests who should handle the work
- As a team lead, I want to enable dispatcher assistance when creating work orders so that routing recommendations are provided automatically

## Specific Requirements

**Agent Tagging in Message Threads**
- Listen for @dispatcher mentions in CommunicationThread messages linked to WorkOrders
- Use MessageMention model with mentionable_type pointing to AIAgent
- Only respond when explicitly tagged, not on all messages
- Process the full message thread context, not just the tagged message
- Respond within the same thread with extraction results and routing recommendations

**Work Order Creation Form Toggle**
- Add "Enable Dispatcher Agent" toggle to work order creation form
- When enabled, agent analyzes provided details and suggests routing after creation
- Toggle should be off by default
- Store toggle preference in work order metadata for future reference

**Work Requirement Extraction**
- Extract from messages: title, description, scope, success criteria, estimated budget/hours, priority, deadline
- Use LLM to parse unstructured message content into structured fields
- Apply AIConfidence enum (High/Medium/Low) to each extracted field
- Access Playbook model to suggest relevant SOPs based on extracted scope and tags

**Skill-Based Routing**
- Query UserSkill model to find team members with matching skills
- Weight proficiency levels (1=Basic, 2=Intermediate, 3=Advanced) in scoring
- Consider all skills mentioned or implied in the work requirements
- Match against skill_name field using semantic similarity when exact matches unavailable

**Capacity-Based Routing**
- Use User.getAvailableCapacity() to calculate remaining hours per team member
- Compare available capacity against estimated_hours for the work
- Penalize routing scores for users with less than 20% available capacity
- Consider current_workload_hours relative to capacity_hours_per_week

**Routing Decision Logic**
- Combine skill match score (50%) and capacity score (50%) for final ranking
- When top candidates are within 10% score difference, present multiple options
- Always present at least top 3 candidates when available
- Include AIConfidence level for each routing recommendation

**Draft Work Order Creation**
- Create WorkOrder with status=Draft (WorkOrderStatus::Draft enum)
- Populate all extracted fields (title, description, priority, due_date, estimated_hours, acceptance_criteria)
- Set responsible_id to top-ranked candidate (user can change before approval)
- Link to parent project from the source work order context

**Routing Reasoning Output**
- Provide detailed reasoning for each routing recommendation
- Include: skill matches found, proficiency levels, capacity analysis, confidence rationale
- Format reasoning as structured JSON for display in UI
- Store reasoning in agent activity log for audit purposes

**Agent Infrastructure Integration**
- Create DispatcherAgent class extending BaseAgent
- Create DispatcherWorkflow class extending BaseAgentWorkflow
- Use AgentType::WorkRouting enum value
- Register new tools in AgentServiceProvider

**New Tools Required**
- GetTeamSkillsTool: Query UserSkill for all team members with skills and proficiency
- GetTeamCapacityTool: Query User capacity and workload for team members
- CreateDraftWorkOrderTool: Create WorkOrder in draft status with extracted data
- GetPlaybooksTool: Search Playbook model for relevant SOPs by tags/keywords

## Visual Design
No visual assets provided. Follow existing UI patterns from work order components.

## Existing Code to Leverage

**BaseAgent and BaseAgentWorkflow**
- Located at app/Agents/BaseAgent.php and app/Agents/Workflows/BaseAgentWorkflow.php
- Provides context management, tool execution, budget checking, and workflow state management
- DispatcherAgent should extend BaseAgent; DispatcherWorkflow should extend BaseAgentWorkflow
- Use setContext() and buildSystemPrompt() for context injection

**ToolGateway and Tool Patterns**
- ToolGateway in app/Services/ToolGateway.php handles permission checks and execution logging
- Follow WorkOrderInfoTool pattern (app/Agents/Tools/WorkOrderInfoTool.php) for new tools
- Implement ToolInterface with name(), description(), category(), execute(), getParameters()

**User Skills and Capacity**
- UserSkill model has skill_name and proficiency (1-3) fields
- User model has skills() relationship and getAvailableCapacity() method
- capacity_hours_per_week and current_workload_hours fields for capacity calculation

**Message and Mention Models**
- CommunicationThread has messages() relationship and morphOne on WorkOrder
- Message model has mentions() relationship to MessageMention
- MessageMention uses morphTo for mentionable (User or AIAgent)

**ContextBuilder Service**
- app/Services/ContextBuilder.php assembles project, client, and org context
- Use build() method with WorkOrder entity to get full context hierarchy
- Provides token limit management and intelligent truncation

## Out of Scope
- Automated follow-up messages to stakeholders after routing
- Deadline negotiation with team members
- Budget approval workflows beyond draft status
- Cross-project routing (agent works within single project context)
- Deliverable extraction (handled by PM Copilot Agent)
- Task breakdown from requirements (handled by PM Copilot Agent)
- Email inbox monitoring as trigger source
- External integration webhooks as trigger source
- Different approval levels based on budget thresholds
- Real-time capacity updates during routing (uses snapshot)
