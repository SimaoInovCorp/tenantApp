import { computed } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import type { Subscription } from '@/types/tenant';

/**
 * Composable that exposes feature-gating helpers based on the active tenant's subscription.
 * Pass an optional subscription object directly (e.g. from Inertia props) for SSR/reactive use.
 */
export function usePlanFeatures(subscription?: Subscription | null) {
    const store = useTenantStore();

    // Use passed subscription OR try to read from store's active tenant (if available)
    const sub = computed<Subscription | null>(() => subscription ?? null);

    const plan = computed(() => sub.value?.plan ?? null);

    /**
     * Returns true if the current plan includes the named feature.
     * Features are listed as strings in plan.features[], e.g. 'api_access'.
     */
    function hasFeature(key: string): boolean {
        return plan.value?.features?.includes(key) ?? false;
    }

    /**
     * Returns the numeric limit for the given key from plan.limits.
     * Returns -1 if not found (treat as unlimited).
     */
    function getLimit(key: string): number {
        return plan.value?.limits?.[key] ?? -1;
    }

    /**
     * Returns true if the current count is at or over the plan limit.
     * Always returns false for unlimited (-1) plans.
     */
    function isAtLimit(key: string, currentCount: number): boolean {
        const limit = getLimit(key);
        if (limit === -1) return false;
        return currentCount >= limit;
    }

    return {
        plan,
        hasFeature,
        getLimit,
        isAtLimit,
    };
}
