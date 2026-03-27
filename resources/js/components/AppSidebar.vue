<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Building2,
    CreditCard,
    FileText,
    Star,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import * as TenantController from '@/actions/App/Http/Controllers/TenantController';
import * as SubscriptionController from '@/actions/App/Http/Controllers/SubscriptionController';
import * as PlanController from '@/actions/App/Http/Controllers/PlanController';
import * as PlanChangeLogController from '@/actions/App/Http/Controllers/PlanChangeLogController';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'My Organizations',
        href: TenantController.index(),
        icon: Building2,
    },
    {
        title: 'Subscription',
        href: SubscriptionController.show(),
        icon: CreditCard,
    },
    {
        title: 'Plans',
        href: PlanController.index(),
        icon: Star,
    },
    {
        title: 'Plan History',
        href: PlanChangeLogController.index(),
        icon: FileText,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
