<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import PlanBadge from '@/components/tenant/PlanBadge.vue';
import UsageMeter from '@/components/tenant/UsageMeter.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import type { Subscription, PlanChangeLog, OnboardingTask } from '@/types/tenant';

const props = defineProps<{
    tenant?: { id: number; name: string; slug: string } | null;
    subscription?: { data: Subscription } | null;
    usage?: { current_users: number; max_users: number } | null;
    recentLogs?: { data: PlanChangeLog[] } | null;
    tasks?: { data: OnboardingTask[] } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: dashboard() }];

const sub = computed(() => props.subscription?.data ?? null);
const logs = computed(() => props.recentLogs?.data ?? []);
const tasks = computed(() => props.tasks?.data ?? []);
const allTasksDone = computed(() => tasks.value.every(t => t.is_completed));

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString();
}

const taskLabels: Record<string, string> = {
    branding: 'Set up branding',
    invite_users: 'Invite team members',
    set_permissions: 'Review permissions',
};

const taskLinks: Record<string, string> = {
    branding: '/onboarding',
    invite_users: '/onboarding',
    set_permissions: '/onboarding',
};
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">

            <!-- No active tenant -->
            <Card v-if="!tenant">
                <CardContent class="py-12 text-center text-muted-foreground">
                    <p class="text-xl font-semibold">No organization selected</p>
                    <p class="text-sm mt-2">Create or switch to an organization to view your dashboard.</p>
                    <div class="flex gap-3 justify-center mt-6">
                        <Button @click="router.visit('/tenants/create')">Create Organization</Button>
                        <Button variant="outline" @click="router.visit('/tenants')">Select Organization</Button>
                    </div>
                </CardContent>
            </Card>

            <template v-else>
                <!-- Welcome header -->
                <div>
                    <h1 class="text-2xl font-semibold">Welcome back!</h1>
                    <p class="text-muted-foreground mt-1">{{ tenant.name }}</p>
                </div>

                <!-- No subscription -->
                <Card v-if="!sub">
                    <CardContent class="py-8 text-center">
                        <p class="text-lg font-medium">No active plan</p>
                        <p class="text-sm text-muted-foreground mt-1">Subscribe to unlock all features.</p>
                        <Button class="mt-4" @click="router.visit('/plans')">Browse Plans</Button>
                    </CardContent>
                </Card>

                <!-- Subscription card -->
                <Card v-else>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle>{{ sub.plan?.name }} Plan</CardTitle>
                        <PlanBadge :plan="sub.plan" :status="sub.status" />
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-muted-foreground">Status</p>
                                <p class="font-medium capitalize">{{ sub.status }}</p>
                            </div>
                            <div v-if="sub.next_billing_date">
                                <p class="text-muted-foreground">Next billing</p>
                                <p class="font-medium">{{ formatDate(sub.next_billing_date) }}</p>
                            </div>
                            <div v-if="sub.is_trial && sub.trial_ends_at">
                                <p class="text-muted-foreground">Trial ends</p>
                                <p class="font-medium">{{ formatDate(sub.trial_ends_at) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Days remaining</p>
                                <p class="font-medium">{{ sub.days_remaining }}</p>
                            </div>
                        </div>

                        <!-- Usage meters -->
                        <div v-if="usage" class="pt-2 border-t space-y-3">
                            <UsageMeter label="Team Members" :used="usage.current_users" :max="usage.max_users" />
                        </div>

                        <Button variant="outline" size="sm" @click="router.visit('/subscription')">
                            Manage Subscription
                        </Button>
                    </CardContent>
                </Card>

                <!-- Onboarding checklist -->
                <Card v-if="tasks.length > 0 && !allTasksDone">
                    <CardHeader><CardTitle>Getting Started</CardTitle></CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="task in tasks" :key="task.id" class="flex items-center gap-3">
                            <div
                                class="h-5 w-5 rounded-full border-2 flex items-center justify-center shrink-0"
                                :class="task.is_completed ? 'bg-green-500 border-green-500' : 'border-muted-foreground'"
                            >
                                <span v-if="task.is_completed" class="text-white text-xs">✓</span>
                            </div>
                            <span
                                class="text-sm flex-1"
                                :class="task.is_completed ? 'line-through text-muted-foreground' : ''"
                            >
                                {{ taskLabels[task.task_key] ?? task.task_key }}
                            </span>
                            <Button
                                v-if="!task.is_completed"
                                variant="ghost"
                                size="sm"
                                @click="router.visit(taskLinks[task.task_key] ?? '/onboarding')"
                            >
                                Complete →
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent plan changes -->
                <Card v-if="logs.length > 0">
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle>Recent Plan Changes</CardTitle>
                        <Button variant="ghost" size="sm" @click="router.visit('/logs/plans')">View all →</Button>
                    </CardHeader>
                    <CardContent>
                        <table class="w-full text-sm">
                            <thead class="text-left border-b">
                                <tr>
                                    <th class="pb-2 font-medium text-muted-foreground">Date</th>
                                    <th class="pb-2 font-medium text-muted-foreground">From</th>
                                    <th class="pb-2 font-medium text-muted-foreground">To</th>
                                    <th class="pb-2 font-medium text-muted-foreground">By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="log in logs" :key="log.id" class="border-b last:border-0">
                                    <td class="py-2 text-muted-foreground">{{ formatDate(log.created_at) }}</td>
                                    <td class="py-2">{{ log.from_plan?.name ?? '—' }}</td>
                                    <td class="py-2 font-medium">{{ log.to_plan?.name }}</td>
                                    <td class="py-2 text-muted-foreground">{{ log.changed_by?.name }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

            </template>
        </div>
    </AppLayout>
</template>

