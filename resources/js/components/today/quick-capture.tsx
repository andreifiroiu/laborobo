import { useState } from 'react';
import { Plus, FileText, StickyNote, CheckSquare, X } from 'lucide-react';
import type { QuickCaptureData, QuickCaptureType } from '@/types/today';

interface QuickCaptureProps {
    onQuickCapture?: (data: QuickCaptureData) => void;
}

const captureTypes: Array<{
    value: QuickCaptureType;
    label: string;
    icon: typeof CheckSquare;
}> = [
    { value: 'task', label: 'Task', icon: CheckSquare },
    { value: 'request', label: 'Request', icon: FileText },
    { value: 'note', label: 'Note', icon: StickyNote },
];

export function QuickCapture({ onQuickCapture }: QuickCaptureProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [selectedType, setSelectedType] = useState<QuickCaptureType>('task');
    const [content, setContent] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (content.trim()) {
            onQuickCapture?.({ type: selectedType, content: content.trim() });
            setContent('');
            setIsOpen(false);
        }
    };

    const handleClose = () => {
        setIsOpen(false);
        setContent('');
    };

    if (!isOpen) {
        return (
            <button
                onClick={() => setIsOpen(true)}
                className="fixed bottom-6 right-6 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-indigo-600 to-indigo-500 text-white shadow-lg transition-all hover:from-indigo-700 hover:to-indigo-600 hover:shadow-xl"
                aria-label="Quick capture"
            >
                <Plus className="h-6 w-6 transition-transform group-hover:rotate-90" />
            </button>
        );
    }

    return (
        <div className="fixed bottom-6 right-6 z-50 w-96 max-w-[calc(100vw-3rem)]">
            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <div className="flex items-center justify-between border-b border-slate-200 p-4 dark:border-slate-800">
                    <h3 className="text-lg font-semibold text-slate-900 dark:text-white">Quick Capture</h3>
                    <button
                        onClick={handleClose}
                        className="rounded-lg p-1 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 dark:hover:bg-slate-800 dark:hover:text-slate-300"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4 p-4">
                    {/* Type selector */}
                    <div className="flex gap-2">
                        {captureTypes.map((type) => {
                            const Icon = type.icon;
                            const isSelected = selectedType === type.value;
                            return (
                                <button
                                    key={type.value}
                                    type="button"
                                    onClick={() => setSelectedType(type.value)}
                                    className={`flex flex-1 items-center justify-center gap-2 rounded-lg border-2 px-3 py-2 text-sm font-medium transition-colors ${
                                        isSelected
                                            ? 'border-indigo-500 bg-indigo-100 text-indigo-700 dark:border-indigo-500 dark:bg-indigo-950/30 dark:text-indigo-400'
                                            : 'border-transparent bg-slate-50 text-slate-600 hover:bg-slate-100 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700'
                                    }`}
                                >
                                    <Icon className="h-4 w-4" />
                                    {type.label}
                                </button>
                            );
                        })}
                    </div>

                    {/* Input */}
                    <textarea
                        value={content}
                        onChange={(e) => setContent(e.target.value)}
                        placeholder={`Enter your ${selectedType}...`}
                        className="h-32 w-full resize-none rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder:text-slate-500 dark:focus:ring-indigo-400"
                        autoFocus
                    />

                    {/* Actions */}
                    <div className="flex gap-2">
                        <button
                            type="button"
                            onClick={handleClose}
                            className="flex-1 rounded-lg bg-slate-100 px-4 py-2 font-medium text-slate-700 transition-colors hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={!content.trim()}
                            className="flex-1 rounded-lg bg-indigo-600 px-4 py-2 font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        >
                            Capture
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
