import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Directory', href: '/directory' },
];

export default function Directory() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Directory" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-semibold">Directory</h1>
                </div>
                <div className="relative min-h-[60vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="relative z-10 flex h-full items-center justify-center">
                        <p className="text-muted-foreground">
                            Directory content coming in future milestones
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
