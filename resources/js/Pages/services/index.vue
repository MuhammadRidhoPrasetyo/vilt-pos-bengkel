<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { CalendarDate } from '@internationalized/date';
import { router } from '@inertiajs/vue3';
import { computed, ref, shallowRef, useTemplateRef, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Servis / Work Order', panelId: 'services' }],
});

const props = defineProps({
    serviceOrders: Object,
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
const statusFilter = ref(props.filters?.status || '');
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

const statusOptions = [
    { label: 'Semua Status', value: '' },
    { label: 'Check-in', value: 'checkin' },
    { label: 'Diagnosis', value: 'diagnosis' },
    { label: 'Dalam Pengerjaan', value: 'in_progress' },
    { label: 'Menunggu Sparepart', value: 'waiting_parts' },
    { label: 'Selesai (Siap Ambil)', value: 'ready' },
    { label: 'Sudah Dilunasi', value: 'invoiced' },
    { label: 'Dibatalkan', value: 'cancelled' },
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

watch([search, statusFilter, storeFilter, startDate, endDate], () => {
    router.get('/services', {
        search: search.value,
        status: statusFilter.value,
        store_id: storeFilter.value,
        start_date: startDate.value,
        end_date: endDate.value,
    }, { preserveState: true, replace: true });
});

const confirmDelete = () => {
    if (!itemToDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/services/${itemToDelete.value.id}`, {
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

const getStatusBadge = (status) => {
    switch (status) {
        case 'checkin':
            return { label: 'Check-in', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' };
        case 'diagnosis':
            return { label: 'Diagnosis', class: 'bg-purple-500/10 text-purple-600 border-purple-500/20' };
        case 'in_progress':
            return { label: 'Pengerjaan', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' };
        case 'waiting_parts':
            return { label: 'Tunggu Part', class: 'bg-orange-500/10 text-orange-600 border-orange-500/20' };
        case 'ready':
            return { label: 'Siap Ambil', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' };
        case 'invoiced':
            return { label: 'Lunas', class: 'bg-teal-500/10 text-teal-600 border-teal-500/20' };
        case 'cancelled':
            return { label: 'Batal', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' };
        default:
            return { label: status, class: 'bg-gray-500/10 text-gray-600 border-gray-500/20' };
    }
};

const rows = computed(() => props.serviceOrders?.data || []);

const columns = [
    {
        accessorKey: 'number',
        header: 'No. SPK',
        meta: { class: { td: 'font-mono font-bold text-primary' } },
    },
    {
        accessorKey: 'plate_number',
        header: 'Kendaraan',
    },
    {
        accessorKey: 'customer_name',
        header: 'Pelanggan',
    },
    {
        accessorKey: 'general_complaint',
        header: 'Keluhan Utama',
        cell: ({ row }) => row.original.general_complaint || '-',
        meta: { class: { td: 'max-w-xs truncate text-muted' } },
    },
    {
        accessorKey: 'status',
        header: 'Status',
    },
    {
        accessorKey: 'estimated_total',
        header: 'Est. Biaya',
        cell: ({ row }) => formatCurrency(row.original.estimated_total),
        meta: { class: { td: 'font-semibold text-highlighted text-right' } },
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { class: { th: 'w-28 text-right', td: 'w-28 text-right' } },
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
                        <p class="text-xs font-medium text-muted">Total SPK</p>
                        <p class="mt-1 text-2xl font-bold text-highlighted">{{ summary?.total_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-primary/10 p-2 text-primary">
                        <UIcon name="i-lucide-wrench" class="size-5" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Check-in</p>
                        <p class="mt-1 text-2xl font-bold text-blue-500">{{ summary?.checkin_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-blue-500/10 p-2 text-blue-500">
                        <UIcon name="i-lucide-clipboard-check" class="size-5" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Dalam Pengerjaan</p>
                        <p class="mt-1 text-2xl font-bold text-amber-500">{{ summary?.in_progress_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-amber-500/10 p-2 text-amber-500">
                        <UIcon name="i-lucide-cog" class="size-5 animate-spin-slow" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Siap Ambil</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-500">{{ summary?.ready_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-emerald-500/10 p-2 text-emerald-500">
                        <UIcon name="i-lucide-check-circle-2" class="size-5" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-3.5' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Est. Biaya SPK</p>
                        <p class="mt-1 text-base font-bold text-highlighted">{{ formatCurrency(summary?.total_estimated) }}</p>
                    </div>
                    <div class="rounded-lg bg-indigo-500/10 p-2 text-indigo-500">
                        <UIcon name="i-lucide-calculator" class="size-5" />
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
                            placeholder="Cari SPK, Plat Motor, Nama Pelanggan..."
                        />
                    </div>

                    <USelect
                        v-model="statusFilter"
                        :items="statusOptions"
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
                        <UInputDate ref="inputDateRef" v-model="dateRangeModel" range class="sm:w-60">
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
                    icon="i-lucide-plus"
                    label="Buat Servis Baru / SPK"
                    class="w-full justify-center sm:w-auto shadow-md"
                    color="primary"
                    @click="router.visit('/services/create')"
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
                    class="min-w-[850px]"
                    :empty="'Belum ada data Surat Perintah Kerja (SPK) servis.'"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'first:rounded-tl-lg last:rounded-tr-lg border-b border-default',
                        td: 'border-b border-default',
                    }"
                >
                    <template #plate_number-cell="{ row }">
                        <div>
                            <span class="inline-block font-mono font-bold text-xs bg-black text-amber-300 px-2 py-0.5 rounded border border-amber-400/40 shadow-sm">
                                {{ row.original.plate_number }}
                            </span>
                            <p class="text-[11px] text-muted mt-0.5">{{ row.original.vehicle_brand }} {{ row.original.vehicle_model }}</p>
                        </div>
                    </template>

                    <template #customer_name-cell="{ row }">
                        <div>
                            <p class="font-medium text-highlighted text-sm">{{ row.original.customer_name }}</p>
                            <p class="text-xs text-muted font-mono">{{ row.original.customer_phone || '-' }}</p>
                        </div>
                    </template>

                    <template #status-cell="{ row }">
                        <span
                            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold border"
                            :class="getStatusBadge(row.original.status).class"
                        >
                            {{ getStatusBadge(row.original.status).label }}
                        </span>
                    </template>

                    <template #actions-cell="{ row }">
                        <div class="flex justify-end gap-1.5">
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted"
                                type="button"
                                title="Lihat Detail"
                                @click="router.visit(`/services/${row.original.id}`)"
                            >
                                <UIcon name="i-lucide-eye" class="size-4" />
                            </button>
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-primary/30 text-primary hover:bg-primary/10"
                                type="button"
                                title="Edit Workspace Fullscreen"
                                @click="router.visit(`/services/${row.original.id}/edit`)"
                            >
                                <UIcon name="i-lucide-pencil" class="size-4" />
                            </button>
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10"
                                type="button"
                                title="Hapus SPK"
                                @click="openDelete(row.original)"
                            >
                                <UIcon name="i-lucide-trash-2" class="size-4" />
                            </button>
                        </div>
                    </template>
                </UTable>
            </div>
        </UCard>

        <PaginationLinks :links="serviceOrders.meta.links" />

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            title="Hapus SPK Servis?"
            :description="`Surat Perintah Kerja ${itemToDelete?.number || ''} (${itemToDelete?.plate_number || ''}) akan dihapus.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
