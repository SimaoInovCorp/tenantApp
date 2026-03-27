import axios from 'axios';
import { useTenantStore } from '@/stores/tenant';

/**
 * Pre-configured Axios instance.
 *
 * - Includes CSRF cookie credentials automatically.
 * - Request interceptor injects X-Tenant-Slug from the active Pinia store,
 *   falling back to localStorage for SSR/boot scenarios.
 *
 * Import and use this instead of raw axios for all tenant-scoped API calls.
 */
const api = axios.create({
    withCredentials: true,
    headers: {
        'Accept':       'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

// Inject the active tenant slug into every outgoing request
api.interceptors.request.use((config) => {
    let slug: string | null = null;

    // Try Pinia store first (preferred — reactive)
    try {
        const store = useTenantStore();
        slug = store.activeTenant?.slug ?? null;
    } catch {
        // Pinia may not be ready during bootstrapping — fall back to localStorage
        slug = localStorage.getItem('active_tenant_slug');
    }

    if (slug) {
        config.headers['X-Tenant-Slug'] = slug;
    }

    return config;
});

export default api;
