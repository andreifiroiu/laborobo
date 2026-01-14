import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
} from '@/components/ui/sidebar';
import { Link } from '@inertiajs/react';
import { Settings, Users, Bot, Plug, CreditCard, Bell, FileText } from 'lucide-react';

interface SettingsNavItem {
  title: string;
  value: string;
  icon: React.ComponentType<{ className?: string }>;
}

const settingsNavItems: SettingsNavItem[] = [
  { title: 'Workspace', value: 'workspace', icon: Settings },
  { title: 'Team & Permissions', value: 'team', icon: Users },
  { title: 'AI Agents', value: 'ai-agents', icon: Bot },
  { title: 'Integrations', value: 'integrations', icon: Plug },
  { title: 'Billing', value: 'billing', icon: CreditCard },
  { title: 'Notifications', value: 'notifications', icon: Bell },
  { title: 'Audit Log', value: 'audit-log', icon: FileText },
];

export function SettingsSidebar() {
  const searchParams = new URLSearchParams(window.location.search);
  const activeTab = searchParams.get('tab') || 'workspace';

  return (
    <Sidebar collapsible="none" className="border-r border-border">
      <SidebarContent>
        <SidebarGroup className="px-2 py-4">
          <SidebarGroupLabel>Settings</SidebarGroupLabel>
          <SidebarMenu>
            {settingsNavItems.map((item) => (
              <SidebarMenuItem key={item.value}>
                <SidebarMenuButton
                  asChild
                  isActive={activeTab === item.value}
                >
                  <Link
                    href={`/settings?tab=${item.value}`}
                    preserveScroll
                  >
                    <item.icon />
                    <span>{item.title}</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            ))}
          </SidebarMenu>
        </SidebarGroup>
      </SidebarContent>
    </Sidebar>
  );
}
