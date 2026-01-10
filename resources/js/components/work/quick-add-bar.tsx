import { useState } from 'react';
import { Plus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { QuickAddData } from '@/types/work';

interface QuickAddBarProps {
    onQuickAdd: (data: QuickAddData) => void;
}

export function QuickAddBar({ onQuickAdd }: QuickAddBarProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [title, setTitle] = useState('');

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (title.trim()) {
            onQuickAdd({ type: 'project', title: title.trim() });
            setTitle('');
            setIsOpen(false);
        }
    };

    if (!isOpen) {
        return (
            <div className="p-4 border-b border-sidebar-border/70 dark:border-sidebar-border">
                <Button
                    variant="ghost"
                    onClick={() => setIsOpen(true)}
                    className="text-primary hover:text-primary/80"
                >
                    <Plus className="mr-2 h-4 w-4" />
                    New Project
                </Button>
            </div>
        );
    }

    return (
        <div className="p-4 border-b border-sidebar-border/70 dark:border-sidebar-border bg-primary/5">
            <form onSubmit={handleSubmit} className="flex items-center gap-2">
                <Input
                    type="text"
                    value={title}
                    onChange={(e) => setTitle(e.target.value)}
                    placeholder="Project name..."
                    className="flex-1"
                    autoFocus
                />
                <Button type="submit" size="sm">
                    Add
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                        setIsOpen(false);
                        setTitle('');
                    }}
                >
                    <X className="h-4 w-4" />
                </Button>
            </form>
            <p className="text-xs text-muted-foreground mt-2">
                Press Enter to add, or click to fill in details later
            </p>
        </div>
    );
}
