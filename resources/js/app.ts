import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Inject X-Tenant-Slug into all Inertia (non-Axios) navigations
router.on('before', (event) => {
    const slug = localStorage.getItem('active_tenant_slug');
    if (slug) {
        (event.detail.visit.headers as Record<string, string>)['X-Tenant-Slug'] = slug;
    }
});

// This will set light / dark mode on page load...
initializeTheme();
