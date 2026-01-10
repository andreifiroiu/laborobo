import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import InputError from '@/components/input-error';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { WorkspaceSettings } from '@/types/settings';

interface WorkspaceSectionProps {
    settings: WorkspaceSettings;
}

export function WorkspaceSection({ settings }: WorkspaceSectionProps) {
    const form = useForm({
        name: settings.name,
        timezone: settings.timezone,
        work_week_start: settings.workWeekStart,
        brand_color: settings.brandColor,
        working_hours_start: settings.workingHoursStart,
        working_hours_end: settings.workingHoursEnd,
        date_format: settings.dateFormat,
        currency: settings.currency,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.patch('/settings/workspace', {
            preserveScroll: true,
        });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Workspace Configuration</CardTitle>
                <CardDescription>
                    Configure your workspace settings, branding, and defaults
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Workspace Name */}
                    <div className="space-y-2">
                        <Label htmlFor="name">Workspace Name</Label>
                        <Input
                            id="name"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                        />
                        <InputError message={form.errors.name} />
                    </div>

                    {/* Timezone */}
                    <div className="space-y-2">
                        <Label htmlFor="timezone">Timezone</Label>
                        <Select value={form.data.timezone} onValueChange={(value) => form.setData('timezone', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="UTC">UTC</SelectItem>
                                <SelectItem value="America/New_York">Eastern Time</SelectItem>
                                <SelectItem value="America/Chicago">Central Time</SelectItem>
                                <SelectItem value="America/Los_Angeles">Pacific Time</SelectItem>
                                <SelectItem value="Europe/London">London</SelectItem>
                                <SelectItem value="Europe/Paris">Paris</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.timezone} />
                    </div>

                    {/* Work Week Start */}
                    <div className="space-y-2">
                        <Label htmlFor="work_week_start">Work Week Start</Label>
                        <Select value={form.data.work_week_start} onValueChange={(value) => form.setData('work_week_start', value)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="monday">Monday</SelectItem>
                                <SelectItem value="sunday">Sunday</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.work_week_start} />
                    </div>

                    {/* Brand Color */}
                    <div className="space-y-2">
                        <Label htmlFor="brand_color">Brand Color</Label>
                        <div className="flex gap-2">
                            <Input
                                id="brand_color"
                                type="color"
                                value={form.data.brand_color}
                                onChange={(e) => form.setData('brand_color', e.target.value)}
                                className="h-10 w-20"
                            />
                            <Input
                                value={form.data.brand_color}
                                onChange={(e) => form.setData('brand_color', e.target.value)}
                                placeholder="#4f46e5"
                            />
                        </div>
                        <InputError message={form.errors.brand_color} />
                    </div>

                    {/* Working Hours */}
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="working_hours_start">Working Hours Start</Label>
                            <Input
                                id="working_hours_start"
                                type="time"
                                value={form.data.working_hours_start}
                                onChange={(e) => form.setData('working_hours_start', e.target.value)}
                            />
                            <InputError message={form.errors.working_hours_start} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="working_hours_end">Working Hours End</Label>
                            <Input
                                id="working_hours_end"
                                type="time"
                                value={form.data.working_hours_end}
                                onChange={(e) => form.setData('working_hours_end', e.target.value)}
                            />
                            <InputError message={form.errors.working_hours_end} />
                        </div>
                    </div>

                    {/* Date Format & Currency */}
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="date_format">Date Format</Label>
                            <Select value={form.data.date_format} onValueChange={(value) => form.setData('date_format', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="Y-m-d">YYYY-MM-DD</SelectItem>
                                    <SelectItem value="m/d/Y">MM/DD/YYYY</SelectItem>
                                    <SelectItem value="d/m/Y">DD/MM/YYYY</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.date_format} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="currency">Currency</Label>
                            <Select value={form.data.currency} onValueChange={(value) => form.setData('currency', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="USD">USD ($)</SelectItem>
                                    <SelectItem value="EUR">EUR (€)</SelectItem>
                                    <SelectItem value="GBP">GBP (£)</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.currency} />
                        </div>
                    </div>

                    {/* Submit Button */}
                    <div className="flex gap-2">
                        <Button type="submit" disabled={form.processing}>
                            {form.processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                        {form.recentlySuccessful && (
                            <p className="text-sm text-muted-foreground">Saved successfully</p>
                        )}
                    </div>
                </form>
            </CardContent>
        </Card>
    );
}
