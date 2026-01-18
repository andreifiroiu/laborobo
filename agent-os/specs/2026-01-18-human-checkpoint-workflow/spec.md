# Specification: Human Checkpoint Workflow

## Goal

Implement a state machine workflow for Tasks and Work Orders that enforces human oversight at critical checkpoints. AI agents can draft and submit work for review, but only humans can approve and deliver, ensuring quality control while leveraging AI productivity gains.

## User Stories

- As a team manager, I want approval checkpoints on work items so that I can maintain quality control over deliverables before they reach clients
- As an AI agent, I want to submit completed work for human review so that my outputs are validated before delivery
- As a team member, I want clear visibility into work item status and transition history so that I understand where work stands in the approval pipeline

## Specific Requirements

**Extended Task Status Workflow**
- Add new statuses to TaskStatus enum: InReview, Approved, Blocked, Cancelled, RevisionRequested
- Todo is the initial state; Done is the terminal state for completed work
- Blocked can only be entered from InProgress state
- Cancelled can be entered from any non-terminal state
- RevisionRequested automatically transitions back to InProgress
- InReview and Approved are optional checkpoints (can skip directly to Done if workflow permits)

**Extended Work Order Status Workflow**
- Add new statuses to WorkOrderStatus enum: Blocked, Cancelled, RevisionRequested
- Draft is the initial state; Delivered is the terminal state
- Blocked can only be entered from Active state
- Cancelled can be entered from any non-terminal state
- RevisionRequested automatically transitions back to Active
- Existing InReview and Approved statuses serve as mandatory checkpoints before Delivered

**State Transition Permission Enforcement**
- Create a WorkflowTransitionService that validates permissions for each transition at runtime
- Draft/Todo to InProgress/Active: Any assigned user or team member
- Any to InReview: Any assigned user or team member
- InReview to Approved: Managers/owners OR any user except the one who submitted for review
- Approved to Done/Delivered: Managers/owners OR the assigned user
- AI agents are explicitly blocked from InReview-to-Approved and Approved-to-Done/Delivered transitions

**Automatic Status Transitions from Timer Actions**
- Starting a timer on a Todo task automatically transitions it to InProgress
- Starting a timer on Done/InReview/Approved tasks triggers a confirmation dialog before moving to InProgress
- Timer start is blocked entirely for Cancelled tasks
- Integrate with existing TimeEntry.startTimer() method to check status and handle transitions

**RACI Framework for Projects**
- Add accountable_id (required), responsible_id, consulted_ids (JSON array), informed_ids (JSON array) to projects table
- Accountable field represents the project owner and is required
- R/C/I fields are optional and can be set by any team member
- Log all RACI assignment changes to AuditLog

**RACI Framework for Work Orders**
- Add accountable_id (required), responsible_id, consulted_ids (JSON array), informed_ids (JSON array) to work_orders table
- Existing created_by_id field already present; ensure it is always populated
- Accountable is required; R/C/I are optional
- Changing RACI assignments requires confirmation dialog when values already exist

**Simplified Assignment for Tasks**
- Tasks use only assigned_to_id (already exists) instead of full RACI
- Add created_by_id field to tasks table (required, auto-populated)
- Changing assigned_to_id when already set requires confirmation dialog

**Reviewer Auto-Determination Logic**
- Create ReviewerResolver service that determines reviewer in priority order
- Priority 1: Explicit reviewer_id field on the work item (new field to add)
- Priority 2: Accountable (A) person from RACI
- Priority 3: Work order assigned_to_id (manager)
- Priority 4: Project owner_id (final fallback)

**Rejection Flow with Required Feedback**
- Rejecting work (InReview to RevisionRequested) requires a non-empty feedback comment
- Store rejection feedback as part of the transition audit record
- Display rejection feedback prominently on the work item detail view
- RevisionRequested status automatically moves to InProgress (Task) or Active (Work Order)

**Status Transition Audit Trail**
- Create status_transitions table to track: model_type, model_id, user_id, from_status, to_status, comment (nullable), created_at
- All status changes must create a transition record
- Display transition history in chronological order on work item detail pages
- Support filtering activity log by transition type

## Visual Design

**`planning/visuals/Screenshot 2026-01-18 at 13.51.50.png`**
- Inbox list view with filtering tabs for Agent Drafts, Approvals, Flagged, and Mentions
- Each item shows source (AI agent name), creation timestamp, and waiting time indicator
- Color-coded urgency badges (red for URGENT) and AI confidence level badges
- Items display related work order and project context for quick navigation
- Supports bulk selection with checkboxes for batch actions

**`planning/visuals/Screenshot 2026-01-18 at 13.52.24.png`**
- Detail view slide-out panel showing flagged item with full context
- Displays source agent, creation date, project, work order links, AI confidence, and waiting time
- Structured content sections: Issue description, Impact assessment, What has been checked, Action needed
- Action buttons at bottom: Edit and Defer options for quick workflow actions
- Badge system distinguishing item types (Flagged Item) and urgency levels

**`planning/visuals/img.png`**
- Document review interface with split-panel layout showing content and activity sidebar
- Top toolbar with navigation (page numbers), download button, and review status controls
- Right sidebar shows review actions: Approve (green) and Request Changes (red/orange) buttons
- Activity feed showing comments with user avatars, timestamps, and status change indicators
- Comments support categorization (e.g., "Design Impact", "Change Request") with colored badges

## Existing Code to Leverage

**TaskStatus and WorkOrderStatus Enums**
- Located at app/Enums/TaskStatus.php and app/Enums/WorkOrderStatus.php
- Both implement label() and color() methods for UI rendering
- Extend these enums with new cases following the same pattern
- WorkOrderStatus already has InReview and Approved statuses

**AuditLog Model**
- Located at app/Models/AuditLog.php with static log() method
- Captures actor (user/agent), action, target, and details
- Reuse this pattern for status transition logging or create specialized StatusTransition model
- Already supports actor_type differentiation for human vs AI

**InboxItem Model and InboxItemType Enum**
- Located at app/Models/InboxItem.php with approval queue capabilities
- InboxItemType enum already includes Approval case
- Extend to create InboxItems when work enters InReview status
- Use existing urgency, ai_confidence, and qa_validation fields

**TimeEntry Model**
- Located at app/Models/TimeEntry.php with startTimer() static method
- Extend startTimer() to check task status and handle automatic transitions
- Add confirmation flow for starting timer on completed/review tasks

**Project and WorkOrder Models**
- Project has owner_id field that serves as default Accountable
- WorkOrder already has created_by_id and assigned_to_id fields
- Add RACI fields to both models following existing relationship patterns

## Out of Scope

- Multi-level approval chains requiring 2+ sequential approvers
- Conditional workflows based on budget thresholds or work order value
- External client-facing approval portals or guest reviewer access
- Automated approval rules or auto-approve based on confidence scores
- Parallel approval paths where multiple approvers must all approve
- Approval delegation or proxy approval capabilities
- SLA tracking or escalation for overdue approvals
- Approval templates or configurable workflow definitions per project
- Integration with external approval systems (DocuSign, etc.)
- Mobile push notifications for approval requests
