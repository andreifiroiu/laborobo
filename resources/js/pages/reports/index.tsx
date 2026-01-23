import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Clock, DollarSign, ArrowRight } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
];

interface ReportCardProps {
    title: string;
    description: string;
    href: string;
    icon: React.ElementType;
}

function ReportCard({ title, description, href, icon: Icon }: ReportCardProps) {
    return (
        <Link href={href} className="block">
            <Card className="h-full transition-colors hover:bg-muted/50">
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                            <Icon className="size-5 text-primary" aria-hidden="true" />
                        </div>
                        <CardTitle className="text-lg">{title}</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <CardDescription className="text-sm">
                        {description}
                    </CardDescription>
                    <div className="mt-4 flex items-center gap-1 text-sm font-medium text-primary">
                        View Report
                        <ArrowRight className="size-4" aria-hidden="true" />
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}

export default function Reports() {
    const reports = [
        {
            title: 'Time Reports',
            description: 'Analyze time tracking data across your team. View hours by user, project, and compare actual vs estimated time.',
            href: '/reports/time',
            icon: Clock,
        },
        {
            title: 'Profitability Reports',
            description: 'Analyze profitability across projects, work orders, team members, and clients. Track margins, revenue, and costs.',
            href: '/reports/profitability',
            icon: DollarSign,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold text-foreground">Reports</h1>
                    <p className="mt-1 text-muted-foreground">
                        Access detailed reports and analytics for your team
                    </p>
                </div>

                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {reports.map((report) => (
                        <ReportCard key={report.href} {...report} />
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
