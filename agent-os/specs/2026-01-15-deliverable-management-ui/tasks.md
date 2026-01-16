# Task Breakdown: Deliverable Management UI

## Overview
Total Tasks: 6 Task Groups with 43 sub-tasks

This feature adds comprehensive deliverable management with version history, 50MB file uploads, file preview capabilities, and status change notifications.

## Task List

### Phase 1: Database & Models

#### Task Group 1: DeliverableVersion Model and Migration
**Dependencies:** None

- [x] 1.0 Complete database layer for version history
  - [x] 1.1 Write 4-6 focused tests for DeliverableVersion model
    - Test version number auto-increment per deliverable
    - Test DeliverableVersion belongs to Deliverable relationship
    - Test DeliverableVersion belongs to User (uploaded_by) relationship
    - Test soft delete functionality
    - Test version ordering (latest first)
  - [x] 1.2 Create DeliverableVersion model
    - **File:** `app/Models/DeliverableVersion.php`
    - Fields: id, deliverable_id, version_number, file_url, file_name, file_size, mime_type, notes, uploaded_by_id, timestamps, soft_deletes
    - Cast file_size as integer
    - Define `deliverable()` BelongsTo relationship
    - Define `uploadedBy()` BelongsTo relationship to User
    - Add `scopeForDeliverable($query, int $deliverableId)` scope
    - Add `scopeLatestFirst($query)` scope for ordering by version_number desc
  - [x] 1.3 Create migration for deliverable_versions table
    - **File:** `database/migrations/xxxx_xx_xx_create_deliverable_versions_table.php`
    - Add foreign key `deliverable_id` with cascade delete
    - Add foreign key `uploaded_by_id` referencing users table (nullable, set null on delete)
    - Add index on `deliverable_id` for efficient version listing
    - Add composite index on `(deliverable_id, version_number)` for unique version lookup
    - Implement reversible down() method
  - [x] 1.4 Update Deliverable model with versions relationship
    - **File:** `app/Models/Deliverable.php`
    - Add `versions()` HasMany relationship to DeliverableVersion
    - Add `latestVersion()` HasOne relationship (version_number desc, limit 1)
    - Add `versionCount()` accessor that returns count of versions
  - [x] 1.5 Create DeliverableVersionFactory for testing
    - **File:** `database/factories/DeliverableVersionFactory.php`
    - Define sensible defaults for all fields
    - Add states for different mime types (image, pdf, video)
  - [x] 1.6 Ensure database layer tests pass
    - Run ONLY the 4-6 tests written in 1.1
    - Verify migration runs successfully with `php artisan migrate`
    - Verify migration rollback works with `php artisan migrate:rollback`

**Acceptance Criteria:**
- DeliverableVersion model created with all specified fields
- Migration creates table with proper indexes and foreign keys
- Deliverable model has versions() relationship working
- All 4-6 tests from 1.1 pass
- Migration is reversible

---

### Phase 2: Backend API

#### Task Group 2: FileUploadService and Validation
**Dependencies:** Task Group 1

- [x] 2.0 Complete file upload service with 50MB limit and blocked extensions
  - [x] 2.1 Write 4-6 focused tests for FileUploadService
    - Test file size validation (accept up to 50MB, reject larger)
    - Test blocked extension validation (reject .exe, .bat, .sh, etc.)
    - Test successful file storage returns correct path
    - Test MIME type detection works correctly
    - Test formatFileSize helper method
  - [x] 2.2 Create FileUploadService class
    - **File:** `app/Services/FileUploadService.php`
    - Define BLOCKED_EXTENSIONS constant with all dangerous extensions:
      `.exe, .bat, .cmd, .com, .msi, .dll, .scr, .vbs, .vbe, .js, .jse, .ws, .wsf, .ps1, .ps1xml, .psc1, .psd1, .psm1, .sh, .bash, .zsh, .csh, .ksh, .app, .dmg, .deb, .rpm, .jar`
    - Define MAX_FILE_SIZE constant (50 * 1024 * 1024 bytes = 52428800)
    - Implement `validateFile(UploadedFile $file): array` - returns errors array or empty
    - Implement `storeDeliverableVersion(UploadedFile $file, Deliverable $deliverable, int $versionNumber): string` - returns stored file URL
    - Implement `deleteFile(string $fileUrl): bool`
    - Implement `formatFileSize(int $bytes): string` (move from DeliverableController)
    - Storage path format: `deliverables/{deliverable_id}/v{version_number}_{original_filename}`
  - [x] 2.3 Create FileUploadRequest form request
    - **File:** `app/Http/Requests/FileUploadRequest.php`
    - Max size rule: `max:51200` (50MB in KB)
    - Use FileUploadService for extension validation via custom rule
    - Return user-friendly error messages for blocked files
  - [x] 2.4 Update php.ini settings documentation
    - **File:** Create `docs/server-configuration.md` or add to existing docs
    - Document required php.ini settings:
      - `upload_max_filesize = 64M`
      - `post_max_size = 64M`
    - Note: Actual php.ini changes are server-level, not codebase
  - [x] 2.5 Ensure file upload service tests pass
    - Run ONLY the 4-6 tests written in 2.1
    - Verify all validation rules work correctly

**Acceptance Criteria:**
- FileUploadService handles 50MB file uploads
- Blocked extensions are properly rejected with clear error messages
- File storage path follows specified format
- All 4-6 tests from 2.1 pass

---

#### Task Group 3: Version History API Endpoints
**Dependencies:** Task Group 2

- [x] 3.0 Complete version history API endpoints
  - [x] 3.1 Write 5-7 focused tests for version API endpoints
    - Test GET versions list returns paginated results
    - Test POST upload creates new version with incremented version_number
    - Test GET single version returns correct data
    - Test POST restore creates new version copying previous version's file
    - Test DELETE soft deletes version
    - Test authorization (user must have access to deliverable)
  - [x] 3.2 Create DeliverableVersionController
    - **File:** `app/Http/Controllers/Work/DeliverableVersionController.php`
    - Implement `index(Deliverable $deliverable)` - GET paginated versions (15 per page)
    - Implement `store(FileUploadRequest $request, Deliverable $deliverable)` - POST upload new version
    - Implement `show(Deliverable $deliverable, DeliverableVersion $version)` - GET single version
    - Implement `restore(Deliverable $deliverable, DeliverableVersion $version)` - POST restore version
    - Implement `destroy(Deliverable $deliverable, DeliverableVersion $version)` - DELETE soft delete
    - Use existing authorization patterns from DeliverableController
    - Return JSON responses with appropriate status codes
  - [x] 3.3 Add API routes for version endpoints
    - **File:** `routes/web.php`
    - Route group under `/work/deliverables/{deliverable}/versions`
    - GET `/` -> index
    - POST `/` -> store
    - GET `/{version}` -> show
    - POST `/{version}/restore` -> restore
    - DELETE `/{version}` -> destroy
    - Apply auth and team middleware
  - [x] 3.4 Create DeliverableVersionResource for API responses
    - **File:** `app/Http/Resources/DeliverableVersionResource.php`
    - Transform version data for consistent JSON structure
    - Include: id, version_number, file_url, file_name, file_size (formatted), mime_type, notes, uploaded_by (user name), created_at, updated_at
  - [x] 3.5 Update DeliverableController show method
    - **File:** `app/Http/Controllers/Work/DeliverableController.php`
    - Load versions relationship with latest first ordering
    - Include versions data in Inertia response
    - Include version_count in deliverable data
    - Include latest_version details for current file preview
  - [x] 3.6 Ensure version API tests pass
    - Run ONLY the 5-7 tests written in 3.1
    - Verify all CRUD operations work
    - Verify proper authorization enforcement

**Acceptance Criteria:**
- All 5 version endpoints functional
- Proper pagination on version list
- Version number auto-increments correctly
- Restore creates new version (not modification)
- All 5-7 tests from 3.1 pass

---

#### Task Group 4: Status Change Notifications
**Dependencies:** Task Group 1

- [x] 4.0 Complete status change notification system
  - [x] 4.1 Write 3-4 focused tests for notifications
    - Test notification is dispatched when status changes
    - Test notification is NOT dispatched when other fields change
    - Test notification includes correct data (old status, new status, deliverable title, changed by user)
    - Test notification is sent to work order owner and assignee
  - [x] 4.2 Create DeliverableStatusChangedNotification
    - **File:** `app/Notifications/DeliverableStatusChangedNotification.php`
    - Extend Laravel's Notification class
    - Implement `via()` returning `['database']` (email channel deferred)
    - Implement `toArray()` with:
      - deliverable_id, deliverable_title
      - old_status, new_status
      - changed_by_user_id, changed_by_user_name
      - work_order_id, work_order_title
      - link to deliverable detail page
    - Add constructor accepting Deliverable, oldStatus, newStatus, changedBy
  - [x] 4.3 Update DeliverableController update method to dispatch notification
    - **File:** `app/Http/Controllers/Work/DeliverableController.php`
    - Detect when status field changes (compare old vs new)
    - After successful update, dispatch DeliverableStatusChangedNotification
    - Notify: work order owner (workOrder->user_id), work order assignee (if exists)
    - Exclude the user who made the change from notification recipients
  - [x] 4.4 Ensure notification tests pass
    - Run ONLY the 3-4 tests written in 4.1
    - Verify notifications appear in database

**Acceptance Criteria:**
- Notification dispatched on status change only
- Correct recipients notified
- Notification data is complete and accurate
- All 3-4 tests from 4.1 pass

---

### Phase 3: Frontend Components

#### Task Group 5: File Preview and Upload Components
**Dependencies:** Task Groups 2, 3

- [x] 5.0 Complete frontend components for file management
  - [x] 5.1 Write 4-6 focused tests for frontend components (using Vitest or Jest)
    - Test FilePreview renders image preview for image mime types
    - Test FilePreview renders PDF embed for pdf mime type
    - Test FilePreview renders video player for video mime types
    - Test FileUploader shows drag-and-drop zone
    - Test FileUploader shows upload progress bar during upload
    - Test VersionHistoryPanel displays version list correctly
  - [x] 5.2 Create FilePreview component
    - **File:** `resources/js/components/work/file-preview.tsx`
    - Props: `{ fileUrl: string; mimeType: string; fileName: string; className?: string }`
    - Image preview for: image/png, image/jpeg, image/jpg, image/gif, image/svg+xml, image/webp
    - PDF viewer using `<embed>` or `<iframe>` for application/pdf
    - Video player using `<video>` for video/mp4, video/webm, video/quicktime (MOV)
    - Fallback: file icon with download button for unsupported types
    - Support opening in modal via optional `inModal` prop
  - [x] 5.3 Create FileUploader component with drag-and-drop
    - **File:** `resources/js/components/work/file-uploader.tsx`
    - Props: `{ onUpload: (file: File, notes?: string) => void; isUploading: boolean; progress?: number; maxSizeMB?: number; error?: string }`
    - Drag-and-drop zone using HTML5 drag events
    - Click to browse fallback
    - Optional notes textarea for version description
    - Progress bar using existing Progress component from `@/components/ui/progress`
    - Display validation errors inline
    - Show accepted file types hint (exclude blocked extensions)
    - File size limit hint (50MB)
  - [x] 5.4 Create VersionHistoryPanel component
    - **File:** `resources/js/components/work/version-history-panel.tsx`
    - Props: `{ deliverableId: string; versions: DeliverableVersion[]; currentVersionNumber: number; onVersionRestore: (versionId: string) => void; onVersionDelete: (versionId: string) => void }`
    - Collapsible panel using existing Collapsible component or custom implementation
    - List versions with: version number, filename, file size, upload date, uploaded by, notes
    - Each version row has:
      - Preview button (opens FilePreview in modal)
      - Download button (direct link to file_url)
      - Restore button (calls onVersionRestore)
      - Delete button (calls onVersionDelete with confirmation)
    - "Upload New Version" button at top
    - Highlight current/latest version
  - [x] 5.5 Create VersionUploadDialog component
    - **File:** `resources/js/components/work/version-upload-dialog.tsx`
    - Props: `{ open: boolean; onOpenChange: (open: boolean) => void; deliverableId: string; onSuccess: () => void }`
    - Dialog using existing Dialog component from `@/components/ui/dialog`
    - Contains FileUploader component
    - Notes textarea for version description
    - Submit handler posts to version upload endpoint
    - Close dialog and call onSuccess on successful upload
  - [x] 5.6 Create file preview modal component
    - **File:** `resources/js/components/work/file-preview-modal.tsx`
    - Props: `{ open: boolean; onOpenChange: (open: boolean) => void; fileUrl: string; mimeType: string; fileName: string }`
    - Full-screen or large modal with FilePreview component
    - Download button in modal header
    - Close button
  - [x] 5.7 Export new components from work index
    - **File:** `resources/js/components/work/index.ts`
    - Export FilePreview, FileUploader, VersionHistoryPanel, VersionUploadDialog, FilePreviewModal
  - [x] 5.8 Ensure frontend component tests pass
    - Run ONLY the 4-6 tests written in 5.1
    - Verify components render correctly

**Acceptance Criteria:**
- FilePreview renders correct preview type based on mime type
- FileUploader supports drag-and-drop with progress indicator
- VersionHistoryPanel displays version list with all actions
- All 4-6 tests from 5.1 pass

---

### Phase 4: Page Integration

#### Task Group 6: Deliverable Detail Page Enhancements
**Dependencies:** Task Group 5

- [x] 6.0 Complete deliverable detail page integration
  - [x] 6.1 Update TypeScript types for version data
    - **File:** `resources/js/types/work.d.ts`
    - Add DeliverableVersion interface:
      ```typescript
      interface DeliverableVersion {
        id: string;
        versionNumber: number;
        fileUrl: string;
        fileName: string;
        fileSize: string;
        mimeType: string;
        notes: string | null;
        uploadedBy: { id: string; name: string } | null;
        createdAt: string;
      }
      ```
    - Update Deliverable interface to include:
      - `versionCount: number`
      - `latestVersion: DeliverableVersion | null`
  - [x] 6.2 Update DeliverableDetailProps interface
    - **File:** `resources/js/pages/work/deliverables/[id].tsx`
    - Add `versions: DeliverableVersion[]` to props
    - Add `latestVersion: DeliverableVersion | null` to deliverable prop
  - [x] 6.3 Add current version file preview section
    - **File:** `resources/js/pages/work/deliverables/[id].tsx`
    - Add FilePreview component at top of main content area
    - Display current version's file preview (if latestVersion exists)
    - Replace simple "View File" button with inline preview
    - Add "View Full Size" button to open preview modal
  - [x] 6.4 Update stats section with version information
    - **File:** `resources/js/pages/work/deliverables/[id].tsx`
    - Update Version stat card to show current version number from latestVersion
    - Add new stat card showing version count (e.g., "5 versions")
    - Use History icon for version count stat
  - [x] 6.5 Integrate VersionHistoryPanel into page
    - **File:** `resources/js/pages/work/deliverables/[id].tsx`
    - Add VersionHistoryPanel component below description section
    - Wire up version restore handler (POST to restore endpoint, then router.reload)
    - Wire up version delete handler (DELETE to version endpoint, then router.reload)
    - Add state for upload dialog visibility
    - Add VersionUploadDialog component
  - [x] 6.6 Update file upload to create versions
    - **File:** `resources/js/pages/work/deliverables/[id].tsx`
    - Replace existing file upload logic with version-based upload
    - POST to `/work/deliverables/{id}/versions` instead of `/work/deliverables/{id}/files`
    - Show upload progress using FileUploader component
    - Refresh page on successful upload to show new version
  - [x] 6.7 Add file preview modal state and rendering
    - **File:** `resources/js/pages/work/deliverables/[id].tsx`
    - Add state for preview modal: `previewModalOpen`, `previewFile`
    - Render FilePreviewModal component
    - Wire up preview buttons in VersionHistoryPanel to open modal
  - [x] 6.8 Keep existing acceptance criteria section unchanged
    - **File:** `resources/js/pages/work/deliverables/[id].tsx`
    - Verify acceptance criteria section still works correctly
    - No modifications needed to this section
  - [x] 6.9 Test full page integration manually
    - Verify file preview renders for images, PDFs, and videos
    - Verify version upload creates new version with incremented number
    - Verify version history panel shows all versions
    - Verify restore creates new version
    - Verify delete soft deletes version
    - Verify status change notifications are created

**Acceptance Criteria:**
- Deliverable detail page shows current version preview
- Version count displayed in stats section
- Version history panel is collapsible and functional
- All version actions (preview, download, restore, delete) work
- Existing acceptance criteria functionality preserved

---

### Phase 5: Testing

#### Task Group 7: Test Review and Gap Analysis
**Dependencies:** Task Groups 1-6

- [x] 7.0 Review existing tests and fill critical gaps only
  - [x] 7.1 Review tests from Task Groups 1-6
    - Review 6 tests from Task 1.1 (DeliverableVersion model)
    - Review 6 tests from Task 2.1 (FileUploadService)
    - Review 6 tests from Task 3.1 (Version API endpoints)
    - Review 4 tests from Task 4.1 (Notifications)
    - Review 8 tests from Task 5.1 (Frontend components)
    - Total existing tests: 30 tests
  - [x] 7.2 Analyze test coverage gaps for this feature only
    - Identified critical user workflows that lack test coverage
    - Focused ONLY on gaps related to deliverable management feature
    - Prioritized end-to-end workflows over unit test gaps
    - Checked for missing integration tests between components
  - [x] 7.3 Write up to 8 additional strategic tests maximum
    - Added 8 new tests in `tests/Feature/Work/DeliverableManagementIntegrationTest.php`
    - Focus areas covered:
      - First version upload starts at version 1
      - Blocked extension rejection at API level
      - Deliverable detail page includes version data
      - Complete version restore workflow
      - Sequential status change notifications
      - End-to-end upload flow with file storage verification
      - Version deletion prevents access but preserves soft deleted record
      - Latest version correctly identified after multiple uploads
  - [x] 7.4 Run feature-specific tests only
    - Ran ONLY tests related to this feature (from groups 1-6 plus 7.3)
    - Total tests: 38 (30 backend + 8 frontend)
    - All critical workflows pass
    - Did NOT run the entire application test suite

**Acceptance Criteria:**
- All feature-specific tests pass (38 tests total)
- Critical user workflows for deliverable management are covered
- 8 additional tests added (maximum allowed)
- Testing focused exclusively on this feature's requirements

---

## Execution Order

Recommended implementation sequence:

1. **Phase 1: Database & Models** (Task Group 1)
   - Foundation for all other work
   - No dependencies

2. **Phase 2: Backend API** (Task Groups 2, 3, 4)
   - Task Group 2 (FileUploadService) - depends on Task Group 1
   - Task Group 3 (Version API) - depends on Task Groups 1, 2
   - Task Group 4 (Notifications) - depends on Task Group 1 only, can run parallel with 2-3

3. **Phase 3: Frontend Components** (Task Group 5)
   - Depends on API endpoints being available
   - Can be developed in parallel with API using mocked data

4. **Phase 4: Page Integration** (Task Group 6)
   - Depends on all previous phases
   - Final integration work

5. **Phase 5: Testing** (Task Group 7)
   - Review and gap analysis after all implementation complete

---

## File Reference Summary

### Backend Files to Create
- `app/Models/DeliverableVersion.php`
- `app/Services/FileUploadService.php`
- `app/Http/Controllers/Work/DeliverableVersionController.php`
- `app/Http/Requests/FileUploadRequest.php`
- `app/Http/Resources/DeliverableVersionResource.php`
- `app/Notifications/DeliverableStatusChangedNotification.php`
- `database/migrations/xxxx_xx_xx_create_deliverable_versions_table.php`
- `database/factories/DeliverableVersionFactory.php`

### Backend Files to Modify
- `app/Models/Deliverable.php` - Add versions relationship
- `app/Http/Controllers/Work/DeliverableController.php` - Add notification dispatch, update show method
- `routes/web.php` - Add version routes

### Frontend Files to Create
- `resources/js/components/work/file-preview.tsx`
- `resources/js/components/work/file-uploader.tsx`
- `resources/js/components/work/version-history-panel.tsx`
- `resources/js/components/work/version-upload-dialog.tsx`
- `resources/js/components/work/file-preview-modal.tsx`

### Frontend Files to Modify
- `resources/js/pages/work/deliverables/[id].tsx` - Main page integration
- `resources/js/types/work.d.ts` - Add DeliverableVersion type
- `resources/js/components/work/index.ts` - Export new components

### Test Files to Create
- `tests/Feature/Work/DeliverableVersionTest.php`
- `tests/Feature/Services/FileUploadServiceTest.php`
- `tests/Feature/Work/DeliverableVersionApiTest.php`
- `tests/Feature/Work/DeliverableStatusNotificationTest.php`
- `tests/Feature/Work/DeliverableManagementIntegrationTest.php`
- `resources/js/components/work/__tests__/file-preview.test.tsx`
- `resources/js/components/work/__tests__/file-uploader.test.tsx`
- `resources/js/components/work/__tests__/version-history-panel.test.tsx`
