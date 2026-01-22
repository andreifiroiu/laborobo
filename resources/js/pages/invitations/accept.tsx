import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Users, CheckCircle, AlertCircle } from 'lucide-react';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AuthLayout from '@/layouts/auth-layout';

interface InvitationAcceptProps {
    invitation: {
        id: number;
        email: string;
        teamName: string;
        roleName: string;
    };
    isLoggedIn: boolean;
    currentUserEmail: string | null;
    errors?: {
        email?: string;
    };
}

export default function AcceptInvitation({
    invitation,
    isLoggedIn,
    currentUserEmail,
    errors,
}: InvitationAcceptProps) {
    const [isSubmitting, setIsSubmitting] = useState(false);

    const isCorrectUser = isLoggedIn && currentUserEmail === invitation.email;
    const isWrongUser = isLoggedIn && currentUserEmail !== invitation.email;

    const handleAccept = () => {
        setIsSubmitting(true);
        router.post(window.location.href, {}, {
            preserveScroll: true,
            onError: () => {
                setIsSubmitting(false);
            },
        });
    };

    return (
        <AuthLayout
            title="Team Invitation"
            description={`You've been invited to join ${invitation.teamName}`}
        >
            <Head title="Accept Invitation" />

            <Card className="w-full max-w-md">
                <CardHeader className="text-center">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                        <Users className="h-8 w-8 text-primary" />
                    </div>
                    <CardTitle>Join {invitation.teamName}</CardTitle>
                    <CardDescription>
                        You've been invited to join as a <strong>{invitation.roleName}</strong>
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-4">
                    <div className="rounded-lg border bg-muted/50 p-4">
                        <div className="text-sm text-muted-foreground">Invitation sent to:</div>
                        <div className="font-medium">{invitation.email}</div>
                    </div>

                    {errors?.email && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>{errors.email}</AlertDescription>
                        </Alert>
                    )}

                    {isWrongUser && (
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertDescription>
                                This invitation was sent to <strong>{invitation.email}</strong>, but
                                you are logged in as <strong>{currentUserEmail}</strong>. Please log
                                out and log in with the correct account.
                            </AlertDescription>
                        </Alert>
                    )}

                    {isCorrectUser && (
                        <Alert>
                            <CheckCircle className="h-4 w-4" />
                            <AlertDescription>
                                You are logged in as <strong>{currentUserEmail}</strong>. Click
                                accept to join the team.
                            </AlertDescription>
                        </Alert>
                    )}
                </CardContent>

                <CardFooter className="flex flex-col gap-3">
                    {isCorrectUser ? (
                        <Button
                            className="w-full"
                            onClick={handleAccept}
                            disabled={isSubmitting}
                        >
                            {isSubmitting ? 'Joining...' : 'Accept Invitation'}
                        </Button>
                    ) : isWrongUser ? (
                        <Button
                            className="w-full"
                            variant="outline"
                            onClick={() => router.post('/logout')}
                        >
                            Log Out & Switch Account
                        </Button>
                    ) : (
                        <>
                            <Button
                                className="w-full"
                                onClick={handleAccept}
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? 'Continuing...' : 'Accept & Continue'}
                            </Button>
                            <p className="text-center text-sm text-muted-foreground">
                                You will be redirected to log in or create an account
                            </p>
                        </>
                    )}
                </CardFooter>
            </Card>
        </AuthLayout>
    );
}
