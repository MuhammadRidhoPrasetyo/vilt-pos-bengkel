<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { CalendarDate } from '@internationalized/date';
import { router } from '@inertiajs/vue3';
import { computed, ref, shallowRef, useTemplateRef, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Transaksi POS / Penjualan', panelId: 'transactions' }],
});

const props = defineProps({
    transactions: Object,
    summary: Object,
    filters: Object,
    options: Object,
});

const parseDateString = (str) => {
    if (!str) return null;
    const [y, m, d] = str.split('-').map(Number);
    return (y && m && d) ? new CalendarDate(y, m, d) : null;
};

const formatDateString = (calDate) => {
    if (!calDate) return '';
    const y = calDate.year;
    const m = String(calDate.month).padStart(2, '0');
    const d = String(calDate.day).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const search = ref(props.filters?.search || '');
const typeFilter = ref(props.filters?.type || '');
const paymentStatusFilter = ref(props.filters?.payment_status || '');
const storeFilter = ref(props.filters?.store_id || '');
const startDate = ref(props.filters?.start_date || '');
const endDate = ref(props.filters?.end_date || '');

const inputDateRef = useTemplateRef('inputDateRef');

const dateRangeModel = shallowRef({
    start: parseDateString(props.filters?.start_date),
    end: parseDateString(props.filters?.end_date),
});

watch(dateRangeModel, (val) => {
    startDate.value = val?.start ? formatDateString(val.start) : '';
    endDate.value = val?.end ? formatDateString(val.end) : '';
}, { deep: true });

const deleteModalOpen = ref(false);
const itemToDelete = ref(null);
const deleting = ref(false);

const storeOptions = computed(() => [
    { label: 'Semua Cabang', value: '' },
    ...(props.options?.stores || []),
]);

const typeOptions = [
    { label: 'Semua Tipe', value: '' },
    { label: 'Penjualan Retail', value: 'retail' },
    { label: 'Pelunasan Servis', value: 'service' },
];

const paymentStatusOptions = [
    { label: 'Semua Status Bayar', value: '' },
    { label: 'Lunas (Paid)', value: 'paid' },
    { label: 'Sebagian (Partial)', value: 'partial' },
    { label: 'Belum Bayar (Unpaid)', value: 'unpaid' },
];

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const clearDateFilter = () => {
    dateRangeModel.value = { start: null, end: null };
    startDate.value = '';
    endDate.value = '';
};

watch([search, typeFilter, paymentStatusFilter, storeFilter, startDate, endDate], () => {
    router.get('/transactions', {
        search: search.value,
        type: typeFilter.value,
        payment_status: paymentStatusFilter.value,
        store_id: storeFilter.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true, replace: true });
});

const confirmDelete = () => {
    if (!itemToDelete.value) return;

    deleting.value = true;
    router.delete(`/transactions/${itemToDelete.value.id}`, {
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

const getTypeBadge = (type) => {
    switch (type) {
        case 'service':
            return { label: 'Pelunasan Servis', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' };
        case 'retail':
        default:
            return { label: 'Retail Sparepart', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' };
    }
};

const getPaymentStatusBadge = (status) => {
    switch (status) {
        case 'paid':
            return { label: 'Lunas', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' };
        case 'partial':
            return { label: 'Sebagian', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' };
        case 'unpaid':
            return { label: 'Belum Bayar', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' };
        default:
            return { label: status, class: 'bg-gray-500/10 text-gray-600 border-gray-500/20' };
    }
};

const rows = computed(() => props.transactions?.data || []);

const columns = [
    {
        accessorKey: 'number',
        header: 'No. Nota Transaksi',
        meta: { class: { td: 'font-mono font-bold text-primary' } },
    },
    {
        accessorKey: 'transaction_date',
        header: 'Tanggal & Waktu',
    },
    {
        accessorKey: 'type',
        header: 'Tipe Transaksi',
    },
    {
        accessorKey: 'customer',
        header: 'Pelanggan / Kasir',
    },
    {
        accessorKey: 'grand_total',
        header: 'Total Bayar',
        cell: ({ row }) => formatCurrency(row.original.grand_total),
        meta: { class: { td: 'font-extrabold text-highlighted text-right' } },
    },
    {
        accessorKey: 'payment_status',
        header: 'Status Bayar',
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { class: { th: 'w-32 text-right', td: 'w-32 text-right' } },
    },
];
</script>

<template>
    <div class="space-y-4">
        <!-- Summary Cards -->
        <div class="grid gap-3 sm:grid-cols-5">
            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Total Transaksi</p>
                        <p class="mt-1 text-2xl font-bold text-highlighted">{{ summary?.total_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-primary/10 p-2 text-primary">
                        <UIcon name="i-lucide-receipt" class="size-5" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Total Omzet</p>
                        <p class="mt-1 text-base font-extrabold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(summary?.total_grand_total) }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-500">
                        <UIcon name="i-lucide-dollar-sign" class="size-5" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Total Profit (Laba)</p>
                        <p class="mt-1 text-base font-bold text-highlighted">{{ formatCurrency(summary?.total_profit) }}</p>
                    </div>
                    <div class="rounded-lg bg-indigo-500/10 p-2 text-indigo-500">
                        <UIcon name="i-lucide-trending-up" class="size-5" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Trans. Retail</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-500">{{ summary?.retail_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-500">
                        <UIcon name="i-lucide-shopping-bag" class="size-5" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Trans. Servis</p>
                        <p class="mt-1 text-2xl font-bold text-blue-500">{{ summary?.service_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-blue-500/10 p-2 text-blue-500">
                        <UIcon name="i-lucide-wrench" class="size-5" />
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
                            placeholder="Cari No. Nota, Pelanggan, Catatan..."
                        />
                    </div>

                    <USelect
                        v-model="typeFilter"
                        :items="typeOptions"
                        class="sm:w-44"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />

                    <USelect
                        v-model="paymentStatusFilter"
                        :items="paymentStatusOptions"
                        class="sm:w-44"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />

                    <USelect
                        v-model="storeFilter"
                        :items="storeOptions"
                        class="sm:w-40"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />

                    <!-- Date Filter -->
                    <div class="flex items-center gap-1.5">
                        <UInputDate ref="inputDateRef" v-model="dateRangeModel" range class="sm:w-56">
                            <template #trailing>
                                <UPopover :reference="inputDateRef?.inputsRef?.[0]?.$el">
                                    <UButton
                                        color="neutral"
                                        variant="link"
                                        size="sm"
                                        icon="i-lucide-calendar"
                                        aria-label="Pilih rentang tanggal"
                                        class="px-0"
                                    />
                                    <template #content>
                                        <UCalendar v-model="dateRangeModel" class="p-2" range />
                                    </template>
                                </UPopover>
                            </template>
                        </UInputDate>

                        <button
                            v-if="startDate || endDate"
                            type="button"
                            class="inline-flex size-8 items-center justify-center rounded-md border border-default bg-default text-muted hover:bg-elevated hover:text-highlighted"
                            title="Reset Tanggal"
                            @click="clearDateFilter"
                        >
                            <UIcon name="i-lucide-x" class="size-4" />
                        </button>
                    </div>
                </div>
            </template>

            <template #right>
                <UButton
                    icon="i-lucide-shopping-cart"
                    label="Buka Kasir POS Baru"
                    class="w-full justify-center sm:w-auto shadow-md"
                    color="primary"
                    @click="router.visit('/transactions/create')"
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
                    class="min-w-[900px]"
                    :empty="'Belum ada data transaksi POS.'"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'first:rounded-tl-lg last:rounded-tr-lg border-b border-default',
                        td: 'border-b border-default',
                    }"
                >
                    <template #transaction_date-cell="{ row }">
                        <span class="text-xs font-mono text-muted">{{ row.original.transaction_date || '-' }}</span>
                    </template>

                    <template #type-cell="{ row }">
                        <span
                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold border"
                            :class="getTypeBadge(row.original.type).class"
                        >
                            {{ getTypeBadge(row.original.type).label }}
                        </span>
                    </template>

                    <template #customer-cell="{ row }">
                        <div>
                            <p class="font-medium text-highlighted text-sm">{{ row.original.customer?.name || 'Walk-In Customer' }}</p>
                            <p class="text-xs text-muted font-mono">Kasir: {{ row.original.user?.name || '-' }}</p>
                        </div>
                    </template>

                    <template #payment_status-cell="{ row }">
                        <span
                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold border"
                            :class="getPaymentStatusBadge(row.original.payment_status).class"
                        >
                            {{ getPaymentStatusBadge(row.original.payment_status).label }}
                        </span>
                    </template>

                    <template #actions-cell="{ row }">
                        <div class="flex justify-end gap-1.5">
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted"
                                type="button"
                                title="Lihat Detail Transaksi"
                                @click="router.visit(`/transactions/${row.original.id}`)"
                            >
                                <UIcon name="i-lucide-eye" class="size-4" />
                            </button>
                            <a
                                :href="`/transactions/${row.original.id}/print`"
                                target="_blank"
                                class="inline-flex size-8 items-center justify-center rounded-md border border-emerald-500/30 text-emerald-600 hover:bg-emerald-500/10"
                                title="Cetak Nota Thermal"
                            >
                                <UIcon name="i-lucide-printer" class="size-4" />
                            </a>
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10"
                                type="button"
                                title="Hapus / Batal Transaksi"
                                @click="openDelete(row.original)"
                            >
                                <UIcon name="i-lucide-trash-2" class="size-4" />
                            </button>
                        </div>
                    </template>
                </UTable>
            </div>
        </UCard>

        <PaginationLinks :links="transactions.meta.links" />

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            title="Batalkan & Hapus Transaksi?"
            :description="`Transaksi ${itemToDelete?.number || ''} akan dihapus dan stok barang akan dikembalikan.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
