import type { Translations } from '@/lib/i18n';
import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            features: {
                registration: boolean;
            };
            sidebarOpen: boolean;
            locale: string;
            translations: Translations;
            [key: string]: unknown;
        };
    }
}
