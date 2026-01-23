import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Globe } from 'lucide-react';
import type { LanguageSelectorProps } from '@/types/client-comms.d';

/**
 * Language name mapping
 */
const languageNames: Record<string, string> = {
    en: 'English',
    es: 'Spanish',
    fr: 'French',
    de: 'German',
    ro: 'Romanian',
    it: 'Italian',
    pt: 'Portuguese',
    nl: 'Dutch',
    pl: 'Polish',
    ru: 'Russian',
};

/**
 * Default available languages if not specified
 */
const defaultLanguages = ['en', 'es', 'fr', 'de', 'ro'];

/**
 * LanguageSelector provides a dropdown to select the target language for client communications.
 * Defaults to Party's preferred_language when available.
 */
export function LanguageSelector({
    value,
    onChange,
    availableLanguages = defaultLanguages,
    disabled = false,
}: LanguageSelectorProps) {
    return (
        <Select
            value={value}
            onValueChange={onChange}
            disabled={disabled}
        >
            <SelectTrigger
                className="w-full"
                aria-label="Select language"
            >
                <div className="flex items-center gap-2">
                    <Globe className="h-4 w-4 text-muted-foreground" />
                    <SelectValue placeholder="Select language" />
                </div>
            </SelectTrigger>
            <SelectContent>
                {availableLanguages.map((lang) => (
                    <SelectItem key={lang} value={lang}>
                        <div className="flex items-center gap-2">
                            <span className="uppercase text-xs font-mono text-muted-foreground w-6">
                                {lang}
                            </span>
                            <span>{languageNames[lang] ?? lang}</span>
                        </div>
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

/**
 * Get language display name from code
 */
export function getLanguageName(code: string): string {
    return languageNames[code] ?? code.toUpperCase();
}

export { languageNames, defaultLanguages };
