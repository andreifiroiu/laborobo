import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import { Bot } from 'lucide-react';

interface DispatcherToggleProps {
    /** Whether the dispatcher agent is enabled */
    checked: boolean;
    /** Callback when toggle state changes */
    onCheckedChange: (checked: boolean) => void;
    /** Whether the toggle is disabled */
    disabled?: boolean;
}

/**
 * Toggle switch for enabling the Dispatcher Agent on work order creation.
 * When enabled, the agent analyzes provided details and suggests routing after creation.
 */
export function DispatcherToggle({
    checked,
    onCheckedChange,
    disabled = false,
}: DispatcherToggleProps) {
    return (
        <div className="flex items-center justify-between gap-4 rounded-lg border border-border bg-muted/30 p-4">
            <div className="flex items-center gap-3">
                <div className="flex h-9 w-9 items-center justify-center rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300">
                    <Bot className="h-5 w-5" />
                </div>
                <div className="space-y-0.5">
                    <Label
                        htmlFor="dispatcher-toggle"
                        className="cursor-pointer text-sm font-medium"
                    >
                        Enable Dispatcher Agent
                    </Label>
                    <p className="text-xs text-muted-foreground">
                        Get AI-powered routing recommendations after creation
                    </p>
                </div>
            </div>
            <Switch
                id="dispatcher-toggle"
                checked={checked}
                onCheckedChange={onCheckedChange}
                disabled={disabled}
                aria-label="Enable Dispatcher Agent for routing recommendations"
            />
        </div>
    );
}
