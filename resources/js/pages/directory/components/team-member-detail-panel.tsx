import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { TeamMemberDetail } from './team-member-detail';
import type { TeamMember, Project } from '@/types/directory';

interface TeamMemberDetailPanelProps {
    teamMember: TeamMember;
    projects: Project[];
    onClose: () => void;
    onEditSkills: (member: TeamMember) => void;
}

export function TeamMemberDetailPanel({
    teamMember,
    projects,
    onClose,
    onEditSkills,
}: TeamMemberDetailPanelProps) {
    return (
        <Sheet open={true} onOpenChange={(open) => !open && onClose()}>
            <SheetContent side="right" className="w-full overflow-y-auto p-0 sm:max-w-2xl">
                <div className="p-6">
                    <SheetHeader className="p-0">
                        <SheetTitle>Team Member Details</SheetTitle>
                    </SheetHeader>
                </div>
                <div className="px-6 pb-6">
                    <TeamMemberDetail
                        teamMember={teamMember}
                        projects={projects}
                        onEditSkills={onEditSkills}
                    />
                </div>
            </SheetContent>
        </Sheet>
    );
}
