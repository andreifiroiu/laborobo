import { CheckCircle2, Clock, AlertCircle } from 'lucide-react'
import type { Metrics } from '@/../product/sections/today/types'

interface MetricsBarProps {
  metrics: Metrics
}

export function MetricsBar({ metrics }: MetricsBarProps) {
  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
      <MetricCard
        icon={<CheckCircle2 className="w-5 h-5" />}
        label="Completed Today"
        value={metrics.tasksCompletedToday}
        color="emerald"
      />
      <MetricCard
        icon={<CheckCircle2 className="w-5 h-5" />}
        label="Completed This Week"
        value={metrics.tasksCompletedThisWeek}
        color="emerald"
      />
      <MetricCard
        icon={<AlertCircle className="w-5 h-5" />}
        label="Approvals Pending"
        value={metrics.approvalsPending}
        color="indigo"
      />
      <MetricCard
        icon={<Clock className="w-5 h-5" />}
        label="Hours Today"
        value={metrics.hoursLoggedToday}
        suffix="h"
        color="slate"
      />
      <MetricCard
        icon={<AlertCircle className="w-5 h-5" />}
        label="Active Blockers"
        value={metrics.activeBlockers}
        color="slate"
      />
    </div>
  )
}

interface MetricCardProps {
  icon: React.ReactNode
  label: string
  value: number
  suffix?: string
  color: 'emerald' | 'indigo' | 'slate'
}

function MetricCard({ icon, label, value, suffix = '', color }: MetricCardProps) {
  const colorClasses = {
    emerald: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30',
    indigo: 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/30',
    slate: 'text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-900/30',
  }

  return (
    <div className="rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-4">
      <div className={`inline-flex items-center justify-center w-10 h-10 rounded-lg ${colorClasses[color]} mb-3`}>
        {icon}
      </div>
      <div className="text-2xl font-bold text-slate-900 dark:text-white">
        {value}
        {suffix}
      </div>
      <div className="text-sm text-slate-600 dark:text-slate-400">{label}</div>
    </div>
  )
}
