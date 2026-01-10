import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { DirectoryView } from './components/directory-view';
import { PartyDetailPanel } from './components/party-detail-panel';
import { ContactDetailPanel } from './components/contact-detail-panel';
import { TeamMemberDetailPanel } from './components/team-member-detail-panel';
import { PartyFormPanel } from './components/party-form-panel';
import { ContactFormPanel } from './components/contact-form-panel';
import { TeamMemberSkillsFormPanel } from './components/team-member-skills-form-panel';
import type {
    DirectoryPageProps,
    DirectoryTab,
    Party,
    Contact,
    TeamMember,
} from '@/types/directory';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Directory', href: '/directory' }];

export default function Directory({
    parties,
    contacts,
    teamMembers,
    projects,
}: DirectoryPageProps) {
    // Tab state
    const [activeTab, setActiveTab] = useState<DirectoryTab>('parties');

    // Selection state for detail panels
    const [selectedPartyId, setSelectedPartyId] = useState<string | null>(null);
    const [selectedContactId, setSelectedContactId] = useState<string | null>(null);
    const [selectedTeamMemberId, setSelectedTeamMemberId] = useState<string | null>(null);

    // Form panel state
    const [partyFormOpen, setPartyFormOpen] = useState(false);
    const [partyToEdit, setPartyToEdit] = useState<Party | undefined>(undefined);

    const [contactFormOpen, setContactFormOpen] = useState(false);
    const [contactToEdit, setContactToEdit] = useState<Contact | undefined>(undefined);

    const [teamMemberSkillsFormOpen, setTeamMemberSkillsFormOpen] = useState(false);
    const [teamMemberToEdit, setTeamMemberToEdit] = useState<TeamMember | undefined>(undefined);

    // Handlers - Party
    const handlePartyClick = (partyId: string) => {
        setSelectedPartyId(partyId);
    };

    const handlePartyEdit = (party: Party) => {
        setPartyToEdit(party);
        setPartyFormOpen(true);
        setSelectedPartyId(null); // Close detail panel
    };

    const handlePartyAdd = () => {
        setPartyToEdit(undefined);
        setPartyFormOpen(true);
    };

    // Handlers - Contact
    const handleContactClick = (contactId: string) => {
        setSelectedContactId(contactId);
    };

    const handleContactEdit = (contact: Contact) => {
        setContactToEdit(contact);
        setContactFormOpen(true);
        setSelectedContactId(null); // Close detail panel
    };

    const handleContactAdd = () => {
        setContactToEdit(undefined);
        setContactFormOpen(true);
    };

    // Handlers - Team Member
    const handleTeamMemberClick = (memberId: string) => {
        setSelectedTeamMemberId(memberId);
    };

    const handleTeamMemberEditSkills = (member: TeamMember) => {
        setTeamMemberToEdit(member);
        setTeamMemberSkillsFormOpen(true);
        setSelectedTeamMemberId(null); // Close detail panel
    };

    // Find selected items
    const selectedParty = parties.find((p) => p.id === selectedPartyId);
    const selectedContact = contacts.find((c) => c.id === selectedContactId);
    const selectedTeamMember = teamMembers.find((m) => m.id === selectedTeamMemberId);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Directory" />

            <DirectoryView
                parties={parties}
                contacts={contacts}
                teamMembers={teamMembers}
                projects={projects}
                activeTab={activeTab}
                onTabChange={setActiveTab}
                onPartyClick={handlePartyClick}
                onContactClick={handleContactClick}
                onTeamMemberClick={handleTeamMemberClick}
                onPartyAdd={handlePartyAdd}
                onContactAdd={handleContactAdd}
            />

            {/* Party Detail Panel */}
            {selectedParty && (
                <PartyDetailPanel
                    party={selectedParty}
                    contacts={contacts.filter((c) => c.partyId === selectedParty.id)}
                    projects={projects.filter((p) => p.partyId === selectedParty.id)}
                    onClose={() => setSelectedPartyId(null)}
                    onEdit={handlePartyEdit}
                />
            )}

            {/* Contact Detail Panel */}
            {selectedContact && (
                <ContactDetailPanel
                    contact={selectedContact}
                    party={parties.find((p) => p.id === selectedContact.partyId)}
                    onClose={() => setSelectedContactId(null)}
                    onEdit={handleContactEdit}
                />
            )}

            {/* Team Member Detail Panel */}
            {selectedTeamMember && (
                <TeamMemberDetailPanel
                    teamMember={selectedTeamMember}
                    projects={projects.filter((p) =>
                        selectedTeamMember.assignedProjectIds.includes(p.id)
                    )}
                    onClose={() => setSelectedTeamMemberId(null)}
                    onEditSkills={handleTeamMemberEditSkills}
                />
            )}

            {/* Party Form Panel */}
            <PartyFormPanel
                open={partyFormOpen}
                party={partyToEdit}
                contacts={contacts}
                onClose={() => {
                    setPartyFormOpen(false);
                    setPartyToEdit(undefined);
                }}
            />

            {/* Contact Form Panel */}
            <ContactFormPanel
                open={contactFormOpen}
                contact={contactToEdit}
                parties={parties}
                onClose={() => {
                    setContactFormOpen(false);
                    setContactToEdit(undefined);
                }}
            />

            {/* Team Member Skills Form Panel */}
            {teamMemberToEdit && (
                <TeamMemberSkillsFormPanel
                    open={teamMemberSkillsFormOpen}
                    teamMember={teamMemberToEdit}
                    onClose={() => {
                        setTeamMemberSkillsFormOpen(false);
                        setTeamMemberToEdit(undefined);
                    }}
                />
            )}
        </AppLayout>
    );
}
