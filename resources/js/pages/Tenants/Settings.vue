<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, reactive } from 'vue';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Tenant, TenantUser, Subscription } from '@/types/tenant';
import type { BreadcrumbItem } from '@/types';
import axios from '@/lib/axios';

const props = defineProps<{
    tenant: { data: Tenant };
    subscription?: { data: Subscription } | null;
}>();

const tenant = props.tenant.data;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Tenants', href: '/tenants' },
    { title: tenant.name, href: `/tenants/${tenant.id}` },
];

// ── General tab ──
const generalForm = reactive({ name: tenant.name, slug: tenant.slug });
const generalErrors = ref<Record<string, string>>({});
const generalSaving = ref(false);

async function saveGeneral() {
    generalSaving.value = true;
    generalErrors.value = {};
    router.put(`/tenants/${tenant.id}`, generalForm, {
        onError: (e) => {
            generalErrors.value = e;
        },
        onFinish: () => {
            generalSaving.value = false;
        },
    });
}

// ── Branding tab ──
const brandingForm = reactive({
    primary_color: tenant.primary_color ?? '#6366f1',
});
const logoFile = ref<File | null>(null);
const logoPreview = ref<string | null>(tenant.logo_url ?? null);
const brandingSaving = ref(false);
const brandingError = ref<string | null>(null);

function onLogoChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    logoFile.value = file;
    logoPreview.value = file ? URL.createObjectURL(file) : logoPreview.value;
}

async function saveBranding() {
    brandingSaving.value = true;
    brandingError.value = null;
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('primary_color', brandingForm.primary_color);
    if (logoFile.value) {
        formData.append('logo', logoFile.value);
    }
    try {
        await axios.post(`/tenants/${tenant.id}`, formData);
        toast.success('Branding saved.');
    } catch (err: any) {
        brandingError.value =
            err.response?.data?.message ?? 'Something went wrong.';
    } finally {
        brandingSaving.value = false;
    }
}

// ── Members tab — current members ──
const members = ref<TenantUser[]>([]);
const membersLoading = ref(false);

async function loadMembers() {
    membersLoading.value = true;
    const res = await axios.get(`/tenants/${tenant.id}/users`);
    members.value = res.data.data ?? res.data;
    membersLoading.value = false;
}

async function removeMember(userId: number) {
    await axios.delete(`/tenants/${tenant.id}/users/${userId}`);
    members.value = members.value.filter((m) => m.id !== userId);
    toast.success('Member removed.');
    // Refresh available list so removed user reappears
    await loadAvailable(availablePage.value);
}

async function changeRole(userId: number, role: string) {
    await axios.put(`/tenants/${tenant.id}/users/${userId}`, { role });
    toast.success('Role updated.');
}

// ── Members tab — email invite form ──
const inviteEmail = ref('');
const inviteRole = ref('member');
const inviteErrors = ref<Record<string, string>>({});
const inviteSaving = ref(false);

async function inviteMember() {
    inviteSaving.value = true;
    inviteErrors.value = {};
    try {
        const res = await axios.post(`/tenants/${tenant.id}/users`, {
            email: inviteEmail.value,
            role: inviteRole.value,
        });
        members.value = res.data.data ?? res.data;
        toast.success(`Invitation sent to ${inviteEmail.value}`);
        inviteEmail.value = '';
        // Refresh available list to exclude freshly-invited user
        await loadAvailable(availablePage.value);
    } catch (err: any) {
        inviteErrors.value = err.response?.data?.errors ?? {};
        const msg =
            Object.values(inviteErrors.value).flat().join(', ') ||
            err.response?.data?.message ||
            'Failed to send invitation.';
        toast.error(msg);
    } finally {
        inviteSaving.value = false;
    }
}

// ── Members tab — available users browser ──
type AvailableUser = { id: number; name: string; email: string };

const availableUsers = ref<AvailableUser[]>([]);
const availableMeta = ref<{
    current_page: number;
    last_page: number;
    total: number;
} | null>(null);
const availableSearch = ref('');
const availableLoading = ref(false);
const availablePage = ref(1);
const browseRole = ref('member');
const invitingUserId = ref<number | null>(null);

let searchTimer: ReturnType<typeof setTimeout> | null = null;

async function loadAvailable(page = 1) {
    availableLoading.value = true;
    availablePage.value = page;
    const res = await axios.get(`/tenants/${tenant.id}/users/available`, {
        params: {
            page,
            search: availableSearch.value.trim() || undefined,
        },
    });
    availableUsers.value = res.data.data;
    availableMeta.value = res.data.meta;
    availableLoading.value = false;
}

function onSearchInput() {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadAvailable(1), 300);
}

async function inviteFromList(user: AvailableUser) {
    invitingUserId.value = user.id;
    try {
        const res = await axios.post(`/tenants/${tenant.id}/users`, {
            email: user.email,
            role: browseRole.value,
        });
        members.value = res.data.data ?? res.data;
        toast.success(`Invitation sent to ${user.name}`);
        // Remove from available list immediately for instant feedback
        availableUsers.value = availableUsers.value.filter(
            (u) => u.id !== user.id,
        );
        if (availableMeta.value) availableMeta.value.total--;
    } catch (err: any) {
        const msg =
            err.response?.data?.errors?.email?.[0] ||
            err.response?.data?.message ||
            'Failed to send invitation.';
        toast.error(msg);
    } finally {
        invitingUserId.value = null;
    }
}

// ── Danger Zone ──
const showDeleteDialog = ref(false);
const deleteProcessing = ref(false);

async function deleteTenant() {
    deleteProcessing.value = true;
    router.delete(`/tenants/${tenant.id}`, {
        onFinish: () => {
            deleteProcessing.value = false;
            showDeleteDialog.value = false;
        },
    });
}

// Load members + available users on first visit to the Members tab
function onTabChange(tab: string) {
    if (tab === 'members') {
        if (members.value.length === 0) loadMembers();
        if (availableUsers.value.length === 0) loadAvailable(1);
    }
}
</script>

<template>
    <Head :title="`${tenant.name} · Settings`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl space-y-6 p-6">
            <h1 class="text-2xl font-semibold">{{ tenant.name }} — Settings</h1>

            <Tabs default-value="general" @update:model-value="onTabChange">
                <TabsList class="mb-6">
                    <TabsTrigger value="general">General</TabsTrigger>
                    <TabsTrigger value="branding">Branding</TabsTrigger>
                    <TabsTrigger value="members">Members</TabsTrigger>
                    <TabsTrigger value="danger" class="text-destructive"
                        >Danger Zone</TabsTrigger
                    >
                </TabsList>

                <!-- General -->
                <TabsContent value="general">
                    <form @submit.prevent="saveGeneral" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="gen-name">Organization Name</Label>
                            <Input
                                id="gen-name"
                                v-model="generalForm.name"
                                required
                            />
                            <p
                                v-if="generalErrors.name"
                                class="text-xs text-destructive"
                            >
                                {{ generalErrors.name }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="gen-slug">URL Slug</Label>
                            <Input id="gen-slug" v-model="generalForm.slug" />
                            <p
                                v-if="generalErrors.slug"
                                class="text-xs text-destructive"
                            >
                                {{ generalErrors.slug }}
                            </p>
                        </div>
                        <Button type="submit" :disabled="generalSaving">
                            {{ generalSaving ? 'Saving…' : 'Save Changes' }}
                        </Button>
                    </form>
                </TabsContent>

                <!-- Branding -->
                <TabsContent value="branding">
                    <form @submit.prevent="saveBranding" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="logo"
                                >Logo
                                <span class="font-normal text-muted-foreground"
                                    >(optional)</span
                                ></Label
                            >
                            <input
                                id="logo"
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
                        <div class="space-y-2">
                            <Label for="color">Primary Color</Label>
                            <p class="text-xs text-muted-foreground">
                                Used as the accent color in your organization's
                                interface and branding materials.
                            </p>
                            <div class="flex items-center gap-3">
                                <input
                                    id="color"
                                    v-model="brandingForm.primary_color"
                                    type="color"
                                    class="h-10 w-16 cursor-pointer rounded border"
                                />
                                <span
                                    class="font-mono text-sm text-muted-foreground"
                                    >{{ brandingForm.primary_color }}</span
                                >
                                <div
                                    class="h-8 w-24 rounded"
                                    :style="{
                                        background: brandingForm.primary_color,
                                    }"
                                ></div>
                            </div>
                        </div>
                        <p
                            v-if="brandingError"
                            class="text-sm text-destructive"
                        >
                            {{ brandingError }}
                        </p>
                        <Button type="submit" :disabled="brandingSaving">
                            {{ brandingSaving ? 'Saving…' : 'Save Branding' }}
                        </Button>
                    </form>
                </TabsContent>

                <!-- Members -->
                <TabsContent value="members">
                    <div class="space-y-8">
                        <!-- ── Invite by email ── -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold">
                                Invite by Email
                            </h3>
                            <form
                                @submit.prevent="inviteMember"
                                class="flex flex-wrap items-end gap-3"
                            >
                                <div class="min-w-48 flex-1 space-y-2">
                                    <Label for="inv-email">Email Address</Label>
                                    <Input
                                        id="inv-email"
                                        v-model="inviteEmail"
                                        type="email"
                                        placeholder="colleague@example.com"
                                        required
                                    />
                                    <p
                                        v-if="inviteErrors.email"
                                        class="text-xs text-destructive"
                                    >
                                        {{ inviteErrors.email }}
                                    </p>
                                </div>
                                <div class="w-36 space-y-2">
                                    <Label>Role</Label>
                                    <Select v-model="inviteRole">
                                        <SelectTrigger
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="admin"
                                                >Admin</SelectItem
                                            >
                                            <SelectItem value="member"
                                                >Member</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button type="submit" :disabled="inviteSaving">
                                    {{
                                        inviteSaving
                                            ? 'Sending…'
                                            : 'Send Invite'
                                    }}
                                </Button>
                            </form>
                        </div>

                        <div class="border-t" />

                        <!-- ── Current Members ── -->
                        <div class="space-y-3">
                            <h3 class="text-sm font-semibold">
                                Current Members
                            </h3>
                            <div
                                v-if="membersLoading"
                                class="text-sm text-muted-foreground"
                            >
                                Loading members…
                            </div>
                            <div
                                v-else-if="members.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                No members loaded yet.
                            </div>
                            <table v-else class="w-full text-sm">
                                <thead class="border-b text-left">
                                    <tr>
                                        <th class="pb-2 font-medium">Name</th>
                                        <th class="pb-2 font-medium">Email</th>
                                        <th class="pb-2 font-medium">Role</th>
                                        <th class="pb-2"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="member in members"
                                        :key="member.id"
                                        class="border-b last:border-0"
                                    >
                                        <td class="py-2">{{ member.name }}</td>
                                        <td class="py-2 text-muted-foreground">
                                            {{ member.email }}
                                        </td>
                                        <td class="py-2">
                                            <Badge
                                                v-if="member.role === 'owner'"
                                                variant="default"
                                                >Owner</Badge
                                            >
                                            <Select
                                                v-else
                                                :model-value="member.role"
                                                @update:model-value="
                                                    changeRole(
                                                        member.id,
                                                        $event,
                                                    )
                                                "
                                            >
                                                <SelectTrigger class="h-7 w-24"
                                                    ><SelectValue
                                                /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="admin"
                                                        >Admin</SelectItem
                                                    >
                                                    <SelectItem value="member"
                                                        >Member</SelectItem
                                                    >
                                                </SelectContent>
                                            </Select>
                                        </td>
                                        <td class="py-2 text-right">
                                            <Button
                                                v-if="member.role !== 'owner'"
                                                variant="ghost"
                                                size="sm"
                                                class="text-destructive hover:text-destructive"
                                                @click="removeMember(member.id)"
                                            >
                                                Remove
                                            </Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="border-t" />

                        <!-- ── Browse registered users ── -->
                        <div class="space-y-3">
                            <div
                                class="flex flex-wrap items-center justify-between gap-3"
                            >
                                <h3 class="text-sm font-semibold">
                                    Browse Registered Users
                                </h3>
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="text-muted-foreground"
                                        >Invite as:</span
                                    >
                                    <Select v-model="browseRole" class="w-28">
                                        <SelectTrigger class="h-8"
                                            ><SelectValue
                                        /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="admin"
                                                >Admin</SelectItem
                                            >
                                            <SelectItem value="member"
                                                >Member</SelectItem
                                            >
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>

                            <Input
                                v-model="availableSearch"
                                placeholder="Search by name or email…"
                                @input="onSearchInput"
                            />

                            <!-- Loading -->
                            <div
                                v-if="availableLoading"
                                class="py-4 text-center text-sm text-muted-foreground"
                            >
                                Loading…
                            </div>

                            <!-- Empty state -->
                            <div
                                v-else-if="availableUsers.length === 0"
                                class="py-4 text-center text-sm text-muted-foreground"
                            >
                                {{
                                    availableSearch
                                        ? 'No users match your search.'
                                        : 'All registered users are already members of this organization.'
                                }}
                            </div>

                            <!-- User list -->
                            <div v-else class="divide-y rounded-lg border">
                                <div
                                    v-for="user in availableUsers"
                                    :key="user.id"
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">
                                            {{ user.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ user.email }}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        class="ml-4 shrink-0"
                                        :disabled="invitingUserId === user.id"
                                        @click="inviteFromList(user)"
                                    >
                                        {{
                                            invitingUserId === user.id
                                                ? 'Sending…'
                                                : 'Invite'
                                        }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Pagination -->
                            <div
                                v-if="
                                    availableMeta && availableMeta.last_page > 1
                                "
                                class="flex items-center justify-between pt-1"
                            >
                                <span class="text-xs text-muted-foreground">
                                    Page {{ availableMeta.current_page }} of
                                    {{ availableMeta.last_page }} ·
                                    {{ availableMeta.total }} users
                                </span>
                                <div class="flex gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        :disabled="
                                            availableMeta.current_page <= 1
                                        "
                                        @click="
                                            loadAvailable(
                                                availableMeta!.current_page - 1,
                                            )
                                        "
                                        >←</Button
                                    >
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        :disabled="
                                            availableMeta.current_page >=
                                            availableMeta.last_page
                                        "
                                        @click="
                                            loadAvailable(
                                                availableMeta!.current_page + 1,
                                            )
                                        "
                                        >→</Button
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </TabsContent>

                <!-- Danger Zone -->
                <TabsContent value="danger">
                    <div
                        v-if="tenant.current_user_role === 'owner'"
                        class="space-y-4 rounded-lg border border-destructive/30 p-6"
                    >
                        <h3 class="font-semibold text-destructive">
                            Delete Organization
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Permanently delete
                            <strong>{{ tenant.name }}</strong> and all its data.
                            This action cannot be undone.
                        </p>
                        <Button
                            variant="destructive"
                            @click="showDeleteDialog = true"
                            >Delete Organization</Button
                        >
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        Only the owner can delete this organization.
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <!-- Delete confirmation dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete "{{ tenant.name }}"?</DialogTitle>
                    <DialogDescription>
                        This will permanently delete the organization and all
                        associated data. This cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" @click="showDeleteDialog = false"
                        >Cancel</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="deleteProcessing"
                        @click="deleteTenant"
                    >
                        {{ deleteProcessing ? 'Deleting…' : 'Yes, Delete' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
