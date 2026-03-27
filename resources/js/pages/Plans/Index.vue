<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { Plan, Subscription } from '@/types/tenant';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    plans: { data: Plan[] };
    subscription?: { data: Subscription } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Plans & Pricing', href: '/plans' },
];

const page = usePage();
const tenantStore = useTenantStore();
const auth = computed(() => (page.props as any).auth);
const flash = computed(
    () =>
        (page.props as any).flash as
            | { success?: string; error?: string }
            | undefined,
);

const planList = computed(() => props.plans?.data ?? []);
const currentSubscription = computed(() => props.subscription?.data ?? null);
const currentPlanId = computed(() => currentSubscription.value?.plan?.id);
const currentPlanPrice = computed(() =>
    parseFloat(currentSubscription.value?.plan?.price ?? '0'),
);

const processing = ref(false);

function ctaLabel(plan: Plan): string {
    if (!auth.value?.user) return 'Get Started';
    if (currentPlanId.value === plan.id) return 'Current Plan';
    const planPrice = parseFloat(plan.price);
    if (!currentPlanId.value)
        return plan.price === '0.00' ? 'Subscribe Free' : 'Subscribe';
    if (planPrice > currentPlanPrice.value) return 'Upgrade';
    return 'Downgrade';
}

function ctaAction(plan: Plan) {
    if (!auth.value?.user) {
        router.visit('/login');
        return;
    }
    if (currentPlanId.value === plan.id) return;

    processing.value = true;
    if (!currentPlanId.value) {
        router.post(
            '/subscription',
            { plan_id: plan.id },
            {
                onFinish: () => {
                    processing.value = false;
                },
            },
        );
        return;
    }
    const planPrice = parseFloat(plan.price);
    if (planPrice > currentPlanPrice.value) {
        router.put(
            '/subscription/upgrade',
            { plan_id: plan.id },
            {
                onFinish: () => {
                    processing.value = false;
                },
            },
        );
    } else {
        router.put(
            '/subscription/downgrade',
            { plan_id: plan.id },
            {
                onFinish: () => {
                    processing.value = false;
                },
            },
        );
    }
}

function isCtaDisabled(plan: Plan): boolean {
    return processing.value || currentPlanId.value === plan.id;
}
</script>

<template>
    <Head title="Plans & Pricing" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-6xl space-y-8 px-6 py-10">
            <!-- Header row with Back button -->
            <div class="flex items-center gap-4">
                <Button
                    variant="outline"
                    size="sm"
                    @click="router.visit('/subscription')"
                >
                    ← Back
                </Button>
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">
                        Plans & Pricing
                    </h1>
                    <p class="mt-1 text-muted-foreground">
                        Choose the plan that works best for your team.
                    </p>
                </div>
            </div>

            <!-- Flash messages -->
            <div
                v-if="flash?.success"
                class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-950/30 dark:text-green-300"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash?.error"
                class="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive"
            >
                {{ flash.error }}
            </div>

            <!-- No tenant selected warning -->
            <div
                v-if="auth?.user && !tenantStore.hasActiveTenant"
                class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300"
            >
                Select an organization first to subscribe to a plan.
            </div>

            <!-- Plan grid -->
            <div class="grid gap-6 pt-4 sm:grid-cols-2 xl:grid-cols-4">
                <Card
                    v-for="plan in planList"
                    :key="plan.id"
                    class="relative flex flex-col overflow-hidden"
                    :class="
                        plan.slug === 'pro' ? 'border-primary shadow-lg' : ''
                    "
                >
                    <div
                        v-if="plan.slug === 'pro'"
                        class="absolute -top-3 left-1/2 -translate-x-1/2"
                    >
                        <Badge class="px-3">Most Popular</Badge>
                    </div>
                    <CardHeader>
                        <CardTitle class="text-lg">{{ plan.name }}</CardTitle>
                        <CardDescription>
                            <div
                                class="mt-1 flex flex-wrap items-baseline gap-1"
                            >
                                <span
                                    class="text-2xl font-bold text-foreground"
                                >
                                    {{
                                        plan.price === '0.00'
                                            ? 'Free'
                                            : `€${plan.price}`
                                    }}
                                </span>
                                <span
                                    v-if="plan.price !== '0.00'"
                                    class="text-sm text-muted-foreground"
                                    >/{{ plan.interval }}</span
                                >
                            </div>
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="flex-1 space-y-3">
                        <div v-if="plan.trial_days > 0">
                            <Badge variant="secondary"
                                >{{ plan.trial_days }}-day free trial</Badge
                            >
                        </div>
                        <ul class="space-y-2 text-sm">
                            <li
                                v-for="feature in plan.features ?? []"
                                :key="feature"
                                class="flex items-center gap-2"
                            >
                                <span class="text-green-500">✓</span>
                                <span class="capitalize">{{
                                    feature.replace(/_/g, ' ')
                                }}</span>
                            </li>
                        </ul>
                        <div
                            v-if="
                                plan.limits &&
                                Object.keys(plan.limits).length > 0
                            "
                            class="space-y-1 border-t pt-2"
                        >
                            <p
                                class="text-xs font-medium text-muted-foreground uppercase"
                            >
                                Limits
                            </p>
                            <template
                                v-for="(val, key) in plan.limits"
                                :key="key"
                            >
                                <p class="text-xs text-muted-foreground">
                                    {{ String(key).replace(/_/g, ' ') }}:
                                    <strong>{{
                                        val === -1 ? 'Unlimited' : val
                                    }}</strong>
                                </p>
                            </template>
                        </div>
                    </CardContent>
                    <CardFooter>
                        <Button
                            class="w-full"
                            :variant="
                                currentPlanId === plan.id
                                    ? 'outline'
                                    : 'default'
                            "
                            :disabled="isCtaDisabled(plan)"
                            @click="ctaAction(plan)"
                        >
                            {{ ctaLabel(plan) }}
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
