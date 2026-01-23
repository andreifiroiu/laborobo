import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { DollarSign } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { TeamMemberRateTable, type TeamMemberRate, type TeamMember } from '@/components/rates/team-member-rate-table';
import type { BreadcrumbItem } from '@/types';

interface RatesSettingsPageProps {
    rates: TeamMemberRate[];
    teamMembers: TeamMember[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Account', href: '/account' },
    { title: 'Rates', href: '/account/settings/rates' },
];

export default function RatesSettingsPage({ rates, teamMembers }: RatesSettingsPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Team Rates" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center gap-4">
                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-sidebar-primary/10 text-sidebar-primary">
                        <DollarSign className="h-6 w-6" />
                    </div>
                    <div>
                        <h1 className="text-2xl font-semibold">Team Rates</h1>
                        <p className="text-sm text-muted-foreground">
                            Configure hourly rates for your team members to calculate project costs and profitability.
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Team Member Rates</CardTitle>
                        <CardDescription>
                            Set the internal (cost) and billing (revenue) rates for each team member.
                            These rates are used to calculate project profitability when team members log time.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <TeamMemberRateTable rates={rates} teamMembers={teamMembers} />
                    </CardContent>
                </Card>

                <Card className="border-dashed">
                    <CardContent className="py-4">
                        <div className="flex items-start gap-3">
                            <div className="flex h-8 w-8 items-center justify-center rounded bg-blue-100 text-blue-600">
                                <DollarSign className="h-4 w-4" />
                            </div>
                            <div className="space-y-1">
                                <p className="text-sm font-medium">How rates work</p>
                                <ul className="text-xs text-muted-foreground space-y-1">
                                    <li>
                                        <strong>Internal Rate:</strong> The cost per hour for this team member (e.g., salary + overhead).
                                    </li>
                                    <li>
                                        <strong>Billing Rate:</strong> The rate charged to clients per hour of work.
                                    </li>
                                    <li>
                                        <strong>Effective Date:</strong> Rates apply to time entries logged on or after this date.
                                    </li>
                                    <li>
                                        <strong>Rate History:</strong> Each new rate is added to the history. Old rates are preserved for accurate historical cost calculations.
                                    </li>
                                    <li>
                                        <strong>Project Overrides:</strong> You can set project-specific rates that override these defaults in project settings.
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
