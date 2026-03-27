<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import TenantSwitcher from '@/components/tenant/TenantSwitcher.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useTenantStore } from '@/stores/tenant';
import type { BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const tenantStore = useTenantStore();
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <!-- Tenant switcher in the header right side -->
        <div class="ml-auto flex items-center gap-2">
            <TenantSwitcher />
        </div>
    </header>

    <!-- No-tenant banner -->
    <div
        v-if="!tenantStore.hasActiveTenant"
        class="border-b bg-yellow-50 px-6 py-2 text-sm text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-200 flex items-center justify-between"
    >
        <span>You don't have an active tenant selected.</span>
        <Button as-child size="sm" variant="outline">
            <Link :href="route('tenants.index')">Select or create a tenant</Link>
        </Button>
    </div>
</template>

