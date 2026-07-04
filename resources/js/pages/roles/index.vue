<script setup>
import AdminShell from '../../Components/AdminShell.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    roles: Object,
    permissions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const modalMode = ref(null);
const selectedRole = ref(null);

const form = useForm({
    name: '',
    guard_name: 'web',
    permissions: [],
});

const permissionOptions = computed(() => props.permissions?.data || []);

watch(search, (value) => {
    router.get('/roles', { search: value }, { preserveState: true, replace: true });
});

const openCreate = () => {
    selectedRole.value = null;
    form.reset();
    form.clearErrors();
    form.guard_name = 'web';
    form.permissions = [];
    modalMode.value = 'create';
};

const openEdit = (role) => {
    selectedRole.value = role;
    form.clearErrors();
    form.name = role.name;
    form.guard_name = role.guard_name;
    form.permissions = role.permissions.map((permission) => permission.id);
    modalMode.value = 'edit';
};

const openShow = (role) => {
    selectedRole.value = role;
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
        form.put(`/roles/${selectedRole.value.id}`, options);
        return;
    }

    form.post('/roles', options);
};

const destroyRole = (role) => {
    if (confirm(`Hapus role ${role.name}?`)) {
        router.delete(`/roles/${role.id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <AdminShell title="Roles" description="Kelola role dan permission yang melekat pada role.">
        <section class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative max-w-md grow">
                    <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                    <input v-model="search" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari role" />
                </div>
                <button class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" type="button" @click="openCreate">
                    <UIcon name="i-lucide-plus" class="size-4" />
                    Tambah Role
                </button>
            </div>

            <div class="overflow-hidden rounded-lg border border-default">
                <table class="w-full min-w-[760px] divide-y divide-default text-sm">
                    <thead class="bg-muted/50 text-left text-xs uppercase text-muted">
                        <tr>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Guard</th>
                            <th class="px-4 py-3">Permissions</th>
                            <th class="px-4 py-3">Users</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="role in roles.data" :key="role.id">
                            <td class="px-4 py-3 font-medium">{{ role.name }}</td>
                            <td class="px-4 py-3 text-muted">{{ role.guard_name }}</td>
                            <td class="px-4 py-3">{{ role.permissions_count }}</td>
                            <td class="px-4 py-3">{{ role.users_count }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button class="rounded-md border border-default p-2 hover:bg-elevated" type="button" title="Detail" @click="openShow(role)">
                                        <UIcon name="i-lucide-eye" class="size-4" />
                                    </button>
                                    <button class="rounded-md border border-default p-2 hover:bg-elevated" type="button" title="Edit" @click="openEdit(role)">
                                        <UIcon name="i-lucide-pencil" class="size-4" />
                                    </button>
                                    <button class="rounded-md border border-red-200 p-2 text-red-600 hover:bg-red-50" type="button" title="Hapus" @click="destroyRole(role)">
                                        <UIcon name="i-lucide-trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="roles.data.length === 0">
                            <td class="px-4 py-8 text-center text-muted" colspan="5">Belum ada role.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <PaginationLinks :links="roles.meta.links" />
        </section>

        <div v-if="modalMode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-xl rounded-lg bg-default p-5 shadow-xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">{{ modalMode === 'create' ? 'Tambah Role' : modalMode === 'edit' ? 'Edit Role' : 'Detail Role' }}</h2>
                    <button class="rounded-md p-2 hover:bg-elevated" type="button" @click="closeModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <div v-if="modalMode === 'show'" class="mt-5 space-y-4">
                    <div>
                        <p class="text-sm text-muted">Nama</p>
                        <p class="font-medium">{{ selectedRole.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted">Permissions</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span v-for="permission in selectedRole.permissions" :key="permission.id" class="rounded-md bg-elevated px-2 py-1 text-xs">{{ permission.name }}</span>
                            <span v-if="selectedRole.permissions.length === 0" class="text-sm text-muted">Tidak ada permission.</span>
                        </div>
                    </div>
                </div>

                <form v-else class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
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
                    </div>

                    <div class="grid max-h-56 gap-2 overflow-y-auto rounded-md border border-default p-3 sm:grid-cols-2">
                        <label v-for="permission in permissionOptions" :key="permission.id" class="flex items-center gap-2 text-sm">
                            <input v-model="form.permissions" class="size-4" type="checkbox" :value="permission.id" />
                            {{ permission.name }}
                        </label>
                    </div>
                    <span v-if="form.errors.permissions" class="text-xs text-red-600">{{ form.errors.permissions }}</span>

                    <div class="flex justify-end gap-2">
                        <button class="rounded-md border border-default px-4 py-2 text-sm hover:bg-elevated" type="button" @click="closeModal">Batal</button>
                        <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 disabled:opacity-60" type="submit" :disabled="form.processing">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminShell>
</template>
