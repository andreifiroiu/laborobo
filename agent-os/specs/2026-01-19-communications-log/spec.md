# Specification: Communications Log

## Goal
Build a comprehensive communications log feature that enables team collaboration through contextual message threads attached to work items (Projects, Work Orders, and Tasks), with support for mentions, message types, file attachments, and reactions.

## User Stories
- As a team member, I want to communicate about work items in context so that discussions stay organized and searchable
- As a project manager, I want to see all communications across my projects in one consolidated view so that I can stay informed without navigating to each work item

## Specific Requirements

**Task Communication Thread Support**
- Add `communicationThread` morphOne relationship to Task model
- Extend `CommunicationController::getModel()` to support `tasks` type parameter
- Create routes for task communications: `GET /work/tasks/{id}/communications` and `POST /work/tasks/{id}/communications`
- Use existing polymorphic `threadable` pattern from CommunicationThread model
- Communication panel on Task detail page follows same Sheet pattern as Work Order detail page

**Extended Message Types**
- Add three new values to `MessageType` enum: `StatusUpdate`, `ApprovalRequest`, `Message`
- Define appropriate labels and colors for new types (status_update=blue, approval_request=purple, message=gray)
- Update validation in `CommunicationController::store()` to accept new type values
- Update frontend message type selector to include all seven types
- Display message type badge using existing `MessageType::color()` pattern

**Mentions System with Autocomplete**
- Create `message_mentions` pivot table: `message_id`, `mentionable_type`, `mentionable_id`, `created_at`
- Create `MessageMention` model with polymorphic `mentionable` relationship
- Add API endpoint `GET /api/mentions/search?q={query}&type={user|work_item}` for autocomplete
- Parse mention patterns: `@username` for users, `@P-123` for projects, `@WO-123` for work orders, `@T-123` for tasks
- Store parsed mentions when message is created via `addMessage()` helper
- Frontend `MentionInput` component uses Popover for autocomplete dropdown triggered on `@` character

**Mention Notifications**
- Create `MentionNotification` notification class following `RejectionFeedbackNotification` pattern
- Dispatch notification to each mentioned user when message is saved
- Use database and mail channels based on user notification preferences
- Include link to parent work item in notification

**File Attachments on Messages**
- Create `message_attachments` table: `id`, `message_id`, `name`, `file_url`, `file_size`, `mime_type`, `timestamps`
- Create `MessageAttachment` model with `belongsTo` relationship to Message
- Add `attachments` hasMany relationship to Message model
- Extend `CommunicationController::store()` to handle file uploads using Laravel file storage
- Reuse `FileUploader` component pattern from `resources/js/components/work/file-uploader.tsx`
- Display attachment previews for images, download links for other file types

**Emoji Reactions**
- Create `message_reactions` table: `id`, `message_id`, `user_id`, `emoji`, `created_at` with unique constraint on `message_id`, `user_id`, `emoji`
- Create `MessageReaction` model with belongsTo relationships to Message and User
- Add `reactions` hasMany relationship to Message model
- API endpoints: `POST /work/communications/messages/{id}/reactions` and `DELETE /work/communications/messages/{id}/reactions/{emoji}`
- Group reactions by emoji with counts in API response
- Frontend `ReactionPicker` component using emoji popover

**Message Edit and Delete**
- Add `edited_at` nullable timestamp column to messages table
- Add `deleted_at` timestamp column for soft deletes, update Message model to use `SoftDeletes` trait
- API endpoints: `PATCH /work/communications/messages/{id}` for edit, `DELETE /work/communications/messages/{id}` for soft-delete
- Validate 10-minute window from `created_at` for both edit and delete operations
- Include `(edited)` indicator in frontend when `edited_at` is set

**AI Agent Message Visual Distinction**
- Use existing `AuthorType::AiAgent` enum to identify AI-generated messages
- Display distinct badge or icon for AI messages using existing styling patterns
- Include "AI Suggestion" label alongside author name for AI messages
- Style with different background color to distinguish from human messages

**Enhanced Communications Panel Component**
- Create reusable `CommunicationsPanel` component for Sheet-based message display
- Props: `threadableType`, `threadableId`, `open`, `onOpenChange`
- Include message list, type selector, mention-enabled input, file attachment button, reaction display
- Support edit/delete actions via dropdown menu on own messages within time window
- Use TanStack Query or Inertia polling for data fetching

**Consolidated Communications View**
- Create new page at `/communications` route with `CommunicationsIndex` page component
- API endpoint `GET /communications` returns paginated messages across all user-accessible threads
- Filter controls: work item type (Project, Work Order, Task), message type, date range
- Search input for full-text search across message content
- Each message row shows parent work item context with link

**WebSocket-Ready Architecture**
- Create Laravel events: `MessageCreated`, `MessageUpdated`, `MessageDeleted`, `ReactionAdded`, `ReactionRemoved`
- Dispatch events from model observers or service methods
- Structure events to broadcast on team-specific channels when Reverb is configured
- Include polling fallback in frontend with configurable refresh interval

## Existing Code to Leverage

**CommunicationThread and Message Models**
- Located at `app/Models/CommunicationThread.php` and `app/Models/Message.php`
- Already supports polymorphic `threadable` relationship for Projects and WorkOrders
- `addMessage()` helper method handles message creation and thread counter updates
- Extend these models rather than creating new ones

**CommunicationController**
- Located at `app/Http/Controllers/Work/CommunicationController.php`
- Existing `show()` and `store()` methods provide pattern for Task support
- `getModel()` match statement needs Task case added
- JSON response format already includes thread and messages structure

**MessageType and AuthorType Enums**
- Located at `app/Enums/MessageType.php` and `app/Enums/AuthorType.php`
- Extend MessageType with new cases following existing label/color pattern
- AuthorType already has `Human` and `AiAgent` values ready for use

**FileUploader Component**
- Located at `resources/js/components/work/file-uploader.tsx`
- Provides drag-and-drop upload with validation, progress, and notes
- Reuse validation logic (blocked extensions, size limits) for message attachments

**Notification Pattern**
- Located at `app/Notifications/RejectionFeedbackNotification.php`
- Provides pattern for database + mail channels with user preference checking
- Follow same structure for MentionNotification class

## Out of Scope
- Nested replies and threaded conversations within a thread
- External integrations (Slack, email forwarding, webhooks)
- Real-time WebSocket implementation (deferred until Reverb is configured)
- Message search within individual threads (only global consolidated search)
- Read receipts showing who has viewed messages
- Typing indicators showing who is composing
- Message pinning to highlight important messages
- Scheduled messages for future delivery
- Direct messages between users outside work item context
- Message formatting beyond plain text (no rich text editor)
