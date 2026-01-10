import { useCallback } from 'react';
import { useLanguage } from './use-language';
import { formatDate, formatDistance } from '@/lib/date-utils';

export function useDate() {
    const { language } = useLanguage();

    const format = useCallback(
        (date: Date | string | number, formatStr: string = 'PPP') => {
            return formatDate(date, formatStr, language);
        },
        [language],
    );

    const distance = useCallback(
        (
            date: Date | string | number,
            baseDate?: Date | string | number,
            addSuffix: boolean = true,
        ) => {
            return formatDistance(date, baseDate, { addSuffix, locale: language });
        },
        [language],
    );

    return { format, distance } as const;
}
