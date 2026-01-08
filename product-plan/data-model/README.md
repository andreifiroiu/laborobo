# Data Model

## Entities

### Party
Someone inside or outside the organization (client, vendor, department, team member, or "nobody"). Represents any entity that work is done for or with.

### Project
A collection of work being done for a party, containing multiple work orders. Represents the top-level container for related work.

### Work Order
A specific unit of work within a project that needs to be completed. Represents a trackable, structured piece of work with clear scope and deliverables.

### Task
Individual action items that make up a work order. Represents the smallest unit of work that can be assigned and tracked.

### Deliverable
Output or artifact that gets delivered to a party (documents, designs, reports, etc.). Represents the tangible outcome of completed work.

### AI Agent
A virtual team member with specific skills (dispatcher, PM copilot, copywriter, analyst, QA). Acts as a fractional employee with defined capabilities and permission boundaries.

### SOP (Standard Operating Procedure)
A documented process template with checklists and acceptance criteria. Ensures consistency and guides both human and AI behavior.

### Approval
A request for human review and sign-off on agent-generated work or deliverables. Enforces the human-in-the-loop requirement before work goes out.

### User
Human team member who works in the system and approves agent work. Has permissions to review, approve, and deliver work.

### TeamMember
A team member (human or AI agent) that can be assigned to parties, projects, work orders, or tasks. Represents workforce capacity and assignment.

### Communication Thread
Conversation history tied to a specific work item (project, work order, or task). Maintains context and prevents lost information.

### Message
Individual message within a communication thread. Represents a single communication entry from a user or agent.

### Document
Files, links, and evidence attached to work items. Represents supporting materials and artifacts throughout the work lifecycle.

## Relationships

- Party has many Projects
- Project belongs to a Party and has many Work Orders
- Work Order belongs to a Project and has many Tasks and Deliverables
- Task belongs to a Work Order
- Deliverable belongs to a Work Order
- TeamMember can be assigned to Party, Project, Work Order, or Task
- Communication Thread belongs to a work item (Project, Work Order, or Task)
- Message belongs to a Communication Thread
- Document can be attached to Projects, Work Orders, Tasks, or Deliverables
- SOP can be applied to Work Orders or Tasks as templates
- Approval is created for Deliverables or agent-generated work
- AI Agent can create draft work that requires Approval from a User
