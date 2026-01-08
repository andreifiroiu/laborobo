// =============================================================================
// Data Types
// =============================================================================

export interface User {
  id: string
  displayName: string
  email: string
  avatarUrl?: string
  phone?: string
  timezone: string
  language: string
}

export interface Organization {
  id: string
  name: string
  slug: string
  avatarUrl?: string
  plan: 'Free' | 'Starter' | 'Pro'
  role: 'Owner' | 'Admin' | 'Member'
  memberCount: number
  lastActive: string
  isCurrent: boolean
}

export interface NotificationChannel {
  email: boolean
  push: boolean
  inApp: boolean
}

export interface QuietHours {
  enabled: boolean
  startTime: string
  endTime: string
  timezone: string
}

export interface NotificationPreferences {
  assignedToMe: NotificationChannel
  mentioned: NotificationChannel
  approvalRequested: NotificationChannel
  taskDueSoon: NotificationChannel
  projectUpdates: NotificationChannel
  agentCompletedWork: NotificationChannel
  weeklyDigest: NotificationChannel
  quietHours: QuietHours
}

export interface AppearanceSettings {
  theme: 'light' | 'dark' | 'system'
  density: 'comfortable' | 'compact'
  sidebarDefault: 'expanded' | 'collapsed'
  startPage: 'today' | 'work' | 'inbox'
}

export interface Session {
  id: string
  device: string
  browser: string
  location: string
  ipAddress: string
  lastActive: string
  isCurrent: boolean
}

export interface ConnectedApp {
  id: string
  name: string
  provider: string
  connectedAt: string
  permissions: string[]
  avatarUrl?: string
}

export interface ApiKey {
  id: string
  name: string
  keyPreview: string
  createdAt: string
  lastUsed: string | null
  expiresAt: string | null
}

export interface PlanLimits {
  members: number
  projects: number
  aiTasksPerMonth: number
  storage: string
}

export interface Plan {
  id: string
  name: string
  price: number
  billingPeriod: 'month' | 'year'
  features: string[]
  limits: PlanLimits
  isPopular?: boolean
}

// =============================================================================
// Component Props
// =============================================================================

export interface UserMenuDropdownProps {
  /** The current user's profile information */
  user: User
  /** List of organizations the user belongs to */
  organizations: Organization[]
  /** The currently active organization */
  currentOrganization: Organization
  /** Called when user switches to a different organization */
  onSwitchOrganization?: (organizationId: string) => void
  /** Called when user clicks "My Profile" to open settings */
  onOpenProfile?: () => void
  /** Called when user clicks "+ Create Organization" */
  onCreateOrganization?: () => void
  /** Called when user clicks "Help & Support" */
  onOpenHelp?: () => void
  /** Called when user clicks "Feedback" */
  onOpenFeedback?: () => void
  /** Called when user clicks "Terms & Privacy" */
  onOpenTerms?: () => void
  /** Called when user clicks "Sign Out" */
  onSignOut?: () => void
  /** Called when user clicks "Open in new tab" for an organization */
  onOpenOrgInNewTab?: (organizationId: string) => void
  /** Called when user clicks "Go to org settings" for an organization */
  onOpenOrgSettings?: (organizationId: string) => void
  /** Called when user clicks "Copy org link" for an organization */
  onCopyOrgLink?: (organizationId: string) => void
}

export interface UserSettingsProps {
  /** The current user's profile information */
  user: User
  /** User's notification preferences */
  notificationPreferences: NotificationPreferences
  /** User's appearance settings */
  appearanceSettings: AppearanceSettings
  /** List of active sessions */
  sessions: Session[]
  /** List of connected third-party apps */
  connectedApps: ConnectedApp[]
  /** List of API keys */
  apiKeys: ApiKey[]
  /** Called when user updates their profile */
  onUpdateProfile?: (updates: Partial<User>) => void
  /** Called when user updates notification preferences */
  onUpdateNotifications?: (preferences: Partial<NotificationPreferences>) => void
  /** Called when user updates appearance settings */
  onUpdateAppearance?: (settings: Partial<AppearanceSettings>) => void
  /** Called when user wants to change their password */
  onChangePassword?: () => void
  /** Called when user toggles 2FA */
  onToggle2FA?: (enabled: boolean) => void
  /** Called when user signs out a specific session */
  onSignOutSession?: (sessionId: string) => void
  /** Called when user disconnects a third-party app */
  onDisconnectApp?: (appId: string) => void
  /** Called when user revokes an API key */
  onRevokeApiKey?: (keyId: string) => void
  /** Called when user creates a new API key */
  onCreateApiKey?: (name: string) => void
  /** Called when user wants to export their data */
  onExportData?: () => void
  /** Called when user confirms account deletion */
  onDeleteAccount?: () => void
}

export interface CreateOrganizationProps {
  /** Available subscription plans */
  plans: Plan[]
  /** Called when user completes the organization creation */
  onCreate?: (data: CreateOrganizationData) => void
  /** Called when user cancels the creation process */
  onCancel?: () => void
}

export interface CreateOrganizationData {
  name: string
  planId: string
  teamInvites?: TeamInvite[]
}

export interface TeamInvite {
  email: string
  role: 'Admin' | 'Member'
}
