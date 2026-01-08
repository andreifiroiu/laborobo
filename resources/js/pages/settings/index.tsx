import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { User, Lock, Palette, Shield } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: '/settings' },
];

const settingsSections = [
    {
        title: 'Profile',
        description: 'Manage your personal information and account details',
        href: '/settings/profile',
        icon: User,
    },
    {
        title: 'Password',
        description: 'Update your password and security preferences',
        href: '/settings/password',
        icon: Lock,
    },
    {
        title: 'Appearance',
        description: 'Customize the look and feel of your workspace',
        href: '/settings/appearance',
        icon: Palette,
    },
    {
        title: 'Two-Factor Authentication',
        description: 'Add an extra layer of security to your account',
        href: '/settings/two-factor',
        icon: Shield,
    },
];

export default function SettingsIndex() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Settings" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Settings</h1>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    {settingsSections.map((section) => {
                        const Icon = section.icon;
                        return (
                            <Link
                                key={section.href}
                                href={section.href}
                                className="group relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 transition-colors hover:bg-sidebar-accent dark:border-sidebar-border"
                            >
                                <div className="flex items-start gap-4">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-sidebar-primary/10 text-sidebar-primary">
                                        <Icon className="h-6 w-6" />
                                    </div>
                                    <div className="flex-1">
                                        <h2 className="text-lg font-semibold group-hover:text-sidebar-primary">
                                            {section.title}
                                        </h2>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {section.description}
                                        </p>
                                    </div>
                                </div>
                            </Link>
                        );
                    })}
                </div>
            </div>
        </AppLayout>
    );
}
