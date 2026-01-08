import type { Contact, Party } from '@/../product/sections/directory/types'

interface ContactDetailProps {
  contact: Contact
  party: Party
  onClose?: () => void
  onEdit?: () => void
  onDelete?: () => void
  onViewParty?: (id: string) => void
}

export function ContactDetail({
  contact,
  party,
  onClose,
  onEdit,
  onDelete,
  onViewParty,
}: ContactDetailProps) {
  const getEngagementColor = (type: string) => {
    const colors: Record<string, string> = {
      requester: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
      approver: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
      stakeholder:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
      billing: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    }
    return colors[type] || colors.requester
  }

  const getCommunicationIcon = (pref: string) => {
    if (pref === 'email') {
      return (
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
          />
        </svg>
      )
    }
    if (pref === 'phone') {
      return (
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
          />
        </svg>
      )
    }
    return (
      <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path
          strokeLinecap="round"
          strokeLinejoin="round"
          strokeWidth={2}
          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
        />
      </svg>
    )
  }

  return (
    <div className="h-full flex flex-col bg-white dark:bg-slate-900">
      {/* Header */}
      <div className="flex-shrink-0 px-6 py-4 border-b border-slate-200 dark:border-slate-800">
        <div className="flex items-start justify-between">
          <div className="flex-1 min-w-0">
            <h2 className="text-2xl font-semibold text-slate-900 dark:text-slate-50 mb-2 truncate">
              {contact.name}
            </h2>
            <div className="flex items-center gap-2 flex-wrap">
              {contact.title && (
                <span className="text-sm text-slate-600 dark:text-slate-400">
                  {contact.title}
                </span>
              )}
              <span
                className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${
                  contact.status === 'active'
                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                    : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                }`}
              >
                {contact.status}
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
        {/* Party Association */}
        <section className="mb-8">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Organization
          </h3>
          <button
            onClick={() => onViewParty?.(party.id)}
            className="w-full flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-800/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left"
          >
            <div>
              <p className="font-medium text-slate-900 dark:text-slate-50">{party.name}</p>
              <p className="text-sm text-slate-500 dark:text-slate-400 capitalize">
                {party.type.replace('-', ' ')}
              </p>
            </div>
            <svg
              className="w-5 h-5 text-slate-400 flex-shrink-0"
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
                  href={`mailto:${contact.email}`}
                  className="text-indigo-600 dark:text-indigo-400 hover:underline"
                >
                  {contact.email}
                </a>
              </div>
            </div>
            {contact.phone && (
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
                    href={`tel:${contact.phone}`}
                    className="text-slate-900 dark:text-slate-50"
                  >
                    {contact.phone}
                  </a>
                </div>
              </div>
            )}
          </div>
        </section>

        {/* Role & Engagement */}
        <section className="mb-8">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Role & Engagement
          </h3>
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-slate-600 dark:text-slate-400">Role</span>
              <span className="text-slate-900 dark:text-slate-50 font-medium">
                {contact.role}
              </span>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-slate-600 dark:text-slate-400">Engagement Type</span>
              <span
                className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getEngagementColor(contact.engagementType)}`}
              >
                {contact.engagementType}
              </span>
            </div>
          </div>
        </section>

        {/* Communication Preferences */}
        <section className="mb-8">
          <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
            Communication Preferences
          </h3>
          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <span className="text-slate-600 dark:text-slate-400">Preferred Method</span>
              <span className="flex items-center gap-2 text-slate-900 dark:text-slate-50 font-medium capitalize">
                {getCommunicationIcon(contact.communicationPreference)}
                {contact.communicationPreference}
              </span>
            </div>
            {contact.timezone && (
              <div className="flex items-center justify-between">
                <span className="text-slate-600 dark:text-slate-400">Timezone</span>
                <span className="text-slate-900 dark:text-slate-50 font-medium">
                  {contact.timezone}
                </span>
              </div>
            )}
          </div>
        </section>

        {/* Notes */}
        {contact.notes && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Notes
            </h3>
            <p className="text-slate-700 dark:text-slate-300 whitespace-pre-wrap">
              {contact.notes}
            </p>
          </section>
        )}

        {/* Tags */}
        {contact.tags.length > 0 && (
          <section className="mb-8">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-slate-50 uppercase tracking-wider mb-4">
              Tags
            </h3>
            <div className="flex flex-wrap gap-2">
              {contact.tags.map((tag) => (
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
              <dt className="text-slate-500 dark:text-slate-400">Added</dt>
              <dd className="text-slate-900 dark:text-slate-50">
                {new Date(contact.createdAt).toLocaleDateString()}
              </dd>
            </div>
          </dl>
        </section>
      </div>
    </div>
  )
}
