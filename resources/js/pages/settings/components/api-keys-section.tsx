import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Plus, Trash2, ExternalLink } from 'lucide-react';
import type { AIProvider, TeamApiKey } from '@/types/settings';

interface ApiKeysSectionProps {
    providers: AIProvider[];
    apiKeys: TeamApiKey[];
}

export function ApiKeysSection({ providers, apiKeys }: ApiKeysSectionProps) {
    const [addDialogOpen, setAddDialogOpen] = useState(false);
    const [addProvider, setAddProvider] = useState<string | null>(null);
    const [deleteKey, setDeleteKey] = useState<TeamApiKey | null>(null);

    const keysForProvider = (providerCode: string) =>
        apiKeys.filter((k) => k.provider === providerCode);

    const openAddDialog = (providerCode: string) => {
        setAddProvider(providerCode);
        setAddDialogOpen(true);
    };

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>API Keys</CardTitle>
                    <CardDescription>
                        Manage API keys for AI providers. Keys are encrypted and never shown after saving.
                        Each provider can have both a team-shared key and a personal key.
                    </CardDescription>
                </CardHeader>
            </Card>

            <div className="grid grid-cols-1 gap-4">
                {providers.map((provider) => {
                    const keys = keysForProvider(provider.code);

                    return (
                        <Card key={provider.code}>
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div>
                                        <CardTitle className="text-base">{provider.name}</CardTitle>
                                        <CardDescription>{provider.description}</CardDescription>
                                    </div>
                                    <a
                                        href={provider.docsUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        <ExternalLink className="h-4 w-4" />
                                    </a>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {keys.length > 0 ? (
                                    <div className="space-y-3">
                                        {keys.map((key) => (
                                            <div
                                                key={key.id}
                                                className="flex items-center justify-between rounded-md border px-4 py-3"
                                            >
                                                <div className="flex items-center gap-3">
                                                    <code className="text-sm font-mono">
                                                        {'····' + key.keyLastFour}
                                                    </code>
                                                    <Badge variant={key.scope === 'team' ? 'default' : 'secondary'}>
                                                        {key.scope === 'team' ? 'Team' : 'Private'}
                                                    </Badge>
                                                    {key.label && (
                                                        <span className="text-sm text-muted-foreground">
                                                            {key.label}
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    {key.lastUsedAt && (
                                                        <span className="text-xs text-muted-foreground">
                                                            Last used {new Date(key.lastUsedAt).toLocaleDateString()}
                                                        </span>
                                                    )}
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => setDeleteKey(key)}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                        {keys.length < 2 && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => openAddDialog(provider.code)}
                                            >
                                                <Plus className="mr-1 h-4 w-4" />
                                                Add {keys[0]?.scope === 'team' ? 'Private' : 'Team'} Key
                                            </Button>
                                        )}
                                    </div>
                                ) : (
                                    <Button
                                        variant="outline"
                                        onClick={() => openAddDialog(provider.code)}
                                    >
                                        <Plus className="mr-1 h-4 w-4" />
                                        Add Key
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <AddKeyDialog
                open={addDialogOpen}
                onOpenChange={setAddDialogOpen}
                providerCode={addProvider}
                providerName={providers.find((p) => p.code === addProvider)?.name ?? ''}
                existingKeys={addProvider ? keysForProvider(addProvider) : []}
            />

            <DeleteKeyDialog
                open={deleteKey !== null}
                onOpenChange={(open) => { if (!open) setDeleteKey(null); }}
                apiKey={deleteKey}
            />
        </div>
    );
}

function AddKeyDialog({
    open,
    onOpenChange,
    providerCode,
    providerName,
    existingKeys,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    providerCode: string | null;
    providerName: string;
    existingKeys: TeamApiKey[];
}) {
    const hasTeamKey = existingKeys.some((k) => k.scope === 'team');
    const hasUserKey = existingKeys.some((k) => k.scope === 'user');
    const defaultScope = hasTeamKey ? 'user' : 'team';

    const form = useForm({
        provider: providerCode ?? '',
        api_key: '',
        scope: defaultScope,
        label: '',
    });

    // Reset form when dialog opens with new provider
    const handleOpenChange = (isOpen: boolean) => {
        if (!isOpen) {
            form.reset();
            form.clearErrors();
        }
        onOpenChange(isOpen);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.transform((data) => ({
            ...data,
            provider: providerCode ?? '',
            scope: hasTeamKey ? 'user' : hasUserKey ? 'team' : data.scope,
        }));

        form.post('/settings/api-keys', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    const canChooseScope = !hasTeamKey && !hasUserKey;

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Add API Key for {providerName}</DialogTitle>
                    <DialogDescription>
                        The key will be encrypted and stored securely. It cannot be viewed after saving.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    {canChooseScope && (
                        <div className="space-y-2">
                            <Label>Scope</Label>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant={form.data.scope === 'team' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => form.setData('scope', 'team')}
                                >
                                    Team (shared)
                                </Button>
                                <Button
                                    type="button"
                                    variant={form.data.scope === 'user' ? 'default' : 'outline'}
                                    size="sm"
                                    onClick={() => form.setData('scope', 'user')}
                                >
                                    Private (just me)
                                </Button>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {form.data.scope === 'team'
                                    ? 'Team keys are shared with all team members.'
                                    : 'Private keys are only used for your own agent runs.'}
                            </p>
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label htmlFor="api_key">API Key</Label>
                        <Input
                            id="api_key"
                            type="password"
                            placeholder="sk-..."
                            value={form.data.api_key}
                            onChange={(e) => form.setData('api_key', e.target.value)}
                            autoComplete="off"
                        />
                        {form.errors.api_key && (
                            <p className="text-sm text-destructive">{form.errors.api_key}</p>
                        )}
                        {form.errors.provider && (
                            <p className="text-sm text-destructive">{form.errors.provider}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="label">Label (optional)</Label>
                        <Input
                            id="label"
                            placeholder="e.g. Production key"
                            value={form.data.label}
                            onChange={(e) => form.setData('label', e.target.value)}
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => handleOpenChange(false)}
                            disabled={form.processing}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={form.processing || !form.data.api_key}>
                            {form.processing ? 'Saving...' : 'Save Key'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function DeleteKeyDialog({
    open,
    onOpenChange,
    apiKey,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    apiKey: TeamApiKey | null;
}) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleDelete = () => {
        if (!apiKey) return;
        setIsSubmitting(true);

        router.delete(`/settings/api-keys/${apiKey.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setIsSubmitting(false);
                onOpenChange(false);
            },
            onError: () => {
                setIsSubmitting(false);
            },
        });
    };

    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Remove API Key</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to remove this API key (<code>····{apiKey?.keyLastFour}</code>)?
                        Agents using this provider will fall back to the next available key or environment variable.
                        This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isSubmitting}>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        onClick={handleDelete}
                        disabled={isSubmitting}
                        className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    >
                        {isSubmitting ? 'Removing...' : 'Remove Key'}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
