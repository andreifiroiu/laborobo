import type { Party, Contact, Project } from '@/../product/sections/directory/types'

interface PartyDetailProps {
  party: Party
  linkedContacts: Contact[]
  linkedProjects: Project[]
  onClose?: () => void
  onEdit?: () => void
  onDelete?: () => void
  onViewContact?: (id: string) => void
  onViewProject?: (id: string) => void
}

export function PartyDetail({
  party,
  linkedContacts,
  linkedProjects,
  onClose,
  onEdit,
  onDelete,
  onViewContact,
  onViewProject,
}: PartyDetailProps) {
  const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
      client: 'Client',
      vendor: 'Vendor',
      partner: 'Partner',
      'internal-department': 'Internal Department',
    }
    return labels[type] || type
  }

  const getTypeColor = (type: string) => {
    const colors: Record<string, string> = {
      client: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
      vendor: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
      partner: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
      'internal-department':
        'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    }
    return colors[type] || colors['internal-department']
  }

  return (
    <div className="h-full flex flex-col bg-white dark:bg-slate-900">
      {/* Header */}
      <div className="flex-shrink-0 px-6 py-4 border-b border-slate-200 dark:border-slate-800">
        <div className="flex items-start justify-between">
          <div className="flex-1 min-w-0">
            <div className="flex items-center gap-3 mb-2">
              <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 truncate">
                {party.name}
              </h2>
              <span
                className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getTypeColor(party.type)}`}
              >
                {getTypeLabel(party.type)}
              </span>
            </div>
            <div className="flex items-center gap-2">
              <span
                className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                  party.status === 'active'
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                }`}
              >
                {party.status}
              </span>
              <span className="text-sm text-slate-500 dark:text-slate-400">
                Last activity: {new Date(party.lastActivity).toLocaleDateString()}
              </span>
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
                  href={`mailto:${party.email}`}
                  className="text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                  {party.email}
                </a>
              </div>
            </div>
            {party.phone && (
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
                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                  />
                </svg>
                <div>
                  <p className="text-sm text-slate-500 dark:text-slate-400">Phone</p>
                  <a
                    href={`tel:${party.phone}`}
                    className="text-slate-900 dark:text-slate-50"
                  >
                    {party.phone}
                  </a>
                </div>
              </div>
            )}
            {party.website && (
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
                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"
                  />
                </svg>
                <div>
                  <p className="text-sm text-slate-500 dark:text-slate-400">Website</p>
                  <a
                    href={party.website}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-indigo-600 dark:text-indigo-400 hover:underline"
                  >
                    {party.website}
                  </a>
                </div>
              </div>
            )}
            {party.address && (
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
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                  />
                  <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth={2}
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
                <div>
                  <p className="text-sm text-slate-500 dark:text-slate-400">Address</p>
                  <p className="text-slate-900 dark:text-slate-50">{party.address}</p>
                </div>
              </div>
            )}
          </div>
        </section>

        {/* Primary Contact */}
        <section className="mb-8">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Primary Contact
          </h3>
          <div className="flex items-center text-slate-900 dark:text-slate-50">
            <svg
              className="w-5 h-5 text-slate-400 dark:text-slate-500 mr-3"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
            <button
              onClick={() => onViewContact?.(party.primaryContactId)}
              className="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
            >
              {party.primaryContactName}
            </button>
          </div>
        </section>

        {/* Linked Contacts */}
        {linkedContacts.length > 0 && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Contacts ({linkedContacts.length})
            </h3>
            <div className="space-y-2">
              {linkedContacts.map((contact) => (
                <button
                  key={contact.id}
                  onClick={() => onViewContact?.(contact.id)}
                  className="w-full flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left"
                >
                  <div className="flex-1 min-w-0">
                    <p className="font-medium text-slate-900 dark:text-slate-50 truncate">
                      {contact.name}
                    </p>
                    <p className="text-sm text-slate-500 dark:text-slate-400 truncate">
                      {contact.title} • {contact.role}
                    </p>
                  </div>
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

        {/* Linked Projects */}
        {linkedProjects.length > 0 && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Projects ({linkedProjects.length})
            </h3>
            <div className="space-y-2">
              {linkedProjects.map((project) => (
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

        {/* Notes */}
        {party.notes && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Notes
            </h3>
            <p className="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
              {party.notes}
            </p>
          </section>
        )}

        {/* Tags */}
        {party.tags.length > 0 && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Tags
            </h3>
            <div className="flex flex-wrap gap-2">
              {party.tags.map((tag) => (
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
              <dt className="text-slate-500 dark:text-slate-400">Created</dt>
              <dd className="text-slate-900 dark:text-slate-50">
                {new Date(party.createdAt).toLocaleDateString()}
              </dd>
            </div>
            <div className="flex justify-between">
              <dt className="text-slate-500 dark:text-slate-400">Last Activity</dt>
              <dd className="text-slate-900 dark:text-slate-50">
                {new Date(party.lastActivity).toLocaleDateString()}
              </dd>
            </div>
          </dl>
        </section>
      </div>
    </div>
  )
}
