# Playbooks Specification

## Overview
The knowledge base that makes AI agents useful. Contains reusable SOPs (step-by-step procedures), Checklists (task lists), Templates (pre-built structures), and Acceptance Criteria (definitions of "done"). Enables consistency across projects and empowers AI agents to follow documented procedures. "This is how we do things."

## User Flows
- **Browse playbooks by type** - User switches between tabs (SOPs, Checklists, Templates, Acceptance Criteria) to view organized lists of each type
- **Search and filter** - User searches across all playbook names, descriptions, content, and tags; filters by tags/categories; sorts by usage/popularity or recently updated
- **View playbook details** - User clicks a playbook to open full page view showing complete content, structure-specific fields, usage statistics, and related work orders
- **Create new playbook** - User selects playbook type, provides AI prompt to generate initial structure, then refines using structured form fields specific to the type (steps for SOPs, items for checklists, etc.)
- **Edit existing playbook** - User opens playbook in edit mode and modifies using structured form fields, with changes tracked in version history
- **Apply to work orders** - User manually searches and attaches playbooks when creating/editing work orders, OR AI agent automatically suggests and attaches relevant playbooks based on work type and context
- **Track usage** - User views which playbooks are most-used, which work orders use each playbook, and effectiveness metrics

## UI Requirements
- Tab navigation for 4 types (SOPs, Checklists, Templates, Acceptance Criteria) with count badges
- List view with cards showing: name, type badge, description preview, usage count, tags, last updated date
- Full page detail view with type-specific structure:
  - **SOPs**: Trigger conditions, ordered steps with evidence requirements, roles involved, estimated time, definition of done, tags
  - **Checklists**: Checkbox items with optional assignees and evidence per item
  - **Templates**: Structure preview (project milestones, work order pre-fill, document template)
  - **Acceptance Criteria**: Reusable criteria blocks with validation rules
- Create/edit interface with AI prompt input and structured form fields based on playbook type
- Full-text search bar across all content
- Tag filter pills for categorization
- Sort options: by usage/popularity, recently updated
- Usage statistics panel showing times applied, related work orders, success metrics
- Version history tracking
- Empty states per tab with "Create your first [type]" prompts
- AI suggestion indicators when playbooks are auto-attached to work items

## Configuration
- shell: true
