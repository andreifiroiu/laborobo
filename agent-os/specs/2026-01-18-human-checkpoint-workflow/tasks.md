# Task Breakdown: Human Checkpoint Workflow

## Overview
Total Tasks: 42

This feature implements a state machine workflow for Tasks and Work Orders that enforces human oversight at critical checkpoints. AI agents can draft and submit work for review, but only humans can approve and deliver.

## Task List

### Database Layer

#### Task Group 1: Status Enums and Status Transition Table
**Dependencies:** None

- [x] 1.0 Complete status enums extension and status transitions table
  - [x] 1.1 Write 4-6 focused tests for status enums and transitions model
    - Test TaskStatus enum has all required cases (InReview, Approved, Blocked, Cancelled, RevisionRequested)
    - Test WorkOrderStatus enum has all required cases (Blocked, Cancelled, RevisionRequested)
    - Test StatusTransition model stores transitions correctly
    - Test StatusTransition polymorphic relationship to Task and WorkOrder
  - [x] 1.2 Extend TaskStatus enum with new cases
    - Add cases: InReview, Approved, Blocked, Cancelled, RevisionRequested
    - Implement label() method for new cases
    - Implement color() method for new cases (amber for InReview, emerald for Approved, red for Blocked/Cancelled, orange for RevisionRequested)
    - File: `app/Enums/TaskStatus.php`
  - [x] 1.3 Extend WorkOrderStatus enum with new cases
    - Add cases: Blocked, Cancelled, RevisionRequested
    - Implement label() method for new cases
    - Implement color() method for new cases
    - File: `app/Enums/WorkOrderStatus.php`
  - [x] 1.4 Create StatusTransition model and migration
    - Fields: id, model_type, model_id, user_id, from_status, to_status, comment (nullable), created_at
    - Add polymorphic relationship (transitionable)
    - Add foreign key for user_id
    - Add index on model_type + model_id for efficient queries
    - File: `app/Models/StatusTransition.php`
  - [x] 1.5 Add statusTransitions() relationship to Task and WorkOrder models
    - Use morphMany relationship
    - Order by created_at desc by default
  - [x] 1.6 Ensure status enum and transition tests pass
    - Run ONLY the 4-6 tests written in 1.1

**Acceptance Criteria:**
- TaskStatus enum includes all 8 statuses (Todo, InProgress, InReview, Approved, Done, Blocked, Cancelled, RevisionRequested)
- WorkOrderStatus enum includes all 8 statuses (Draft, Active, InReview, Approved, Delivered, Blocked, Cancelled, RevisionRequested)
- StatusTransition model correctly stores polymorphic transitions
- All tests pass

---

#### Task Group 2: RACI Fields for Projects and Work Orders
**Dependencies:** Task Group 1

- [x] 2.0 Complete RACI fields implementation
  - [x] 2.1 Write 4-6 focused tests for RACI fields
    - Test Project requires accountable_id
    - Test WorkOrder requires accountable_id
    - Test consulted_ids and informed_ids JSON array casting
    - Test RACI relationships resolve to User models
  - [x] 2.2 Create migration to add RACI fields to projects table
    - Add accountable_id (foreign key, required - defaults to owner_id for existing records)
    - Add responsible_id (foreign key, nullable)
    - Add consulted_ids (JSON, nullable)
    - Add informed_ids (JSON, nullable)
    - Add indexes on accountable_id and responsible_id
  - [x] 2.3 Create migration to add RACI fields to work_orders table
    - Add accountable_id (foreign key, required)
    - Add responsible_id (foreign key, nullable)
    - Add consulted_ids (JSON, nullable)
    - Add informed_ids (JSON, nullable)
    - Add reviewer_id (foreign key, nullable) for explicit reviewer assignment
    - Add indexes on accountable_id, responsible_id, reviewer_id
  - [x] 2.4 Update Project model with RACI relationships and casts
    - Add accountable(), responsible() BelongsTo relationships
    - Add consulted_ids and informed_ids to $casts as array
    - Add to $fillable array
    - File: `app/Models/Project.php`
  - [x] 2.5 Update WorkOrder model with RACI relationships and casts
    - Add accountable(), responsible(), reviewer() BelongsTo relationships
    - Add consulted_ids and informed_ids to $casts as array
    - Add to $fillable array
    - File: `app/Models/WorkOrder.php`
  - [x] 2.6 Ensure RACI tests pass
    - Run ONLY the 4-6 tests written in 2.1

**Acceptance Criteria:**
- Projects and Work Orders have RACI fields
- Accountable is required on both models
- JSON arrays properly cast for consulted/informed
- Relationships resolve correctly

---

#### Task Group 3: Task Model Extensions
**Dependencies:** Task Group 1

- [x] 3.0 Complete Task model extensions
  - [x] 3.1 Write 3-4 focused tests for Task model changes
    - Test created_by_id is required and auto-populated
    - Test reviewer_id relationship
    - Test statusTransitions relationship returns correct records
  - [x] 3.2 Create migration to add fields to tasks table
    - Add created_by_id (foreign key, required)
    - Add reviewer_id (foreign key, nullable)
    - Add indexes on created_by_id and reviewer_id
  - [x] 3.3 Update Task model with new relationships
    - Add createdBy() BelongsTo relationship
    - Add reviewer() BelongsTo relationship
    - Add to $fillable array
    - File: `app/Models/Task.php`
  - [x] 3.4 Ensure Task model tests pass
    - Run ONLY the 3-4 tests written in 3.1

**Acceptance Criteria:**
- Task has created_by_id and reviewer_id fields
- Relationships work correctly
- Tests pass

---

### Service Layer

#### Task Group 4: Workflow Transition Service
**Dependencies:** Task Groups 1, 2, 3

- [x] 4.0 Complete WorkflowTransitionService implementation
  - [x] 4.1 Write 6-8 focused tests for transition service
    - Test valid transitions are allowed (Todo to InProgress)
    - Test invalid transitions are rejected (Todo to Approved)
    - Test AI agents cannot approve work (InReview to Approved blocked)
    - Test AI agents cannot deliver work (Approved to Done blocked)
    - Test managers can approve work
    - Test rejection requires comment
    - Test RevisionRequested auto-transitions to InProgress/Active
  - [x] 4.2 Create WorkflowTransitionService class
    - File: `app/Services/WorkflowTransitionService.php`
    - Implement transition validation logic
    - Define allowed transitions for TaskStatus
    - Define allowed transitions for WorkOrderStatus
  - [x] 4.3 Implement permission checking methods
    - canTransition(Model $item, User|AIAgent $actor, string $toStatus): bool
    - validateTransition() throws exception with reason on failure
    - Check actor type (human vs AI agent) for restricted transitions
    - Check user role/relationship for approval transitions
  - [x] 4.4 Implement transition execution method
    - transition(Model $item, User|AIAgent $actor, string $toStatus, ?string $comment): StatusTransition
    - Create StatusTransition record
    - Update model status
    - Handle RevisionRequested auto-transition logic
    - Log to AuditLog
  - [x] 4.5 Implement rejection flow with required feedback
    - Require non-empty comment for InReview to RevisionRequested transition
    - Store comment in StatusTransition record
    - Auto-transition: RevisionRequested -> InProgress (Task) or Active (WorkOrder)
  - [x] 4.6 Ensure WorkflowTransitionService tests pass
    - Run ONLY the 6-8 tests written in 4.1

**Acceptance Criteria:**
- Service validates transitions against allowed state machine
- AI agents blocked from approval/delivery transitions
- Permission checks enforce role requirements
- Rejection requires feedback comment
- All transitions logged to StatusTransition table

---

#### Task Group 5: Reviewer Resolver Service
**Dependencies:** Task Groups 2, 3

- [x] 5.0 Complete ReviewerResolver service implementation
  - [x] 5.1 Write 4-5 focused tests for reviewer resolution
    - Test explicit reviewer_id takes priority
    - Test fallback to Accountable person
    - Test fallback to Work Order assigned_to_id
    - Test final fallback to Project owner_id
  - [x] 5.2 Create ReviewerResolver service class
    - File: `app/Services/ReviewerResolver.php`
    - Implement resolve(Task|WorkOrder $item): ?User method
  - [x] 5.3 Implement priority resolution logic
    - Priority 1: Check explicit reviewer_id field
    - Priority 2: Check Accountable (A) person from RACI
    - Priority 3: Check Work Order assigned_to_id
    - Priority 4: Check Project owner_id
    - Return null if no reviewer found
  - [x] 5.4 Ensure ReviewerResolver tests pass
    - Run ONLY the 4-5 tests written in 5.1

**Acceptance Criteria:**
- Resolver follows 4-level priority order
- Returns correct user at each fallback level
- Returns null when no reviewer available

---

#### Task Group 6: Timer Integration with Status Transitions
**Dependencies:** Task Group 4

- [x] 6.0 Complete timer integration with status transitions
  - [x] 6.1 Write 4-5 focused tests for timer-status integration
    - Test starting timer on Todo task transitions to InProgress
    - Test starting timer on Cancelled task is blocked
    - Test starting timer returns confirmation_required flag for Done/InReview/Approved
    - Test confirmAndStartTimer() transitions and starts timer
  - [x] 6.2 Create TimerTransitionService class
    - File: `app/Services/TimerTransitionService.php`
    - Inject WorkflowTransitionService dependency
  - [x] 6.3 Implement timer start with status check
    - checkAndStartTimer(Task $task, User $user): array
    - Return status: 'started', 'blocked', or 'confirmation_required'
    - Return reason for blocked/confirmation cases
  - [x] 6.4 Implement confirm and start method
    - confirmAndStartTimer(Task $task, User $user): TimeEntry
    - Transition task to InProgress
    - Call TimeEntry::startTimer()
  - [x] 6.5 Ensure timer integration tests pass
    - Run ONLY the 4-5 tests written in 6.1

**Acceptance Criteria:**
- Timer on Todo auto-transitions to InProgress
- Timer blocked for Cancelled tasks
- Confirmation required for Done/InReview/Approved tasks
- Integration works with existing TimeEntry model

---

#### Task Group 7: InboxItem Integration for Approvals
**Dependencies:** Task Group 4

- [x] 7.0 Complete InboxItem integration for approval workflow
  - [x] 7.1 Write 3-4 focused tests for InboxItem creation
    - Test InboxItem created when Task enters InReview
    - Test InboxItem created when WorkOrder enters InReview
    - Test InboxItem includes correct reviewer and context
  - [x] 7.2 Extend WorkflowTransitionService to create InboxItems
    - Create InboxItem when status transitions to InReview
    - Set type to InboxItemType::Approval
    - Populate related_work_order_id and related_project_id
    - Set urgency based on due date proximity
  - [x] 7.3 Implement InboxItem resolution on approval/rejection
    - Soft delete InboxItem when approved or rejected
    - Add approved_at or rejected_at timestamp
  - [x] 7.4 Ensure InboxItem integration tests pass
    - Run ONLY the 3-4 tests written in 7.1

**Acceptance Criteria:**
- InboxItems auto-created on InReview transition
- Items include full context for reviewer
- Items resolved on approval/rejection

---

### API Layer

#### Task Group 8: Workflow Transition API Endpoints
**Dependencies:** Task Groups 4, 5, 6, 7

- [x] 8.0 Complete workflow transition API endpoints
  - [x] 8.1 Write 5-6 focused tests for transition API
    - Test POST /tasks/{id}/transition validates allowed transition
    - Test POST /work-orders/{id}/transition validates allowed transition
    - Test rejection requires comment in request body
    - Test AI agent restriction returns 403
    - Test successful transition returns updated model with history
  - [x] 8.2 Create TaskTransitionController
    - File: `app/Http/Controllers/Work/TaskTransitionController.php`
    - transition(Request $request, Task $task) action
    - Validate status parameter
    - Use WorkflowTransitionService
  - [x] 8.3 Create WorkOrderTransitionController
    - File: `app/Http/Controllers/Work/WorkOrderTransitionController.php`
    - transition(Request $request, WorkOrder $workOrder) action
    - Validate status parameter
    - Use WorkflowTransitionService
  - [x] 8.4 Create TransitionRequest form request
    - File: `app/Http/Requests/TransitionRequest.php`
    - Validate status is valid enum value
    - Validate comment required when status is RevisionRequested
  - [x] 8.5 Register routes in web.php
    - POST /tasks/{task}/transition
    - POST /work-orders/{workOrder}/transition
    - Apply auth middleware
  - [x] 8.6 Ensure transition API tests pass
    - Run ONLY the 5-6 tests written in 8.1

**Acceptance Criteria:**
- Transition endpoints enforce permissions
- Proper validation and error responses
- Returns updated model with transition history

---

#### Task Group 9: Timer Start API with Confirmation Flow
**Dependencies:** Task Group 6

- [x] 9.0 Complete timer start API with confirmation flow
  - [x] 9.1 Write 3-4 focused tests for timer API
    - Test POST /tasks/{id}/timer/start returns confirmation_required response
    - Test POST /tasks/{id}/timer/start?confirmed=true transitions and starts
    - Test timer blocked for cancelled returns 422
  - [x] 9.2 Update or create TimerController
    - File: `app/Http/Controllers/Work/TimeEntryController.php`
    - start(Request $request, Task $task) action
    - Use TimerTransitionService
    - Handle confirmed query parameter
  - [x] 9.3 Implement response format for confirmation flow
    - Return JSON with confirmation_required flag
    - Include current status and message for dialog
  - [x] 9.4 Ensure timer API tests pass
    - Run ONLY the 3-4 tests written in 9.1

**Acceptance Criteria:**
- Timer start handles confirmation flow
- Returns appropriate response for each case
- Works with existing timer functionality

---

#### Task Group 10: RACI Assignment API Endpoints
**Dependencies:** Task Groups 2, 3

- [x] 10.0 Complete RACI assignment API endpoints
  - [x] 10.1 Write 4-5 focused tests for RACI API
    - Test PATCH /projects/{id}/raci updates RACI fields
    - Test PATCH /work-orders/{id}/raci updates RACI fields
    - Test assignment change logs to AuditLog
    - Test response includes confirmation_required when overwriting
  - [x] 10.2 Create ProjectRaciController
    - File: `app/Http/Controllers/ProjectRaciController.php`
    - update(Request $request, Project $project) action
    - Log changes to AuditLog
  - [x] 10.3 Create WorkOrderRaciController
    - File: `app/Http/Controllers/WorkOrderRaciController.php`
    - update(Request $request, WorkOrder $workOrder) action
    - Log changes to AuditLog
  - [x] 10.4 Create RaciUpdateRequest form request
    - File: `app/Http/Requests/RaciUpdateRequest.php`
    - Validate user IDs exist
    - Validate arrays for consulted_ids and informed_ids
  - [x] 10.5 Ensure RACI API tests pass
    - Run ONLY the 4-5 tests written in 10.1

**Acceptance Criteria:**
- RACI fields can be updated via API
- Changes logged to audit trail
- Proper validation of user IDs

---

### Frontend Layer

#### Task Group 11: Status Badge and Transition UI Components
**Dependencies:** Task Groups 1, 8

- [x] 11.0 Complete status badge and transition UI components
  - [x] 11.1 Write 3-4 focused tests for status components
    - Test StatusBadge renders correct color for each status
    - Test TransitionButton shows only valid transitions
    - Test TransitionDialog handles rejection comment requirement
  - [x] 11.2 Create StatusBadge component
    - File: `resources/js/components/ui/status-badge.tsx`
    - Props: status, variant (task | work_order)
    - Display label with appropriate color
    - Follow existing badge patterns in codebase
  - [x] 11.3 Create TransitionButton component
    - File: `resources/js/components/workflow/transition-button.tsx`
    - Props: currentStatus, allowedTransitions, onTransition
    - Dropdown menu with available transitions
    - Use Radix UI DropdownMenu primitive
  - [x] 11.4 Create TransitionDialog component
    - File: `resources/js/components/workflow/transition-dialog.tsx`
    - Props: isOpen, targetStatus, onConfirm, onCancel
    - Show comment textarea for RevisionRequested
    - Require comment before allowing submission
    - Use Radix UI Dialog primitive
  - [x] 11.5 Ensure status component tests pass
    - Run ONLY the 3-4 tests written in 11.1

**Acceptance Criteria:**
- StatusBadge displays all 8 statuses correctly
- TransitionButton shows only valid transitions
- TransitionDialog enforces comment for rejection

---

#### Task Group 12: Timer Confirmation Dialog
**Dependencies:** Task Groups 6, 9

- [x] 12.0 Complete timer confirmation dialog
  - [x] 12.1 Write 2-3 focused tests for timer confirmation
    - Test dialog appears when confirmation_required response received
    - Test confirming dialog starts timer and transitions status
  - [x] 12.2 Create TimerConfirmationDialog component
    - File: `resources/js/components/workflow/timer-confirmation-dialog.tsx`
    - Props: isOpen, currentStatus, onConfirm, onCancel
    - Display message about status change
    - Use Radix UI AlertDialog primitive
  - [x] 12.3 Integrate with existing timer controls
    - Update timer start logic to handle confirmation flow
    - Show dialog when API returns confirmation_required
    - Call confirmed endpoint on user confirmation
  - [x] 12.4 Ensure timer confirmation tests pass
    - Run ONLY the 2-3 tests written in 12.1

**Acceptance Criteria:**
- Dialog appears for Done/InReview/Approved tasks
- User can confirm or cancel
- Timer starts only after confirmation

---

#### Task Group 13: Status Transition History Component
**Dependencies:** Task Group 11

- [x] 13.0 Complete status transition history component
  - [x] 13.1 Write 2-3 focused tests for transition history
    - Test history displays transitions in chronological order
    - Test rejection comments display prominently
  - [x] 13.2 Create TransitionHistory component
    - File: `resources/js/components/workflow/transition-history.tsx`
    - Props: transitions array
    - Display user, from/to status, timestamp, comment
    - Highlight rejection feedback with distinct styling
    - Match visual design from `planning/visuals/img.png`
  - [x] 13.3 Create TransitionHistoryItem sub-component
    - Display single transition with avatar, status badges, timestamp
    - Show comment with category badge if present
  - [x] 13.4 Ensure transition history tests pass
    - Run ONLY the 2-3 tests written in 13.1

**Acceptance Criteria:**
- History shows all transitions chronologically
- Rejection feedback prominently displayed
- Matches visual design reference

---

#### Task Group 14: RACI Assignment UI Components
**Dependencies:** Task Group 10

- [x] 14.0 Complete RACI assignment UI components
  - [x] 14.1 Write 2-3 focused tests for RACI components
    - Test RaciSelector allows user selection for each role
    - Test confirmation dialog appears when changing existing assignment
  - [x] 14.2 Create RaciSelector component
    - File: `resources/js/components/workflow/raci-selector.tsx`
    - Props: value, onChange, users array, entityType
    - Four-field layout for R/A/C/I
    - Use Radix UI Select for user selection
    - Support multi-select for Consulted and Informed
  - [x] 14.3 Create AssignmentConfirmationDialog component
    - File: `resources/js/components/workflow/assignment-confirmation-dialog.tsx`
    - Props: isOpen, currentAssignment, newAssignment, onConfirm, onCancel
    - Display who is being replaced
    - Use Radix UI AlertDialog primitive
  - [x] 14.4 Ensure RACI component tests pass
    - Run ONLY the 2-3 tests written in 14.1

**Acceptance Criteria:**
- RACI roles can be assigned via UI
- Confirmation required when changing existing assignments
- Multi-select works for C and I roles

---

#### Task Group 15: Task Detail Page Updates
**Dependencies:** Task Groups 11, 12, 13, 14

- [x] 15.0 Complete Task detail page with workflow features
  - [x] 15.1 Write 3-4 focused tests for Task detail page
    - Test page displays current status with badge
    - Test transition button shows valid transitions
    - Test transition history section displays
  - [x] 15.2 Update Task detail page layout
    - File: `resources/js/pages/work/tasks/[id].tsx`
    - Add StatusBadge to header
    - Add TransitionButton to actions area
    - Add TransitionHistory section
  - [x] 15.3 Integrate rejection feedback display
    - Show most recent rejection comment prominently if status is InProgress and previous was RevisionRequested
    - Style as alert/banner at top of detail area
  - [x] 15.4 Connect to transition API
    - Handle transition responses
    - Update local state on success
    - Show error messages on failure
  - [x] 15.5 Ensure Task detail page tests pass
    - Run ONLY the 3-4 tests written in 15.1

**Acceptance Criteria:**
- Task detail shows status and transition controls
- Transition history visible
- Rejection feedback prominently displayed

---

#### Task Group 16: Work Order Detail Page Updates
**Dependencies:** Task Groups 11, 13, 14

- [x] 16.0 Complete Work Order detail page with workflow features
  - [x] 16.1 Write 3-4 focused tests for Work Order detail page
    - Test page displays current status with badge
    - Test RACI selector displays and updates
    - Test transition history section displays
  - [x] 16.2 Update Work Order detail page layout
    - File: `resources/js/pages/work/work-orders/[id].tsx`
    - Add StatusBadge to header
    - Add TransitionButton to actions area
    - Add RaciSelector section
    - Add TransitionHistory section
  - [x] 16.3 Integrate RACI display and editing
    - Show current RACI assignments
    - Allow editing with confirmation flow
  - [x] 16.4 Connect to transition and RACI APIs
    - Handle responses and update local state
    - Show error messages on failure
  - [x] 16.5 Ensure Work Order detail page tests pass
    - Run ONLY the 3-4 tests written in 16.1

**Acceptance Criteria:**
- Work Order detail shows status, RACI, and transition controls
- RACI editing works with confirmation
- Transition history visible

---

#### Task Group 17: Inbox Approval List View Enhancements
**Dependencies:** Task Groups 7, 11

- [x] 17.0 Complete Inbox approval list view
  - [x] 17.1 Write 2-3 focused tests for Inbox enhancements
    - Test Approvals tab filters to approval type items
    - Test approval items display source, context, and waiting time
  - [x] 17.2 Update Inbox list view for approvals
    - File: `resources/js/pages/inbox/index.tsx` (or create if needed)
    - Add Approvals filter tab
    - Display approval items with context
    - Show waiting time indicator
    - Match visual design from `planning/visuals/Screenshot 2026-01-18 at 13.51.50.png`
  - [x] 17.3 Create ApprovalListItem component
    - File: `resources/js/components/inbox/approval-list-item.tsx`
    - Display source, work order context, urgency badge
    - Show AI confidence level badge
    - Checkbox for bulk selection
  - [x] 17.4 Ensure Inbox approval tests pass
    - Run ONLY the 2-3 tests written in 17.1

**Acceptance Criteria:**
- Inbox shows approval items with filtering
- Items display full context
- Matches visual design reference

---

#### Task Group 18: Inbox Approval Detail Panel
**Dependencies:** Task Group 17

- [x] 18.0 Complete Inbox approval detail panel
  - [x] 18.1 Write 2-3 focused tests for approval detail panel
    - Test panel displays full item content and context
    - Test Approve and Request Changes buttons work
  - [x] 18.2 Create ApprovalDetailPanel component
    - File: `resources/js/components/inbox/approval-detail-panel.tsx`
    - Slide-out panel layout
    - Display source, dates, project/work order links
    - Show AI confidence and waiting time
    - Match visual design from `planning/visuals/Screenshot 2026-01-18 at 13.52.24.png`
  - [x] 18.3 Add approval action buttons
    - Approve button (green) triggers transition to Approved
    - Request Changes button (red/orange) opens rejection dialog
    - Integrate with TransitionDialog for comment requirement
  - [x] 18.4 Ensure approval detail panel tests pass
    - Run ONLY the 2-3 tests written in 18.1

**Acceptance Criteria:**
- Detail panel shows full approval context
- Approve and reject actions work correctly
- Matches visual design reference

---

### Testing

#### Task Group 19: Test Review and Gap Analysis
**Dependencies:** Task Groups 1-18

- [x] 19.0 Review existing tests and fill critical gaps only
  - [x] 19.1 Review tests from Task Groups 1-18
    - Review database layer tests (Groups 1-3): 14 tests
    - Review service layer tests (Groups 4-7): 20 tests
    - Review API layer tests (Groups 8-10): 15 tests
    - Review frontend tests (Groups 11-18): 36 tests
    - Total existing tests: 85 tests (backend) + 36 tests (frontend) = 101 tests
  - [x] 19.2 Analyze test coverage gaps for workflow feature only
    - Identified critical end-to-end workflows lacking coverage:
      - Complete approval workflow (Todo -> Done)
      - Complete work order delivery workflow (Draft -> Delivered)
      - Rejection and revision workflow with auto-transition
      - AI agent restriction verification
      - Timer confirmation flow
      - RACI assignment with audit logging
      - Blocked status workflow
  - [x] 19.3 Write up to 10 additional strategic tests maximum
    - E2E test: Complete approval workflow (Todo -> InProgress -> InReview -> Approved -> Done)
    - E2E test: Complete work order delivery workflow (Draft -> Active -> InReview -> Approved -> Delivered)
    - E2E test: Rejection and revision workflow with auto-transition and resubmit
    - E2E test: AI agent is blocked from approval and delivery transitions (tasks)
    - E2E test: AI agent is blocked from work order approval and delivery
    - E2E test: Timer confirmation flow for Done tasks
    - E2E test: RACI changes are logged to audit trail
    - E2E test: Changing existing RACI assignment requires confirmation
    - E2E test: Task can be blocked and unblocked
    - E2E test: Work order can be blocked and unblocked
    - Added 10 tests in `tests/Feature/Workflow/WorkflowEndToEndTest.php`
  - [x] 19.4 Run feature-specific tests only
    - Backend workflow tests: 65 passed (421 assertions)
    - Frontend workflow tests: 36 passed
    - Total: 101 feature-specific tests (all passing)

**Acceptance Criteria:**
- All feature-specific tests pass
- Critical end-to-end workflows covered
- No more than 10 additional tests added
- Testing focused exclusively on Human Checkpoint Workflow requirements

---

## Execution Order

Recommended implementation sequence:

### Phase 1: Database Layer (Task Groups 1-3)
1. Task Group 1: Status Enums and Status Transition Table
2. Task Group 2: RACI Fields for Projects and Work Orders (can run parallel with 3)
3. Task Group 3: Task Model Extensions (can run parallel with 2)

### Phase 2: Service Layer (Task Groups 4-7)
4. Task Group 4: Workflow Transition Service
5. Task Group 5: Reviewer Resolver Service (can run parallel with 4)
6. Task Group 6: Timer Integration with Status Transitions (depends on 4)
7. Task Group 7: InboxItem Integration for Approvals (depends on 4)

### Phase 3: API Layer (Task Groups 8-10)
8. Task Group 8: Workflow Transition API Endpoints
9. Task Group 9: Timer Start API with Confirmation Flow (can run parallel with 8)
10. Task Group 10: RACI Assignment API Endpoints (can run parallel with 8, 9)

### Phase 4: Frontend Layer (Task Groups 11-18)
11. Task Group 11: Status Badge and Transition UI Components
12. Task Group 12: Timer Confirmation Dialog (can run parallel with 11)
13. Task Group 13: Status Transition History Component (depends on 11)
14. Task Group 14: RACI Assignment UI Components (can run parallel with 11-13)
15. Task Group 15: Task Detail Page Updates (depends on 11, 12, 13)
16. Task Group 16: Work Order Detail Page Updates (depends on 11, 13, 14)
17. Task Group 17: Inbox Approval List View Enhancements (depends on 11)
18. Task Group 18: Inbox Approval Detail Panel (depends on 17)

### Phase 5: Testing (Task Group 19)
19. Task Group 19: Test Review and Gap Analysis

---

## Visual Asset References

- **Inbox List View**: `planning/visuals/Screenshot 2026-01-18 at 13.51.50.png`
  - Use for Task Group 17 (ApprovalListItem styling, filter tabs, urgency badges)
- **Detail Panel**: `planning/visuals/Screenshot 2026-01-18 at 13.52.24.png`
  - Use for Task Group 18 (ApprovalDetailPanel layout, action buttons)
- **Review Interface**: `planning/visuals/img.png`
  - Use for Task Group 13 (TransitionHistory comment styling, activity feed layout)

---

## Technical Notes

### State Machine Rules

**Task Status Transitions:**
- Todo -> InProgress, Cancelled
- InProgress -> InReview, Done, Blocked, Cancelled
- InReview -> Approved, RevisionRequested, Cancelled
- Approved -> Done, RevisionRequested, Cancelled
- Done -> (terminal, no outgoing except via timer confirmation)
- Blocked -> InProgress, Cancelled
- RevisionRequested -> (auto-transitions to InProgress)
- Cancelled -> (terminal)

**Work Order Status Transitions:**
- Draft -> Active, Cancelled
- Active -> InReview, Delivered, Blocked, Cancelled
- InReview -> Approved, RevisionRequested, Cancelled
- Approved -> Delivered, RevisionRequested, Cancelled
- Delivered -> (terminal)
- Blocked -> Active, Cancelled
- RevisionRequested -> (auto-transitions to Active)
- Cancelled -> (terminal)

### AI Agent Restrictions
- AI agents CAN: create work items, submit for review (transition to InReview)
- AI agents CANNOT: approve (InReview -> Approved), deliver (Approved -> Done/Delivered)
- Enforcement: Check actor_type in WorkflowTransitionService before allowing transition
