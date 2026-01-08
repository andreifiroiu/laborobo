import { useState } from 'react'
import { Plus, X } from 'lucide-react'
import type { QuickAddData } from '@/../product/sections/work/types'

interface QuickAddBarProps {
  onQuickAdd?: (data: QuickAddData) => void
}

export function QuickAddBar({ onQuickAdd }: QuickAddBarProps) {
  const [isOpen, setIsOpen] = useState(false)
  const [title, setTitle] = useState('')

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (title.trim()) {
      onQuickAdd?.({ type: 'project', title: title.trim() })
      setTitle('')
      setIsOpen(false)
    }
  }

  if (!isOpen) {
    return (
      <div className="p-4 border-b border-slate-200 dark:border-slate-800">
        <button
          onClick={() => setIsOpen(true)}
          className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 rounded-lg transition-colors"
        >
          <Plus size={16} />
          New Project
        </button>
      </div>
    )
  }

  return (
    <div className="p-4 border-b border-slate-200 dark:border-slate-800 bg-indigo-50 dark:bg-indigo-950/20">
      <form onSubmit={handleSubmit} className="flex items-center gap-2">
        <input
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="Project name..."
          className="flex-1 px-3 py-2 bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"
          autoFocus
        />
        <button
          type="submit"
          className="px-4 py-2 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-colors"
        >
          Add
        </button>
        <button
          type="button"
          onClick={() => {
            setIsOpen(false)
            setTitle('')
          }}
          className="p-2 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 transition-colors"
        >
          <X size={16} />
        </button>
      </form>
      <p className="text-xs text-slate-500 dark:text-slate-400 mt-2">
        Press Enter to add, or click to fill in details later
      </p>
    </div>
  )
}
