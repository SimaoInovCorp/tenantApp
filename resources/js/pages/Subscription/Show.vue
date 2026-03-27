<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import PlanBadge from '@/components/tenant/PlanBadge.vue';
import UsageMeter from '@/components/tenant/UsageMeter.vue';
import UpgradeModal from '@/components/subscription/UpgradeModal.vue';
import DowngradeModal from '@/components/subscription/DowngradeModal.vue';
import CancelModal from '@/components/subscription/CancelModal.vue';
import type { Subscription, Plan } from '@/types/tenant';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    subscription: { data: Subscription } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Subscription', href: '/subscription' },
];
const sub = computed(() => props.subscription?.data ?? null);
const page = usePage();
const flash = computed(
    () =>
        ((page.props as any).flash as { success?: string; error?: string }) ??
        {},
);

const showUpgrade = ref(false);
const showDowngrade = ref(false);
const showCancel = ref(false);
const targetPlan = ref<Plan | null>(null);

function openUpgrade(plan?: Plan) {
    targetPlan.value = plan ?? null;
    showUpgrade.value = true;
}
function openDowngrade(plan?: Plan) {
    targetPlan.value = plan ?? null;
    showDowngrade.value = true;
}

const limits = computed(() => {
    if (!sub.value?.plan?.limits) return [];
    return Object.entries(sub.value.plan.limits).map(([key, max]) => ({
        label: key.replace(/_/g, ' '),
        key,
        max: max as number,
    }));
});
</script>

<template>
    <Head title="Subscription" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl space-y-6 p-6">
            <h1 class="text-2xl font-semibold">Subscription</h1>

            <!-- Flash messages -->
            <div
                v-if="flash.success"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash.error"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
            >
                {{ flash.error }}
            </div>
            <Card v-if="!sub">
                <CardContent class="py-12 text-center text-muted-foreground">
                    <p class="text-lg font-medium">No active subscription</p>
                    <p class="mt-1 text-sm">Browse our plans to get started.</p>
                    <Button class="mt-4" @click="router.visit('/plans')"
                        >Browse Plans</Button
                    >
                </CardContent>
            </Card>

            <!-- Active subscription -->
            <template v-else>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between"
                    >
                        <CardTitle>{{ sub.plan?.name }}</CardTitle>
                        <PlanBadge :plan="sub.plan" :status="sub.status" />
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status</span>
                            <span class="font-medium capitalize">{{
                                sub.status
                            }}</span>
                        </div>
                        <div
                            v-if="sub.is_trial && sub.trial_ends_at"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground"
                                >Trial ends</span
                            >
                            <span>{{
                                new Date(sub.trial_ends_at).toLocaleDateString()
                            }}</span>
                        </div>
                        <div
                            v-if="sub.next_billing_date"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground"
                                >Next billing</span
                            >
                            <span>{{
                                new Date(
                                    sub.next_billing_date,
                                ).toLocaleDateString()
                            }}</span>
                        </div>
                        <div
                            v-if="sub.ends_at && sub.status === 'canceled'"
                            class="flex justify-between"
                        >
                            <span class="text-muted-foreground"
                                >Access until</span
                            >
                            <span>{{
                                new Date(sub.ends_at).toLocaleDateString()
                            }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground"
                                >Days remaining</span
                            >
                            <span>{{ sub.days_remaining }} days</span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Usage meters -->
                <Card v-if="limits.length > 0">
                    <CardHeader><CardTitle>Usage</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <UsageMeter
                            v-for="limit in limits"
                            :key="limit.key"
                            :label="limit.label"
                            :used="0"
                            :max="limit.max"
                        />
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex flex-wrap gap-3">
                    <Button variant="outline" @click="router.visit('/plans')"
                        >Change Plan</Button
                    >
                    <Button
                        v-if="sub.status !== 'canceled'"
                        variant="outline"
                        class="border-destructive/30 text-destructive hover:bg-destructive/10"
                        @click="showCancel = true"
                    >
                        Cancel Subscription
                    </Button>
                </div>
            </template>
        </div>

        <UpgradeModal
            v-model:open="showUpgrade"
            :target-plan="targetPlan"
            :current-subscription="sub"
        />
        <DowngradeModal
            v-model:open="showDowngrade"
            :target-plan="targetPlan"
            :current-subscription="sub"
        />
        <CancelModal v-model:open="showCancel" :subscription="sub" />
    </AppLayout>
</template>
