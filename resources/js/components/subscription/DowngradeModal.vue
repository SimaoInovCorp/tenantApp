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
    router.put('/subscription/downgrade', { plan_id: props.targetPlan.id }, {
        onFinish: () => { processing.value = false; emit('update:open', false); emit('confirmed'); },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Downgrade Plan</DialogTitle>
                <DialogDescription>
                    Downgrade to <strong>{{ targetPlan?.name }}</strong>. The change will take effect at the end of your current billing cycle.
                </DialogDescription>
            </DialogHeader>
            <div v-if="currentSubscription?.ends_at" class="py-4 text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Effective date</span>
                    <strong>{{ new Date(currentSubscription.ends_at).toLocaleDateString() }}</strong>
                </div>
                <p class="text-muted-foreground text-xs">
                    You'll retain access to your current plan's features until {{ new Date(currentSubscription.ends_at).toLocaleDateString() }}.
                </p>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
                <Button :disabled="processing" @click="confirm">
                    {{ processing ? 'Processing…' : 'Confirm Downgrade' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
