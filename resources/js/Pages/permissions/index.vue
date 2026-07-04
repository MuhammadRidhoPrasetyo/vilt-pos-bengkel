<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Permissions', panelId: 'permissions' }],
});

const props = defineProps({
    permissions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const modalMode = ref(null);
const selectedPermission = ref(null);
const deleteModalOpen = ref(false);
const permissionToDelete = ref(null);
const deleting = ref(false);

const form = useForm({
    name: '',
    guard_name: 'web',
});

watch(search, (value) => {
    router.get('/permissions', { search: value }, { preserveState: true, replace: true });
});

const openCreate = () => {
    selectedPermission.value = null;
    form.reset();
    form.clearErrors();
    form.guard_name = 'web';
    modalMode.value = 'create';
};

const openEdit = (permission) => {
    selectedPermission.value = permission;
    form.clearErrors();
    form.name = permission.name;
    form.guard_name = permission.guard_name;
    modalMode.value = 'edit';
};

const openShow = (permission) => {
    selectedPermission.value = permission;
    modalMode.value = 'show';
};

const closeModal = () => {
    modalMode.value = null;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (modalMode.value === 'edit') {
        form.put(`/permissions/${selectedPermission.value.id}`, options);
        return;
    }

    form.post('/permissions', options);
};

const destroyPermission = (permission) => {
    permissionToDelete.value = permission;
    deleteModalOpen.value = true;
};

const confirmDelete = () => {
    if (!permissionToDelete.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/permissions/${permissionToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false;
            permissionToDelete.value = null;
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};
</script>

<template>
    <div class="space-y-4">
        <section class="space-y-4">
            <UDashboardToolbar>
                <template #left>
                    <div class="flex w-full flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="relative max-w-md grow">
                            <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                            <input v-model="search" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari permission" />
                        </div>
                        <button class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" type="button" @click="openCreate">
                            <UIcon name="i-lucide-plus" class="size-4" />
                            Tambah Permission
                        </button>
                    </div>
                </template>
            </UDashboardToolbar>

            <div class="overflow-hidden rounded-lg border border-default">
                <table class="w-full min-w-[680px] divide-y divide-default text-sm">
                    <thead class="bg-muted/50 text-left text-xs uppercase text-muted">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Guard</th>
                            <th class="px-4 py-3">Roles</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="permission in permissions.data" :key="permission.id">
                            <td class="px-4 py-3 font-medium">{{ permission.name }}</td>
                            <td class="px-4 py-3 text-muted">{{ permission.guard_name }}</td>
                            <td class="px-4 py-3">{{ permission.roles_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button class="rounded-md border border-default p-2 hover:bg-elevated" type="button" title="Detail" @click="openShow(permission)">
                                        <UIcon name="i-lucide-eye" class="size-4" />
                                    </button>
                                    <button class="rounded-md border border-default p-2 hover:bg-elevated" type="button" title="Edit" @click="openEdit(permission)">
                                        <UIcon name="i-lucide-pencil" class="size-4" />
                                    </button>
                                    <button class="rounded-md border border-red-200 p-2 text-red-600 hover:bg-red-50" type="button" title="Hapus" @click="destroyPermission(permission)">
                                        <UIcon name="i-lucide-trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="permissions.data.length === 0">
                            <td class="px-4 py-8 text-center text-muted" colspan="4">Belum ada permission.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationLinks :links="permissions.meta.links" />
        </section>

        <div v-if="modalMode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-lg bg-default p-5 shadow-xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">{{ modalMode === 'create' ? 'Tambah Permission' : modalMode === 'edit' ? 'Edit Permission' : 'Detail Permission' }}</h2>
                    <button class="rounded-md p-2 hover:bg-elevated" type="button" @click="closeModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <div v-if="modalMode === 'show'" class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm text-muted">Nama</p>
                        <p class="font-medium">{{ selectedPermission.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted">Guard</p>
                        <p class="font-medium">{{ selectedPermission.guard_name }}</p>
                    </div>
                </div>

                <form v-else class="mt-5 space-y-4" @submit.prevent="submit">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Nama</span>
                        <input v-model="form.name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" required />
                        <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                    </label>
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Guard</span>
                        <input v-model="form.guard_name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" required />
                        <span v-if="form.errors.guard_name" class="text-xs text-red-600">{{ form.errors.guard_name }}</span>
                    </label>
                    <div class="flex justify-end gap-2">
                        <button class="rounded-md border border-default px-4 py-2 text-sm hover:bg-elevated" type="button" @click="closeModal">Batal</button>
                        <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 disabled:opacity-60" type="submit" :disabled="form.processing">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            title="Hapus permission?"
            :description="`Permission ${permissionToDelete?.name || ''} akan dihapus dari sistem.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
