# UserMenu Specification

## Overview
The UserMenu section provides user account management, organization switching, and workspace creation. It includes a dropdown menu component (integrated into the app shell) and full-page views for user settings and organization creation. All user-level settings persist across organizations while maintaining complete data isolation between workspaces.

## User Flows
- **Organization Switching:** User clicks current organization name in dropdown menu, selects different organization from list showing plan, role, and last active time. System switches context, keeping user on same page type (Today→Today, Work→Work, etc.). Visual confirmation displays which org is currently active.
- **Create New Organization:** Click "+ Create Organization" in dropdown, navigate to dedicated wizard page: Name → Plan selection → Team invites (optional) → Confirmation. Land in new organization's onboarding flow. Full context switch to new workspace.
- **Manage User Profile:** Click "My Profile" in dropdown to open settings. Edit display name, email, avatar, phone, timezone, and language via tabbed sidebar navigation. Changes apply across all organizations.
- **Configure Notifications:** Navigate to Notifications tab in user settings. Set global notification preferences (email/push/in-app) per notification type: assigned to me, mentions, approvals, task due, project updates, agent completions, weekly digest. Configure quiet hours for do-not-disturb windows. Preferences apply across all orgs (can be overridden per-org in org settings).
- **Customize Appearance:** Navigate to Appearance tab in user settings. Set theme (Light/Dark/System), density (Comfortable/Compact). Configure sidebar default (Expanded/Collapsed). Choose start page on login (Today/Work/Inbox).
- **Manage Security:** Navigate to Security tab in user settings. Change password, enable/disable 2FA (TOTP, SMS, passkey). View active sessions and sign out other devices. Manage connected apps (OAuth connections like Google, Slack). Create and manage personal API keys.
- **Danger Zone:** Navigate to Danger Zone tab in user settings. Export all personal data (profile, settings, preferences, activity history). Delete account permanently with confirmation (requires typing "DELETE").
- **Access Help & Sign Out:** Quick links in dropdown: Help & Support, Feedback, Terms & Privacy. Sign out from dropdown.

## UI Requirements
- **Dropdown Menu Component:** User info header with avatar, name, email. Organizations section displaying checkmark for current org, org avatar/logo, plan tier badge (Free, Starter, Pro, etc.), user's role (Owner, Admin, Member), last active timestamp. Hover/right-click quick actions: Open in new tab, Go to org settings, Copy org link. "+ Create Organization" action button. Quick navigation links: My Profile, Help & Support, Feedback, Terms & Privacy. Sign Out action.
- **User Settings Pages:** Shared sidebar navigation with tabs: Profile, Notifications, Appearance, Security, Danger Zone. Profile form with display name, email, avatar upload, phone, timezone selector, language selector. Notifications table with toggles for each notification type across channels (email/push/in-app) and quiet hours configuration. Appearance controls with theme selector, density toggle, sidebar preference, start page selector. Security panel with password change form, 2FA toggle, active sessions list, connected apps management, API key management. Danger Zone with data export (downloads personal data as JSON/CSV) and account deletion (requires typing "DELETE" to confirm, shows warning about owned organizations). Clear input validation and error messaging. Save confirmations and success states.
- **Create Organization Page:** Multi-step wizard: Name → Plan → Team (optional) → Confirm. Clear progress indicator showing current step. Plan comparison table or cards. Team invite form with email input and role selection. Confirmation summary before creation.
- **Visual Design:** Mobile-responsive with dropdown accessible via hamburger menu. Current organization always visible in header/sidebar. Clear "You are in: [Org Name]" confirmation when switching. Optional organization color-coding for quick visual identification. Consistent with app shell design patterns.
- **Data Architecture:** Complete multi-org data isolation: projects, work orders, parties, team, subscriptions, AI budgets, integrations, and settings are fully separate per organization. Shared user identity: single login, profile, avatar, and security settings across all organizations. User-level notification and appearance preferences persist globally.

## Configuration
- shell: true
