import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import type { InboxSearchBarProps } from '@/types/inbox';

export function InboxSearchBar({ searchQuery, onSearchChange }: InboxSearchBarProps) {
    return (
        <div className="flex-1 relative">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <Input
                type="text"
                value={searchQuery}
                onChange={(e) => onSearchChange(e.target.value)}
                placeholder="Search inbox items..."
                className="pl-10"
            />
        </div>
    );
}
