import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { createTranslator } from '@/lib/i18n';
import type { Translator } from '@/lib/i18n';

/**
 * Translates with the active locale's lines, shared by HandleInertiaRequests.
 *
 *     const { t, choice } = useTrans();
 *     t('home.greeting.afternoon', { name: user.name });
 *     choice('home.streak', days);
 */
export function useTrans(): Translator & { locale: string } {
    const { locale, translations } = usePage().props;

    return useMemo(
        () => ({ locale, ...createTranslator(translations) }),
        [locale, translations],
    );
}
