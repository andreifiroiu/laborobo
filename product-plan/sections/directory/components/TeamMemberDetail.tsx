import type { TeamMember, Project } from '@/../product/sections/directory/types'

interface TeamMemberDetailProps {
  teamMember: TeamMember
  assignedProjects: Project[]
  onClose?: () => void
  onEdit?: () => void
  onDelete?: () => void
  onViewProject?: (id: string) => void
}

export function TeamMemberDetail({
  teamMember,
  assignedProjects,
  onClose,
  onEdit,
  onDelete,
  onViewProject,
}: TeamMemberDetailProps) {
  const getProficiencyLabel = (level: number) => {
    const labels: Record<number, string> = {
      1: 'Basic',
      2: 'Intermediate',
      3: 'Advanced',
    }
    return labels[level] || 'Unknown'
  }

  const getProficiencyColor = (level: number) => {
    const colors: Record<number, string> = {
      1: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
      2: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
      3: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    }
    return colors[level] || colors[1]
  }

  const capacityPercentage =
    (teamMember.currentWorkloadHours / teamMember.capacityHoursPerWeek) * 100
  const availableHours =
    teamMember.capacityHoursPerWeek - teamMember.currentWorkloadHours

  const getCapacityColor = () => {
    if (capacityPercentage >= 90) return 'text-red-600 dark:text-red-400'
    if (capacityPercentage >= 75) return 'text-amber-600 dark:text-amber-400'
    return 'text-emerald-600 dark:text-emerald-400'
  }

  const getCapacityBarColor = () => {
    if (capacityPercentage >= 90) return 'bg-red-600 dark:bg-red-500'
    if (capacityPercentage >= 75) return 'bg-amber-600 dark:bg-amber-500'
    return 'bg-emerald-600 dark:bg-emerald-500'
  }

  return (
    <div className="h-full flex flex-col bg-white dark:bg-slate-900">
      {/* Header */}
      <div className="flex-shrink-0 px-6 py-4 border-b border-slate-200 dark:border-slate-800">
        <div className="flex items-start justify-between">
          <div className="flex items-start gap-4 flex-1 min-w-0">
            <img
              src={teamMember.avatar}
              alt={teamMember.name}
              className="w-16 h-16 rounded-full object-cover flex-shrink-0"
            />
            <div className="flex-1 min-w-0">
              <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 mb-1 truncate">
                {teamMember.name}
              </h2>
              <p className="text-slate-600 dark:text-slate-400 mb-2">{teamMember.role}</p>
              <div className="flex items-center gap-2">
                <span
                  className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                    teamMember.status === 'active'
                      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                      : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                  }`}
                >
                  {teamMember.status}
                </span>
                <span className="text-sm text-slate-500 dark:text-slate-400">
                  Joined {new Date(teamMember.joinedAt).toLocaleDateString()}
                </span>
              </div>
            </div>
          </div>
          <div className="flex items-center gap-2 ml-4">
            <button
              onClick={onEdit}
              className="p-2 text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
              title="Edit"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
            </button>
            <button
              onClick={onDelete}
              className="p-2 text-slate-600 dark:text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
              title="Delete"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
            <button
              onClick={onClose}
              className="p-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-50 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
              title="Close"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-y-auto px-6 py-6">
        {/* Capacity Overview */}
        <section className="mb-8">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Capacity Overview
          </h3>
          <div className="bg-slate-50 dark:bg-slate-800/50 rounded-lg p-4">
            <div className="flex items-center justify-between mb-3">
              <div>
                <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">
                  Current Workload
                </p>
                <p className={`text-2xl font-semibold ${getCapacityColor()}`}>
                  {teamMember.currentWorkloadHours}h / {teamMember.capacityHoursPerWeek}h
                </p>
              </div>
              <div className="text-right">
                <p className="text-sm text-slate-600 dark:text-slate-400 mb-1">Available</p>
                <p className="text-2xl font-semibold text-slate-900 dark:text-slate-50">
                  {availableHours}h
                </p>
              </div>
            </div>
            <div className="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-3">
              <div
                className={`h-3 rounded-full transition-all ${getCapacityBarColor()}`}
                style={{
                  width: `${Math.min(capacityPercentage, 100)}%`,
                }}
              />
            </div>
            <div className="flex items-center justify-between mt-2">
              <p className="text-xs text-slate-500 dark:text-slate-400">
                {capacityPercentage.toFixed(0)}% utilized
              </p>
              {capacityPercentage >= 90 && (
                <p className="text-xs text-red-600 dark:text-red-400 font-medium">
                  At capacity
                </p>
              )}
              {capacityPercentage >= 75 && capacityPercentage < 90 && (
                <p className="text-xs text-amber-600 dark:text-amber-400 font-medium">
                  Near capacity
                </p>
              )}
            </div>
          </div>
        </section>

        {/* Contact Information */}
        <section className="mb-8">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Contact Information
          </h3>
          <div className="space-y-3">
            <div className="flex items-start">
              <svg
                className="w-5 h-5 text-slate-400 dark:text-slate-500 mr-3 mt-0.5 flex-shrink-0"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
              <div>
                <p className="text-sm text-slate-500 dark:text-slate-400">Email</p>
                <a
                  href={`mailto:${teamMember.email}`}
                  className="text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                  {teamMember.email}
                </a>
              </div>
            </div>
            {teamMember.timezone && (
              <div className="flex items-start">
                <svg
                  className="w-5 h-5 text-slate-400 dark:text-slate-500 mr-3 mt-0.5 flex-shrink-0"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                  />
                </svg>
                <div>
                  <p className="text-sm text-slate-500 dark:text-slate-400">Timezone</p>
                  <p className="text-slate-900 dark:text-slate-50">{teamMember.timezone}</p>
                </div>
              </div>
            )}
          </div>
        </section>

        {/* Skills */}
        <section className="mb-8">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Skills ({teamMember.skills.length})
          </h3>
          <div className="space-y-3">
            {teamMember.skills.map((skill) => (
              <div
                key={skill.name}
                className="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg"
              >
                <span className="text-slate-900 dark:text-slate-50 font-medium">
                  {skill.name}
                </span>
                <div className="flex items-center gap-2">
                  <span
                    className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getProficiencyColor(skill.proficiency)}`}
                  >
                    {getProficiencyLabel(skill.proficiency)}
                  </span>
                  <div className="flex gap-1">
                    {[1, 2, 3].map((level) => (
                      <div
                        key={level}
                        className={`w-2 h-2 rounded-full ${
                          level <= skill.proficiency
                            ? 'bg-emerald-500 dark:bg-emerald-400'
                            : 'bg-slate-200 dark:bg-slate-700'
                        }`}
                      />
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </section>

        {/* Assigned Projects */}
        {assignedProjects.length > 0 && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Assigned Projects ({assignedProjects.length})
            </h3>
            <div className="space-y-2">
              {assignedProjects.map((project) => (
                <button
                  key={project.id}
                  onClick={() => onViewProject?.(project.id)}
                  className="w-full flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left"
                >
                  <p className="font-medium text-slate-900 dark:text-slate-50 truncate">
                    {project.name}
                  </p>
                  <svg
                    className="w-5 h-5 text-slate-400 ml-2 flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M9 5l7 7-7 7"
                    />
                  </svg>
                </button>
              ))}
            </div>
          </section>
        )}

        {/* Tags */}
        {teamMember.tags.length > 0 && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Tags
            </h3>
            <div className="flex flex-wrap gap-2">
              {teamMember.tags.map((tag) => (
                <span
                  key={tag}
                  className="inline-flex items-center px-3 py-1 rounded-full text-sm bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                >
                  {tag}
                </span>
              ))}
            </div>
          </section>
        )}

        {/* Metadata */}
        <section>
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Metadata
          </h3>
          <dl className="space-y-2 text-sm">
            <div className="flex justify-between">
              <dt className="text-slate-500 dark:text-slate-400">Joined</dt>
              <dd className="text-slate-900 dark:text-slate-50">
                {new Date(teamMember.joinedAt).toLocaleDateString()}
              </dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-slate-500 dark:text-slate-400">Member ID</dt>
              <dd className="text-slate-900 dark:text-slate-50 font-mono text-xs">
                {teamMember.id}
              </dd>
            </div>
          </dl>
        </section>
      </div>
    </div>
  )
}
