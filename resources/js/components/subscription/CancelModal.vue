<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Subscription } from '@/types/tenant';

const props = defineProps<{
    open: boolean;
    subscription: Subscription | null;
}>();

const emit = defineEmits<{
    'update:open': [boolean];
    confirmed: [];
}>();

const reason = ref('');
const processing = ref(false);

function confirm() {
    processing.value = true;
    router.delete('/subscription', { data: { reason: reason.value || undefined } } as any, {
        onFinish: () => { processing.value = false; emit('update:open', false); emit('confirmed'); },
    } as any);
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Cancel Subscription</DialogTitle>
                <DialogDescription>
                    Your subscription will be cancelled. You'll retain access until
                    <strong v-if="subscription?.ends_at">{{ new Date(subscription.ends_at).toLocaleDateString() }}</strong>.
                </DialogDescription>
            </DialogHeader>
            <div class="py-4 space-y-3">
                <div class="space-y-2">
                    <Label for="cancel-reason">Reason <span class="text-muted-foreground font-normal">(optional)</span></Label>
                    <Textarea id="cancel-reason" v-model="reason" placeholder="Let us know why you're leaving…" rows="3" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">Keep Subscription</Button>
                <Button variant="destructive" :disabled="processing" @click="confirm">
                    {{ processing ? 'Cancelling…' : 'Cancel Subscription' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
