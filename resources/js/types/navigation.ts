import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    /** Translation key, such as "nav.home". */
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    /** Translation key, such as "nav.home". */
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};
