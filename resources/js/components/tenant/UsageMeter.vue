<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    /** Human-readable label (e.g. 'Users'). */
    label: string;
    /** Current count. */
    used: number;
    /** Plan maximum. -1 means unlimited. */
    max: number;
}>();

const isUnlimited = computed(() => props.max === -1);

/** Percentage 0-100, clamped. */
const percent = computed(() => {
    if (isUnlimited.value || props.max === 0) return 0;
    return Math.min(100, Math.round((props.used / props.max) * 100));
});

const progressColor = computed(() => {
    if (percent.value >= 90) return 'bg-destructive';
    if (percent.value >= 70) return 'bg-yellow-500';
    return 'bg-primary';
});
</script>

<template>
    <div class="space-y-1">
        <div class="flex items-center justify-between text-sm">
            <span class="font-medium">{{ label }}</span>
            <span class="text-muted-foreground">
                <template v-if="isUnlimited">
                    {{ used }} / Unlimited
                </template>
                <template v-else>
                    {{ used }} / {{ max }}
                </template>
            </span>
        </div>
        <div
            v-if="!isUnlimited"
            class="h-2 w-full overflow-hidden rounded-full bg-secondary"
            role="progressbar"
            :aria-valuenow="percent"
            aria-valuemin="0"
            aria-valuemax="100"
        >
            <div
                class="h-full rounded-full transition-all"
                :class="progressColor"
                :style="{ width: percent + '%' }"
            />
        </div>
    </div>
</template>
