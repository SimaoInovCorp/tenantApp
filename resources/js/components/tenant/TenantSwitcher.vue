<script setup lang="ts">
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Building2, Check, ChevronsUpDown, PlusCircle } from 'lucide-vue-next';
import { useTenantStore } from '@/stores/tenant';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { getInitials } from '@/composables/useInitials';
import PlanBadge from './PlanBadge.vue';
import type { Plan } from '@/types/tenant';

const store = useTenantStore();

onMounted(() => {
    store.fetchTenants();
});

function initials(name: string): string {
    return getInitials(name);
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" class="flex items-center gap-2 px-2">
                <Avatar class="size-6">
                    <AvatarImage
                        v-if="store.activeTenant?.logo_url"
                        :src="store.activeTenant.logo_url"
                        :alt="store.activeTenant.name"
                    />
                    <AvatarFallback class="text-xs">
                        {{ store.activeTenant ? initials(store.activeTenant.name) : '?' }}
                    </AvatarFallback>
                </Avatar>
                <span class="max-w-32 truncate text-sm font-medium">
                    {{ store.activeTenant?.name ?? 'Select tenant' }}
                </span>
                <ChevronsUpDown class="size-4 shrink-0 text-muted-foreground" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-64">
            <DropdownMenuLabel class="text-xs text-muted-foreground">Your tenants</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem
                v-for="tenant in store.tenants"
                :key="tenant.id"
                class="flex items-center gap-2 cursor-pointer"
                @click="store.switchTenant(tenant.slug)"
            >
                <Avatar class="size-5">
                    <AvatarImage v-if="tenant.logo_url" :src="tenant.logo_url" :alt="tenant.name" />
                    <AvatarFallback class="text-[10px]">{{ initials(tenant.name) }}</AvatarFallback>
                </Avatar>
                <span class="flex-1 truncate text-sm">{{ tenant.name }}</span>
                <Check
                    v-if="store.activeTenant?.id === tenant.id"
                    class="size-4 shrink-0 text-primary"
                />
            </DropdownMenuItem>
            <DropdownMenuSeparator v-if="store.tenants.length" />
            <DropdownMenuItem as-child>
                <Link :href="route('tenants.index')" class="flex items-center gap-2 cursor-pointer">
                    <PlusCircle class="size-4" />
                    <span class="text-sm">Create Tenant</span>
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
