<script setup lang="ts">
import { ref, reactive } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from '@/lib/axios';
import type { OnboardingTask } from '@/types/tenant';

const props = defineProps<{
    tenant: { id: number; name: string; slug: string };
    task?: OnboardingTask;
}>();

const emit = defineEmits<{ next: [] }>();

const form = reactive({
    name: props.tenant.name,
    primary_color: '#6366f1',
});

const logoFile = ref<File | null>(null);
const logoPreview = ref<string | null>(null);
const saving = ref(false);
const saveError = ref<string | null>(null);

function onLogoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    logoFile.value = file;
    logoPreview.value = file ? URL.createObjectURL(file) : null;
}

async function save() {
    saving.value = true;
    saveError.value = null;
    try {
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('name', form.name);
        formData.append('primary_color', form.primary_color);
        if (logoFile.value) {
            formData.append('logo', logoFile.value);
        }
        await axios.post(`/tenants/${props.tenant.id}`, formData);

        // Mark onboarding task done — non-critical, don't block advancing on failure
        if (props.task && !props.task.is_completed) {
            try {
                await axios.put(`/onboarding/${props.task.id}`, {});
            } catch {
                // Task marking failed — acceptable, user can still continue
            }
        }

        emit('next');
    } catch (err: any) {
        saveError.value =
            err.response?.data?.message ??
            'Something went wrong. Please try again.';
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold">Branding</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Customize how your organization looks.
            </p>
        </div>

        <div class="space-y-4">
            <div class="space-y-2">
                <Label for="s1-name">Organization Name</Label>
                <Input id="s1-name" v-model="form.name" required />
            </div>

            <!-- Logo upload -->
            <div class="space-y-2">
                <Label for="s1-logo"
                    >Logo
                    <span class="font-normal text-muted-foreground"
                        >(optional)</span
                    ></Label
                >
                <input
                    id="s1-logo"
                    type="file"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    class="block w-full cursor-pointer text-sm text-muted-foreground file:mr-4 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary hover:file:bg-primary/20"
                    @change="onLogoChange"
                />
                <img
                    v-if="logoPreview"
                    :src="logoPreview"
                    alt="Logo preview"
                    class="mt-2 h-16 w-16 rounded-lg border object-cover"
                />
                <p class="text-xs text-muted-foreground">
                    JPEG, PNG, GIF or WebP — max 2 MB
                </p>
            </div>

            <!-- Primary color -->
            <div class="space-y-2">
                <Label for="s1-color">Primary Color</Label>
                <p class="text-xs text-muted-foreground">
                    Used as the accent color in your organization's interface
                    and branding materials.
                </p>
                <div class="flex items-center gap-3">
                    <input
                        id="s1-color"
                        v-model="form.primary_color"
                        type="color"
                        class="h-10 w-16 cursor-pointer rounded border"
                    />
                    <span class="font-mono text-sm text-muted-foreground">{{
                        form.primary_color
                    }}</span>
                    <div
                        class="h-8 w-24 rounded"
                        :style="{ background: form.primary_color }"
                    ></div>
                </div>
            </div>
        </div>

        <p v-if="saveError" class="text-sm text-destructive">{{ saveError }}</p>

        <Button @click="save" :disabled="saving">
            {{ saving ? 'Saving…' : 'Save & Continue →' }}
        </Button>
    </div>
</template>
