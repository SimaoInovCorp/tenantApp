<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Plan } from '@/types/tenant';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    plans?: { data: Plan[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Tenants', href: '/tenants' },
    { title: 'New Organization', href: '/tenants/create' },
];

const name = ref('');
const slug = ref('');
const selectedPlanId = ref<number | null>(null);
const errors = ref<Record<string, string>>({});
const processing = ref(false);
const plans = computed(() => props.plans?.data ?? []);
const store = useTenantStore();

// Auto-generate slug from name
watch(name, (val) => {
    slug.value = val
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
});

async function submit() {
    processing.value = true;
    errors.value = {};
    router.post(
        '/tenants',
        {
            name: name.value,
            slug: slug.value || undefined,
            plan_id: selectedPlanId.value || undefined,
        },
        {
            onError: (errs) => {
                errors.value = errs;
            },
            onFinish: () => {
                processing.value = false;
            },
            onSuccess: (page) => {
                const newSlug = (page.props as any).flash?.new_tenant_slug as
                    | string
                    | undefined;
                if (newSlug) {
                    // Find the real tenant data from the tenants list returned by tenants.index
                    const tenantData = (page.props as any).tenants?.data?.find(
                        (t: any) => t.slug === newSlug,
                    );
                    if (tenantData) {
                        store.setActiveTenant(tenantData);
                    } else {
                        // Fallback: store slug only and let TenantSwitcher hydrate on next mount
                        localStorage.setItem('active_tenant_slug', newSlug);
                    }
                    router.visit('/onboarding');
                }
            },
        },
    );
}
</script>

<template>
    <Head title="New Organization" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl space-y-6 p-6">
            <div>
                <h1 class="text-2xl font-semibold">New Organization</h1>
                <p class="mt-1 text-muted-foreground">
                    Set up your workspace in seconds.
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Name -->
                <div class="space-y-2">
                    <Label for="name">Organization Name</Label>
                    <Input
                        id="name"
                        v-model="name"
                        placeholder="Acme Corp"
                        required
                        autofocus
                    />
                    <p v-if="errors.name" class="text-xs text-destructive">
                        {{ errors.name }}
                    </p>
                </div>

                <!-- Slug preview -->
                <div class="space-y-2">
                    <Label for="slug">URL Slug</Label>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground"
                            >app.example.com/</span
                        >
                        <Input
                            id="slug"
                            v-model="slug"
                            placeholder="acme-corp"
                            class="flex-1"
                        />
                    </div>
                    <p v-if="errors.slug" class="text-xs text-destructive">
                        {{ errors.slug }}
                    </p>
                </div>

                <!-- Plan selector -->
                <div v-if="plans.length > 0" class="space-y-3">
                    <Label
                        >Choose a Plan
                        <span class="font-normal text-muted-foreground"
                            >(optional)</span
                        ></Label
                    >
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="plan in plans"
                            :key="plan.id"
                            type="button"
                            class="rounded-lg border-2 p-4 text-left transition-all focus:outline-none"
                            :class="
                                selectedPlanId === plan.id
                                    ? 'border-primary bg-primary/5'
                                    : 'border-border hover:border-primary/40'
                            "
                            @click="selectedPlanId = plan.id"
                        >
                            <div class="text-sm font-semibold">
                                {{ plan.name }}
                            </div>
                            <div class="mt-1 text-base font-bold">
                                {{
                                    plan.price === '0.00'
                                        ? 'Free'
                                        : `€${plan.price}/${plan.interval === 'monthly' ? 'mo' : 'yr'}`
                                }}
                            </div>
                            <div v-if="plan.trial_days > 0" class="mt-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    {{ plan.trial_days }}-day trial
                                </span>
                            </div>
                        </button>
                    </div>
                    <p v-if="errors.plan_id" class="text-xs text-destructive">
                        {{ errors.plan_id }}
                    </p>
                </div>

                <div class="flex gap-3 pt-2">
                    <Button type="submit" :disabled="processing || !name">
                        {{ processing ? 'Creating…' : 'Create Organization' }}
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        @click="router.visit('/tenants')"
                        >Cancel</Button
                    >
                </div>
            </form>
        </div>
    </AppLayout>
</template>
