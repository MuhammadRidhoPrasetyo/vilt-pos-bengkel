<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, h, ref, resolveComponent, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Permissions', panelId: 'permissions' }],
});

const props = defineProps({
    permissions: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const guardFilter = ref('all');
const rowSelection = ref({});
const columnVisibility = ref({});
const modalMode = ref(null);
const selectedPermission = ref(null);
const deleteModalOpen = ref(false);
const permissionToDelete = ref(null);
const deleting = ref(false);
const UCheckbox = resolveComponent('UCheckbox');

const form = useForm({
    name: '',
    guard_name: 'web',
});

const permissionRows = computed(() => {
    const rows = props.permissions?.data || [];

    if (guardFilter.value === 'all') {
        return rows;
    }

    return rows.filter((permission) => permission.guard_name === guardFilter.value);
});
const guardFilterItems = computed(() => {
    const guards = [...new Set((props.permissions?.data || []).map((permission) => permission.guard_name))];

    return [
        { label: 'All', value: 'all' },
        ...guards.map((guard) => ({ label: guard, value: guard })),
    ];
});
const selectedRowsCount = computed(() => Object.keys(rowSelection.value).length);
const displayableColumns = [
    { id: 'name', label: 'Nama' },
    { id: 'guard_name', label: 'Guard' },
    { id: 'roles_count', label: 'Roles' },
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

watch(search, (value) => {
    router.get('/permissions', { search: value }, { preserveState: true, replace: true });
});

watch([guardFilter, () => props.permissions?.data], () => {
    rowSelection.value = {};
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

const columns = computed(() => [
    {
        id: 'select',
        header: ({ table }) => h(UCheckbox, {
            modelValue: table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected(),
            'aria-label': 'Pilih semua permission',
            class: 'mx-auto',
            'onUpdate:modelValue': (value) => table.toggleAllPageRowsSelected(!!value),
        }),
        cell: ({ row }) => h(UCheckbox, {
            modelValue: row.getIsSelected(),
            'aria-label': `Pilih permission ${row.original.name}`,
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
        accessorKey: 'roles_count',
        header: 'Roles',
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
                        <input v-model="search" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari permission" />
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

                    <UButton icon="i-lucide-plus" label="Tambah Permission" class="w-full justify-center sm:w-auto" @click="openCreate" />
                </template>
            </UDashboardToolbar>

            <UCard :ui="{ root: 'overflow-hidden', body: 'p-0!' }">
                <div class="overflow-x-auto">
                    <UTable
                        v-model:column-visibility="columnVisibility"
                        v-model:row-selection="rowSelection"
                        :data="permissionRows"
                        :columns="columns"
                        :get-row-id="(row) => String(row.id)"
                        class="min-w-[740px]"
                        :empty="'Belum ada permission.'"
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
                                <button class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10" type="button" title="Hapus" @click="destroyPermission(row.original)">
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                            </div>
                        </template>
                    </UTable>
                </div>
            </UCard>

            <p v-if="selectedRowsCount > 0" class="text-sm text-muted">
                {{ selectedRowsCount }} permission dipilih.
            </p>

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
