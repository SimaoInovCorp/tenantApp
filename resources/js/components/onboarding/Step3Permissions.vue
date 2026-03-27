<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import axios from '@/lib/axios';
import type { OnboardingTask } from '@/types/tenant';

const props = defineProps<{
    tenant: { id: number; name: string; slug: string };
    task?: OnboardingTask;
}>();

const emit = defineEmits<{ finish: [] }>();
const finishing = ref(false);

const roles = [
    { name: 'Owner', description: 'Full access: manage billing, members, and all settings. Cannot be removed.', color: 'bg-purple-500' },
    { name: 'Admin', description: 'Can manage members, settings, and subscription. Cannot delete the organization.', color: 'bg-blue-500' },
    { name: 'Member', description: 'Standard access to the workspace. Cannot manage billing or members.', color: 'bg-green-500' },
];

async function finish() {
    finishing.value = true;
    try {
        if (props.task && !props.task.is_completed) {
            await axios.put(`/onboarding/${props.task.id}`, {});
        }
        emit('finish');
    } finally {
        finishing.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold">Roles & Permissions</h2>
            <p class="text-muted-foreground text-sm mt-1">Understand what each role can do in your organization.</p>
        </div>

        <div class="space-y-3">
            <Card v-for="role in roles" :key="role.name">
                <CardHeader class="flex flex-row items-center gap-3 pb-2">
                    <div :class="['h-3 w-3 rounded-full', role.color]"></div>
                    <CardTitle class="text-base">{{ role.name }}</CardTitle>
                </CardHeader>
                <CardContent class="pt-0">
                    <p class="text-sm text-muted-foreground">{{ role.description }}</p>
                </CardContent>
            </Card>
        </div>

        <Button @click="finish" :disabled="finishing">
            {{ finishing ? 'Finishing…' : '🎉 Finish Setup' }}
        </Button>
    </div>
</template>
