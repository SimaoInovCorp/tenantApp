<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import axios from '@/lib/axios';
import type { OnboardingTask, TenantUser } from '@/types/tenant';

const props = defineProps<{
    tenant: { id: number; name: string; slug: string };
    task?: OnboardingTask;
}>();

const emit = defineEmits<{ next: []; skip: [] }>();

const email = ref('');
const role = ref('member');
const inviting = ref(false);
const inviteError = ref('');
const invited = ref<TenantUser[]>([]);

async function invite() {
    inviting.value = true;
    inviteError.value = '';
    try {
        const res = await axios.post(`/tenants/${props.tenant.id}/users`, {
            email: email.value,
            role: role.value,
        });
        invited.value = res.data.data ?? res.data;
        email.value = '';
        if (props.task && !props.task.is_completed) {
            await axios.put(`/onboarding/${props.task.id}`, {});
        }
    } catch (err: any) {
        inviteError.value = err.response?.data?.message ?? 'Invite failed.';
    } finally {
        inviting.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold">Invite Team Members</h2>
            <p class="text-muted-foreground text-sm mt-1">Add colleagues to your organization.</p>
        </div>

        <form @submit.prevent="invite" class="flex gap-3 items-end">
            <div class="flex-1 space-y-2">
                <Label for="s2-email">Email Address</Label>
                <Input id="s2-email" v-model="email" type="email" placeholder="colleague@example.com" required />
            </div>
            <div class="w-32 space-y-2">
                <Label>Role</Label>
                <Select v-model="role">
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        <SelectItem value="admin">Admin</SelectItem>
                        <SelectItem value="member">Member</SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <Button type="submit" :disabled="inviting">{{ inviting ? 'Inviting…' : 'Invite' }}</Button>
        </form>
        <p v-if="inviteError" class="text-xs text-destructive">{{ inviteError }}</p>

        <div v-if="invited.length > 0" class="space-y-2">
            <p class="text-sm font-medium">Invited members:</p>
            <div v-for="m in invited" :key="m.id" class="flex items-center gap-2">
                <span class="text-sm">{{ m.name }} ({{ m.email }})</span>
                <Badge variant="outline" class="capitalize">{{ m.role }}</Badge>
            </div>
        </div>

        <div class="flex gap-3">
            <Button @click="emit('next')">Continue →</Button>
            <Button variant="outline" @click="emit('skip')">Skip for now</Button>
        </div>
    </div>
</template>
