interface ProgressBarProps {
    progress: number;
    className?: string;
}

export function ProgressBar({ progress, className = '' }: ProgressBarProps) {
    const percentage = Math.min(100, Math.max(0, progress));

    return (
        <div className={`h-2 bg-muted rounded-full overflow-hidden ${className}`}>
            <div
                className="h-full bg-primary rounded-full transition-all duration-300"
                style={{ width: `${percentage}%` }}
            />
        </div>
    );
}
