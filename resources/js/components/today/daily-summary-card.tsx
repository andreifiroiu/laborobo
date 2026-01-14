import { Sparkles, RefreshCw } from 'lucide-react';
import type { TodayDailySummary } from '@/types/today';

interface DailySummaryCardProps {
    summary: TodayDailySummary;
    onRefresh?: () => void;
}

export function DailySummaryCard({ summary, onRefresh }: DailySummaryCardProps) {
    if (!summary.summary) {
        return (
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 p-6 shadow-lg">
                <div className="flex items-center justify-center py-8">
                    <div className="text-center">
                        <Sparkles className="mx-auto mb-3 h-8 w-8 text-white/60" />
                        <p className="text-white/80">No summary available yet</p>
                        <p className="mt-1 text-sm text-white/60">Check back later for your daily priorities</p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 dark:from-indigo-600 dark:to-indigo-700 p-6 shadow-lg">
            {/* Decorative background pattern */}
            <div className="pointer-events-none absolute inset-0 opacity-10">
                <div className="absolute right-0 top-0 h-64 w-64 translate-x-20 -translate-y-20 transform rounded-full bg-white blur-3xl" />
                <div className="absolute bottom-0 left-0 h-48 w-48 -translate-x-10 translate-y-10 transform rounded-full bg-white blur-3xl" />
            </div>

            <div className="relative">
                <div className="mb-4 flex items-start justify-between">
                    <div className="flex items-center gap-2">
                        <Sparkles className="h-6 w-6 text-emerald-300" />
                        <h2 className="text-lg font-semibold text-white">Daily Summary</h2>
                    </div>
                    <button
                        onClick={() => onRefresh?.()}
                        className="rounded-lg bg-white/10 p-2 text-white transition-colors hover:bg-white/20"
                        aria-label="Refresh summary"
                    >
                        <RefreshCw className="h-4 w-4" />
                    </button>
                </div>

                <p className="mb-6 leading-relaxed text-white/90">{summary.summary}</p>

                {summary.priorities.length > 0 && (
                    <div className="mb-4 space-y-3">
                        <h3 className="text-sm font-medium text-white/80">Top Priorities</h3>
                        <ul className="space-y-2">
                            {summary.priorities.map((priority, index) => (
                                <li key={index} className="flex items-start gap-3 text-sm text-white/90">
                                    <span className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-emerald-400/20 text-xs font-medium text-emerald-300">
                                        {index + 1}
                                    </span>
                                    <span className="flex-1">{priority}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {summary.suggestedFocus && (
                    <div className="border-t border-white/20 pt-4">
                        <p className="text-sm italic text-white/80">{summary.suggestedFocus}</p>
                    </div>
                )}
            </div>
        </div>
    );
}
