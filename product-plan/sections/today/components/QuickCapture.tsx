import { useState } from 'react'
import { Plus, FileText, StickyNote, CheckSquare } from 'lucide-react'
import type { QuickCaptureData, QuickCaptureType } from '@/../product/sections/today/types'

interface QuickCaptureProps {
  onQuickCapture?: (data: QuickCaptureData) => void
}

export function QuickCapture({ onQuickCapture }: QuickCaptureProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [selectedType, setSelectedType] = useState<QuickCaptureType>('task')
  const [content, setContent] = useState('')

  const captureTypes = [
    { value: 'task' as const, label: 'Task', icon: CheckSquare, color: 'emerald' },
    { value: 'request' as const, label: 'Request', icon: FileText, color: 'indigo' },
    { value: 'note' as const, label: 'Note', icon: StickyNote, color: 'amber' },
  ]

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (content.trim()) {
      onQuickCapture?.({ type: selectedType, content: content.trim() })
      setContent('')
      setIsOpen(false)
    }
  }

  if (!isOpen) {
    return (
      <button
        onClick={() => setIsOpen(true)}
        className="fixed bottom-6 right-6 z-50 w-14 h-14 rounded-full bg-gradient-to-br from-indigo-600 to-indigo-500 hover:from-indigo-700 hover:to-indigo-600 text-white shadow-lg hover:shadow-xl transition-all flex items-center justify-center group"
        aria-label="Quick capture"
      >
        <Plus className="w-6 h-6 group-hover:rotate-90 transition-transform" />
      </button>
    )
  }

  return (
    <div className="fixed bottom-6 right-6 z-50 w-96 max-w-[calc(100vw-3rem)]">
      <div className="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden">
        <div className="p-4 border-b border-slate-200 dark:border-slate-800">
          <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Quick Capture</h3>
        </div>

        <form onSubmit={handleSubmit} className="p-4 space-y-4">
          {/* Type selector */}
          <div className="flex gap-2">
            {captureTypes.map((type) => {
              const Icon = type.icon
              const isSelected = selectedType === type.value
              return (
                <button
                  key={type.value}
                  type="button"
                  onClick={() => setSelectedType(type.value)}
                  className={`flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-colors ${
                    isSelected
                      ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-400 border-2 border-indigo-500 dark:border-indigo-500'
                      : 'bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border-2 border-transparent hover:bg-slate-100 dark:hover:bg-slate-700'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  {type.label}
                </button>
              )
            })}
          </div>

          {/* Input */}
          <textarea
            value={content}
            onChange={(e) => setContent(e.target.value)}
            placeholder={`Enter your ${selectedType}...`}
            className="w-full h-32 px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 resize-none"
            autoFocus
          />

          {/* Actions */}
          <div className="flex gap-2">
            <button
              type="button"
              onClick={() => {
                setIsOpen(false)
                setContent('')
              }}
              className="flex-1 px-4 py-2 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={!content.trim()}
              className="flex-1 px-4 py-2 rounded-lg bg-indigo-600 dark:bg-indigo-500 text-white font-medium hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Capture
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
