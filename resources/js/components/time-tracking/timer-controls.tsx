import { useState, useEffect, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { Play, Square } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { ActiveTimer } from '@/types';
import { cn } from '@/lib/utils';

interface TimerControlsProps {
    taskId: number;
    activeTimerForTask?: ActiveTimer | null;
    isBillable?: boolean;
    className?: string;
}

/**
 * Formats elapsed seconds into HH:MM:SS format.
 */
function formatElapsedTime(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return [
        hours.toString().padStart(2, '0'),
        minutes.toString().padStart(2, '0'),
        secs.toString().padStart(2, '0'),
    ].join(':');
}

/**
 * Calculates elapsed seconds from a start timestamp.
 */
function calculateElapsedSeconds(startedAt: string): number {
    const startTime = new Date(startedAt).getTime();
    const now = Date.now();
    return Math.floor((now - startTime) / 1000);
}

export function TimerControls({
    taskId,
    activeTimerForTask,
    isBillable = true,
    className,
}: TimerControlsProps) {
    const [isProcessing, setIsProcessing] = useState(false);
    const [elapsedSeconds, setElapsedSeconds] = useState(() => {
        if (activeTimerForTask?.startedAt) {
            return calculateElapsedSeconds(activeTimerForTask.startedAt);
        }
        return 0;
    });

    const isTimerActive = Boolean(activeTimerForTask);

    // Update elapsed time every second when timer is active
    useEffect(() => {
        if (!activeTimerForTask?.startedAt) {
            setElapsedSeconds(0);
            return;
        }

        // Calculate initial elapsed time
        setElapsedSeconds(calculateElapsedSeconds(activeTimerForTask.startedAt));

        // Set up interval to update every second
        const interval = setInterval(() => {
            setElapsedSeconds(calculateElapsedSeconds(activeTimerForTask.startedAt));
        }, 1000);

        return () => clearInterval(interval);
    }, [activeTimerForTask?.startedAt]);

    const handleStartTimer = useCallback(() => {
        setIsProcessing(true);
        router.post(
            `/work/tasks/${taskId}/timer/start`,
            { is_billable: isBillable },
            {
                preserveScroll: true,
                onFinish: () => setIsProcessing(false),
            }
        );
    }, [taskId, isBillable]);

    const handleStopTimer = useCallback(() => {
        setIsProcessing(true);
        router.post(
            `/work/tasks/${taskId}/timer/stop`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setIsProcessing(false),
            }
        );
    }, [taskId]);

    if (isTimerActive) {
        return (
            <div className={cn('flex items-center gap-2', className)}>
                <span
                    data-testid="elapsed-time"
                    className="font-mono text-sm tabular-nums text-muted-foreground"
                >
                    {formatElapsedTime(elapsedSeconds)}
                </span>
                <Button
                    variant="destructive"
                    size="sm"
                    onClick={handleStopTimer}
                    disabled={isProcessing}
                    aria-label="Stop timer"
                >
                    <Square className="size-4" />
                    <span>Stop Timer</span>
                </Button>
            </div>
        );
    }

    return (
        <div className={cn('flex items-center gap-2', className)}>
            <Button
                variant="outline"
                size="sm"
                onClick={handleStartTimer}
                disabled={isProcessing}
                aria-label="Start timer"
            >
                <Play className="size-4" />
                <span>Start Timer</span>
            </Button>
        </div>
    );
}
