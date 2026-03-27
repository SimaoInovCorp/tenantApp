<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { onMounted, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { getInitials } from '@/composables/useInitials';
import { useTenantStore } from '@/stores/tenant';
import type { Tenant } from '@/types/tenant';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    tenants: { data: Tenant[] };
}>();

const page = usePage();
const flash = computed(() => (page.props as Record<string, any>).flash);
const tenantStore = useTenantStore();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My Tenants', href: '/tenants' }];
const tenantList = computed(() => props.tenants?.data ?? []);

onMounted(() => {
    const newSlug = flash.value?.new_tenant_slug as string | undefined;
    if (newSlug) {
        localStorage.setItem('active_tenant_slug', newSlug);
        localStorage.setItem('active_tenant', JSON.stringify({ slug: newSlug, id: 0, name: '' }));
        router.visit('/onboarding');
    }
});
</script>

<template>
    <Head title="My Organizations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-semibold">My Organizations</h1>
                <Button @click="router.visit('/tenants/create')">+ New Organization</Button>
            </div>

            <div v-if="tenantList.length === 0" class="text-center py-20 text-muted-foreground">
                <p class="text-lg font-medium">No organizations yet</p>
                <p class="text-sm mt-1">Create your first one to get started.</p>
                <Button class="mt-6" @click="router.visit('/tenants/create')">Create Organization</Button>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="tenant in tenantList" :key="tenant.id" class="hover:shadow-md transition-shadow">
                    <CardHeader class="flex flex-row items-center gap-3 pb-2">
                        <Avatar class="h-10 w-10">
                            <AvatarImage v-if="tenant.logo_url" :src="tenant.logo_url" :alt="tenant.name" />
                            <AvatarFallback :style="tenant.primary_color ? { background: tenant.primary_color } : {}">
                                {{ getInitials(tenant.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex-1 min-w-0">
                            <CardTitle class="text-base truncate">{{ tenant.name }}</CardTitle>
                            <p class="text-xs text-muted-foreground">{{ tenant.slug }}</p>
                        </div>
                        <Badge variant="outline" class="capitalize shrink-0">
                            {{ tenant.current_user_role ?? 'member' }}
                        </Badge>
                    </CardHeader>
                    <CardContent class="flex items-center justify-between pt-0">
                        <Button variant="default" size="sm" @click="tenantStore.switchTenant(tenant.slug)">
                            Switch
                        </Button>
                        <Button variant="ghost" size="sm" @click="router.visit(`/tenants/${tenant.id}`)">
                            Settings
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
