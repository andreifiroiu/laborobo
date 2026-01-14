import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import { Progress } from '@/components/ui/progress';
import type { BillingInfo, Invoice } from '@/types/settings';

interface BillingSectionProps {
    billingInfo: BillingInfo | null;
    invoices: Invoice[];
}

export function BillingSection({ billingInfo, invoices }: BillingSectionProps) {
    if (!billingInfo) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Billing & Usage</CardTitle>
                    <CardDescription>No billing information available</CardDescription>
                </CardHeader>
            </Card>
        );
    }

    const statusBadgeVariant = (status: string) => {
        switch (status) {
            case 'active': return 'default' as const;
            case 'trial': return 'secondary' as const;
            case 'past_due': return 'destructive' as const;
            case 'canceled': return 'outline' as const;
            default: return 'default' as const;
        }
    };

    const invoiceStatusBadgeVariant = (status: string) => {
        switch (status) {
            case 'paid': return 'default' as const;
            case 'pending': return 'secondary' as const;
            case 'overdue': return 'destructive' as const;
            case 'void': return 'outline' as const;
            default: return 'default' as const;
        }
    };

    const calculatePercentage = (current: number, total: number) => {
        return Math.round((current / total) * 100);
    };

    return (
        <div className="space-y-6">
            {/* Plan Information */}
            <Card>
                <CardHeader>
                    <div className="flex items-center justify-between">
                        <div>
                            <CardTitle>Current Plan</CardTitle>
                            <CardDescription>Manage your subscription</CardDescription>
                        </div>
                        <Badge variant={statusBadgeVariant(billingInfo.status)}>
                            {billingInfo.status}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 className="text-2xl font-bold">{billingInfo.planName}</h3>
                            <p className="text-muted-foreground">
                                ${billingInfo.planPrice} / {billingInfo.billingCycle}
                            </p>
                            <div className="mt-4 space-y-2 text-sm">
                                <p>
                                    <span className="font-medium">Billing Period:</span>{' '}
                                    {new Date(billingInfo.billingPeriodStart).toLocaleDateString()} -{' '}
                                    {new Date(billingInfo.billingPeriodEnd).toLocaleDateString()}
                                </p>
                                <p>
                                    <span className="font-medium">Next Billing Date:</span>{' '}
                                    {new Date(billingInfo.nextBillingDate).toLocaleDateString()}
                                </p>
                            </div>
                        </div>

                        <div>
                            <h4 className="font-medium mb-2">Payment Method</h4>
                            {billingInfo.paymentMethod === 'card' && billingInfo.cardBrand ? (
                                <div className="text-sm space-y-1">
                                    <p>
                                        {billingInfo.cardBrand} ending in {billingInfo.cardLast4}
                                    </p>
                                    <p className="text-muted-foreground">
                                        Expires {billingInfo.cardExpiry ? new Date(billingInfo.cardExpiry).toLocaleDateString('en-US', { month: '2-digit', year: 'numeric' }) : 'N/A'}
                                    </p>
                                </div>
                            ) : (
                                <p className="text-sm text-muted-foreground">No payment method on file</p>
                            )}
                            <Button variant="outline" className="mt-4" size="sm">
                                Update Payment Method
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Usage Metrics */}
            <Card>
                <CardHeader>
                    <CardTitle>Usage</CardTitle>
                    <CardDescription>Current billing period usage</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-6">
                        {/* Users */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium">Team Members</span>
                                <span className="text-sm text-muted-foreground">
                                    {billingInfo.usersCurrent} / {billingInfo.usersIncluded}
                                </span>
                            </div>
                            <Progress
                                value={calculatePercentage(billingInfo.usersCurrent, billingInfo.usersIncluded)}
                            />
                        </div>

                        {/* Projects */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium">Projects</span>
                                <span className="text-sm text-muted-foreground">
                                    {billingInfo.projectsCurrent} / {billingInfo.projectsIncluded}
                                </span>
                            </div>
                            <Progress
                                value={calculatePercentage(billingInfo.projectsCurrent, billingInfo.projectsIncluded)}
                            />
                        </div>

                        {/* Storage */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium">Storage</span>
                                <span className="text-sm text-muted-foreground">
                                    {billingInfo.storageGbCurrent} GB / {billingInfo.storageGbIncluded} GB
                                </span>
                            </div>
                            <Progress
                                value={calculatePercentage(parseFloat(billingInfo.storageGbCurrent), parseFloat(billingInfo.storageGbIncluded))}
                            />
                        </div>

                        {/* AI Requests */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <span className="text-sm font-medium">AI Requests</span>
                                <span className="text-sm text-muted-foreground">
                                    {billingInfo.aiRequestsCurrent.toLocaleString()} / {billingInfo.aiRequestsIncluded.toLocaleString()}
                                </span>
                            </div>
                            <Progress
                                value={calculatePercentage(billingInfo.aiRequestsCurrent, billingInfo.aiRequestsIncluded)}
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Invoices */}
            <Card>
                <CardHeader>
                    <CardTitle>Billing History</CardTitle>
                    <CardDescription>View and download invoices</CardDescription>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Invoice</TableHead>
                                <TableHead>Date</TableHead>
                                <TableHead>Amount</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {invoices.length === 0 ? (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-muted-foreground">
                                        No invoices yet
                                    </TableCell>
                                </TableRow>
                            ) : (
                                invoices.map((invoice) => (
                                    <TableRow key={invoice.id}>
                                        <TableCell className="font-mono text-sm">
                                            {invoice.invoiceNumber}
                                        </TableCell>
                                        <TableCell>
                                            {new Date(invoice.invoiceDate).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell>${invoice.amount}</TableCell>
                                        <TableCell>
                                            <Badge variant={invoiceStatusBadgeVariant(invoice.status)}>
                                                {invoice.status}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {invoice.pdfUrl && (
                                                <Button variant="ghost" size="sm" asChild>
                                                    <a href={invoice.pdfUrl} download>
                                                        Download
                                                    </a>
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
