import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useTrans } from '@/hooks/use-trans';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { t } = useTrans();

    return (
        <header className="flex h-16 shrink-0 items-center gap-2 border-b border-border px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger
                    className="-ml-1 size-11"
                    aria-label={t('nav.toggle_sidebar')}
                />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
        </header>
    );
}
