/*
 * A stand-in for @inertiajs/react in component tests:
 *
 *     vi.mock('@inertiajs/react', () => import('@/test/inertia'));
 *
 * `page` is the current page; tests change `page.url` or `page.props` before
 * rendering. The translations mirror the lang/en lines the shell uses
 * (TranslationsTest checks that every key the front end uses exists).
 */
import type { InertiaLinkProps } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { toUrl } from '@/lib/utils';

export const page = {
    url: '/home',
    props: {
        name: 'Parlora Hub',
        auth: {
            user: {
                id: 1,
                name: 'Alex Doe',
                email: 'alex@example.com',
                email_verified_at: null,
                created_at: '',
                updated_at: '',
            },
        },
        features: { registration: true },
        sidebarOpen: true,
        locale: 'en',
        translations: {
            app: { brand: { name: 'Parlora', product: 'Hub' } },
            nav: {
                main: 'Main',
                toggle_sidebar: 'Toggle sidebar',
                home: 'Home',
                settings: 'Settings',
                log_out: 'Log out',
            },
            home: {
                head: 'Home',
                greeting: {
                    morning: 'Good morning, :name',
                    afternoon: 'Good afternoon, :name',
                    evening: 'Good evening, :name',
                },
                empty: {
                    title: 'Your lessons are on their way',
                    body: 'Lessons, practice and exams will appear here as each section opens.',
                },
            },
        },
    },
};

export function usePage() {
    return page;
}

export function Head() {
    return null;
}

export function Link({
    href,
    prefetch: _prefetch,
    children,
    ...props
}: Omit<InertiaLinkProps, 'href'> & {
    href: NonNullable<InertiaLinkProps['href']>;
    children?: ReactNode;
}) {
    return (
        <a href={toUrl(href)} {...(props as Record<string, unknown>)}>
            {children}
        </a>
    );
}

export const router = {
    flushAll: () => {},
    visit: () => {},
};
