// =============================================================================
// Data Types
// =============================================================================

export type PartyType = 'client' | 'vendor' | 'partner' | 'internal-department'

export type PartyStatus = 'active' | 'inactive'

export interface Party {
  id: string
  name: string
  type: PartyType
  status: PartyStatus
  primaryContactId: string
  primaryContactName: string
  email: string
  phone: string
  website: string
  address: string
  notes: string
  tags: string[]
  linkedContactIds: string[]
  linkedProjectIds: string[]
  createdAt: string
  lastActivity: string
}

export type EngagementType = 'requester' | 'approver' | 'stakeholder' | 'billing'

export type ContactStatus = 'active' | 'inactive'

export type CommunicationPreference = 'email' | 'phone' | 'slack'

export interface Contact {
  id: string
  name: string
  email: string
  phone: string
  partyId: string
  partyName: string
  title: string
  role: string
  engagementType: EngagementType
  communicationPreference: CommunicationPreference
  timezone: string
  notes: string
  status: ContactStatus
  tags: string[]
  createdAt: string
}

export type TeamMemberStatus = 'active' | 'inactive'

export interface Skill {
  name: string
  proficiency: 1 | 2 | 3 // 1 = Basic, 2 = Intermediate, 3 = Advanced
}

export interface TeamMember {
  id: string
  name: string
  email: string
  role: string
  avatar: string
  status: TeamMemberStatus
  skills: Skill[]
  capacityHoursPerWeek: number
  currentWorkloadHours: number
  timezone: string
  joinedAt: string
  assignedProjectIds: string[]
  tags: string[]
}

export interface Project {
  id: string
  name: string
  partyId: string
}

// =============================================================================
// View Types
// =============================================================================

export type DirectoryTab = 'parties' | 'contacts' | 'team'

export interface DirectoryFilters {
  tab: DirectoryTab
  search?: string
  type?: PartyType | EngagementType
  status?: PartyStatus | ContactStatus | TeamMemberStatus
  tags?: string[]
}

// =============================================================================
// Component Props
// =============================================================================

export interface DirectoryProps {
  /** All parties in the directory */
  parties: Party[]

  /** All contacts in the directory */
  contacts: Contact[]

  /** All team members */
  teamMembers: TeamMember[]

  /** Projects for linking to parties */
  projects: Project[]

  /** Currently active tab */
  currentTab?: DirectoryTab

  /** Called when user switches tabs */
  onTabChange?: (tab: DirectoryTab) => void

  /** Called when user clicks a party to view details */
  onViewParty?: (id: string) => void

  /** Called when user clicks a contact to view details */
  onViewContact?: (id: string) => void

  /** Called when user clicks a team member to view details */
  onViewTeamMember?: (id: string) => void

  /** Called when user wants to create a new party */
  onCreateParty?: () => void

  /** Called when user wants to create a new contact */
  onCreateContact?: () => void

  /** Called when user wants to create a new team member */
  onCreateTeamMember?: () => void

  /** Called when user wants to edit a party */
  onEditParty?: (id: string) => void

  /** Called when user wants to edit a contact */
  onEditContact?: (id: string) => void

  /** Called when user wants to edit a team member */
  onEditTeamMember?: (id: string) => void

  /** Called when user wants to delete a party */
  onDeleteParty?: (id: string) => void

  /** Called when user wants to delete a contact */
  onDeleteContact?: (id: string) => void

  /** Called when user wants to delete a team member */
  onDeleteTeamMember?: (id: string) => void

  /** Called when user performs a search */
  onSearch?: (query: string) => void

  /** Called when user applies filters */
  onFilter?: (filters: DirectoryFilters) => void

  /** Called when user clicks a linked project */
  onViewProject?: (id: string) => void
}
