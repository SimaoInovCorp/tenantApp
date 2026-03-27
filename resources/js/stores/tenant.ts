import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import type { Tenant } from '@/types/tenant';

const STORAGE_KEY = 'active_tenant';
const STORAGE_SLUG_KEY = 'active_tenant_slug';

/**
 * Pinia store for multi-tenant state management.
 *
 * Persists the active tenant in localStorage so it survives page reloads.
 * The axios interceptor in lib/axios.ts reads activeTenant.slug and adds
 * X-Tenant-Slug to every API request.
 */
export const useTenantStore = defineStore('tenant', () => {
    // ── State ──────────────────────────────────────────────────────────────────
    const tenants = ref<Tenant[]>([]);
    const activeTenant = ref<Tenant | null>(null);

    // Hydrate from localStorage on store creation
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        try {
            activeTenant.value = JSON.parse(stored) as Tenant;
        } catch {
            localStorage.removeItem(STORAGE_KEY);
        }
    }

    // ── Getters ────────────────────────────────────────────────────────────────
    const hasActiveTenant = computed(() => activeTenant.value !== null);
    const activeTenantRole = computed<string | null>(
        () => activeTenant.value?.current_user_role ?? null
    );

    // ── Actions ────────────────────────────────────────────────────────────────

    /** Fetch all tenants for the authenticated user and update state. */
    async function fetchTenants(): Promise<void> {
        const { data } = await axios.get<{ data: Tenant[] }>('/tenants', {
            headers: { Accept: 'application/json' },
        });
        tenants.value = data.data;
    }

    /** Persist the given tenant as the active one. */
    function setActiveTenant(tenant: Tenant): void {
        activeTenant.value = tenant;
        localStorage.setItem(STORAGE_KEY, JSON.stringify(tenant));
        localStorage.setItem(STORAGE_SLUG_KEY, tenant.slug);
    }

    /**
     * Switch to a different tenant by slug.
     * Posts to the backend to validate membership, then persists and reloads.
     */
    async function switchTenant(slug: string): Promise<void> {
        const { data } = await axios.post<Tenant>('/api/switch-tenant', { slug });
        setActiveTenant(data);
        // Full page reload ensures all Inertia page props refresh with new tenant context
        window.location.href = '/dashboard';
    }

    /** Clear active tenant state and localStorage (call on logout). */
    function clearActiveTenant(): void {
        activeTenant.value = null;
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(STORAGE_SLUG_KEY);
    }

    return {
        // State
        tenants,
        activeTenant,
        // Getters
        hasActiveTenant,
        activeTenantRole,
        // Actions
        fetchTenants,
        setActiveTenant,
        switchTenant,
        clearActiveTenant,
    };
});
