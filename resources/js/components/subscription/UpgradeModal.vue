<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { Plan, Subscription } from '@/types/tenant';

const props = defineProps<{
    open: boolean;
    targetPlan: Plan | null;
    currentSubscription: Subscription | null;
}>();

const emit = defineEmits<{
    'update:open': [boolean];
    confirmed: [];
}>();

const processing = ref(false);

function confirm() {
    if (!props.targetPlan) return;
    processing.value = true;
    router.put('/subscription/upgrade', { plan_id: props.targetPlan.id }, {
        onFinish: () => { processing.value = false; emit('update:open', false); emit('confirmed'); },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Upgrade Plan</DialogTitle>
                <DialogDescription>
                    Upgrade to <strong>{{ targetPlan?.name }}</strong>
                    <span v-if="currentSubscription?.plan"> from {{ currentSubscription.plan.name }}</span>.
                    You'll be charged a prorated amount for the remainder of your billing cycle.
                </DialogDescription>
            </DialogHeader>
            <div v-if="targetPlan" class="py-4 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">New plan</span>
                    <strong>{{ targetPlan.name }} — €{{ targetPlan.price }}/{{ targetPlan.interval }}</strong>
                </div>
                <div v-if="currentSubscription?.prorated_amount" class="flex justify-between">
                    <span class="text-muted-foreground">Prorated charge today</span>
                    <strong>€{{ currentSubscription.prorated_amount }}</strong>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
                <Button :disabled="processing" @click="confirm">
                    {{ processing ? 'Processing…' : 'Confirm Upgrade' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
