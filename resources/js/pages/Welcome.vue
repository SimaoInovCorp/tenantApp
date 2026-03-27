<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);
</script>

<template>
    <Head title="Welcome to inoVcorp" />
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background px-6 text-foreground"
    >
        <!-- Nav / Auth Links -->
        <header class="absolute top-0 right-0 p-6">
            <nav class="flex items-center gap-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="dashboard()"
                    class="inline-flex items-center rounded-md border border-border px-5 py-1.5 text-sm font-medium transition hover:bg-accent"
                >
                    Dashboard
                </Link>
                <template v-else>
                    <Link
                        :href="login()"
                        class="inline-flex items-center rounded-md px-5 py-1.5 text-sm font-medium transition hover:bg-accent"
                    >
                        Log in
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="register()"
                        class="inline-flex items-center rounded-md bg-primary px-5 py-1.5 text-sm font-medium text-primary-foreground transition hover:bg-primary/90"
                    >
                        Get started
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <main class="flex max-w-2xl flex-col items-center gap-8 text-center">
            <h1 class="text-6xl font-black tracking-tight select-none">
                ino<span class="text-red-500">V</span>corp
            </h1>
            <p class="text-xl leading-relaxed text-muted-foreground">
                Multi-tenant SaaS management platform.<br />
                Manage organizations, subscriptions, and plans — all in one
                place.
            </p>
        </main>
    </div>
</template>
