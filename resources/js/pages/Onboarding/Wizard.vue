<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Step1Branding from '@/components/onboarding/Step1Branding.vue';
import Step2InviteUsers from '@/components/onboarding/Step2InviteUsers.vue';
import Step3Permissions from '@/components/onboarding/Step3Permissions.vue';
import type { OnboardingTask } from '@/types/tenant';
import type { BreadcrumbItem } from '@/types';

const props = defineProps<{
    tenant: { id: number; name: string; slug: string };
    tasks: { data: OnboardingTask[] };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Onboarding', href: '/onboarding' },
];

const steps = ['Branding', 'Invite Users', 'Permissions'];
const currentStep = ref(1);
const taskList = computed(() => props.tasks?.data ?? []);

const taskFor = (key: string) => taskList.value.find(t => t.task_key === key);

function next() { if (currentStep.value < 3) currentStep.value++; }
function back() { if (currentStep.value > 1) currentStep.value--; }
function finish() { router.visit('/dashboard'); }
</script>

<template>
    <Head title="Onboarding" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-2xl mx-auto">
            <!-- Step indicator -->
            <div class="flex items-center gap-2 mb-8">
                <template v-for="(step, i) in steps" :key="i">
                    <div class="flex items-center gap-2">
                        <div
                            class="h-8 w-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors"
                            :class="currentStep === i + 1
                                ? 'bg-primary text-primary-foreground'
                                : currentStep > i + 1
                                    ? 'bg-primary/20 text-primary'
                                    : 'bg-muted text-muted-foreground'"
                        >
                            {{ i + 1 }}
                        </div>
                        <span
                            class="text-sm hidden sm:inline"
                            :class="currentStep === i + 1 ? 'font-semibold' : 'text-muted-foreground'"
                        >
                            {{ step }}
                        </span>
                    </div>
                    <div v-if="i < steps.length - 1" class="flex-1 h-px bg-border" />
                </template>
            </div>

            <!-- Step content -->
            <div class="min-h-[300px]">
                <Step1Branding
                    v-if="currentStep === 1"
                    :tenant="tenant"
                    :task="taskFor('branding')"
                    @next="next"
                />
                <Step2InviteUsers
                    v-else-if="currentStep === 2"
                    :tenant="tenant"
                    :task="taskFor('invite_users')"
                    @next="next"
                    @skip="next"
                />
                <Step3Permissions
                    v-else-if="currentStep === 3"
                    :tenant="tenant"
                    :task="taskFor('set_permissions')"
                    @finish="finish"
                />
            </div>

            <!-- Navigation -->
            <div class="flex items-center justify-between mt-8 pt-4 border-t">
                <Button v-if="currentStep > 1" variant="outline" @click="back">← Back</Button>
                <div v-else></div>
                <div class="text-xs text-muted-foreground">Step {{ currentStep }} of {{ steps.length }}</div>
            </div>
        </div>
    </AppLayout>
</template>
