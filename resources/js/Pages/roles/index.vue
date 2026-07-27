<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, h, ref, resolveComponent, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Roles', panelId: 'roles' }],
});

const props = defineProps({
    roles: Object,
    permissions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const guardFilter = ref('all');
const rowSelection = ref({});
const columnVisibility = ref({});
const modalMode = ref(null);
const selectedRole = ref(null);
const deleteModalOpen = ref(false);
const roleToDelete = ref(null);
const deleting = ref(false);
const UCheckbox = resolveComponent('UCheckbox');

const form = useForm({
    name: '',
    guard_name: 'web',
    permissions: [],
});

const permissionOptions = computed(() => props.permissions?.data || []);
const permissionSearch = ref('');
const permissionGuardFilter = ref('all');
const roleRows = computed(() => {
    const rows = props.roles?.data || [];

    if (guardFilter.value === 'all') {
        return rows;
    }

    return rows.filter((role) => role.guard_name === guardFilter.value);
});
const guardFilterItems = computed(() => {
    const guards = [...new Set((props.roles?.data || []).map((role) => role.guard_name))];

    return [
        { label: 'All', value: 'all' },
        ...guards.map((guard) => ({ label: guard, value: guard })),
    ];
});
const selectedRowsCount = computed(() => Object.keys(rowSelection.value).length);
const displayableColumns = [
    { id: 'name', label: 'Nama' },
    { id: 'guard_name', label: 'Guard' },
    { id: 'permissions_count', label: 'Permissions' },
    { id: 'users_count', label: 'Users' },
    { id: 'actions', label: 'Aksi' },
];
const displayColumnItems = computed(() => [
    displayableColumns.map((column) => ({
        label: column.label,
        type: 'checkbox',
        checked: columnVisibility.value[column.id] !== false,
        onSelect: (event) => {
            event.preventDefault();
            columnVisibility.value = {
                ...columnVisibility.value,
                [column.id]: columnVisibility.value[column.id] === false,
            };
        },
    })),
]);
const permissionGuardFilterItems = computed(() => {
    const guards = [...new Set(permissionOptions.value.map((permission) => permission.guard_name))];

    return [
        { label: 'All guard', value: 'all' },
        ...guards.map((guard) => ({ label: guard, value: guard })),
    ];
});
const filteredPermissionOptions = computed(() => {
    const searchValue = permissionSearch.value.trim().toLowerCase();

    return permissionOptions.value.filter((permission) => {
        const matchesGuard = permissionGuardFilter.value === 'all' || permission.guard_name === permissionGuardFilter.value;
        const matchesSearch = !searchValue
            || permission.name.toLowerCase().includes(searchValue)
            || (permission.description && permission.description.toLowerCase().includes(searchValue));

        return matchesGuard && matchesSearch;
    });
});
const selectedPermissionsCount = computed(() => form.permissions.length);
const allFilteredPermissionsSelected = computed(() => filteredPermissionOptions.value.length > 0 && filteredPermissionOptions.value.every((permission) => form.permissions.includes(permission.id)));
const selectedPermissionPreview = computed(() => permissionOptions.value.filter((permission) => form.permissions.includes(permission.id)).slice(0, 5));
const remainingSelectedPermissionsCount = computed(() => Math.max(0, selectedPermissionsCount.value - selectedPermissionPreview.value.length));

const toggleFilteredPermissions = () => {
    const filteredIds = filteredPermissionOptions.value.map((permission) => permission.id);

    if (allFilteredPermissionsSelected.value) {
        form.permissions = form.permissions.filter((permissionId) => !filteredIds.includes(permissionId));
        return;
    }

    form.permissions = [...new Set([...form.permissions, ...filteredIds])];
};

const clearPermissions = () => {
    form.permissions = [];
};

watch(search, (value) => {
    router.get('/roles', { search: value }, { preserveState: true, replace: true });
});

watch([guardFilter, () => props.roles?.data], () => {
    rowSelection.value = {};
});

const openCreate = () => {
    selectedRole.value = null;
    form.reset();
    form.clearErrors();
    form.guard_name = 'web';
    form.permissions = [];
    permissionSearch.value = '';
    permissionGuardFilter.value = 'all';
    modalMode.value = 'create';
};

const openEdit = (role) => {
    selectedRole.value = role;
    form.clearErrors();
    form.name = role.name;
    form.guard_name = role.guard_name;
    form.permissions = role.permissions.map((permission) => permission.id);
    permissionSearch.value = '';
    permissionGuardFilter.value = 'all';
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
    roleToDelete.value = role;
    deleteModalOpen.value = true;
};

const confirmDelete = () => {
    if (!roleToDelete.value) {
        return;
    }

    deleting.value = true;

    router.delete(`/roles/${roleToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false;
            roleToDelete.value = null;
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

const columns = computed(() => [
    {
        id: 'select',
        header: ({ table }) => h(UCheckbox, {
            modelValue: table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected(),
            'aria-label': 'Pilih semua role',
            class: 'mx-auto',
            'onUpdate:modelValue': (value) => table.toggleAllPageRowsSelected(!!value),
        }),
        cell: ({ row }) => h(UCheckbox, {
            modelValue: row.getIsSelected(),
            'aria-label': `Pilih role ${row.original.name}`,
            class: 'mx-auto',
            'onUpdate:modelValue': (value) => row.toggleSelected(!!value),
        }),
        meta: {
            class: {
                th: 'w-12 text-center',
                td: 'w-12 text-center',
            },
        },
    },
    {
        accessorKey: 'name',
        header: 'Nama',
        meta: {
            class: {
                td: 'font-medium text-highlighted',
            },
        },
    },
    {
        accessorKey: 'guard_name',
        header: 'Guard',
        meta: {
            class: {
                td: 'text-muted',
            },
        },
    },
    {
        accessorKey: 'permissions_count',
        header: 'Permissions',
    },
    {
        accessorKey: 'users_count',
        header: 'Users',
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: {
            class: {
                th: 'w-20 text-right',
                td: 'w-20 text-right',
            },
        },
    },
]);
</script>

<template>
    <div class="space-y-4">
        <section class="space-y-4">
            <UDashboardToolbar
                :ui="{
                    root: 'min-h-0 flex-col items-stretch gap-3 overflow-visible border-b-0 px-0 sm:flex-row sm:items-center sm:px-0',
                    left: 'w-full sm:w-auto',
                    right: 'w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end',
                }"
            >
                <template #left>
                    <div class="relative w-full sm:w-64">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input v-model="search" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari role" />
                    </div>
                </template>

                <template #right>
                    <USelect
                        v-model="guardFilter"
                        :items="guardFilterItems"
                        class="w-full sm:w-32"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />

                    <UDropdownMenu :items="displayColumnItems" :content="{ align: 'end' }">
                        <UButton color="neutral" variant="outline" icon="i-lucide-sliders-horizontal" label="Display" class="w-full justify-center sm:w-auto" />
                    </UDropdownMenu>

                    <UButton icon="i-lucide-plus" label="Tambah Role" class="w-full justify-center sm:w-auto" @click="openCreate" />
                </template>
            </UDashboardToolbar>

            <UCard :ui="{ root: 'overflow-hidden', body: 'p-0!' }">
                <div class="overflow-x-auto">
                    <UTable
                        v-model:column-visibility="columnVisibility"
                        v-model:row-selection="rowSelection"
                        :data="roleRows"
                        :columns="columns"
                        :get-row-id="(row) => String(row.id)"
                        class="min-w-[820px]"
                        :empty="'Belum ada role.'"
                        :ui="{
                            base: 'table-fixed border-separate border-spacing-0',
                            thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                            tbody: '[&>tr]:last:[&>td]:border-b-0',
                            th: 'first:rounded-tl-lg last:rounded-tr-lg border-b border-default',
                            td: 'border-b border-default',
                            tr: 'data-[selected=true]:bg-elevated/40',
                        }"
                    >
                        <template #actions-cell="{ row }">
                            <div class="flex justify-end gap-2">
                                <button class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted" type="button" title="Detail" @click="openShow(row.original)">
                                    <UIcon name="i-lucide-eye" class="size-4" />
                                </button>
                                <button class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted" type="button" title="Edit" @click="openEdit(row.original)">
                                    <UIcon name="i-lucide-pencil" class="size-4" />
                                </button>
                                <button class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10" type="button" title="Hapus" @click="destroyRole(row.original)">
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                            </div>
                        </template>
                    </UTable>
                </div>
            </UCard>

            <p v-if="selectedRowsCount > 0" class="text-sm text-muted">
                {{ selectedRowsCount }} role dipilih.
            </p>

            <PaginationLinks :links="roles.meta.links" />
        </section>

        <div v-if="modalMode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-3xl rounded-lg bg-default p-5 shadow-xl">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">{{ modalMode === 'create' ? 'Tambah Role' : modalMode === 'edit' ? 'Edit Role' : 'Detail Role' }}</h2>
                    <button class="rounded-md p-2 hover:bg-elevated" type="button" @click="closeModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <div v-if="modalMode === 'show'" class="mt-5 space-y-4">
                    <div>
                        <p class="text-sm text-muted">Nama Role</p>
                        <p class="text-base font-semibold text-highlighted">{{ selectedRole.name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-muted">Guard</p>
                        <span class="inline-flex items-center rounded-md bg-elevated px-2 py-0.5 text-xs font-mono text-muted">{{ selectedRole.guard_name }}</span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-highlighted mb-2">Permissions Ditugaskan ({{ selectedRole.permissions.length }})</p>
                        <div v-if="selectedRole.permissions.length > 0" class="grid gap-2.5 sm:grid-cols-2 max-h-80 overflow-y-auto pr-1">
                            <div v-for="permission in selectedRole.permissions" :key="permission.id" class="rounded-lg border border-default bg-elevated/30 p-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-mono text-xs font-semibold text-primary">{{ permission.name }}</span>
                                    <span class="text-[10px] text-muted font-mono bg-default px-1.5 py-0.5 rounded border border-default">{{ permission.guard_name }}</span>
                                </div>
                                <p class="mt-1 text-xs text-muted leading-relaxed">{{ permission.description || 'Tidak ada deskripsi' }}</p>
                            </div>
                        </div>
                        <span v-else class="text-sm text-muted">Tidak ada permission.</span>
                    </div>
                </div>

                <form v-else class="mt-5 space-y-4" @submit.prevent="submit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Nama Role</span>
                            <input v-model="form.name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" placeholder="Contoh: Supervisor, Kasir Senior" required />
                            <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
                        </label>
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Guard</span>
                            <input v-model="form.guard_name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" required />
                            <span v-if="form.errors.guard_name" class="text-xs text-red-600">{{ form.errors.guard_name }}</span>
                        </label>
                    </div>

                    <div class="rounded-lg border border-default overflow-hidden">
                        <div class="border-b border-default bg-elevated/30 p-3.5">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-highlighted">Kelola Hak Akses (Permissions)</p>
                                    <p class="text-xs text-muted">{{ selectedPermissionsCount }} dari {{ permissionOptions.length }} permission dipilih</p>
                                </div>

                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <div class="relative sm:w-64">
                                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                                        <input v-model="permissionSearch" class="w-full rounded-md border border-default bg-default py-1.5 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari nama / deskripsi permission" />
                                    </div>

                                    <USelect
                                        v-model="permissionGuardFilter"
                                        :items="permissionGuardFilterItems"
                                        class="sm:w-36"
                                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                                    />
                                </div>
                            </div>

                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <button class="rounded-md border border-default bg-default px-3 py-1.5 text-xs font-medium hover:bg-elevated transition-colors" type="button" @click="toggleFilteredPermissions">
                                        {{ allFilteredPermissionsSelected ? 'Batalkan semua pilihan filter' : 'Pilih semua hasil filter' }}
                                    </button>
                                    <button class="rounded-md border border-default bg-default px-3 py-1.5 text-xs font-medium hover:bg-elevated transition-colors disabled:opacity-50" type="button" :disabled="selectedPermissionsCount === 0" @click="clearPermissions">
                                        Kosongkan semua
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="max-h-96 overflow-y-auto p-3.5 space-y-3">
                            <div v-if="filteredPermissionOptions.length > 0" class="grid gap-3 sm:grid-cols-2">
                                <label
                                    v-for="permission in filteredPermissionOptions"
                                    :key="permission.id"
                                    class="relative flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition-all duration-150 select-none"
                                    :class="form.permissions.includes(permission.id) ? 'border-primary/60 bg-primary/5 shadow-xs' : 'border-default bg-default hover:border-default/80 hover:bg-elevated/40'"
                                >
                                    <input v-model="form.permissions" class="mt-0.5 size-4 rounded text-primary focus:ring-primary accent-primary" type="checkbox" :value="permission.id" />
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="font-mono text-xs font-semibold text-highlighted">{{ permission.name }}</span>
                                            <span class="text-[10px] text-muted font-mono bg-elevated px-1.5 py-0.5 rounded border border-default">{{ permission.guard_name }}</span>
                                        </span>
                                        <span class="mt-1 block text-xs text-muted leading-snug">{{ permission.description || 'Tidak ada deskripsi.' }}</span>
                                    </span>
                                </label>
                            </div>

                            <div v-else class="py-8 text-center">
                                <UIcon name="i-lucide-shield-alert" class="mx-auto size-8 text-muted/60" />
                                <p class="mt-2 text-sm text-muted">Tidak ada permission yang sesuai dengan kata kunci pencarian.</p>
                            </div>
                        </div>
                    </div>
                    <span v-if="form.errors.permissions" class="text-xs text-red-600">{{ form.errors.permissions }}</span>

                    <div class="flex justify-end gap-2 pt-2">
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
            title="Hapus role?"
            :description="`Role ${roleToDelete?.name || ''} akan dihapus dari sistem.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
