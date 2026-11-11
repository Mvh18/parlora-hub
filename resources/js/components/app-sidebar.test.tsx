import { fireEvent, render, screen, within } from '@testing-library/react';
import { beforeEach, describe, expect, it, vi } from 'vite-plus/test';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { SidebarProvider } from '@/components/ui/sidebar';
import { TooltipProvider } from '@/components/ui/tooltip';
import { page } from '@/test/inertia';
import { mediaQuery } from '@/test/media';

vi.mock('@inertiajs/react', () => import('@/test/inertia'));

function renderShell() {
    return render(
        <TooltipProvider>
            <SidebarProvider>
                <AppSidebar />
                <AppSidebarHeader />
            </SidebarProvider>
        </TooltipProvider>,
    );
}

describe('AppSidebar', () => {
    beforeEach(() => {
        page.url = '/home';
    });

    it('renders the brand block, the navigation landmark and the user menu', () => {
        renderShell();

        const brand = screen.getByText('Parlora').closest('a');

        expect(brand).toHaveAttribute('href', '/home');
        expect(within(brand!).getByText('Hub')).toBeInTheDocument();
        expect(
            screen.getByRole('navigation', { name: 'Main' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: /Alex Doe/ }),
        ).toHaveAttribute('aria-haspopup', 'menu');
    });

    it('lists exactly one section, Home, marked as the current page', () => {
        renderShell();

        const links = within(
            screen.getByRole('navigation', { name: 'Main' }),
        ).getAllByRole('link');

        expect(links).toHaveLength(1);
        expect(links[0]).toHaveAccessibleName('Home');
        expect(links[0]).toHaveAttribute('href', '/home');
        expect(links[0]).toHaveAttribute('aria-current', 'page');
    });

    it('does not mark Home as current on other pages', () => {
        page.url = '/settings/profile';

        renderShell();

        expect(screen.getByRole('link', { name: 'Home' })).not.toHaveAttribute(
            'aria-current',
        );
    });

    it('gives every interactive target at least 44 px and a focus ring', () => {
        renderShell();

        const targets = [
            screen.getByRole('link', { name: 'Home' }),
            screen.getByRole('button', { name: /Alex Doe/ }),
            screen.getByRole('button', { name: 'Toggle sidebar' }),
        ];

        for (const target of targets) {
            // h-11 / size-11 = 2.75rem = 44 px; h-12 = 48 px.
            expect(target.className).toMatch(/\b(h|size)-1[12]\b/);
            expect(target.className).not.toMatch(/(^|\s)(h|size)-[0-9]\b/);
            expect(target.className).toMatch(/focus-visible:ring-/);
        }
    });

    it('collapses into a menu opened from the header on narrow screens', () => {
        mediaQuery.matches = true;

        renderShell();

        expect(screen.queryByRole('navigation', { name: 'Main' })).toBeNull();

        fireEvent.click(screen.getByRole('button', { name: 'Toggle sidebar' }));

        expect(
            screen.getByRole('navigation', { name: 'Main' }),
        ).toBeInTheDocument();
    });
});
