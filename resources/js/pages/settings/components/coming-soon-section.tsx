import { Clock } from 'lucide-react';

interface ComingSoonSectionProps {
    title: string;
    description: string;
}

export function ComingSoonSection({ title, description }: ComingSoonSectionProps) {
    return (
        <div className="flex flex-col items-center justify-center rounded-lg border border-dashed p-12 text-center">
            <div className="mx-auto flex max-w-md flex-col items-center">
                <div className="mb-4 rounded-full bg-muted p-3">
                    <Clock className="h-6 w-6 text-muted-foreground" />
                </div>
                <h3 className="mb-2 text-lg font-semibold">{title}</h3>
                <p className="text-sm text-muted-foreground">{description}</p>
                <p className="mt-4 text-xs text-muted-foreground">Coming soon...</p>
            </div>
        </div>
    );
}
