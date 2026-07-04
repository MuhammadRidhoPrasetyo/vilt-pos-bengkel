<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Users', panelId: 'users' }],
});

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const deleteModalOpen = ref(false);
const userToDelete = ref(null);
const deleting = ref(false);

watch(search, (value) => {
    router.get('/users', { search: value }, { preserveState: true, replace: true });
});

const destroyUser = (user) => {
    userToDelete.value = user;
    deleteModalOpen.value = true;
};

const confirmDelete = () => {
    if (!userToDelete.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/users/${userToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false;
            userToDelete.value = null;
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};
</script>

<template>
    <section class="space-y-4">
        <UDashboardToolbar>
            <template #left>
                <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative max-w-md grow">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input v-model="search" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari user" />
                    </div>
                    <Link href="/users/create" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90">
                        <UIcon name="i-lucide-plus" class="size-4" />
                        Tambah User
                    </Link>
                </div>
            </template>
        </UDashboardToolbar>

        <div class="overflow-hidden rounded-lg border border-default">
            <table class="w-full min-w-[900px] divide-y divide-default text-sm">
                    <thead class="bg-muted/50 text-left text-xs uppercase text-muted">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Telepon</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="user in users.data" :key="user.id">
                            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-3 text-muted">{{ user.email }}</td>
                            <td class="px-4 py-3 text-muted">{{ user.phone || '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="role in user.roles" :key="role.id" class="rounded-md bg-elevated px-2 py-1 text-xs">{{ role.name }}</span>
                                    <span v-if="user.roles.length === 0" class="text-muted">-</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2 py-1 text-xs" :class="user.active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                                    {{ user.active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="`/users/${user.id}`" class="rounded-md border border-default p-2 hover:bg-elevated" title="Detail">
                                        <UIcon name="i-lucide-eye" class="size-4" />
                                    </Link>
                                    <Link :href="`/users/${user.id}/edit`" class="rounded-md border border-default p-2 hover:bg-elevated" title="Edit">
                                        <UIcon name="i-lucide-pencil" class="size-4" />
                                    </Link>
                                    <button class="rounded-md border border-red-200 p-2 text-red-600 hover:bg-red-50" type="button" title="Hapus" @click="destroyUser(user)">
                                        <UIcon name="i-lucide-trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data.length === 0">
                            <td class="px-4 py-8 text-center text-muted" colspan="6">Belum ada user.</td>
                        </tr>
                    </tbody>
            </table>
        </div>

        <PaginationLinks :links="users.meta.links" />

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            title="Hapus user?"
            :description="`User ${userToDelete?.name || ''} akan dihapus dari sistem.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </section>
</template>
