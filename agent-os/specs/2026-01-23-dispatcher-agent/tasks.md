# Task Breakdown: Dispatcher Agent

## Overview
Total Tasks: 28 (across 5 task groups)

This feature implements a Dispatcher Agent that monitors message threads linked to work orders when tagged by users, extracts work requirements from messages, and routes work to appropriate team members based on skills and capacity.

## Task List

### Agent Infrastructure Layer

#### Task Group 1: Core Agent Classes and Tools
**Dependencies:** None
**Complexity:** Medium-High

- [x] 1.0 Complete agent infrastructure layer
  - [x] 1.1 Write 4-6 focused tests for Dispatcher Agent core functionality
    - Test DispatcherAgent extends BaseAgent properly
    - Test DispatcherWorkflow step execution flow
    - Test tool registration and availability
    - Test context building with work order and message thread
  - [x] 1.2 Create DispatcherAgent class extending BaseAgent
    - Location: `app/Agents/DispatcherAgent.php`
    - Override `instructions()` with dispatcher-specific system prompt
    - Override `tools()` to return dispatcher-specific tools
    - Implement work requirement extraction logic
    - Implement routing decision logic with scoring algorithm
    - Follow pattern from `app/Agents/BaseAgent.php`
  - [x] 1.3 Create DispatcherWorkflow class extending BaseAgentWorkflow
    - Location: `app/Agents/Workflows/DispatcherWorkflow.php`
    - Define workflow steps: analyze_thread, extract_requirements, route_work, create_draft
    - Implement `getIdentifier()` returning 'dispatcher-workflow'
    - Implement `getDescription()` returning workflow purpose
    - Follow pattern from `app/Agents/Workflows/BaseAgentWorkflow.php`
  - [x] 1.4 Create GetTeamSkillsTool implementing ToolInterface
    - Location: `app/Agents/Tools/GetTeamSkillsTool.php`
    - Query UserSkill model for all team members with skills and proficiency
    - Return skill_name and proficiency (1=Basic, 2=Intermediate, 3=Advanced)
    - Filter by team_id from agent context
    - Follow pattern from `app/Agents/Tools/WorkOrderInfoTool.php`
  - [x] 1.5 Create GetTeamCapacityTool implementing ToolInterface
    - Location: `app/Agents/Tools/GetTeamCapacityTool.php`
    - Query User capacity and workload for team members
    - Use User.getAvailableCapacity() method
    - Return capacity_hours_per_week, current_workload_hours, available_capacity
    - Filter by team_id from agent context
  - [x] 1.6 Create CreateDraftWorkOrderTool implementing ToolInterface
    - Location: `app/Agents/Tools/CreateDraftWorkOrderTool.php`
    - Create WorkOrder with WorkOrderStatus::Draft status
    - Populate: title, description, priority, due_date, estimated_hours, acceptance_criteria
    - Set responsible_id to top-ranked candidate
    - Link to parent project from source work order context
    - Store routing reasoning in metadata JSON field
  - [x] 1.7 Create GetPlaybooksTool implementing ToolInterface
    - Location: `app/Agents/Tools/GetPlaybooksTool.php`
    - Search Playbook model by tags and keywords
    - Return matching playbooks with content, tags, type
    - Filter by team_id from agent context
  - [x] 1.8 Register tools in AgentServiceProvider
    - Add tool bindings in `app/Providers/AgentServiceProvider.php`
    - Map tool names to classes
    - Set appropriate categories for permission filtering
  - [x] 1.9 Ensure agent infrastructure tests pass
    - Run ONLY the 4-6 tests written in 1.1
    - Verify all tools implement ToolInterface correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 1.1 pass
- DispatcherAgent properly extends BaseAgent
- DispatcherWorkflow properly extends BaseAgentWorkflow
- All four tools implement ToolInterface with name(), description(), category(), execute(), getParameters()
- Tools registered in AgentServiceProvider

---

### Message Thread Integration Layer

#### Task Group 2: Message Mention Detection and Thread Processing
**Dependencies:** Task Group 1
**Complexity:** Medium

- [x] 2.0 Complete message thread integration layer
  - [x] 2.1 Write 4-6 focused tests for message mention detection
    - Test MessageMention with mentionable_type pointing to AIAgent
    - Test dispatcher mention detection in message thread
    - Test full thread context retrieval (not just tagged message)
    - Test agent response creation within same thread
  - [x] 2.2 Create DispatcherMentionListener event listener
    - Location: `app/Listeners/DispatcherMentionListener.php`
    - Listen for MessageCreated event (or create if not exists)
    - Check for @dispatcher mention in message content
    - Verify MessageMention has mentionable_type = AIAgent
    - Trigger DispatcherAgent only when explicitly tagged
  - [x] 2.3 Create ProcessDispatcherMention job
    - Location: `app/Jobs/ProcessDispatcherMention.php`
    - Queue job for async processing
    - Load full message thread context via CommunicationThread.messages()
    - Build AgentContext with work order and thread data
    - Execute DispatcherWorkflow
  - [x] 2.4 Implement thread context retrieval service
    - Location: `app/Services/ThreadContextService.php`
    - Get all messages from CommunicationThread
    - Include message author (User or AIAgent via author_type)
    - Order by created_at for chronological context
    - Format for agent system prompt injection
  - [x] 2.5 Implement agent response posting to thread
    - Create Message with author_type = 'ai'
    - Link to AIAgent via author_id
    - Include extraction results and routing recommendations
    - Format as structured JSON for display
  - [x] 2.6 Ensure message thread integration tests pass
    - Run ONLY the 4-6 tests written in 2.1
    - Verify mention detection triggers workflow
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 2.1 pass
- Dispatcher responds only when explicitly tagged
- Full thread context is processed, not just tagged message
- Agent responses appear in same thread

---

### Work Extraction and Routing Logic Layer

#### Task Group 3: Extraction and Routing Algorithm
**Dependencies:** Task Groups 1, 2
**Complexity:** High

- [ ] 3.0 Complete work extraction and routing logic layer
  - [ ] 3.1 Write 4-6 focused tests for extraction and routing
    - Test extraction of title, description, scope from messages
    - Test AIConfidence enum assignment to extracted fields
    - Test skill-based routing score calculation
    - Test capacity-based routing score calculation
    - Test combined routing decision with 50/50 weighting
  - [ ] 3.2 Implement work requirement extraction service
    - Location: `app/Services/WorkRequirementExtractor.php`
    - Extract: title, description, scope, success_criteria, estimated_hours, priority, deadline
    - Use LLM to parse unstructured message content
    - Apply AIConfidence::High, ::Medium, ::Low to each field
    - Return structured extraction result with confidence scores
  - [ ] 3.3 Implement skill matching service
    - Location: `app/Services/SkillMatchingService.php`
    - Query UserSkill for team members with matching skills
    - Weight proficiency levels: 1=Basic (0.33), 2=Intermediate (0.66), 3=Advanced (1.0)
    - Support semantic similarity matching when exact match unavailable
    - Calculate skill match score (0-100) per team member
  - [ ] 3.4 Implement capacity scoring service
    - Location: `app/Services/CapacityScoreService.php`
    - Use User.getAvailableCapacity() for remaining hours
    - Compare available_capacity against estimated_hours
    - Penalize score by 50% for users with less than 20% available capacity
    - Calculate capacity score (0-100) per team member
  - [ ] 3.5 Implement routing decision service
    - Location: `app/Services/RoutingDecisionService.php`
    - Combine skill score (50%) and capacity score (50%)
    - Present multiple options when top candidates within 10% score difference
    - Always return at least top 3 candidates when available
    - Include AIConfidence level for each recommendation
    - Generate detailed reasoning JSON for each candidate
  - [ ] 3.6 Implement playbook suggestion logic
    - Enhance WorkRequirementExtractor to suggest playbooks
    - Match extracted scope and tags against Playbook.tags
    - Return relevant SOPs sorted by relevance score
  - [ ] 3.7 Ensure extraction and routing tests pass
    - Run ONLY the 4-6 tests written in 3.1
    - Verify extraction accuracy and confidence assignment
    - Verify routing algorithm produces correct rankings
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 3.1 pass
- All required fields extracted with confidence scores
- Routing combines skill (50%) and capacity (50%) scores
- Multiple routing options presented when scores are close
- Detailed reasoning provided for each recommendation

---

### Frontend Integration Layer

#### Task Group 4: UI Components and Toggle
**Dependencies:** Task Groups 1-3
**Complexity:** Medium

- [ ] 4.0 Complete frontend integration layer
  - [ ] 4.1 Write 3-5 focused tests for UI components
    - Test dispatcher toggle renders in work order creation form
    - Test toggle state persists to work order metadata
    - Test routing recommendations display component renders candidates
  - [ ] 4.2 Add "Enable Dispatcher Agent" toggle to work order creation form
    - Location: `resources/js/components/work/promote-to-work-order-dialog.tsx`
    - Add toggle switch component (off by default)
    - Label: "Enable Dispatcher Agent for routing recommendations"
    - Store toggle state in form data
    - Follow existing toggle patterns in codebase
  - [ ] 4.3 Update work order creation controller to handle toggle
    - Store toggle preference in work_orders.metadata JSON field
    - Key: dispatcher_enabled (boolean)
    - Trigger DispatcherAgent after creation if toggle enabled
  - [ ] 4.4 Create routing recommendations display component
    - Location: `resources/js/components/agents/routing-recommendations.tsx`
    - Display top 3+ candidates with scores
    - Show skill matches, proficiency levels, capacity analysis
    - Include confidence badge for each recommendation
    - Allow selection of recommended candidate
    - Expandable detailed reasoning section
  - [ ] 4.5 Create agent response display component for message threads
    - Location: `resources/js/components/messages/agent-message.tsx`
    - Display structured extraction results
    - Display routing recommendations inline
    - Link to draft work order if created
    - Follow existing message component patterns
  - [ ] 4.6 Integrate routing recommendations into work order detail page
    - Location: `resources/js/pages/work/work-orders/[id].tsx`
    - Show recommendations panel when work order has dispatcher metadata
    - Allow accepting/modifying routing suggestion
    - Update responsible_id on acceptance
  - [ ] 4.7 Ensure frontend tests pass
    - Run ONLY the 3-5 tests written in 4.1
    - Verify toggle functionality
    - Verify recommendations display correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 3-5 tests written in 4.1 pass
- Toggle appears in work order creation form (off by default)
- Toggle preference stored in work order metadata
- Routing recommendations display with all required information
- Agent messages render properly in threads

---

### Testing and Integration

#### Task Group 5: Test Review and Gap Analysis
**Dependencies:** Task Groups 1-4
**Complexity:** Low-Medium

- [ ] 5.0 Review existing tests and fill critical gaps only
  - [ ] 5.1 Review tests from Task Groups 1-4
    - Review the 4-6 tests from Task 1.1 (agent infrastructure)
    - Review the 4-6 tests from Task 2.1 (message integration)
    - Review the 4-6 tests from Task 3.1 (extraction/routing)
    - Review the 3-5 tests from Task 4.1 (UI components)
    - Total existing tests: approximately 15-23 tests
  - [ ] 5.2 Analyze test coverage gaps for Dispatcher Agent feature only
    - Identify critical end-to-end workflows lacking coverage
    - Focus ONLY on gaps related to this spec's requirements
    - Do NOT assess entire application test coverage
    - Prioritize integration flows: mention to draft work order
  - [ ] 5.3 Write up to 8 additional strategic tests maximum
    - End-to-end: @dispatcher mention creates draft work order
    - End-to-end: Toggle-enabled work order triggers routing
    - Integration: Full extraction + routing flow
    - Integration: Playbook suggestion accuracy
    - Do NOT write comprehensive coverage for all scenarios
    - Skip edge cases unless business-critical
  - [ ] 5.4 Run feature-specific tests only
    - Run ONLY tests related to Dispatcher Agent feature
    - Expected total: approximately 23-31 tests maximum
    - Do NOT run the entire application test suite
    - Verify all critical workflows pass

**Acceptance Criteria:**
- All feature-specific tests pass (approximately 23-31 tests total)
- Critical user workflows for Dispatcher Agent are covered
- No more than 8 additional tests added when filling gaps
- Testing focused exclusively on this spec's feature requirements

---

## Execution Order

Recommended implementation sequence:

1. **Agent Infrastructure Layer (Task Group 1)**
   - Create DispatcherAgent and DispatcherWorkflow classes
   - Implement all four tools (GetTeamSkillsTool, GetTeamCapacityTool, CreateDraftWorkOrderTool, GetPlaybooksTool)
   - Register tools in AgentServiceProvider

2. **Message Thread Integration Layer (Task Group 2)**
   - Implement mention detection and thread processing
   - Set up async job processing
   - Enable agent response posting

3. **Work Extraction and Routing Logic Layer (Task Group 3)**
   - Implement extraction service with LLM integration
   - Implement skill and capacity scoring services
   - Implement routing decision algorithm

4. **Frontend Integration Layer (Task Group 4)**
   - Add toggle to work order creation form
   - Build routing recommendations display
   - Integrate into work order detail page

5. **Test Review and Gap Analysis (Task Group 5)**
   - Review all tests from previous groups
   - Fill critical gaps with up to 8 additional tests
   - Run feature-specific test suite

---

## Key Files to Create

### Backend (PHP/Laravel)
- `app/Agents/DispatcherAgent.php`
- `app/Agents/Workflows/DispatcherWorkflow.php`
- `app/Agents/Tools/GetTeamSkillsTool.php`
- `app/Agents/Tools/GetTeamCapacityTool.php`
- `app/Agents/Tools/CreateDraftWorkOrderTool.php`
- `app/Agents/Tools/GetPlaybooksTool.php`
- `app/Listeners/DispatcherMentionListener.php`
- `app/Jobs/ProcessDispatcherMention.php`
- `app/Services/ThreadContextService.php`
- `app/Services/WorkRequirementExtractor.php`
- `app/Services/SkillMatchingService.php`
- `app/Services/CapacityScoreService.php`
- `app/Services/RoutingDecisionService.php`

### Frontend (React/TypeScript)
- `resources/js/components/agents/routing-recommendations.tsx`
- `resources/js/components/messages/agent-message.tsx`

### Tests (Pest)
- `tests/Feature/Agents/DispatcherAgentTest.php`
- `tests/Feature/Agents/DispatcherWorkflowTest.php`
- `tests/Feature/Agents/Tools/GetTeamSkillsToolTest.php`
- `tests/Feature/Agents/Tools/GetTeamCapacityToolTest.php`
- `tests/Feature/Agents/Tools/CreateDraftWorkOrderToolTest.php`
- `tests/Feature/Agents/Tools/GetPlaybooksToolTest.php`
- `tests/Feature/Services/WorkRequirementExtractorTest.php`
- `tests/Feature/Services/RoutingDecisionServiceTest.php`

---

## Existing Code to Leverage

| Component | Location | Usage |
|-----------|----------|-------|
| BaseAgent | `app/Agents/BaseAgent.php` | Extend for DispatcherAgent |
| BaseAgentWorkflow | `app/Agents/Workflows/BaseAgentWorkflow.php` | Extend for DispatcherWorkflow |
| WorkOrderInfoTool | `app/Agents/Tools/WorkOrderInfoTool.php` | Pattern for new tools |
| ToolInterface | `app/Contracts/Tools/ToolInterface.php` | Implement for all tools |
| ToolGateway | `app/Services/ToolGateway.php` | Tool execution and permissions |
| ContextBuilder | `app/Services/ContextBuilder.php` | Build agent context |
| AgentType::WorkRouting | `app/Enums/AgentType.php` | Agent type enum value |
| AIConfidence | `app/Enums/AIConfidence.php` | Confidence levels |
| WorkOrderStatus::Draft | `app/Enums/WorkOrderStatus.php` | Draft status for new WOs |
| UserSkill | `app/Models/UserSkill.php` | Skill matching queries |
| User.getAvailableCapacity() | `app/Models/User.php` | Capacity calculations |
| CommunicationThread | `app/Models/CommunicationThread.php` | Message thread access |
| MessageMention | `app/Models/MessageMention.php` | Mention detection |
| Playbook | `app/Models/Playbook.php` | SOP suggestions |
