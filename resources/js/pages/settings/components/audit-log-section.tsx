import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from '@/components/ui/table';
import type { AuditLogEntry } from '@/types/settings';

interface AuditLogSectionProps {
    entries: AuditLogEntry[];
}

export function AuditLogSection({ entries }: AuditLogSectionProps) {
    const [search, setSearch] = useState('');

    const handleExport = () => {
        router.get('/settings/audit-log/export');
    };

    const actorTypeBadgeVariant = (type: string) => {
        switch (type) {
            case 'user': return 'default' as const;
            case 'agent': return 'secondary' as const;
            case 'system': return 'outline' as const;
            default: return 'default' as const;
        }
    };

    const filteredEntries = entries.filter(entry =>
        search === '' ||
        entry.details.toLowerCase().includes(search.toLowerCase()) ||
        entry.actorName.toLowerCase().includes(search.toLowerCase()) ||
        entry.action.toLowerCase().includes(search.toLowerCase())
    );

    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between">
                    <div>
                        <CardTitle>Audit Log</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Review all activity in your workspace
                        </p>
                    </div>
                    <Button onClick={handleExport} variant="outline">
                        Export Log
                    </Button>
                </div>
                <div className="mt-4 flex gap-4">
                    <Input
                        placeholder="Search activity..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="max-w-sm"
                    />
                </div>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Timestamp</TableHead>
                            <TableHead>Actor</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Action</TableHead>
                            <TableHead>Details</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {filteredEntries.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={5} className="text-center text-muted-foreground">
                                    {search ? 'No matching entries found' : 'No audit log entries yet'}
                                </TableCell>
                            </TableRow>
                        ) : (
                            filteredEntries.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell className="font-mono text-xs">
                                        {new Date(entry.timestamp).toLocaleString()}
                                    </TableCell>
                                    <TableCell>{entry.actorName}</TableCell>
                                    <TableCell>
                                        <Badge variant={actorTypeBadgeVariant(entry.actorType)}>
                                            {entry.actorType}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="font-mono text-xs">
                                        {entry.action}
                                    </TableCell>
                                    <TableCell className="max-w-md truncate">
                                        {entry.details}
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
                <div className="mt-4 text-sm text-muted-foreground">
                    Showing {filteredEntries.length} of {entries.length} entries
                </div>
            </CardContent>
        </Card>
    );
}
