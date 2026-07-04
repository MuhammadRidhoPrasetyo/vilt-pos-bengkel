<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    title: String,
    description: String,
});

const page = usePage();

const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash || {});

const navItems = [
    { label: 'Dashboard', href: '/home', icon: 'i-lucide-layout-dashboard' },
    { label: 'Users', href: '/users', icon: 'i-lucide-users' },
    { label: 'Roles', href: '/roles', icon: 'i-lucide-shield' },
    { label: 'Permissions', href: '/permissions', icon: 'i-lucide-key-round' },
];

const isActive = (href) => page.url?.split('?')[0] === href;
</script>

<template>
    <div class="min-h-screen bg-default text-highlighted">
        <aside class="fixed inset-y-0 left-0 hidden w-64 border-r border-default bg-muted/30 p-4 lg:block">
            <div class="flex items-center gap-3 px-2 py-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-primary text-inverted">
                    <UIcon name="i-lucide-badge-dollar-sign" class="size-5" />
                </div>
                <div>
                    <p class="font-semibold">POS Bengkel</p>
                    <p class="text-xs text-muted">Access Management</p>
                </div>
            </div>

            <nav class="mt-6 grid gap-1">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition hover:bg-elevated"
                    :class="isActive(item.href) ? 'bg-elevated text-primary' : 'text-muted'"
                >
                    <UIcon :name="item.icon" class="size-4" />
                    {{ item.label }}
                </Link>
            </nav>
        </aside>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-default bg-default/90 px-4 py-3 backdrop-blur sm:px-6">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold sm:text-xl">{{ title }}</h1>
                        <p v-if="description" class="truncate text-sm text-muted">{{ description }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden text-sm text-muted sm:inline">{{ user?.name }}</span>
                        <button class="inline-flex items-center gap-2 rounded-md border border-default px-3 py-2 text-sm hover:bg-elevated" type="button" @click="router.post('/logout')">
                            <UIcon name="i-lucide-log-out" class="size-4" />
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6">
                <div v-if="flash.success" class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ flash.success }}
                </div>
                <div v-if="flash.error" class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ flash.error }}
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
