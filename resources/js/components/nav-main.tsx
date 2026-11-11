import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { useTrans } from '@/hooks/use-trans';
import type { NavItem } from '@/types';

export function NavMain({ items }: { items: NavItem[] }) {
    const { isCurrentUrl } = useCurrentUrl();
    const { t } = useTrans();

    return (
        <nav aria-label={t('nav.main')}>
            <SidebarGroup className="px-3 py-0">
                <SidebarMenu className="gap-1">
                    {items.map((item) => {
                        const current = isCurrentUrl(item.href);

                        return (
                            <SidebarMenuItem key={item.title}>
                                <SidebarMenuButton
                                    asChild
                                    isActive={current}
                                    tooltip={{ children: t(item.title) }}
                                    className="h-11 gap-3 rounded-control px-3 text-body-sm font-medium group-data-[collapsible=icon]:size-11! group-data-[collapsible=icon]:p-3! data-[active=true]:font-semibold [&>svg]:size-5"
                                >
                                    <Link
                                        href={item.href}
                                        prefetch
                                        aria-current={
                                            current ? 'page' : undefined
                                        }
                                    >
                                        {item.icon && (
                                            <item.icon aria-hidden="true" />
                                        )}
                                        <span>{t(item.title)}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        );
                    })}
                </SidebarMenu>
            </SidebarGroup>
        </nav>
    );
}
