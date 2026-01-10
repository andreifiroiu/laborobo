import { format as dateFnsFormat, formatDistance as dateFnsFormatDistance } from 'date-fns';
import { enUS, es, fr, de, ro } from 'date-fns/locale';

const locales = {
    en: enUS,
    es: es,
    fr: fr,
    de: de,
    ro: ro,
};

export function getDateFnsLocale(locale: string = 'en') {
    return locales[locale as keyof typeof locales] || enUS;
}

export function formatDate(
    date: Date | string | number,
    formatStr: string = 'PPP',
    locale?: string,
): string {
    const dateObj = typeof date === 'string' || typeof date === 'number' ? new Date(date) : date;
    const currentLocale = locale || (typeof window !== 'undefined' ? localStorage.getItem('language') : null) || 'en';

    return dateFnsFormat(dateObj, formatStr, {
        locale: getDateFnsLocale(currentLocale),
    });
}

export function formatDistance(
    date: Date | string | number,
    baseDate: Date | string | number = new Date(),
    options?: { addSuffix?: boolean; locale?: string },
): string {
    const dateObj = typeof date === 'string' || typeof date === 'number' ? new Date(date) : date;
    const baseDateObj = typeof baseDate === 'string' || typeof baseDate === 'number' ? new Date(baseDate) : baseDate;
    const currentLocale = options?.locale || (typeof window !== 'undefined' ? localStorage.getItem('language') : null) || 'en';

    return dateFnsFormatDistance(dateObj, baseDateObj, {
        addSuffix: options?.addSuffix,
        locale: getDateFnsLocale(currentLocale),
    });
}
