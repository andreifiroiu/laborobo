# Task Breakdown: Communications Log

## Overview
Total Tasks: 7 Task Groups

This feature extends the existing communications system to support Tasks, adds mentions with notifications, file attachments, emoji reactions, message edit/delete capabilities, AI message distinction, and a consolidated communications view.

## Task List

### Database Layer

#### Task Group 1: Data Models and Migrations
**Dependencies:** None

- [x] 1.0 Complete database layer for communications extensions
  - [x] 1.1 Write 6 focused tests for new database functionality
    - Test MessageMention polymorphic relationship (user mention)
    - Test MessageMention polymorphic relationship (work item mention)
    - Test MessageAttachment belongs to Message
    - Test MessageReaction unique constraint (user + message + emoji)
    - Test Message soft delete functionality
    - Test Task communicationThread morphOne relationship
  - [x] 1.2 Create migration for `message_mentions` table
    - Fields: `id`, `message_id` (foreign key), `mentionable_type`, `mentionable_id`, `created_at`
    - Add index on `mentionable_type` and `mentionable_id`
    - Add index on `message_id`
  - [x] 1.3 Create `MessageMention` model
    - Polymorphic `mentionable` relationship (morphTo)
    - BelongsTo relationship to Message
    - Fillable: `message_id`, `mentionable_type`, `mentionable_id`
  - [x] 1.4 Create migration for `message_attachments` table
    - Fields: `id`, `message_id` (foreign key), `name`, `file_url`, `file_size`, `mime_type`, `timestamps`
    - Add index on `message_id`
    - Add foreign key constraint with cascade delete
  - [x] 1.5 Create `MessageAttachment` model
    - BelongsTo relationship to Message
    - Fillable: `message_id`, `name`, `file_url`, `file_size`, `mime_type`
  - [x] 1.6 Create migration for `message_reactions` table
    - Fields: `id`, `message_id` (foreign key), `user_id` (foreign key), `emoji`, `created_at`
    - Add unique constraint on (`message_id`, `user_id`, `emoji`)
    - Add indexes on `message_id` and `user_id`
  - [x] 1.7 Create `MessageReaction` model
    - BelongsTo relationships to Message and User
    - Fillable: `message_id`, `user_id`, `emoji`
  - [x] 1.8 Create migration to add columns to `messages` table
    - Add `edited_at` nullable timestamp column
    - Add `deleted_at` nullable timestamp column for soft deletes
  - [x] 1.9 Update `Message` model
    - Add `SoftDeletes` trait
    - Add `mentions` hasMany relationship to MessageMention
    - Add `attachments` hasMany relationship to MessageAttachment
    - Add `reactions` hasMany relationship to MessageReaction
    - Add `edited_at` to casts array as datetime
  - [x] 1.10 Add `communicationThread` morphOne relationship to `Task` model
    - Follow existing pattern from Project and WorkOrder models
  - [x] 1.11 Extend `MessageType` enum with new cases
    - Add `StatusUpdate` case with label "Status Update" and color "blue"
    - Add `ApprovalRequest` case with label "Approval Request" and color "purple"
    - Add `Message` case with label "Message" and color "gray"
  - [x] 1.12 Ensure database layer tests pass
    - Run ONLY the 6 tests written in 1.1
    - Verify all migrations run successfully
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 6 tests written in 1.1 pass
- All new migrations execute without errors
- Polymorphic relationships resolve correctly
- Soft deletes work on Message model
- Task model has communication thread support

---

### Backend API Layer

#### Task Group 2: Core API Endpoints
**Dependencies:** Task Group 1

- [x] 2.0 Complete API layer for communications
  - [x] 2.1 Write 6 focused tests for API endpoints
    - Test GET `/work/tasks/{id}/communications` returns thread and messages
    - Test POST `/work/tasks/{id}/communications` creates message with mentions parsed
    - Test PATCH `/work/communications/messages/{id}` edits message within 10-minute window
    - Test DELETE `/work/communications/messages/{id}` soft-deletes within 10-minute window
    - Test POST `/work/communications/messages/{id}/reactions` adds reaction
    - Test DELETE `/work/communications/messages/{id}/reactions/{emoji}` removes reaction
  - [x] 2.2 Extend `CommunicationController::getModel()` to support `tasks` type
    - Add `'tasks'` case to match statement returning Task model
    - Use same pattern as existing `projects` and `work-orders` cases
  - [x] 2.3 Add routes for task communications
    - `GET /work/tasks/{task}/communications` mapped to `CommunicationController::show`
    - `POST /work/tasks/{task}/communications` mapped to `CommunicationController::store`
    - Group with existing work routes and appropriate middleware
  - [x] 2.4 Update `CommunicationController::store()` validation
    - Accept new MessageType values (status_update, approval_request, message)
    - Validate file attachments if provided (mime types, size limits)
  - [x] 2.5 Create mention parsing service
    - Parse `@username` patterns for user mentions
    - Parse `@P-{id}`, `@WO-{id}`, `@T-{id}` patterns for work item mentions
    - Return array of mentionable entities found in content
  - [x] 2.6 Extend `CommunicationThread::addMessage()` to handle mentions
    - Parse message content for mentions using parsing service
    - Create MessageMention records for each found mention
    - Return created message with mentions relationship loaded
  - [x] 2.7 Create `GET /api/mentions/search` endpoint
    - Accept query params: `q` (search term), `type` (user|work_item|all)
    - Return matching users and/or work items for autocomplete
    - Limit results to 10 per type for performance
  - [x] 2.8 Add file attachment handling to message creation
    - Accept file uploads in store request
    - Store files using Laravel file storage (local or S3)
    - Create MessageAttachment records with file metadata
    - Reuse validation logic from existing FileUploader patterns
  - [x] 2.9 Create message edit endpoint
    - Route: `PATCH /work/communications/messages/{message}`
    - Validate user owns the message
    - Validate within 10-minute window from created_at
    - Update content and set `edited_at` timestamp
    - Re-parse and update mentions
  - [x] 2.10 Create message delete endpoint
    - Route: `DELETE /work/communications/messages/{message}`
    - Validate user owns the message
    - Validate within 10-minute window from created_at
    - Perform soft delete
  - [x] 2.11 Create reaction endpoints
    - `POST /work/communications/messages/{message}/reactions` - add reaction (body: emoji)
    - `DELETE /work/communications/messages/{message}/reactions/{emoji}` - remove reaction
    - Return updated reaction counts grouped by emoji
  - [x] 2.12 Update message API responses
    - Include `mentions` relationship with mentionable details
    - Include `attachments` relationship
    - Include `reactions` grouped by emoji with counts and user flags
    - Include `edited_at` timestamp
    - Include `can_edit` and `can_delete` computed flags based on time window
  - [x] 2.13 Ensure API layer tests pass
    - Run ONLY the 6 tests written in 2.1
    - Verify all endpoints return expected responses
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 6 tests written in 2.1 pass
- Task communications work identically to Project/WorkOrder communications
- Mentions are parsed and stored correctly
- File attachments upload and are retrievable
- Reactions can be added/removed with proper counts
- Edit/delete respect 10-minute window

---

#### Task Group 3: Notifications and Events
**Dependencies:** Task Group 2

- [x] 3.0 Complete notifications and WebSocket-ready events
  - [x] 3.1 Write 4 focused tests for notifications and events
    - Test MentionNotification dispatched when user is mentioned
    - Test MentionNotification includes correct work item link
    - Test MessageCreated event is dispatched on message creation
    - Test ReactionAdded event is dispatched when reaction added
  - [x] 3.2 Create `MentionNotification` notification class
    - Follow pattern from `RejectionFeedbackNotification`
    - Support database and mail channels based on user preferences
    - Include message author, content preview, and work item link
    - Use existing notification layout and styling
  - [x] 3.3 Dispatch mention notifications on message creation
    - Filter mentions to only user mentions (not work items)
    - Exclude message author from notifications
    - Queue notifications for async processing
  - [x] 3.4 Create Laravel events for WebSocket readiness
    - `MessageCreated` event with message and thread data
    - `MessageUpdated` event with updated message data
    - `MessageDeleted` event with message ID
    - `ReactionAdded` event with reaction and message data
    - `ReactionRemoved` event with reaction details
  - [x] 3.5 Configure events for future broadcasting
    - Implement `ShouldBroadcast` interface on events
    - Define broadcast channel (e.g., `team.{teamId}` or `thread.{threadId}`)
    - Structure payload for frontend consumption
    - Add conditional check for Reverb configuration (defer actual broadcasting)
  - [x] 3.6 Dispatch events from appropriate locations
    - Dispatch MessageCreated in addMessage() method
    - Dispatch MessageUpdated in edit endpoint
    - Dispatch MessageDeleted in delete endpoint
    - Dispatch ReactionAdded/Removed in reaction endpoints
  - [x] 3.7 Ensure notification and event tests pass
    - Run ONLY the 4 tests written in 3.1
    - Verify notifications are queued correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4 tests written in 3.1 pass
- Mentioned users receive notifications
- Events are structured for WebSocket broadcasting
- Events dispatch correctly but defer actual broadcasting

---

#### Task Group 4: Consolidated Communications Endpoint
**Dependencies:** Task Group 2

- [x] 4.0 Complete consolidated communications view backend
  - [x] 4.1 Write 4 focused tests for consolidated view
    - Test GET `/communications` returns paginated messages across threads
    - Test filtering by work item type (project, work_order, task)
    - Test filtering by message type
    - Test full-text search on message content
  - [x] 4.2 Create `CommunicationsController` for consolidated view
    - Index action returns paginated messages from user-accessible threads
    - Eager load thread, threadable (work item), author relationships
    - Apply authorization to filter to user's team/projects
  - [x] 4.3 Add route for consolidated communications page
    - `GET /communications` mapped to `CommunicationsController::index`
    - Returns Inertia page with messages and filter options
  - [x] 4.4 Implement filter parameters
    - `type` - filter by threadable type (project, work_order, task)
    - `message_type` - filter by MessageType enum value
    - `from` and `to` - date range filter on created_at
    - `search` - full-text search on message content using LIKE
  - [x] 4.5 Format consolidated view response
    - Include work item context (type, ID, name) for each message
    - Include link/route to parent work item
    - Support pagination with cursor or offset
    - Return filter options with active values
  - [x] 4.6 Ensure consolidated view tests pass
    - Run ONLY the 4 tests written in 4.1
    - Verify filtering and pagination work correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4 tests written in 4.1 pass
- Consolidated view shows messages across all work items
- Filters narrow results correctly
- Search finds messages by content
- Work item context is included with each message

---

### Frontend Components

#### Task Group 5: Core UI Components
**Dependencies:** Task Groups 2, 3, 4

- [x] 5.0 Complete core frontend components
  - [x] 5.1 Write 6 focused tests for UI components
    - Test MentionInput triggers autocomplete on @ character
    - Test MentionInput inserts selected mention into content
    - Test ReactionPicker displays emoji options and handles selection
    - Test MessageItem shows edit/delete actions within time window
    - Test MessageItem hides edit/delete actions after time window
    - Test CommunicationsPanel renders message list and input
  - [x] 5.2 Create `MentionInput` component
    - Extend textarea/input with @ trigger detection
    - Show Popover with autocomplete results on @ character
    - Call `/api/mentions/search` endpoint for suggestions
    - Insert formatted mention on selection
    - Support both user and work item mention formats
    - Props: `value`, `onChange`, `placeholder`
  - [x] 5.3 Create `ReactionPicker` component
    - Button that opens emoji selection Popover
    - Common reaction emojis as quick options
    - Call reaction API on emoji selection
    - Props: `messageId`, `onReactionAdd`
  - [x] 5.4 Create `MessageReactions` component
    - Display grouped reactions with counts
    - Highlight reactions from current user
    - Toggle own reaction on click
    - Props: `reactions`, `messageId`, `currentUserId`
  - [x] 5.5 Create `MessageAttachments` component
    - Display attachment list with icons by type
    - Image thumbnails for image attachments
    - Download link for non-image files
    - Props: `attachments`
  - [x] 5.6 Create `MessageItem` component
    - Display message content with author and timestamp
    - Show message type badge using existing color pattern
    - Display "(edited)" indicator when edited_at is set
    - Show AI badge/styling for AuthorType.AiAgent messages
    - Include MessageReactions display
    - Include MessageAttachments display
    - Dropdown menu with Edit/Delete for own messages within time window
    - Props: `message`, `currentUserId`, `onEdit`, `onDelete`
  - [x] 5.7 Create `MessageInput` component
    - Combine MentionInput with message type selector
    - File attachment button using FileUploader pattern
    - Submit button with loading state
    - Props: `threadableType`, `threadableId`, `onMessageSent`
  - [x] 5.8 Create enhanced `CommunicationsPanel` component
    - Reusable Sheet-based component for all work item types
    - Props: `threadableType`, `threadableId`, `open`, `onOpenChange`
    - Message list using MessageItem components
    - MessageInput at bottom
    - Use TanStack Query or Inertia reload for data fetching
    - Polling fallback with configurable interval (30 seconds default)
  - [x] 5.9 Ensure UI component tests pass
    - Run ONLY the 6 tests written in 5.1
    - Verify component behaviors work correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 6 tests written in 5.1 pass
- MentionInput provides autocomplete experience
- Reactions can be added/removed/toggled
- Messages display all metadata correctly
- AI messages are visually distinct
- Edit/delete work within time constraints

---

#### Task Group 6: Page Integration
**Dependencies:** Task Group 5

- [x] 6.0 Complete page integrations
  - [x] 6.1 Write 4 focused tests for page integrations
    - Test Task detail page renders CommunicationsPanel in Sheet
    - Test CommunicationsIndex page renders with filters
    - Test CommunicationsIndex filter controls update results
    - Test message creation from CommunicationsPanel updates list
  - [x] 6.2 Update Task detail page with CommunicationsPanel
    - Add communications button/trigger to open Sheet
    - Integrate CommunicationsPanel component
    - Pass task ID and type to panel
    - Follow same pattern as WorkOrder detail page
  - [x] 6.3 Update Project detail page CommunicationsPanel
    - Replace existing communications Sheet content
    - Use new enhanced CommunicationsPanel component
    - Maintain existing trigger behavior
  - [x] 6.4 Update WorkOrder detail page CommunicationsPanel
    - Replace existing communications Sheet content
    - Use new enhanced CommunicationsPanel component
    - Maintain existing trigger behavior
  - [x] 6.5 Create `CommunicationsIndex` page component
    - Route: `/communications`
    - Filter controls: work item type dropdown, message type dropdown, date range picker
    - Search input for content search
    - Message list with work item context links
    - Pagination controls
  - [x] 6.6 Add sidebar navigation for Communications
    - Add "Communications" link to app sidebar
    - Use appropriate icon (MessageSquare or similar)
    - Position in navigation hierarchy appropriately
  - [x] 6.7 Style AI agent messages distinctively
    - Different background color for AI messages
    - "AI Suggestion" label next to author name
    - Use existing design system colors
  - [x] 6.8 Ensure page integration tests pass
    - Run ONLY the 4 tests written in 6.1
    - Verify pages render and function correctly
    - Do NOT run the entire test suite at this stage

**Acceptance Criteria:**
- The 4 tests written in 6.1 pass
- Task detail page has working communications panel
- Project and WorkOrder pages use enhanced panel
- Consolidated view displays and filters messages
- Navigation includes Communications link

---

### Testing

#### Task Group 7: Test Review and Gap Analysis
**Dependencies:** Task Groups 1-6

- [x] 7.0 Review existing tests and fill critical gaps only
  - [x] 7.1 Review tests from Task Groups 1-6
    - Review the 6 database tests from Task 1.1 in `tests/Feature/Communications/CommunicationsModelTest.php`
    - Review the 6 API tests from Task 2.1 in `tests/Feature/Communications/CommunicationsApiTest.php`
    - Review the 4 notification/event tests from Task 3.1 in `tests/Feature/Communications/NotificationsAndEventsTest.php`
    - Review the 4 consolidated view tests from Task 4.1 in `tests/Feature/Communications/ConsolidatedViewTest.php`
    - Review the 6 UI component tests from Task 5.1 in `resources/js/components/communications/__tests__/communications.test.tsx`
    - Review the 6 page integration tests from Task 6.1 in `resources/js/pages/communications/__tests__/` and `resources/js/pages/work/__tests__/`
    - Total existing tests: 32 tests
  - [x] 7.2 Analyze test coverage gaps for communications feature only
    - Identified critical user workflows lacking coverage
    - Focused on end-to-end message creation flow
    - Focused on mention-to-notification flow
    - Focused on file attachment upload flow
    - Did NOT assess entire application coverage
  - [x] 7.3 Write up to 8 additional strategic tests maximum
    - Added end-to-end test for complete message creation with mentions and notification dispatch
    - Added test for file attachment upload and retrieval
    - Added test for consolidated view with multiple filter combinations
    - Added test for message edit preserves and re-parses mentions
    - Added test for edit rejection outside 10-minute window
    - Added test for delete rejection outside 10-minute window
    - Added test for work order communications API parity
    - Added test for blocked file extensions rejection
    - Tests added to `tests/Feature/Communications/CommunicationsIntegrationTest.php`
  - [x] 7.4 Run feature-specific tests only
    - Ran ONLY tests related to Communications Log feature
    - Total: 40 tests (28 PHP + 12 frontend)
    - All critical workflows pass
    - Did NOT run the entire application test suite

**Acceptance Criteria:**
- All feature-specific tests pass (40 tests total)
- Critical user workflows are covered
- 8 additional tests added (within limit)
- Testing focused exclusively on Communications Log feature

---

## Execution Order

Recommended implementation sequence:

1. **Database Layer** (Task Group 1)
   - Foundation for all other work
   - No dependencies

2. **Core API Endpoints** (Task Group 2)
   - Depends on database models
   - Enables frontend development

3. **Notifications and Events** (Task Group 3)
   - Depends on API layer for message creation hooks
   - Can run parallel to Task Group 4

4. **Consolidated Communications Endpoint** (Task Group 4)
   - Depends on database models
   - Can run parallel to Task Group 3

5. **Core UI Components** (Task Group 5)
   - Depends on API endpoints being available
   - Component-level work

6. **Page Integration** (Task Group 6)
   - Depends on UI components
   - Final assembly

7. **Test Review and Gap Analysis** (Task Group 7)
   - Final verification
   - Depends on all other groups

---

## Technical Notes

### Existing Code to Extend
- `app/Models/CommunicationThread.php` - Add mention handling to addMessage()
- `app/Models/Message.php` - Add relationships and SoftDeletes
- `app/Models/Task.php` - Add communicationThread relationship
- `app/Enums/MessageType.php` - Add three new cases
- `app/Http/Controllers/Work/CommunicationController.php` - Extend getModel() and store()
- `resources/js/components/work/file-uploader.tsx` - Reference for attachment handling

### New Files to Create
- `app/Models/MessageMention.php`
- `app/Models/MessageAttachment.php`
- `app/Models/MessageReaction.php`
- `app/Notifications/MentionNotification.php`
- `app/Events/MessageCreated.php` (and other events)
- `app/Http/Controllers/CommunicationsController.php`
- `app/Services/MentionParserService.php`
- `resources/js/components/communications/` (multiple components)
- `resources/js/pages/communications/index.tsx`

### Database Migrations
- `create_message_mentions_table`
- `create_message_attachments_table`
- `create_message_reactions_table`
- `add_edit_delete_columns_to_messages_table`
