# Spec Requirements: AI Agent Foundation and Tool Gateway

## Initial Description
Agent abstraction layer, tool gateway for controlled agent actions, permission system, comprehensive audit logging, and orchestration engine.

This is from the AI Agent Platform section of the roadmap (item #22). This foundation will support all subsequent AI agent implementations:
- Dispatcher Agent (analyzes requests, extracts info, suggests work order structure)
- PM Copilot Agent (generates project plans, identifies dependencies)
- Domain Skill Agents (Copy, Marketing, Tech, Operations, Design, Analytics)
- QA/Compliance Agent (validates deliverables against SOPs)
- Finance Agent (drafts estimates, generates invoices)
- Client Comms Agent (drafts client communications)
- Agent Workflow Orchestration (chaining agents together)

## Requirements Discussion

### First Round Questions

**Q1:** For the agent architecture, I'm assuming we want a template library of pre-built agents (Dispatcher, PM Copilot, etc.) that teams can enable/configure, rather than a fully custom agent builder. Is that correct, or should teams be able to define entirely new agent types from scratch?
**Answer:** Template library plus team-specific agents. Teams can create from templates OR define custom agents with their own configurations.

**Q2:** I assume the Tool Gateway should enforce permission checks at the gateway level before any tool execution, with tools themselves not self-checking permissions. This creates a single auditable security boundary. Is this the right approach?
**Answer:** Yes, all permission checks at gateway before tool execution. Single auditable security boundary. Tools don't self-check permissions.

**Q3:** For human-in-the-loop checkpoints, I'm thinking certain high-risk actions should always require human approval: external communications, financial transactions, scope changes, and contract modifications. Should these be hardcoded, or configurable per team?
**Answer:** Human approval required for: external sends, contracts, financial, scope changes. These should be enforced at the system level.

**Q4:** I assume agents should have memory/context scoped at three levels: Project (work order history), Client (party relationship history), and Organization (team patterns and preferences). Is this the right memory architecture?
**Answer:** Yes. Memory Types: Project, Client, Org.

**Q5:** For the permission system, I'm thinking category-based permissions (read, write, execute per tool category) rather than individual tool permissions. Should we keep the existing category-based approach or move to granular tool-level permissions?
**Answer:** Category-based permissions (keep current approach).

**Q6:** The existing AgentActivityLog tracks input, output, tokens, and cost. I assume we need to extend this to capture: tool calls made, context accessed, approval states, and replay data. Is that correct?
**Answer:** Extend AgentActivityLog with JSON for tool calls. Add configurable retention policy.

**Q7:** For multi-agent orchestration, I assume we want a pause/resume capability where workflows can be paused awaiting human approval, then resumed with the approval decision. Should this state be stored in the database or handled in-memory?
**Answer:** Pause state in DB, resume after approval.

**Q8:** What default agents should be available in the template library for launch?
**Answer:** Default Agents:
1. Dispatcher (triage, routing, work orders, task breakdown)
2. PM Copilot (planning, milestones, dependencies, nudges)
3. QA/Compliance (validates against criteria, SOP, brand rules)
4. Finance (drafts estimates/invoices, flags margin issues)
5. Client Comms (drafts communications, never auto-sends)
6. Domain Skill Agents (Copy, Ops, Analyst, Tech, Design)

**Q9:** Should agents have budget caps?
**Answer:** Daily/monthly budget caps per agent.

**Q10:** What core components should the orchestration engine include?
**Answer:** Components: Orchestrator, Tool Gateway, Context Builder, Audit+Replay.

**Q11:** What about conversation support for agents?
**Answer:** Multi-turn conversation for certain agents.

**Q12:** How should tools be configured?
**Answer:** Configuration-based tools (JSON/YAML).

**Q13:** What is the capacity model for agents?
**Answer:** Skill vectors for tasks, humans, agents. Agents have permission boundaries. Checkpoints: Draft > Review > Approve > Deliver.

**Q14:** What is OUT OF SCOPE for this foundation spec?
**Answer:** Specific agent implementations OUT OF SCOPE. This spec covers only the foundation and infrastructure.

### Existing Code to Reference

**Similar Features Identified:**
- Feature: AIAgent model - Path: `app/Models/AIAgent.php`
- Feature: AgentConfiguration model - Path: `app/Models/AgentConfiguration.php`
- Feature: AgentActivityLog model - Path: `app/Models/AgentActivityLog.php`
- Feature: GlobalAISettings model - Path: `app/Models/GlobalAISettings.php`
- Feature: AgentType enum - Path: `app/Enums/AgentType.php`
- Feature: Existing AI Agents settings UI - Path: `resources/js/pages/settings/ai-agents/`
- Feature: Human Checkpoint Workflow - Path: `app/Services/WorkflowTransitionService.php`

### Follow-up Questions

**Follow-up 1:** Regarding neuron-ai integration, the package has built-in Workflow support using PHP Node classes. We have three options for workflow implementation:

**Option A: Neuron's Native Workflow**
- Use neuron-ai's built-in Workflow system with PHP Node classes
- Pros: Leverages tested framework, full neuron features, simpler integration
- Cons: Workflows defined in PHP code, requires deployment to change

**Option B: Thin Abstraction for Database-Configurable Workflows**
- Build a custom abstraction that stores workflow definitions in the database
- Pros: Teams can modify workflows without code changes, UI-configurable
- Cons: More development work, may not leverage all neuron features

**Option C: Hybrid Approach**
- Core workflows as code using neuron's native system
- Team customizations stored in database (enable/disable steps, add hooks)
- Pros: Balance of stability and flexibility
- Cons: Moderate complexity, two systems to maintain

Which approach should we take?

**Answer:** Option C (Hybrid Approach) - Core workflows as PHP code using neuron's native Workflow system, team customizations stored in database (enable/disable steps, add hooks, configure parameters).

## Visual Assets

### Files Provided:
- `Screenshot 2026-01-19 at 21.38.08.png`: AI Agents settings page showing Global AI Budget ($2000 monthly, $701.90 spent, $1298.10 remaining) and list of agents (PM Copilot, Dispatcher Agent, Copywriter Agent, QA Agent, Analyst Agent) with enabled/disabled status and monthly spend per agent.
- `Screenshot 2026-01-19 at 21.38.15.png`: Agent expanded view showing Config tab with "Enable Agent" toggle, Daily Run Limit (50), and Monthly Budget Cap (150) fields.
- `Screenshot 2026-01-19 at 21.38.24.png`: Agent Activity tab showing individual agent runs with timestamps, costs, descriptions, and approval status (approved/rejected). Examples include status report generation, risk identification, timeline suggestion.
- `Screenshot 2026-01-19 at 21.38.31.png`: Agent Budget tab showing Budget Cap ($150), Spent ($87.40), Remaining ($62.60) with a visual progress bar for budget usage.

### Visual Insights:
- Existing UI already supports per-agent budget caps and daily run limits
- Activity tracking already shows individual runs with approval status
- Three-tab structure per agent: Config, Activity, Budget
- Global AI Budget displayed at top level
- Agents can be individually enabled/disabled
- Cost tracking per agent run is already implemented
- UI is in dark mode with emerald accent for enabled states
- Fidelity level: High-fidelity production screenshots of existing functionality

## Requirements Summary

### Functional Requirements

**Agent Abstraction Layer:**
- Base agent class supporting neuron-ai package integration
- Template library of pre-built agents (Dispatcher, PM Copilot, QA/Compliance, Finance, Client Comms, Domain Skills)
- Custom agent creation capability for teams
- Multi-turn conversation support for applicable agents
- Agent lifecycle management (create, configure, enable, disable, archive)

**Tool Gateway Architecture:**
- Centralized gateway for all agent tool access
- Permission enforcement at gateway level before tool execution
- Tools do not self-check permissions (single security boundary)
- Configuration-based tool definitions (JSON/YAML)
- Tool registration and discovery system
- Tool execution auditing

**Permission System:**
- Category-based permissions (read, write, execute per tool category)
- Extend existing category-based approach
- Agent permission boundaries based on skill vectors
- Human approval enforcement for high-risk actions:
  - External communications (sending)
  - Contract modifications
  - Financial transactions
  - Scope changes

**Budget and Capacity Management:**
- Daily budget caps per agent (existing)
- Monthly budget caps per agent (existing)
- Global AI budget at workspace level (existing)
- Skill vectors for capacity matching (tasks, humans, agents)

**Context and Memory:**
- Project-level context (work order history, task details)
- Client-level context (party relationship history)
- Organization-level context (team patterns, preferences)
- Context Builder component for assembling relevant context

**Audit and Logging:**
- Extend AgentActivityLog with JSON field for tool calls
- Track: tool calls made, context accessed, approval states
- Replay capability for debugging and compliance
- Configurable retention policy for audit data

**Orchestration Engine:**
- Orchestrator component for coordinating multi-agent workflows
- Pause/resume capability with state stored in database
- Human checkpoint integration (Draft > Review > Approve > Deliver)
- Context passing between agents in workflows

### Reusability Opportunities

**Existing Models to Extend:**
- AIAgent model - add abstraction layer support
- AgentConfiguration model - add tool permissions, memory settings
- AgentActivityLog model - add tool_calls JSON field, context_accessed field
- GlobalAISettings model - may need workflow settings

**Existing UI to Extend:**
- AI Agents settings page - add tool gateway configuration
- Agent detail tabs (Config, Activity, Budget) - add permissions, tools tabs

**Existing Patterns to Follow:**
- Human Checkpoint Workflow (WorkflowTransitionService) - for agent approval flows
- Inbox and Approvals Queue - for agent approval items
- Playbooks System - for agent SOP integration

### Scope Boundaries

**In Scope:**
- Agent abstraction layer and base classes
- Tool Gateway with permission enforcement
- Permission system (category-based)
- Extended audit logging with tool calls
- Orchestration engine core components
- Pause/resume workflow capability
- Context Builder component
- Memory architecture (Project, Client, Org levels)
- neuron-ai package integration
- Agent template library structure (not implementations)

**Out of Scope:**
- Specific agent implementations (Dispatcher, PM Copilot, etc.)
- Agent-specific tools and capabilities
- Agent training or fine-tuning
- Natural language agent configuration
- Public API for third-party agents
- Real-time collaboration features
- Agent-to-agent direct communication (only through orchestrator)

### Technical Considerations

**Integration Points:**
- neuron-ai package for AI agent functionality
- Existing Human Checkpoint Workflow for approval integration
- Existing Inbox system for surfacing agent approvals
- Playbooks system for SOP-driven agent behavior
- Existing budget tracking UI and models

**Technology Decisions:**
- Use neuron-ai package as specified in tech stack
- Workflow approach: Option C (Hybrid) - Core workflows in PHP, team customizations in database
- Tool definitions in JSON/YAML configuration files
- Pause state stored in database for durability
- Memory scoped at Project, Client, Org levels

**Existing Constraints:**
- Must integrate with existing AIAgent, AgentConfiguration, AgentActivityLog models
- Must preserve existing budget cap and activity tracking functionality
- Must follow existing category-based permission approach
- Must integrate with Human Checkpoint Workflow (Draft > Review > Approve > Deliver)

**Database Extensions Needed:**
- AgentActivityLog: Add tool_calls (JSON), context_accessed (JSON)
- New table: AgentWorkflowState (for pause/resume)
- New table: ToolDefinition (for configuration-based tools)
- New table: AgentMemory (for context storage)
- Consider: AgentTemplate table for template library

---

## Final Decisions Summary

All questions have been resolved. Key architectural decisions:

| Decision | Choice |
|----------|--------|
| LLM Package | neuron-ai |
| Agent State | Multi-turn conversation for certain agents |
| Tool Definition | Configuration-based (JSON/YAML) |
| Permission Enforcement | Tool Gateway (single security boundary) |
| Permission Granularity | Category-based (current approach) |
| Audit Logging | Extend AgentActivityLog with JSON field |
| Retention Policy | Configurable |
| Workflow Approach | **Option C: Hybrid** (core in PHP, customizations in DB) |
| Approval State | Pause in database, resume after approval |
| Scope | Foundation only (agent implementations separate) |

**Ready for specification creation.**
