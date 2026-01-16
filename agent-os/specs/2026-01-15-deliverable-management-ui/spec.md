# Specification: Deliverable Management UI

## Goal
Build a comprehensive deliverable management system with version history, 50MB file uploads, file preview capabilities, and status change notifications to enable team members to effectively manage and track work order deliverables.

## User Stories
- As a team member, I want to upload files to a deliverable with version tracking so that I can maintain a history of changes and restore previous versions if needed
- As a team member, I want to preview uploaded files (images, PDFs, videos) directly in the UI so that I can quickly review deliverable content without downloading

## Specific Requirements

**DeliverableVersion Model and Migration**
- Create `deliverable_versions` table with: id, deliverable_id, version_number, file_url, file_name, file_size, mime_type, notes, uploaded_by_id, timestamps, soft deletes
- Add foreign key to deliverables table with cascade delete
- Index deliverable_id for efficient version listing queries
- Version number auto-increments per deliverable (1, 2, 3...)
- Store original filename separately from storage path

**50MB File Upload with Blocked Extensions**
- Increase max file size from 10MB to 50MB in validation rules
- Update php.ini upload_max_filesize and post_max_size to 64MB
- Block dangerous file extensions: .exe, .bat, .cmd, .com, .msi, .dll, .scr, .vbs, .vbe, .js, .jse, .ws, .wsf, .ps1, .ps1xml, .psc1, .psd1, .psm1, .sh, .bash, .zsh, .csh, .ksh, .app, .dmg, .deb, .rpm, .jar
- Create FileUploadService with validation logic that can be reused
- Return clear validation error messages for blocked files

**Version History API Endpoints**
- GET `/work/deliverables/{deliverable}/versions` - List all versions with pagination
- POST `/work/deliverables/{deliverable}/versions` - Upload new version (creates version record + stores file)
- GET `/work/deliverables/{deliverable}/versions/{version}` - Get single version details
- POST `/work/deliverables/{deliverable}/versions/{version}/restore` - Restore a previous version as the current version
- DELETE `/work/deliverables/{deliverable}/versions/{version}` - Soft delete a version

**Status Change Notifications**
- Create DeliverableStatusChangedNotification class extending Laravel's Notification
- Notify work order owner and assignee on status transitions
- Include deliverable title, old status, new status, changed by user, and link to deliverable
- Use database notification channel (enable email channel later if requested)
- Dispatch notification in DeliverableController update method when status changes

**File Preview System**
- Support inline preview for: PNG, JPG, JPEG, GIF, SVG, WebP (images), PDF (embedded viewer), MP4, WebM, MOV (video player)
- Detect file type from mime_type stored in version record
- For unsupported types, show file icon with download button
- Render previews in a modal or expandable panel on deliverable detail page

**Version History UI Panel**
- Add collapsible "Version History" section to deliverable detail page
- Display list of versions: version number, filename, file size, upload date, uploaded by, notes
- Each version row has: preview button, download button, restore button, delete button
- "Upload New Version" button opens file upload modal with optional notes field
- Show upload progress indicator during file upload

**Enhanced File Upload Component**
- Build FileUploader component with drag-and-drop zone
- Show upload progress bar using Progress Radix primitive
- Display file validation errors inline
- Accept optional notes textarea for version description
- On successful upload, refresh version list and close modal

**Deliverable Detail Page Enhancements**
- Add current version file preview at top of page (replaces simple "View File" button)
- Show current version number prominently in stats section
- Add version count to stats (e.g., "5 versions")
- Integrate version history panel below description section
- Keep existing acceptance criteria section unchanged

**File Storage Structure**
- Store files in `storage/app/public/deliverables/{deliverable_id}/v{version_number}_{original_filename}`
- Generate unique storage paths to prevent filename collisions
- Store public URL in version record for direct access
- Clean up orphaned files when version is permanently deleted

## Existing Code to Leverage

**Deliverable Model (`app/Models/Deliverable.php`)**
- Extend with `versions()` hasMany relationship to DeliverableVersion
- Existing `documents()` morphMany relationship can remain for backward compatibility
- Reuse scopeForTeam pattern for version queries
- Existing status enum (DeliverableStatus) and type enum (DeliverableType) remain unchanged

**DeliverableController (`app/Http/Controllers/Work/DeliverableController.php`)**
- Existing uploadFile method provides pattern for file upload handling
- formatFileSize helper method should be moved to shared service
- Update update method to dispatch notification on status change
- Add version-related methods following existing authorization patterns

**Deliverable Detail Page (`resources/js/pages/work/deliverables/[id].tsx`)**
- Existing file upload logic (handleFileSelect, handleFileChange) can be adapted for versioned uploads
- Existing file list rendering pattern can be adapted for version list
- getFileIcon helper provides file type icon logic to reuse
- Keep existing edit dialog, delete dialog, and acceptance criteria patterns

**UI Components (`resources/js/components/ui/`)**
- Progress component exists for upload progress bar
- Dialog, Sheet, and DropdownMenu components for modals and menus
- Badge component for version badges
- Button, Input, Textarea components for forms

**StatusBadge Component (`resources/js/components/work/status-badge.tsx`)**
- Already supports deliverable status styling
- Reuse for version status if needed in future

## Out of Scope
- S3 or cloud storage integration (continue using local filesystem)
- Task-level deliverable attachments (work order level only)
- Role-based permissions for status changes (any team member can change)
- Version diff or comparison functionality between versions
- Real-time collaboration or co-editing on files
- External client portal access to deliverables
- Advanced document management (folders, tagging, search)
- Drag-and-drop reordering of versions
- Batch file uploads (single file per version)
- Automatic version number suggestion or semantic versioning
