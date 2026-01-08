# Milestone 1: Foundation & Shell

This milestone establishes the core foundation of the Laborobo application, including project setup, design system, data model, routing, and the application shell with navigation.

## Overview

**Goal**: Set up the project infrastructure and implement the persistent shell that wraps all sections.

**Time Estimate**: 1 week

**What You'll Build**:
- Project setup with Tailwind CSS v4
- Design tokens (colors, typography, spacing)
- TypeScript data model types
- Routing structure for all sections
- Application shell with sidebar navigation
- User menu with organization switching
- Responsive layout (desktop/tablet/mobile)

## Prerequisites
- **Framework**: Vite
- **State Management**: React Query
- **Authentication**: Laravel Sanctum and Laravel Socialite

## Step 1: Project Setup

### Install Dependencies

```bash
# Core dependencies
npm install lucide-react

# State management (choose one)
npm install @tanstack/react-query  # Recommended

# Forms (optional)
npm install react-hook-form zod @hookform/resolvers

# Date handling (optional)
npm install date-fns
```

## Step 2: Configure Tailwind CSS v4

### Create Tailwind Config

**tailwind.config.ts** (if needed for v4):
```typescript
import type { Config } from 'tailwindcss'

export default {
  content: [
    './app/**/*.{js,ts,jsx,tsx}', // Next.js App Router
    './components/**/*.{js,ts,jsx,tsx}',
    './src/**/*.{js,ts,jsx,tsx}', // Vite
  ],
  // v4 uses @theme in CSS for customization
} satisfies Config
```

### Import Design Tokens

Copy the design tokens from `design-system/tokens.css` into your project:

**resources/css/app.css** (Vite):
```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&display=swap');

@tailwind base;
@tailwind components;
@tailwind utilities;

@theme {
  /* Copy all theme variables from design-system/tokens.css */

  --color-primary-50: #eef2ff;
  --color-primary-600: #4f46e5;
  /* ... etc */

  --font-heading: 'Inter', sans-serif;
  --font-body: 'Inter', sans-serif;
  --font-mono: 'IBM Plex Mono', monospace;
}

/* Global styles */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  font-family: var(--font-body);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

body {
  color: var(--color-neutral-900);
  background-color: var(--color-neutral-50);
}

@media (prefers-color-scheme: dark) {
  body {
    color: var(--color-neutral-100);
    background-color: var(--color-neutral-900);
  }
}
```

**Reference**: See `design-system/tokens.css`, `design-system/tailwind-colors.md`, and `design-system/fonts.md` for complete details.

## Step 3: Set Up Data Model Types

### Copy Type Definitions

Create `resources/js/types.ts` and copy the content from `data-model/types.ts`.

This provides TypeScript interfaces for all entities:
- Party, Project, WorkOrder, Task, Deliverable
- TeamMember, Contact
- Approval, Blocker
- Reports, Settings, etc.

### Create Mock Data Utilities

**resources/js/mock-data.ts**:
```typescript
import type { User, Organization, NavigationItem } from './types'

export const mockUser: User = {
  id: 'usr-001',
  displayName: 'Jane Doe',
  email: 'jane@example.com',
  timezone: 'America/New_York',
  language: 'en-US',
}

export const mockOrganizations: Organization[] = [
  {
    id: 'org-001',
    name: 'Acme Agency',
    slug: 'acme-agency',
    plan: 'Pro',
    role: 'Owner',
    memberCount: 12,
    lastActive: new Date().toISOString(),
    isCurrent: true,
  },
]

// Reference: data-model/sample-data.json for more examples
```

## Step 4: Set Up Routing

### Define Routes

Create routes for all sections:

**Next.js App Router Structure**:
```
resources/js/
├── layouts          # Root layout with shell
├── page.tsx            # Redirect to /today
├── sections           
│   ├ today/
│   │   └── page.tsx
│   ├ work/
│   │   └── page.tsx
│   ├ nbox/
│   │   └── page.tsx
│   ├ playbooks/
│   │   └── page.tsx
│   ├ directory/
│   │   └── page.tsx
│   ├ reports/
│   │   └── page.tsx
│   ├ settings/
│   │   └── page.tsx
│   ├ profile/
│   │   └── page.tsx        # User menu profile page
```

**Vite React Router Structure**:
```typescript
// src/router.tsx
import { createBrowserRouter } from 'react-router-dom'
import Layout from './components/Layout'
import TodayPage from './pages/TodayPage'
// ... other imports

export const router = createBrowserRouter([
  {
    path: '/',
    element: <Layout />,
    children: [
      { index: true, element: <Navigate to="/today" /> },
      { path: 'today', element: <TodayPage /> },
      { path: 'work', element: <WorkPage /> },
      { path: 'inbox', element: <InboxPage /> },
      { path: 'playbooks', element: <PlaybooksPage /> },
      { path: 'directory', element: <DirectoryPage /> },
      { path: 'reports', element: <ReportsPage /> },
      { path: 'settings', element: <SettingsPage /> },
      { path: 'profile', element: <ProfilePage /> },
    ],
  },
])
```

## Step 5: Implement Application Shell

### Copy Shell Components

Copy all files from `shell/components/` into your project:

**Vite**:
```
src/components/shell/
├── AppShell.tsx
├── MainNav.tsx
├── UserMenu.tsx
└── index.ts
```

### Create Root Layout

**src/components/Layout.tsx** (Vite):
```typescript
import { AppShell } from './shell'
import { Outlet, useLocation, useNavigate } from 'react-router-dom'
import { mockUser, mockOrganizations } from '../lib/mock-data'

export default function Layout() {
  const location = useLocation()
  const navigate = useNavigate()

  const navigationItems = [
    { label: 'Today', href: '/today', isActive: location.pathname === '/today' },
    { label: 'Work', href: '/work', isActive: location.pathname === '/work' },
    // ... other items
  ]

  return (
    <AppShell
      navigationItems={navigationItems}
      user={mockUser}
      organizations={mockOrganizations}
      currentOrganization={mockOrganizations[0]}
      onNavigate={(href) => navigate(href)}
      onSwitchOrganization={(id) => console.log('Switch org:', id)}
      onOpenProfile={() => navigate('/profile')}
      onLogout={() => console.log('Logout')}
    >
      <Outlet />
    </AppShell>
  )
}
```

### Create Placeholder Pages

Create simple placeholder pages for each section:

**app/today/page.tsx** (Next.js):
```typescript
export default function TodayPage() {
  return (
    <div className="p-8">
      <h1 className="text-4xl font-semibold text-slate-900 dark:text-slate-100 mb-4">
        Today
      </h1>
      <p className="text-slate-600 dark:text-slate-400">
        Daily command center - coming in Milestone 3
      </p>
    </div>
  )
}
```

Create similar pages for: `work`, `inbox`, `playbooks`, `directory`, `reports`, `settings`, `profile`.

## Step 6: Test the Foundation

### Checklist

- [ ] Project builds without errors
- [ ] Tailwind CSS v4 is configured correctly
- [ ] Google Fonts (Inter + IBM Plex Mono) load properly
- [ ] Design tokens (colors, typography) are applied
- [ ] All routes are accessible
- [ ] Sidebar navigation works on desktop
- [ ] Hamburger menu works on mobile/tablet
- [ ] Navigation highlights active section
- [ ] User menu dropdown opens and closes
- [ ] Organization list displays in user menu
- [ ] Dark mode toggle works (if implemented)
- [ ] Clicking navigation items changes routes
- [ ] Layout is responsive at all breakpoints

### Manual Testing

1. **Desktop (1024px+)**:
   - Sidebar should be visible at 280px width
   - Navigation items should highlight on click
   - User menu should open as dropdown

2. **Tablet (768px-1023px)**:
   - Hamburger button should appear in top-left
   - Clicking hamburger should open sidebar as overlay
   - Clicking backdrop should close sidebar

3. **Mobile (<768px)**:
   - Same as tablet but sidebar takes full width

4. **Dark Mode**:
   - Toggle system dark mode
   - Verify all colors use dark: variants
   - Check contrast is sufficient

### TypeScript Validation

```bash
# Check for type errors
npm run type-check  # or tsc --noEmit
```

## Next Steps

With the foundation complete, you're ready to implement sections:

- **Milestone 3**: Today section (daily command center)
- **Milestone 4**: Work section (projects, work orders, tasks)
- **Milestone 5**: Inbox section (agent drafts, approvals)
- And so on...

## Troubleshooting

### Tailwind Classes Not Working

- Verify `tailwind.config.ts` includes correct content paths
- Check that `@tailwind` directives are in your CSS
- Restart dev server after config changes

### Fonts Not Loading

- Check network tab for font requests
- Verify Google Fonts import in CSS
- Try adding font-display: swap

### Dark Mode Not Working

- Check system preferences are set to dark mode
- Verify all components use `dark:` variants
- Add `class="dark"` to html element to force dark mode

### Navigation Not Highlighting

- Verify pathname/location detection logic
- Check `isActive` prop is set correctly
- Ensure route paths match exactly

## Additional Resources

- Review `shell/README.md` for complete shell documentation
- Check `design-system/` for color and typography guides
- Reference `data-model/types.ts` for all type definitions

---

**Next Milestone**: [02-shell.md](./02-shell.md) - Wait, the shell is already done! Move on to [03-today.md](./03-today.md) to build the Today section.
