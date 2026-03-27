<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { Plan } from '@/types/tenant';

const props = defineProps<{
    plan: Plan | null | undefined;
    status: 'active' | 'trial' | 'canceled' | 'expired' | string;
}>();

/** Map subscription status to Badge variant. */
const variant = computed(() => {
    switch (props.status) {
        case 'active':   return 'default';
        case 'trial':    return 'secondary';
        case 'canceled': return 'outline';
        case 'expired':  return 'destructive';
        default:         return 'secondary';
    }
});

const label = computed(() => {
    const planName = props.plan?.name ?? 'No Plan';
    const statusLabel = props.status.charAt(0).toUpperCase() + props.status.slice(1);
    return `${planName} · ${statusLabel}`;
});
</script>

<template>
    <Badge :variant="variant">
        {{ label }}
    </Badge>
</template>
