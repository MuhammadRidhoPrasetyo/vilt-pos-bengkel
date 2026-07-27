<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed, h, ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Pembelian Restok', panelId: 'purchases' }],
});

const props = defineProps({
    purchases: Object,
    summary: Object,
    filters: Object,
    options: Object,
});

const search = ref(props.filters?.search || '');
const storeFilter = ref(props.filters?.store_id || '');
const supplierFilter = ref(props.filters?.supplier_id || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const deleteModalOpen = ref(false);
const itemToDelete = ref(null);
const deleting = ref(false);

const storeOptions = computed(() => [
    { label: 'Semua Cabang', value: '' },
    ...(props.options?.stores || []),
]);

const supplierOptions = computed(() => [
    { label: 'Semua Supplier', value: '' },
    ...(props.options?.suppliers || []),
]);

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const clearDateFilter = () => {
    startDate.value = '';
    endDate.value = '';
};

watch([search, storeFilter, supplierFilter, startDate, endDate], () => {
    router.get('/purchases', {
        search: search.value,
        store_id: storeFilter.value,
        supplier_id: supplierFilter.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true, replace: true });
});

const confirmDelete = () => {
    if (!itemToDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/purchases/${itemToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false;
            itemToDelete.value = null;
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

const openDelete = (item) => {
    itemToDelete.value = item;
    deleteModalOpen.value = true;
};

const rows = computed(() => props.purchases?.data || []);

const columns = [
    {
        accessorKey: 'number',
        header: 'No. PO',
        meta: { class: { td: 'font-mono font-medium text-primary' } },
    },
    {
        accessorKey: 'invoice_number',
        header: 'Invoice Supplier',
        cell: ({ row }) => row.original.invoice_number || '-',
        meta: { class: { td: 'text-muted font-mono text-xs' } },
    },
    {
        accessorKey: 'purchase_date',
        header: 'Tanggal',
    },
    {
        accessorKey: 'store',
        header: 'Cabang Toko',
        cell: ({ row }) => row.original.store?.name || '-',
    },
    {
        accessorKey: 'supplier',
        header: 'Supplier',
        cell: ({ row }) => row.original.supplier?.name || '-',
        meta: { class: { td: 'font-medium text-highlighted' } },
    },
    {
        accessorKey: 'price',
        header: 'Total Pembelian',
        cell: ({ row }) => formatCurrency(row.original.price),
        meta: { class: { td: 'font-semibold text-highlighted text-right' } },
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { class: { th: 'w-20 text-right', td: 'w-20 text-right' } },
    },
];
</script>

<template>
    <div class="space-y-4">
        <!-- Summary Cards -->
        <div class="grid gap-3 sm:grid-cols-3">
            <UCard :ui="{ body: 'p-4' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Total Pembelian</p>
                        <p class="mt-1 text-2xl font-bold text-highlighted">{{ summary?.total_count || 0 }} Nota</p>
                    </div>
                    <div class="rounded-lg bg-primary/10 p-2.5 text-primary">
                        <UIcon name="i-lucide-receipt" class="size-6" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-4' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Total Nominal Pengeluaran</p>
                        <p class="mt-1 text-xl font-bold text-highlighted">{{ formatCurrency(summary?.total_amount) }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-500/10 p-2.5 text-amber-500">
                        <UIcon name="i-lucide-wallet" class="size-6" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-4' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Pembelian Bulan Ini</p>
                        <p class="mt-1 text-xl font-bold text-highlighted">{{ formatCurrency(summary?.month_amount) }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-500/10 p-2.5 text-emerald-500">
                        <UIcon name="i-lucide-calendar-days" class="size-6" />
                    </div>
                </div>
            </UCard>
        </div>

        <!-- Toolbar -->
        <UDashboardToolbar
            :ui="{
                root: 'min-h-0 flex-col items-stretch gap-3 overflow-visible border-b-0 px-0 sm:flex-row sm:items-center sm:px-0',
                left: 'w-full sm:w-auto flex-1',
                right: 'w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end',
            }"
        >
            <template #left>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center w-full flex-wrap">
                    <div class="relative flex-1 min-w-[200px] sm:w-64">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input
                            v-model="search"
                            class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary"
                            type="search"
                            placeholder="Cari PO, invoice, supplier..."
                        />
                    </div>

                    <USelect
                        v-model="storeFilter"
                        :items="storeOptions"
                        class="sm:w-40"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />

                    <USelect
                        v-model="supplierFilter"
                        :items="supplierOptions"
                        class="sm:w-40"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />

                    <!-- Date Range Inputs -->
                    <div class="flex items-center gap-1.5 border border-default bg-default rounded-md px-2 py-1 text-xs">
                        <UIcon name="i-lucide-calendar" class="size-4 text-muted shrink-0" />
                        <input v-model="startDate" type="date" title="Dari Tanggal" class="bg-transparent outline-none text-xs" />
                        <span class="text-muted font-medium">-</span>
                        <input v-model="endDate" type="date" title="Sampai Tanggal" class="bg-transparent outline-none text-xs" />
                        <button v-if="startDate || endDate" type="button" class="ml-1 text-muted hover:text-highlighted" title="Reset Tanggal" @click="clearDateFilter">
                            <UIcon name="i-lucide-x" class="size-3.5" />
                        </button>
                    </div>
                </div>
            </template>

            <template #right>
                <UButton
                    icon="i-lucide-plus"
                    label="Buat Transaksi Pembelian"
                    class="w-full justify-center sm:w-auto"
                    @click="router.visit('/purchases/create')"
                />
            </template>
        </UDashboardToolbar>

        <!-- Table -->
        <UCard :ui="{ root: 'overflow-hidden', body: 'p-0!' }">
            <div class="overflow-x-auto">
                <UTable
                    :data="rows"
                    :columns="columns"
                    :get-row-id="(row) => String(row.id)"
                    class="min-w-[820px]"
                    :empty="'Belum ada transaksi pembelian.'"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'first:rounded-tl-lg last:rounded-tr-lg border-b border-default',
                        td: 'border-b border-default',
                    }"
                >
                    <template #actions-cell="{ row }">
                        <div class="flex justify-end gap-2">
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted"
                                type="button"
                                title="Lihat Detail"
                                @click="router.visit(`/purchases/${row.original.id}`)"
                            >
                                <UIcon name="i-lucide-eye" class="size-4" />
                            </button>
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10"
                                type="button"
                                title="Hapus & Batalkan Stok"
                                @click="openDelete(row.original)"
                            >
                                <UIcon name="i-lucide-trash-2" class="size-4" />
                            </button>
                        </div>
                    </template>
                </UTable>
            </div>
        </UCard>

        <PaginationLinks :links="purchases.meta.links" />

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            title="Batalkan & Hapus Pembelian?"
            :description="`Transaksi #${itemToDelete?.number || ''} akan dihapus. Stok produk yang diterima dan pencatatan kas keluar akan otomatis ditarik kembali.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
