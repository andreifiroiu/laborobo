// =============================================================================
// Directory Types
// =============================================================================

export type PartyType = 'client' | 'vendor' | 'partner' | 'internal-department'
export type PartyStatus = 'active' | 'inactive'

export interface Party {
    id: string
    name: string
    type: PartyType
    status: PartyStatus
    primaryContactId: string | null
    primaryContactName: string | null
    email: string | null
    phone: string | null
    website: string | null
    address: string | null
    notes: string | null
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
    phone: string | null
    partyId: string
    partyName: string
    title: string | null
    role: string | null
    engagementType: EngagementType
    communicationPreference: CommunicationPreference
    timezone: string | null
    notes: string | null
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
    role: string | null
    avatar: string
    status: TeamMemberStatus
    skills: Skill[]
    capacityHoursPerWeek: number
    currentWorkloadHours: number
    timezone: string | null
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
    search?: string
    type?: PartyType | EngagementType
    status?: PartyStatus | ContactStatus | TeamMemberStatus
    tags?: string[]
}

// =============================================================================
// Page Props
// =============================================================================

export interface DirectoryPageProps {
    parties: Party[]
    contacts: Contact[]
    teamMembers: TeamMember[]
    projects: Project[]
}
