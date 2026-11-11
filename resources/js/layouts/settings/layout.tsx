import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTrans } from '@/hooks/use-trans';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'settings.nav.profile',
        href: edit(),
        icon: null,
    },
    {
        title: 'settings.nav.security',
        href: editSecurity(),
        icon: null,
    },
    {
        title: 'settings.nav.appearance',
        href: editAppearance(),
        icon: null,
    },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl } = useCurrentUrl();
    const { t } = useTrans();

    return (
        <div className="px-4 py-6">
            <Heading
                title={t('settings.title')}
                description={t('settings.description')}
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label={t('settings.title')}
                    >
                        {sidebarNavItems.map((item, index) => {
                            const current = isCurrentOrParentUrl(item.href);

                            return (
                                <Link
                                    key={`${toUrl(item.href)}-${index}`}
                                    href={item.href}
                                    aria-current={current ? 'page' : undefined}
                                    className={cn(
                                        'flex min-h-11 w-full items-center gap-3 rounded-control px-3 text-body-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                        current
                                            ? 'bg-accent-soft font-semibold text-primary'
                                            : 'font-medium text-ink-secondary hover:bg-surface hover:text-ink',
                                    )}
                                >
                                    {item.icon && (
                                        <item.icon className="size-5" />
                                    )}
                                    {t(item.title)}
                                </Link>
                            );
                        })}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
