import { router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import type { Integration } from '@/types/settings';
import * as Icons from 'lucide-react';

interface IntegrationsSectionProps {
    integrations: Integration[];
}

export function IntegrationsSection({ integrations }: IntegrationsSectionProps) {
    const handleConnect = (integrationId: number) => {
        router.post(`/settings/integrations/${integrationId}/connect`, {}, {
            preserveScroll: true,
        });
    };

    const handleDisconnect = (integrationId: number) => {
        router.post(`/settings/integrations/${integrationId}/disconnect`, {}, {
            preserveScroll: true,
        });
    };

    const getIconComponent = (iconName: string) => {
        const Icon = Icons[iconName as keyof typeof Icons] as React.ComponentType<{ className?: string }>;
        return Icon ? <Icon className="h-6 w-6" /> : null;
    };

    const categoryBadgeVariant = (category: string) => {
        switch (category) {
            case 'communication': return 'default' as const;
            case 'storage': return 'secondary' as const;
            case 'crm': return 'outline' as const;
            case 'analytics': return 'outline' as const;
            case 'automation': return 'secondary' as const;
            default: return 'default' as const;
        }
    };

    const groupedIntegrations = integrations.reduce((acc, integration) => {
        if (!acc[integration.category]) {
            acc[integration.category] = [];
        }
        acc[integration.category].push(integration);
        return acc;
    }, {} as Record<string, Integration[]>);

    const categoryLabels: Record<string, string> = {
        communication: 'Communication',
        storage: 'Storage',
        crm: 'CRM',
        analytics: 'Analytics',
        automation: 'Automation',
    };

    return (
        <div className="space-y-8">
            <Card>
                <CardHeader>
                    <CardTitle>Integrations</CardTitle>
                    <CardDescription>
                        Connect third-party services to enhance your workspace
                    </CardDescription>
                </CardHeader>
            </Card>

            {Object.entries(groupedIntegrations).map(([category, categoryIntegrations]) => (
                <div key={category} className="space-y-4">
                    <h2 className="text-lg font-semibold">{categoryLabels[category]}</h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {categoryIntegrations.map((integration) => (
                            <Card key={integration.id} className="relative">
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="rounded-lg border p-2">
                                                {getIconComponent(integration.icon)}
                                            </div>
                                            <div>
                                                <CardTitle className="text-base">{integration.name}</CardTitle>
                                                <Badge variant={categoryBadgeVariant(integration.category)} className="mt-1">
                                                    {categoryLabels[integration.category]}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-sm text-muted-foreground mb-4">
                                        {integration.description}
                                    </p>

                                    <div className="mb-4">
                                        <p className="text-xs font-medium mb-2">Features:</p>
                                        <ul className="text-xs text-muted-foreground space-y-1">
                                            {integration.features.map((feature, idx) => (
                                                <li key={idx}>• {feature}</li>
                                            ))}
                                        </ul>
                                    </div>

                                    <div className="flex items-center justify-between">
                                        {integration.connected ? (
                                            <>
                                                <div className="flex flex-col">
                                                    <Badge variant="default" className="mb-1 w-fit">
                                                        Connected
                                                    </Badge>
                                                    {integration.lastSyncAt && (
                                                        <span className="text-xs text-muted-foreground">
                                                            Last sync: {new Date(integration.lastSyncAt).toLocaleDateString()}
                                                        </span>
                                                    )}
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDisconnect(integration.id)}
                                                >
                                                    Disconnect
                                                </Button>
                                            </>
                                        ) : (
                                            <>
                                                <Badge variant="outline">Not connected</Badge>
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleConnect(integration.id)}
                                                >
                                                    Connect
                                                </Button>
                                            </>
                                        )}
                                    </div>

                                    {integration.syncStatus === 'error' && integration.errorMessage && (
                                        <div className="mt-2 text-xs text-destructive">
                                            Error: {integration.errorMessage}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            ))}
        </div>
    );
}
