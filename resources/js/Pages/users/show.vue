<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router, setLayoutProps } from '@inertiajs/vue3';
import { onUnmounted } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail User', panelId: 'users-show' }],
});

defineProps({
    user: Object,
});

setLayoutProps({
    navbarAction: {
        label: 'Kembali',
        icon: 'i-lucide-arrow-left',
        color: 'neutral',
        variant: 'outline',
        onClick: () => router.visit('/users'),
    },
});

onUnmounted(() => {
    setLayoutProps({ navbarAction: null });
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ user.data.name }}</h2>
                <p class="text-sm text-muted">{{ user.data.email }}</p>
            </div>
            <Link :href="`/users/${user.data.id}/edit`" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90">
                <UIcon name="i-lucide-pencil" class="size-4" />
                Edit
            </Link>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.6fr)]">
            <UCard :ui="{ body: 'p-5!' }">
                <div class="mb-5">
                    <h3 class="text-base font-semibold text-highlighted">Data Diri User</h3>
                    <p class="text-sm text-muted">Informasi profil dan status user.</p>
                </div>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-muted">NIK</dt>
                        <dd class="font-medium">{{ user.data.nik || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Telepon</dt>
                        <dd class="font-medium">{{ user.data.phone || '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-muted">Alamat</dt>
                        <dd class="font-medium">{{ user.data.address || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="font-medium">{{ user.data.active ? 'Aktif' : 'Nonaktif' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Top navigation</dt>
                        <dd class="font-medium">{{ user.data.top_navigation ? 'Ya' : 'Tidak' }}</dd>
                    </div>
                </dl>
            </UCard>

            <UCard :ui="{ body: 'p-5!' }">
                <div>
                    <h3 class="text-base font-semibold text-highlighted">Role User</h3>
                    <p class="text-sm text-muted">{{ user.data.roles.length }} role terhubung</p>
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span v-for="role in user.data.roles" :key="role.id" class="rounded-md bg-elevated px-2 py-1 text-xs">{{ role.name }}</span>
                    <span v-if="user.data.roles.length === 0" class="text-sm text-muted">Tidak ada role.</span>
                </div>
            </UCard>
        </div>
    </div>
</template>
