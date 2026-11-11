import { House } from 'lucide-react';
import { home } from '@/routes';
import type { NavItem } from '@/types';

/**
 * The sidebar navigation: only sections that exist. Each phase appends its
 * own item when its pages ship (Lessons, Practice, Exams, Progress, Review).
 */
export const navigation: NavItem[] = [
    {
        title: 'nav.home',
        href: home(),
        icon: House,
    },
];
