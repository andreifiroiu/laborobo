# Task Breakdown: PM Copilot Agent

## Overview
Total Tasks: 53

This feature implements an AI agent that assists with project management tasks including creating deliverables from work order descriptions, breaking down work orders into tasks with LLM-based estimates, and providing project insights such as bottleneck identification, overdue flagging, and scope creep risk detection.

## Task List

### Agent Infrastructure

#### Task Group 1: PM Copilot Agent Core
**Dependencies:** None

- [x] 1.0 Complete PM Copilot Agent infrastructure
  - [x] 1.1 Write 4-6 focused tests for PMCopilotAgent functionality
    - Test agent instantiation with valid configuration
    - Test `instructions()` returns PM-specific system prompt
    - Test `tools()` filters to PM-relevant tools only
    - Test confidence level determination for recommendations
    - Test context building with work order data
  - [x] 1.2 Create `PMCopilotAgent` class extending `BaseAgent`
    - File: `app/Agents/PMCopilotAgent.php`
    - Follow `DispatcherAgent` pattern for structure
    - Define PM-specific tool list in `tools()` method
    - Override `instructions()` for PM-focused system prompt
  - [x] 1.3 Implement PM-specific system instructions
    - Define responsibilities: deliverable generation, task breakdown, insights
    - Specify JSON response format for structured recommendations
    - Include confidence level guidelines
    - Reference playbook usage patterns
  - [x] 1.4 Add `AgentType::ProjectManagement` enum value
    - File: `app/Enums/AgentType.php`
    - Add enum case for PM Copilot classification
  - [x] 1.5 Implement confidence determination methods
    - `determineDeliverableConfidence()` for deliverable suggestions
    - `determineTaskConfidence()` for task breakdown suggestions
    - `determineInsightConfidence()` for project insights
    - Follow pattern from `DispatcherAgent::determineRoutingConfidence()`
  - [x] 1.6 Ensure PMCopilotAgent tests pass
    - Run ONLY the 4-6 tests written in 1.1
    - Verify agent instantiation and configuration
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 1.1 pass
- PMCopilotAgent extends BaseAgent correctly
- Agent filters tools to PM-relevant subset
- Confidence levels use AIConfidence enum consistently

---

#### Task Group 2: PM Copilot Workflow
**Dependencies:** Task Group 1

- [x] 2.0 Complete PM Copilot Workflow implementation
  - [x] 2.1 Write 4-6 focused tests for PMCopilotWorkflow
    - Test workflow step definition returns correct callable array
    - Test workflow start with valid input creates state
    - Test pause for approval creates InboxItem
    - Test resume from checkpoint continues workflow
    - Test staged vs full plan mode behavior
  - [x] 2.2 Create `PMCopilotWorkflow` class extending `BaseAgentWorkflow`
    - File: `app/Agents/Workflows/PMCopilotWorkflow.php`
    - Follow `DispatcherWorkflow` pattern for structure
    - Implement `getIdentifier()` returning 'pm-copilot'
    - Implement `getDescription()` with workflow summary
  - [x] 2.3 Implement `defineSteps()` method
    - Step 1: `gatherContext` - Assemble work order and project context
    - Step 2: `generateDeliverables` - Create deliverable alternatives
    - Step 3: `checkpointDeliverables` - Optional pause for approval (staged mode)
    - Step 4: `generateTaskBreakdown` - Break deliverables into tasks
    - Step 5: `generateInsights` - Analyze project for issues
    - Step 6: `presentResults` - Format and return final output
  - [x] 2.4 Implement context gathering step
    - Use `ContextBuilder` service for project/client/org context
    - Query work order via `WorkOrderInfoTool`
    - Query relevant playbooks via `GetPlaybooksTool`
    - Store context in workflow state data
  - [x] 2.5 Implement `onResume()` hook for checkpoint handling
    - Process approval/rejection data from InboxItem
    - Update workflow state with approved deliverables
    - Skip rejected deliverables in task breakdown
  - [x] 2.6 Implement staged vs full plan mode logic
    - Check work order setting for `pm_copilot_mode` (staged/full)
    - In staged mode: call `pauseForApproval()` after deliverables step
    - In full mode: continue directly to task breakdown
  - [x] 2.7 Ensure PMCopilotWorkflow tests pass
    - Run ONLY the 4-6 tests written in 2.1
    - Verify workflow step execution order
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 2.1 pass
- Workflow executes steps in correct order
- Staged mode pauses after deliverable generation
- Resume from checkpoint works correctly

---

### Agent Tools

#### Task Group 3: Deliverable and Task Creation Tools
**Dependencies:** Task Group 1

- [x] 3.0 Complete agent tools for deliverable and task creation
  - [x] 3.1 Write 4-6 focused tests for new tools
    - Test CreateDeliverableTool creates deliverable with valid params
    - Test CreateDeliverableTool validates required fields
    - Test CreateTaskTool creates task with estimates and position
    - Test CreateTaskTool handles checklist items from playbook
  - [x] 3.2 Create `CreateDeliverableTool` implementing `ToolInterface`
    - File: `app/Agents/Tools/CreateDeliverableTool.php`
    - Follow `CreateDraftWorkOrderTool` pattern
    - Parameters: team_id, work_order_id, title, description, type, acceptance_criteria
    - Create deliverable with `Draft` status by default
    - Return created deliverable data with confidence level
  - [x] 3.3 Create `CreateTaskTool` implementing `ToolInterface`
    - File: `app/Agents/Tools/CreateTaskTool.php`
    - Parameters: team_id, work_order_id, title, description, estimated_hours, position_in_work_order, checklist_items, dependencies
    - Create task with `Todo` status by default
    - Handle checklist_items array from playbook templates
    - Return created task data with confidence level
  - [x] 3.4 Create `GetProjectInsightsTool` implementing `ToolInterface`
    - File: `app/Agents/Tools/GetProjectInsightsTool.php`
    - Query overdue tasks/deliverables/work orders by due_date
    - Identify blocked tasks via is_blocked and blocker_reason fields
    - Calculate capacity vs workload for resource insights
    - Return structured insights with severity levels
  - [x] 3.5 Register new tools in `AgentServiceProvider`
    - Add CreateDeliverableTool to tool registry
    - Add CreateTaskTool to tool registry
    - Add GetProjectInsightsTool to tool registry
    - Configure ToolGateway permissions
  - [x] 3.6 Ensure agent tools tests pass
    - Run ONLY the 4-6 tests written in 3.1
    - Verify tool execution and validation
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 3.1 pass
- CreateDeliverableTool creates deliverables correctly
- CreateTaskTool creates tasks with all fields
- GetProjectInsightsTool returns structured insights

---

### Backend Services

#### Task Group 4: Deliverable Generation Service
**Dependencies:** Task Groups 1, 3

- [x] 4.0 Complete deliverable generation service
  - [x] 4.1 Write 4-6 focused tests for deliverable generation
    - Test service generates 2-3 alternatives from work order
    - Test service incorporates playbook templates when available
    - Test confidence levels assigned based on context clarity
    - Test deliverables linked to work order correctly
  - [x] 4.2 Create `DeliverableGeneratorService`
    - File: `app/Services/DeliverableGeneratorService.php`
    - Method: `generateAlternatives(WorkOrder $workOrder): array`
    - Analyze work order title, description, scope, acceptance_criteria
    - Query playbooks for relevant templates
    - Generate 2-3 deliverable structure alternatives
  - [x] 4.3 Implement LLM prompt construction for deliverables
    - Build prompt with work order context
    - Include playbook suggestions if applicable
    - Request structured JSON output with confidence levels
    - Parse response into DeliverableSuggestion value objects
  - [x] 4.4 Create `DeliverableSuggestion` value object
    - File: `app/ValueObjects/DeliverableSuggestion.php`
    - Properties: title, description, type, acceptance_criteria, confidence
    - Method: `toArray()` for serialization
    - Method: `createDeliverable(WorkOrder $workOrder)` for creation
  - [x] 4.5 Ensure deliverable generation tests pass
    - Run ONLY the 4-6 tests written in 4.1
    - Verify alternative generation logic
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 4.1 pass
- Service generates 2-3 alternatives with confidence levels
- Playbook templates incorporated when relevant
- Value objects properly serialize suggestions

---

#### Task Group 5: Task Breakdown Service
**Dependencies:** Task Groups 1, 3, 4

- [x] 5.0 Complete task breakdown service
  - [x] 5.1 Write 4-6 focused tests for task breakdown
    - Test service breaks deliverable into actionable tasks
    - Test LLM-based hour estimation populated
    - Test position_in_work_order ordering generated
    - Test checklist items from playbook included
  - [x] 5.2 Create `TaskBreakdownService`
    - File: `app/Services/TaskBreakdownService.php`
    - Method: `generateBreakdown(WorkOrder $workOrder, array $deliverables): array`
    - Analyze deliverables and work order context
    - Query playbooks for standard task patterns
    - Generate 2-3 task breakdown alternatives
  - [x] 5.3 Implement LLM prompt construction for tasks
    - Build prompt with deliverable and work order context
    - Include playbook task templates if applicable
    - Request estimated_hours for each task
    - Request checklist_items where appropriate
    - Parse response into TaskSuggestion value objects
  - [x] 5.4 Create `TaskSuggestion` value object
    - File: `app/ValueObjects/TaskSuggestion.php`
    - Properties: title, description, estimated_hours, position, checklist_items, dependencies, confidence
    - Method: `toArray()` for serialization
    - Method: `createTask(WorkOrder $workOrder)` for creation
  - [x] 5.5 Implement dependency detection logic
    - Analyze task descriptions for implicit dependencies
    - Set dependencies array with task references
    - Order tasks by dependency chain
  - [x] 5.6 Ensure task breakdown tests pass
    - Run ONLY the 4-6 tests written in 5.1
    - Verify task generation with estimates
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 5.1 pass
- Service generates tasks with hour estimates
- Dependencies detected and ordered correctly
- Checklist items populated from playbooks

---

#### Task Group 6: Project Insights Service
**Dependencies:** Task Groups 1, 3

- [x] 6.0 Complete project insights service
  - [x] 6.1 Write 4-6 focused tests for project insights
    - Test service flags overdue items correctly
    - Test bottleneck identification from blocked tasks
    - Test resource reallocation suggestions generated
    - Test scope creep detection based on estimates vs actual
  - [x] 6.2 Create `ProjectInsightsService`
    - File: `app/Services/ProjectInsightsService.php`
    - Method: `generateInsights(Project $project): array`
    - Analyze project work orders, tasks, and deliverables
    - Use ContextBuilder for full project context
  - [x] 6.3 Implement overdue item detection
    - Query tasks/deliverables/work orders with past due_date
    - Categorize by severity (days overdue)
    - Return structured overdue items list
  - [x] 6.4 Implement bottleneck identification
    - Query tasks where is_blocked = true
    - Group by blocker_reason
    - Analyze blocked task chains
    - Generate bottleneck summary with affected items
  - [x] 6.5 Implement resource reallocation suggestions
    - Use GetTeamCapacityTool for capacity data
    - Compare team member workload vs capacity
    - Identify overloaded members and available capacity
    - Generate reallocation suggestions
  - [x] 6.6 Implement scope creep detection
    - Compare estimated_hours vs actual_hours on work orders
    - Flag items exceeding estimates by threshold (e.g., 20%)
    - Analyze acceptance_criteria changes if tracked
    - Generate scope creep risk summary
  - [x] 6.7 Create `ProjectInsight` value object
    - File: `app/ValueObjects/ProjectInsight.php`
    - Properties: type, severity, title, description, affected_items, suggestion, confidence
    - Enum types: overdue, bottleneck, resource, scope_creep
  - [x] 6.8 Ensure project insights tests pass
    - Run ONLY the 4-6 tests written in 6.1
    - Verify insight generation accuracy
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 6.1 pass
- Overdue items flagged with severity levels
- Bottlenecks identified from blocked tasks
- Scope creep detected from estimate variances

---

### Trigger Mechanisms

#### Task Group 7: Event-Driven and Dispatcher Integration
**Dependencies:** Task Groups 1, 2

- [x] 7.0 Complete trigger mechanisms
  - [x] 7.1 Write 4-6 focused tests for trigger mechanisms
    - Test WorkOrderCreated event triggers PM Copilot when configured
    - Test manual trigger via service invocation
    - Test Dispatcher Agent can invoke PM Copilot
    - Test auto-suggest respects team GlobalAISettings
  - [x] 7.2 Create `WorkOrderCreated` event listener
    - File: `app/Listeners/TriggerPMCopilotOnWorkOrderCreated.php`
    - Check team GlobalAISettings for auto-suggest enabled
    - Queue PMCopilotWorkflow start for new work orders
    - Skip if work order already has deliverables/tasks
  - [x] 7.3 Register event listener in EventServiceProvider
    - Map WorkOrderCreated event to listener
    - Configure queue connection for async processing
  - [x] 7.4 Implement AgentOrchestrator integration
    - Add method: `invokePMCopilot(WorkOrder $workOrder)`
    - Allow DispatcherAgent to call PM Copilot after routing
    - Pass work order context to PMCopilotWorkflow
  - [x] 7.5 Add `pm_copilot_auto_suggest` setting to GlobalAISettings
    - Add column via migration if needed
    - Default to false for opt-in behavior
    - Check setting in event listener before triggering
  - [x] 7.6 Ensure trigger mechanism tests pass
    - Run ONLY the 4-6 tests written in 7.1
    - Verify event-driven triggers work correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 7.1 pass
- WorkOrderCreated event triggers PM Copilot when enabled
- Dispatcher Agent can invoke PM Copilot via orchestrator
- Team settings respected for auto-suggest behavior

---

### Configuration

#### Task Group 8: Work Order and Auto-Approval Settings
**Dependencies:** Task Groups 1, 2

- [x] 8.0 Complete configuration settings
  - [x] 8.1 Write 4-6 focused tests for configuration
    - Test work order pm_copilot_mode setting (staged/full)
    - Test auto-approval threshold check for low-risk suggestions
    - Test GlobalAISettings pm_copilot_auto_approval_threshold
    - Test configuration API endpoint updates settings
  - [x] 8.2 Add PM Copilot settings migration
    - Add `pm_copilot_mode` column to work_orders table (enum: staged, full)
    - Add `pm_copilot_auto_approval_threshold` to global_ai_settings
    - Default: mode = 'full', threshold = 0.8 (high confidence only)
  - [x] 8.3 Update WorkOrder model with pm_copilot_mode
    - Add to fillable array
    - Add enum cast if using dedicated enum
    - Add accessor for default value
  - [x] 8.4 Update GlobalAISettings model
    - Add pm_copilot_auto_suggest (boolean) - already done in Task Group 7
    - Add pm_copilot_auto_approval_threshold (float 0-1)
    - Add to fillable and casts
  - [x] 8.5 Create auto-approval evaluation logic
    - File: `app/Services/PMCopilotAutoApprovalService.php`
    - Method: `shouldAutoApprove(array $suggestion, GlobalAISettings $settings): bool`
    - Check: confidence >= threshold
    - Check: no budget impact (no budget_cost modifications)
    - Return boolean approval decision
  - [x] 8.6 Ensure configuration tests pass
    - Run ONLY the 4-6 tests written in 8.1
    - Verify settings save and retrieve correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 8.1 pass
- Work order pm_copilot_mode setting persists
- Auto-approval threshold evaluated correctly
- Settings accessible via model attributes

---

### API Layer

#### Task Group 9: PM Copilot API Endpoints
**Dependencies:** Task Groups 1-8

- [x] 9.0 Complete API endpoints for PM Copilot
  - [x] 9.1 Write 4-6 focused tests for API endpoints
    - Test manual trigger endpoint starts workflow
    - Test get suggestions endpoint returns alternatives
    - Test approve/reject endpoint updates InboxItem
    - Test work order settings endpoint updates pm_copilot_mode
  - [x] 9.2 Create `PMCopilotController`
    - File: `app/Http/Controllers/PMCopilotController.php`
    - Method: `trigger(WorkOrder $workOrder)` - Start PM Copilot workflow
    - Method: `getSuggestions(WorkOrder $workOrder)` - Get generated alternatives
    - Method: `approveSuggestion(Request $request)` - Approve deliverable/task suggestion
    - Method: `rejectSuggestion(Request $request)` - Reject suggestion with reason
  - [x] 9.3 Create `WorkOrderAgentSettingsController`
    - File: `app/Http/Controllers/WorkOrderAgentSettingsController.php`
    - Method: `update(WorkOrder $workOrder, Request $request)` - Update pm_copilot_mode
    - Validate mode value (staged/full)
    - Return updated work order with new settings
  - [x] 9.4 Define API routes
    - POST `/work-orders/{workOrder}/pm-copilot/trigger`
    - GET `/work-orders/{workOrder}/pm-copilot/suggestions`
    - POST `/pm-copilot/suggestions/{suggestion}/approve`
    - POST `/pm-copilot/suggestions/{suggestion}/reject`
    - PATCH `/work-orders/{workOrder}/agent-settings`
  - [x] 9.5 Create form request validation classes
    - `TriggerPMCopilotRequest` - Validate work order exists
    - `ApproveSuggestionRequest` - Validate suggestion ID and type
    - `RejectSuggestionRequest` - Validate rejection reason provided
  - [x] 9.6 Ensure API endpoint tests pass
    - Run ONLY the 4-6 tests written in 9.1
    - Verify endpoints return correct responses
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 9.1 pass
- Manual trigger endpoint starts workflow
- Approval/rejection endpoints update state correctly
- Settings endpoint persists configuration

---

### Frontend Components

#### Task Group 10: PM Copilot UI Components
**Dependencies:** Task Group 9

- [x] 10.0 Complete frontend components for PM Copilot
  - [x] 10.1 Write 4-6 focused tests for UI components
    - Test PMCopilotTriggerButton renders and handles click
    - Test PlanAlternativesPanel displays alternatives with confidence
    - Test suggestion approval/rejection updates state
    - Test InsightCard displays insight with severity indicator
  - [x] 10.2 Create `PMCopilotTriggerButton` component
    - File: `resources/js/components/pm-copilot/pm-copilot-trigger-button.tsx`
    - Props: workOrderId, onTrigger callback
    - Display "Generate Plan" button
    - Show loading state while workflow runs
    - Disable if workflow already in progress
  - [x] 10.3 Create `PlanAlternativesPanel` component
    - File: `resources/js/components/pm-copilot/plan-alternatives-panel.tsx`
    - Props: alternatives array, onApprove, onReject callbacks
    - Display 2-3 alternatives as selectable cards
    - Show confidence badge (High/Medium/Low)
    - Include approve/reject action buttons per alternative
  - [x] 10.4 Create `DeliverableSuggestionCard` component
    - File: `resources/js/components/pm-copilot/deliverable-suggestion-card.tsx`
    - Props: suggestion object, onApprove, onReject
    - Display title, description, type, acceptance criteria
    - Show confidence indicator
    - Approve/Reject buttons
  - [x] 10.5 Create `TaskSuggestionCard` component
    - File: `resources/js/components/pm-copilot/task-suggestion-card.tsx`
    - Props: suggestion object with tasks array
    - Display task list with estimates
    - Show checklist items preview
    - Show dependencies if present
  - [x] 10.6 Create `ProjectInsightsPanel` component
    - File: `resources/js/components/pm-copilot/project-insights-panel.tsx`
    - Props: insights array
    - Display insights grouped by type
    - Show severity indicators (warning, danger)
    - Link to affected items
  - [x] 10.7 Create `InsightCard` component
    - File: `resources/js/components/pm-copilot/insight-card.tsx`
    - Props: insight object (type, severity, title, description, suggestion)
    - Display icon based on insight type
    - Show affected items count
    - Include actionable suggestion text
  - [x] 10.8 Ensure UI component tests pass
    - Run ONLY the 4-6 tests written in 10.1
    - Verify component rendering and interactions
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 10.1 pass
- Trigger button initiates workflow
- Alternatives display with confidence levels
- Insights display with proper severity styling

---

#### Task Group 11: Work Order Integration UI
**Dependencies:** Task Group 10

- [x] 11.0 Complete work order integration for PM Copilot
  - [x] 11.1 Write 4-6 focused tests for integration
    - Test PM Copilot section renders in work order detail view
    - Test settings toggle updates pm_copilot_mode
    - Test suggestions panel loads when workflow completes
    - Test approved suggestions create deliverables/tasks
  - [x] 11.2 Add PM Copilot section to work order detail page
    - File: `resources/js/pages/work/work-orders/[id].tsx`
    - Import and render PMCopilotTriggerButton
    - Import and render PlanAlternativesPanel when suggestions available
    - Add settings toggle for staged/full mode
  - [x] 11.3 Create `PMCopilotSettingsToggle` component
    - File: `resources/js/components/pm-copilot/pm-copilot-settings-toggle.tsx`
    - Props: workOrderId, currentMode, onChange
    - Toggle between "Staged" and "Full Plan" modes
    - Call API to update work order setting
  - [x] 11.4 Implement TanStack Query hooks for PM Copilot
    - File: `resources/js/hooks/use-pm-copilot.ts`
    - `useTriggerPMCopilot()` - Mutation for triggering workflow
    - `usePMCopilotSuggestions()` - Query for fetching suggestions
    - `useApproveSuggestion()` - Mutation for approving
    - `useRejectSuggestion()` - Mutation for rejecting
  - [x] 11.5 Add PM Copilot insights to project dashboard
    - File: `resources/js/pages/work/projects/[id].tsx`
    - Import and render ProjectInsightsPanel
    - Fetch insights via API on project load
    - Display collapsible insights section
  - [x] 11.6 Ensure integration tests pass
    - Run ONLY the 4-6 tests written in 11.1
    - Verify end-to-end integration works
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4-6 tests written in 11.1 pass
- PM Copilot section visible on work order detail
- Settings toggle persists mode preference
- Approved suggestions create actual records

---

### Testing

#### Task Group 12: Test Review and Gap Analysis
**Dependencies:** Task Groups 1-11

- [x] 12.0 Review existing tests and fill critical gaps only
  - [x] 12.1 Review tests from Task Groups 1-11
    - Review the 4-6 tests written by each task group (approximately 44-66 tests total)
    - Identify any critical user workflows lacking coverage
    - Document gaps in end-to-end workflow testing
  - [x] 12.2 Analyze test coverage gaps for PM Copilot feature only
    - Focus on critical user workflows: trigger, generate, approve, create
    - Identify missing integration tests between services
    - Do NOT assess entire application test coverage
  - [x] 12.3 Write up to 10 additional strategic tests maximum
    - Add integration test: full workflow from trigger to creation
    - Add test: auto-approval flow for high-confidence suggestions
    - Add test: staged mode pause and resume workflow
    - Add test: Dispatcher Agent invoking PM Copilot
    - Add test: Project insights generation with real data
    - Focus on end-to-end workflows, not unit test gaps
  - [x] 12.4 Run feature-specific tests only
    - Run ONLY tests related to PM Copilot feature
    - Expected total: approximately 54-76 tests maximum
    - Do NOT run the entire application test suite
    - Verify all critical workflows pass

**Acceptance Criteria:**
- All feature-specific tests pass (approximately 54-76 tests total)
- Critical user workflows for PM Copilot are covered
- No more than 10 additional tests added when filling gaps
- Testing focused exclusively on PM Copilot feature requirements

---

## Execution Order

Recommended implementation sequence:

1. **Agent Infrastructure (Task Groups 1-2)**
   - PMCopilotAgent and PMCopilotWorkflow provide the foundation

2. **Agent Tools (Task Group 3)**
   - CreateDeliverableTool, CreateTaskTool, GetProjectInsightsTool enable agent actions

3. **Backend Services (Task Groups 4-6)**
   - DeliverableGeneratorService, TaskBreakdownService, ProjectInsightsService provide core logic

4. **Trigger Mechanisms (Task Group 7)**
   - Event listeners and orchestrator integration enable activation

5. **Configuration (Task Group 8)**
   - Work order settings and auto-approval logic enable customization

6. **API Layer (Task Group 9)**
   - Controllers and routes expose functionality to frontend

7. **Frontend Components (Task Groups 10-11)**
   - UI components and integration complete the user experience

8. **Test Review and Gap Analysis (Task Group 12)**
   - Final test coverage review ensures quality

---

## Files to Create

### Backend
- `app/Agents/PMCopilotAgent.php`
- `app/Agents/Workflows/PMCopilotWorkflow.php`
- `app/Agents/Tools/CreateDeliverableTool.php`
- `app/Agents/Tools/CreateTaskTool.php`
- `app/Agents/Tools/GetProjectInsightsTool.php`
- `app/Services/DeliverableGeneratorService.php`
- `app/Services/TaskBreakdownService.php`
- `app/Services/ProjectInsightsService.php`
- `app/Services/PMCopilotAutoApprovalService.php`
- `app/ValueObjects/DeliverableSuggestion.php`
- `app/ValueObjects/TaskSuggestion.php`
- `app/ValueObjects/ProjectInsight.php`
- `app/Http/Controllers/PMCopilotController.php`
- `app/Http/Controllers/WorkOrderAgentSettingsController.php`
- `app/Http/Requests/TriggerPMCopilotRequest.php`
- `app/Http/Requests/ApproveSuggestionRequest.php`
- `app/Http/Requests/RejectSuggestionRequest.php`
- `app/Listeners/TriggerPMCopilotOnWorkOrderCreated.php`
- `database/migrations/xxxx_add_pm_copilot_settings.php`

### Frontend
- `resources/js/components/pm-copilot/pm-copilot-trigger-button.tsx`
- `resources/js/components/pm-copilot/plan-alternatives-panel.tsx`
- `resources/js/components/pm-copilot/deliverable-suggestion-card.tsx`
- `resources/js/components/pm-copilot/task-suggestion-card.tsx`
- `resources/js/components/pm-copilot/project-insights-panel.tsx`
- `resources/js/components/pm-copilot/insight-card.tsx`
- `resources/js/components/pm-copilot/pm-copilot-settings-toggle.tsx`
- `resources/js/hooks/use-pm-copilot.ts`

### Tests
- `tests/Feature/Agents/PMCopilotAgentTest.php`
- `tests/Feature/Agents/Workflows/PMCopilotWorkflowTest.php`
- `tests/Feature/Agents/Tools/CreateDeliverableToolTest.php`
- `tests/Feature/Agents/Tools/CreateTaskToolTest.php`
- `tests/Feature/Agents/Tools/GetProjectInsightsToolTest.php`
- `tests/Feature/Services/DeliverableGeneratorServiceTest.php`
- `tests/Feature/Services/TaskBreakdownServiceTest.php`
- `tests/Feature/Services/ProjectInsightsServiceTest.php`
- `tests/Feature/Http/Controllers/PMCopilotControllerTest.php`

---

## Existing Code to Reference

- `app/Agents/DispatcherAgent.php` - Agent class pattern
- `app/Agents/BaseAgent.php` - Base agent implementation
- `app/Agents/Workflows/BaseAgentWorkflow.php` - Workflow pattern
- `app/Agents/Tools/CreateDraftWorkOrderTool.php` - Tool implementation pattern
- `app/Agents/Tools/WorkOrderInfoTool.php` - Context retrieval pattern
- `app/Agents/Tools/GetPlaybooksTool.php` - Playbook query pattern
- `app/Services/ContextBuilder.php` - Context building pattern
- `app/Enums/AIConfidence.php` - Confidence level enum
- `app/Models/WorkOrder.php` - Work order model structure
- `app/Models/Deliverable.php` - Deliverable model structure
- `app/Models/Task.php` - Task model structure
