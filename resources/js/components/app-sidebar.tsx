import { Link } from '@inertiajs/react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { navigation } from '@/lib/navigation';
import { home } from '@/routes';

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon">
            <SidebarHeader className="px-3 pt-5 pb-4">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="h-12 group-data-[collapsible=icon]:size-11! hover:bg-transparent active:bg-transparent"
                        >
                            <Link href={home()} prefetch data-test="brand">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navigation} />
            </SidebarContent>

            <SidebarFooter className="p-3">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
