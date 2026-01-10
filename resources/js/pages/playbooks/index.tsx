import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { PlaybooksView } from './components/playbooks-view';
import { PlaybookDetailPanel } from './components/playbook-detail-panel';
import { PlaybookFormPanel } from './components/playbook-form-panel';
import type {
    PlaybooksPageProps,
    PlaybookTab,
    Playbook,
    PlaybookType,
} from '@/types/playbooks';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Playbooks', href: '/playbooks' }];

export default function Playbooks({ playbooks, workOrders }: PlaybooksPageProps) {
    // Tab state
    const [activeTab, setActiveTab] = useState<PlaybookTab>('all');

    // Detail panel state
    const [selectedPlaybookId, setSelectedPlaybookId] = useState<string | null>(null);

    // Form panel state
    const [formOpen, setFormOpen] = useState(false);
    const [playbookToEdit, setPlaybookToEdit] = useState<Playbook | undefined>(
        undefined
    );
    const [createType, setCreateType] = useState<PlaybookType>('sop');

    // Search and filter state
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedTags, setSelectedTags] = useState<string[]>([]);
    const [sortBy, setSortBy] = useState<'recent' | 'popular' | 'alphabetical'>(
        'recent'
    );

    // Find selected playbook
    const selectedPlaybook = playbooks.find((p) => p.id === selectedPlaybookId);

    // Handle create new playbook
    const handleCreate = (type: PlaybookType) => {
        setCreateType(type);
        setPlaybookToEdit(undefined);
        setFormOpen(true);
    };

    // Handle edit playbook
    const handleEdit = (playbook: Playbook) => {
        setPlaybookToEdit(playbook);
        setFormOpen(true);
        setSelectedPlaybookId(null); // Close detail panel
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Playbooks" />

            <PlaybooksView
                playbooks={playbooks}
                workOrders={workOrders}
                activeTab={activeTab}
                onTabChange={setActiveTab}
                searchQuery={searchQuery}
                onSearchChange={setSearchQuery}
                selectedTags={selectedTags}
                onTagsChange={setSelectedTags}
                sortBy={sortBy}
                onSortChange={setSortBy}
                onViewPlaybook={setSelectedPlaybookId}
                onCreatePlaybook={handleCreate}
            />

            {/* Detail panel */}
            {selectedPlaybook && (
                <PlaybookDetailPanel
                    playbook={selectedPlaybook}
                    workOrders={workOrders}
                    onClose={() => setSelectedPlaybookId(null)}
                    onEdit={handleEdit}
                />
            )}

            {/* Form panel */}
            {formOpen && (
                <PlaybookFormPanel
                    open={formOpen}
                    playbook={playbookToEdit}
                    type={playbookToEdit?.type ?? createType}
                    onClose={() => setFormOpen(false)}
                />
            )}
        </AppLayout>
    );
}
