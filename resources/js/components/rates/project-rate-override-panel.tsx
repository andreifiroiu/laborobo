import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Plus, Pencil, Trash2, AlertCircle, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import InputError from '@/components/input-error';

interface ProjectRateOverride {
    id: string;
    userId: string;
    userName: string;
    userEmail: string;
    internalRate: number;
    billingRate: number;
    effectiveDate: string;
}

interface TeamMember {
    id: string;
    name: string;
    email: string;
}

interface ProjectRateOverridePanelProps {
    projectId: number;
    teamMembers: TeamMember[];
    initialRates?: ProjectRateOverride[];
}

interface FormErrors {
    user_id?: string;
    internal_rate?: string;
    billing_rate?: string;
    effective_date?: string;
    general?: string;
}

export function ProjectRateOverridePanel({
    projectId,
    teamMembers,
    initialRates = [],
}: ProjectRateOverridePanelProps) {
    const [rates, setRates] = useState<ProjectRateOverride[]>(initialRates);
    const [isLoading, setIsLoading] = useState(false);
    const [addDialogOpen, setAddDialogOpen] = useState(false);
    const [editDialogOpen, setEditDialogOpen] = useState(false);
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [selectedRate, setSelectedRate] = useState<ProjectRateOverride | null>(null);

    // Form state
    const [selectedUserId, setSelectedUserId] = useState('');
    const [internalRate, setInternalRate] = useState('');
    const [billingRate, setBillingRate] = useState('');
    const [effectiveDate, setEffectiveDate] = useState(
        new Date().toISOString().split('T')[0]
    );
    const [errors, setErrors] = useState<FormErrors>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Fetch rates on mount if not provided
    useEffect(() => {
        if (initialRates.length === 0) {
            fetchRates();
        }
    }, [projectId]);

    const fetchRates = async () => {
        setIsLoading(true);
        try {
            const response = await fetch(`/work/projects/${projectId}/rates`);
            const data = await response.json();
            setRates(data.rates || []);
        } catch (error) {
            console.error('Failed to fetch project rates:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const formatCurrency = (value: number): string => {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
        }).format(value);
    };

    const formatDate = (dateString: string): string => {
        return new Intl.DateTimeFormat('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        }).format(new Date(dateString));
    };

    const resetForm = () => {
        setSelectedUserId('');
        setInternalRate('');
        setBillingRate('');
        setEffectiveDate(new Date().toISOString().split('T')[0]);
        setErrors({});
    };

    const validateRate = (value: string, fieldName: string): string | null => {
        if (!value.trim()) {
            return `${fieldName} is required`;
        }

        const numValue = parseFloat(value);

        if (isNaN(numValue)) {
            return `${fieldName} must be a valid number`;
        }

        if (numValue <= 0) {
            return `${fieldName} must be a positive number`;
        }

        const decimalParts = value.split('.');
        if (decimalParts.length === 2 && decimalParts[1].length > 2) {
            return `${fieldName} must have maximum 2 decimal places`;
        }

        return null;
    };

    const validateForm = (): boolean => {
        const newErrors: FormErrors = {};

        if (!selectedUserId && !selectedRate) {
            newErrors.user_id = 'Team member is required';
        }

        const internalRateError = validateRate(internalRate, 'Internal rate');
        if (internalRateError) {
            newErrors.internal_rate = internalRateError;
        }

        const billingRateError = validateRate(billingRate, 'Billing rate');
        if (billingRateError) {
            newErrors.billing_rate = billingRateError;
        }

        if (!effectiveDate) {
            newErrors.effective_date = 'Effective date is required';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleAddClick = () => {
        resetForm();
        setAddDialogOpen(true);
    };

    const handleEditClick = (rate: ProjectRateOverride) => {
        setSelectedRate(rate);
        setSelectedUserId(rate.userId);
        setInternalRate(rate.internalRate.toString());
        setBillingRate(rate.billingRate.toString());
        setEffectiveDate(rate.effectiveDate);
        setErrors({});
        setEditDialogOpen(true);
    };

    const handleDeleteClick = (rate: ProjectRateOverride) => {
        setSelectedRate(rate);
        setDeleteDialogOpen(true);
    };

    const handleAddSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm()) {
            return;
        }

        setIsSubmitting(true);

        router.post(
            `/work/projects/${projectId}/rates`,
            {
                user_id: selectedUserId,
                internal_rate: parseFloat(internalRate),
                billing_rate: parseFloat(billingRate),
                effective_date: effectiveDate,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsSubmitting(false);
                    setAddDialogOpen(false);
                    resetForm();
                    fetchRates();
                },
                onError: (formErrors) => {
                    setIsSubmitting(false);
                    setErrors(formErrors as FormErrors);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            }
        );
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!validateForm() || !selectedRate) {
            return;
        }

        setIsSubmitting(true);

        router.patch(
            `/work/projects/${projectId}/rates/${selectedRate.id}`,
            {
                internal_rate: parseFloat(internalRate),
                billing_rate: parseFloat(billingRate),
                effective_date: effectiveDate,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setIsSubmitting(false);
                    setEditDialogOpen(false);
                    setSelectedRate(null);
                    resetForm();
                    fetchRates();
                },
                onError: (formErrors) => {
                    setIsSubmitting(false);
                    setErrors(formErrors as FormErrors);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            }
        );
    };

    const handleDeleteConfirm = () => {
        if (!selectedRate) return;

        router.delete(`/work/projects/${projectId}/rates/${selectedRate.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteDialogOpen(false);
                setSelectedRate(null);
                fetchRates();
            },
        });
    };

    // Get team members not already having an override
    const availableTeamMembers = teamMembers.filter(
        (member) => !rates.some((rate) => rate.userId === member.id)
    );

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <div>
                    <CardTitle>Project Rate Overrides</CardTitle>
                    <CardDescription>
                        Set project-specific rates that override team default rates for this project.
                    </CardDescription>
                </div>
                {availableTeamMembers.length > 0 && (
                    <Button onClick={handleAddClick} size="sm">
                        <Plus className="mr-2 h-4 w-4" />
                        Add Override
                    </Button>
                )}
            </CardHeader>
            <CardContent>
                {isLoading ? (
                    <div className="flex items-center justify-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>
                ) : rates.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-8 text-center">
                        <AlertCircle className="h-8 w-8 text-muted-foreground mb-2" />
                        <p className="text-sm text-muted-foreground">
                            No project-specific rate overrides configured.
                        </p>
                        <p className="text-xs text-muted-foreground mt-1">
                            Team default rates will be used for all members.
                        </p>
                    </div>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Team Member</TableHead>
                                <TableHead className="text-right">Internal Rate</TableHead>
                                <TableHead className="text-right">Billing Rate</TableHead>
                                <TableHead>Effective Date</TableHead>
                                <TableHead className="w-[100px]">
                                    <span className="sr-only">Actions</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rates.map((rate) => (
                                <TableRow key={rate.id}>
                                    <TableCell>
                                        <div>
                                            <div className="font-medium">{rate.userName}</div>
                                            <div className="text-sm text-muted-foreground">
                                                {rate.userEmail}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatCurrency(rate.internalRate)}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {formatCurrency(rate.billingRate)}
                                    </TableCell>
                                    <TableCell>{formatDate(rate.effectiveDate)}</TableCell>
                                    <TableCell>
                                        <div className="flex items-center gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleEditClick(rate)}
                                                aria-label={`Edit rate for ${rate.userName}`}
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => handleDeleteClick(rate)}
                                                aria-label={`Remove rate override for ${rate.userName}`}
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>

            {/* Add Override Dialog */}
            <Dialog open={addDialogOpen} onOpenChange={setAddDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={handleAddSubmit}>
                        <DialogHeader>
                            <DialogTitle>Add Rate Override</DialogTitle>
                            <DialogDescription>
                                Set a project-specific rate for a team member that overrides their
                                default rate.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="user_id">Team Member</Label>
                                <Select value={selectedUserId} onValueChange={setSelectedUserId}>
                                    <SelectTrigger id="user_id">
                                        <SelectValue placeholder="Select a team member" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableTeamMembers.map((member) => (
                                            <SelectItem key={member.id} value={member.id}>
                                                {member.name} ({member.email})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.user_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="add_internal_rate">Internal Rate (Cost per Hour)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                        $
                                    </span>
                                    <Input
                                        id="add_internal_rate"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={internalRate}
                                        onChange={(e) => setInternalRate(e.target.value)}
                                        placeholder="0.00"
                                        className="pl-7"
                                    />
                                </div>
                                <InputError message={errors.internal_rate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="add_billing_rate">Billing Rate (Revenue per Hour)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                        $
                                    </span>
                                    <Input
                                        id="add_billing_rate"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={billingRate}
                                        onChange={(e) => setBillingRate(e.target.value)}
                                        placeholder="0.00"
                                        className="pl-7"
                                    />
                                </div>
                                <InputError message={errors.billing_rate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="add_effective_date">Effective Date</Label>
                                <Input
                                    id="add_effective_date"
                                    type="date"
                                    value={effectiveDate}
                                    onChange={(e) => setEffectiveDate(e.target.value)}
                                />
                                <InputError message={errors.effective_date} />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setAddDialogOpen(false)}
                                disabled={isSubmitting}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isSubmitting || !selectedUserId}>
                                {isSubmitting ? 'Adding...' : 'Add Override'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Edit Override Dialog */}
            <Dialog open={editDialogOpen} onOpenChange={setEditDialogOpen}>
                <DialogContent className="sm:max-w-md">
                    <form onSubmit={handleEditSubmit}>
                        <DialogHeader>
                            <DialogTitle>Edit Rate Override</DialogTitle>
                            <DialogDescription>
                                Update the project-specific rate for {selectedRate?.userName}.
                            </DialogDescription>
                        </DialogHeader>

                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit_internal_rate">Internal Rate (Cost per Hour)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                        $
                                    </span>
                                    <Input
                                        id="edit_internal_rate"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={internalRate}
                                        onChange={(e) => setInternalRate(e.target.value)}
                                        placeholder="0.00"
                                        className="pl-7"
                                    />
                                </div>
                                <InputError message={errors.internal_rate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit_billing_rate">Billing Rate (Revenue per Hour)</Label>
                                <div className="relative">
                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                        $
                                    </span>
                                    <Input
                                        id="edit_billing_rate"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={billingRate}
                                        onChange={(e) => setBillingRate(e.target.value)}
                                        placeholder="0.00"
                                        className="pl-7"
                                    />
                                </div>
                                <InputError message={errors.billing_rate} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="edit_effective_date">Effective Date</Label>
                                <Input
                                    id="edit_effective_date"
                                    type="date"
                                    value={effectiveDate}
                                    onChange={(e) => setEffectiveDate(e.target.value)}
                                />
                                <InputError message={errors.effective_date} />
                            </div>
                        </div>

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setEditDialogOpen(false)}
                                disabled={isSubmitting}
                            >
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isSubmitting}>
                                {isSubmitting ? 'Saving...' : 'Save Changes'}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            {/* Delete Confirmation Dialog */}
            <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Remove Rate Override</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to remove the rate override for{' '}
                            <strong>{selectedRate?.userName}</strong>? Their time entries will use
                            the team default rate instead.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleDeleteConfirm}
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        >
                            Remove Override
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </Card>
    );
}
