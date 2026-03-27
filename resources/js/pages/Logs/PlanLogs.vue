<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import type { PlanChangeLog } from '@/types/tenant';
import type { BreadcrumbItem } from '@/types';

interface PaginatedLogs {
    data: PlanChangeLog[];
    links: { url: string | null; label: string; active: boolean }[];
    meta?: { current_page: number; last_page: number };
}

const props = defineProps<{
    logs: PaginatedLogs;
    hasTenant?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Plan Audit Logs', href: '/logs/plans' },
];

function navigate(url: string | null) {
    if (url) router.visit(url);
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString();
}
</script>

<template>
    <Head title="Plan Audit Logs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <h1 class="text-2xl font-semibold">Plan Change Logs</h1>

            <div
                v-if="!hasTenant"
                class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300"
            >
                No organization selected. Go to
                <button
                    class="font-medium underline"
                    @click="router.visit('/tenants')"
                >
                    My Organizations
                </button>
                and select one to view its plan history.
            </div>

            <div
                v-else-if="logs.data.length === 0"
                class="py-16 text-center text-muted-foreground"
            >
                No plan changes recorded yet.
            </div>

            <div v-else class="overflow-x-auto rounded-lg border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium">
                                Date
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Changed By
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                From Plan
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                To Plan
                            </th>
                            <th class="px-4 py-3 text-left font-medium">
                                Reason
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                {{ log.changed_by?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="log.from_plan">{{
                                    log.from_plan.name
                                }}</span>
                                <span
                                    v-else
                                    class="text-muted-foreground italic"
                                    >New subscription</span
                                >
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ log.to_plan?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ log.reason ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="logs.links?.length > 1"
                class="flex flex-wrap items-center justify-center gap-1"
            >
                <Button
                    v-for="link in logs.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    @click="navigate(link.url)"
                    v-html="link.label"
                />
            </div>
        </div>
    </AppLayout>
</template>
