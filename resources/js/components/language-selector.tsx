import { useLanguage } from '@/hooks/use-language';
import { cn } from '@/lib/utils';
import { Check, Languages } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const languageNames: Record<string, string> = {
    en: 'English',
    es: 'Español',
    fr: 'Français',
    de: 'Deutsch',
    ro: 'Română',
};

export default function LanguageSelector() {
    const { language, changeLanguage, availableLanguages } = useLanguage();

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="gap-2">
                    <Languages className="h-4 w-4" />
                    <span className="hidden sm:inline">
                        {languageNames[language]}
                    </span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {availableLanguages.map((lang) => (
                    <DropdownMenuItem
                        key={lang}
                        onClick={() => changeLanguage(lang)}
                        className={cn(
                            'flex items-center justify-between gap-2',
                            language === lang && 'bg-muted',
                        )}
                    >
                        <span>{languageNames[lang]}</span>
                        {language === lang && (
                            <Check className="h-4 w-4 text-primary" />
                        )}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
