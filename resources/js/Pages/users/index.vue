<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, h, ref, resolveComponent, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Users', panelId: 'users' }],
});

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref('all');
const rowSelection = ref({});
const columnVisibility = ref({});
const deleteModalOpen = ref(false);
const userToDelete = ref(null);
const deleting = ref(false);
const UBadge = resolveComponent('UBadge');
const UCheckbox = resolveComponent('UCheckbox');

const userRows = computed(() => {
    const rows = props.users?.data || [];

    if (statusFilter.value === 'all') {
        return rows;
    }

    return rows.filter((user) => String(user.active) === statusFilter.value);
});
const statusFilterItems = [
    { label: 'All', value: 'all' },
    { label: 'Aktif', value: 'true' },
    { label: 'Nonaktif', value: 'false' },
];
const selectedRowsCount = computed(() => Object.keys(rowSelection.value).length);
const displayableColumns = [
    { id: 'name', label: 'Nama' },
    { id: 'email', label: 'Email' },
    { id: 'phone', label: 'Telepon' },
    { id: 'roles', label: 'Role' },
    { id: 'active', label: 'Status' },
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
    router.get('/users', { search: value }, { preserveState: true, replace: true });
});

watch([statusFilter, () => props.users?.data], () => {
    rowSelection.value = {};
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

const columns = computed(() => [
    {
        id: 'select',
        header: ({ table }) => h(UCheckbox, {
            modelValue: table.getIsSomePageRowsSelected() ? 'indeterminate' : table.getIsAllPageRowsSelected(),
            'aria-label': 'Pilih semua user',
            class: 'mx-auto',
            'onUpdate:modelValue': (value) => table.toggleAllPageRowsSelected(!!value),
        }),
        cell: ({ row }) => h(UCheckbox, {
            modelValue: row.getIsSelected(),
            'aria-label': `Pilih user ${row.original.name}`,
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
        accessorKey: 'email',
        header: 'Email',
        meta: {
            class: {
                td: 'text-muted',
            },
        },
    },
    {
        accessorKey: 'phone',
        header: 'Telepon',
        cell: ({ row }) => row.getValue('phone') || '-',
        meta: {
            class: {
                td: 'text-muted',
            },
        },
    },
    {
        accessorKey: 'roles',
        header: 'Role',
        cell: ({ row }) => {
            const roles = row.original.roles || [];

            if (roles.length === 0) {
                return h('span', { class: 'text-muted' }, '-');
            }

            return h('div', { class: 'flex flex-wrap gap-1' }, roles.map((role) => h(UBadge, {
                key: role.id,
                color: 'neutral',
                variant: 'subtle',
            }, () => role.name)));
        },
    },
    {
        accessorKey: 'active',
        header: 'Status',
        cell: ({ row }) => {
            const active = row.getValue('active');

            return h(UBadge, {
                color: active ? 'success' : 'error',
                variant: 'subtle',
            }, () => (active ? 'Aktif' : 'Nonaktif'));
        },
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
                    <input v-model="search" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" type="search" placeholder="Cari user" />
                </div>
            </template>

            <template #right>
                <USelect
                    v-model="statusFilter"
                    :items="statusFilterItems"
                    class="w-full sm:w-32"
                    :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                />

                <UDropdownMenu :items="displayColumnItems" :content="{ align: 'end' }">
                    <UButton color="neutral" variant="outline" icon="i-lucide-sliders-horizontal" label="Display" class="w-full justify-center sm:w-auto" />
                </UDropdownMenu>

                <UButton icon="i-lucide-plus" label="Tambah User" class="w-full justify-center sm:w-auto" @click="router.visit('/users/create')" />
            </template>
        </UDashboardToolbar>

        <UCard :ui="{ root: 'overflow-hidden', body: 'p-0!' }">
            <div class="overflow-x-auto">
                <UTable
                    v-model:column-visibility="columnVisibility"
                    v-model:row-selection="rowSelection"
                    :data="userRows"
                    :columns="columns"
                    :get-row-id="(row) => String(row.id)"
                    class="min-w-[960px]"
                    :empty="'Belum ada user.'"
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
                            <Link :href="`/users/${row.original.id}`" class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted" title="Detail">
                                <UIcon name="i-lucide-eye" class="size-4" />
                            </Link>
                            <Link :href="`/users/${row.original.id}/edit`" class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted" title="Edit">
                                <UIcon name="i-lucide-pencil" class="size-4" />
                            </Link>
                            <button class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10" type="button" title="Hapus" @click="destroyUser(row.original)">
                                <UIcon name="i-lucide-trash-2" class="size-4" />
                            </button>
                        </div>
                    </template>
                </UTable>
            </div>
        </UCard>

        <p v-if="selectedRowsCount > 0" class="text-sm text-muted">
            {{ selectedRowsCount }} user dipilih.
        </p>

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
