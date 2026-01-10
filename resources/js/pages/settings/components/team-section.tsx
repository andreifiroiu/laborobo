import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { TeamMember } from '@/types/settings';

interface TeamSectionProps {
    members: TeamMember[];
}

export function TeamSection({ members }: TeamSectionProps) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Team Members</CardTitle>
                <CardDescription>
                    Manage your team members, roles, and permissions
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Member</TableHead>
                            <TableHead>Role</TableHead>
                            <TableHead>Joined</TableHead>
                            <TableHead>Last Active</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {members.map((member) => (
                            <TableRow key={member.id}>
                                <TableCell>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 text-sm font-medium">
                                            {member.name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <div className="font-medium">{member.name}</div>
                                            <div className="text-sm text-muted-foreground">
                                                {member.email}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Badge variant="secondary">{member.role}</Badge>
                                </TableCell>
                                <TableCell>{formatDate(member.joinedAt)}</TableCell>
                                <TableCell className="text-muted-foreground">
                                    {formatDate(member.lastActiveAt)}
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}
