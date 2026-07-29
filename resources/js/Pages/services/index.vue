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
    activeOrders: Object,
    summary: Object,
    filters: Object,
    options: Object,
});

const viewMode = ref('kanban'); // 'kanban' or 'table'

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

// Status Quick Action / Mechanic Assign Modal
const statusModalOpen = ref(false);
const selectedOrder = ref(null);
const targetStatus = ref('');
const selectedMechanicId = ref('');
const updatingStatus = ref(false);

const mechanicsList = computed(() => props.options?.mechanics?.data || props.options?.mechanics || []);

const storeOptions = computed(() => [
    { label: 'Semua Cabang', value: '' },
    ...(props.options?.stores || []),
]);

const statusOptions = [
    { label: 'Semua Status', value: '' },
    { label: 'Check-in', value: 'checkin' },
    { label: 'Menunggu Sparepart', value: 'waiting_parts' },
    { label: 'Dalam Pengerjaan', value: 'in_progress' },
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
    if (!itemToDelete.value) return;

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

// Handle status change action
const handleStatusChange = (order, newStatus) => {
    selectedOrder.value = order;
    targetStatus.value = newStatus;
    const currentMechanicId = order.items?.find(i => i.mechanic?.id)?.mechanic?.id || '';
    selectedMechanicId.value = currentMechanicId ? String(currentMechanicId) : '';

    // Assign mechanic is prompted specifically when transitioning to 'in_progress'
    if (newStatus === 'in_progress') {
        statusModalOpen.value = true;
    } else {
        submitStatusDirect(order, newStatus, currentMechanicId);
    }
};

const submitStatusDirect = (order, status, mechanicId) => {
    updatingStatus.value = true;
    router.patch(`/services/${order.id}/status`, {
        status: status,
        mechanic_id: mechanicId || null,
    }, {
        preserveScroll: true,
        onFinish: () => {
            updatingStatus.value = false;
        },
    });
};

const submitStatusUpdate = () => {
    if (!selectedOrder.value || !targetStatus.value) return;

    updatingStatus.value = true;
    router.patch(`/services/${selectedOrder.value.id}/status`, {
        status: targetStatus.value,
        mechanic_id: selectedMechanicId.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            statusModalOpen.value = false;
            selectedOrder.value = null;
            targetStatus.value = '';
        },
        onFinish: () => {
            updatingStatus.value = false;
        },
    });
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'checkin':
            return { label: 'Check-in', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' };
        case 'waiting_parts':
            return { label: 'Tunggu Part', class: 'bg-orange-500/10 text-orange-600 border-orange-500/20' };
        case 'in_progress':
            return { label: 'Pengerjaan', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' };
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

const getMechanicName = (order) => {
    if (!order.items || order.items.length === 0) return null;
    const laborItem = order.items.find(i => i.mechanic?.name);
    return laborItem ? laborItem.mechanic.name : null;
};

const formatTimeAgo = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const checkin = new Date(dateTimeStr);
    const now = new Date();
    const diffMins = Math.floor((now - checkin) / 60000);
    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins}m lalu`;
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    return `${hours}j ${mins}m lalu`;
};

const rows = computed(() => props.serviceOrders?.data || []);
const activeOrdersList = computed(() => props.activeOrders?.data || props.activeOrders || []);

// Kanban Columns Data (Order: Checkin -> Waiting Parts -> In Progress -> Ready)
const kanbanCheckin = computed(() => activeOrdersList.value.filter(o => o.status === 'checkin'));
const kanbanWaitingParts = computed(() => activeOrdersList.value.filter(o => o.status === 'waiting_parts'));
const kanbanInProgress = computed(() => activeOrdersList.value.filter(o => o.status === 'in_progress'));
const kanbanReady = computed(() => activeOrdersList.value.filter(o => o.status === 'ready'));

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
                        <p class="text-xs font-medium text-muted">Total SPK Hari Ini</p>
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
                        <p class="text-xs font-medium text-muted">Check-in (Menunggu)</p>
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
                        <p class="text-xs font-medium text-muted">Menunggu Sparepart</p>
                        <p class="mt-1 text-2xl font-bold text-orange-500">{{ summary?.waiting_parts_count || 0 }}</p>
                    </div>
                    <div class="rounded-lg bg-orange-500/10 p-2 text-orange-500">
                        <UIcon name="i-lucide-package" class="size-5" />
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
                    <!-- Search Input -->
                    <div class="relative flex-1 min-w-[200px] sm:w-64">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input
                            v-model="search"
                            class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary"
                            type="search"
                            placeholder="Cari SPK, Plat Motor, Pelanggan..."
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
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <!-- View Switcher -->
                    <div class="flex items-center rounded-lg border border-default bg-elevated/50 p-1">
                        <button
                            type="button"
                            class="flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md transition-all"
                            :class="viewMode === 'kanban' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-highlighted'"
                            @click="viewMode = 'kanban'"
                        >
                            <UIcon name="i-lucide-kanban" class="size-3.5" />
                            Kanban
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-md transition-all"
                            :class="viewMode === 'table' ? 'bg-primary text-white shadow-sm' : 'text-muted hover:text-highlighted'"
                            @click="viewMode = 'table'"
                        >
                            <UIcon name="i-lucide-table" class="size-3.5" />
                            Tabel
                        </button>
                    </div>

                    <!-- Open TV Display Screen Link -->
                    <a
                        href="/services/display"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-bold text-emerald-600 bg-emerald-500/10 border border-emerald-500/30 rounded-md hover:bg-emerald-500/20 transition-colors shadow-sm"
                        title="Buka Layar TV Display Antrean"
                    >
                        <UIcon name="i-lucide-tv" class="size-4" />
                        Layar TV
                    </a>

                    <UButton
                        icon="i-lucide-plus"
                        label="Servis Baru"
                        class="justify-center shadow-md"
                        color="primary"
                        @click="router.visit('/services/create')"
                    />
                </div>
            </template>
        </UDashboardToolbar>

        <!-- KANBAN BOARD VIEW -->
        <!-- Columns Sequence: 1. Check-in -> 2. Menunggu Sparepart -> 3. Sedang Dikerjakan -> 4. Siap Diambil -->
        <div v-if="viewMode === 'kanban'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-start min-h-[550px]">

            <!-- Column 1: Check-in (Menunggu) -->
            <div class="bg-elevated/40 border border-default rounded-xl p-3.5 flex flex-col min-h-[520px] shadow-sm">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-default">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-blue-500"></span>
                        <h3 class="font-extrabold text-sm text-highlighted uppercase tracking-wider">1. Check-in (Menunggu)</h3>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-black rounded-full bg-blue-500/10 text-blue-600 border border-blue-500/20">
                        {{ kanbanCheckin.length }}
                    </span>
                </div>

                <div v-if="kanbanCheckin.length === 0" class="flex-1 flex flex-col items-center justify-center text-muted py-12 text-xs">
                    <UIcon name="i-lucide-inbox" class="size-8 mb-2 opacity-50" />
                    Belum ada antrean check-in.
                </div>

                <div v-else class="space-y-3 flex-1 overflow-y-auto pr-0.5">
                    <div
                        v-for="order in kanbanCheckin"
                        :key="order.id"
                        class="bg-default border border-default rounded-lg p-3 shadow-sm hover:border-blue-500/50 transition-all flex flex-col justify-between gap-3 group"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-mono font-black text-xs bg-black text-amber-300 px-2 py-0.5 rounded border border-amber-400/40">
                                    {{ order.plate_number }}
                                </span>
                                <span class="text-[11px] font-mono text-muted">{{ formatTimeAgo(order.checkin_at) }}</span>
                            </div>

                            <p class="font-bold text-sm text-highlighted line-clamp-1">
                                {{ order.vehicle_brand || '' }} {{ order.vehicle_model || 'Motor/Mobil' }}
                            </p>
                            <p class="text-xs text-muted font-medium">Pelanggan: <strong class="text-highlighted">{{ order.customer_name }}</strong></p>
                            <p class="text-xs text-muted mt-1 bg-elevated/60 p-1.5 rounded text-[11px] line-clamp-2">
                                {{ order.general_complaint || 'Servis Rutin' }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-default/70 flex items-center justify-between gap-1">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded bg-orange-500/10 text-orange-600 border border-orange-500/30 hover:bg-orange-500/20"
                                title="Menunggu pengadaan sparepart terlebih dahulu"
                                @click="handleStatusChange(order, 'waiting_parts')"
                            >
                                <UIcon name="i-lucide-package" class="size-3" /> Tunggu Part
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded bg-amber-500 text-white hover:bg-amber-600 transition-colors shadow-sm"
                                title="Pilih Mekanik & Mulai Pengerjaan"
                                @click="handleStatusChange(order, 'in_progress')"
                            >
                                <UIcon name="i-lucide-play" class="size-3.5" /> Mulai Kerja
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2: Menunggu Sparepart (Waiting Parts) -->
            <div class="bg-orange-500/5 border border-orange-500/20 rounded-xl p-3.5 flex flex-col min-h-[520px] shadow-sm">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-orange-500/20">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-orange-500"></span>
                        <h3 class="font-extrabold text-sm text-orange-600 dark:text-orange-400 uppercase tracking-wider">2. Menunggu Sparepart</h3>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-black rounded-full bg-orange-500/10 text-orange-600 border border-orange-500/20">
                        {{ kanbanWaitingParts.length }}
                    </span>
                </div>

                <div v-if="kanbanWaitingParts.length === 0" class="flex-1 flex flex-col items-center justify-center text-muted py-12 text-xs">
                    <UIcon name="i-lucide-box" class="size-8 mb-2 opacity-50" />
                    Tidak ada yang menunggu sparepart.
                </div>

                <div v-else class="space-y-3 flex-1 overflow-y-auto pr-0.5">
                    <div
                        v-for="order in kanbanWaitingParts"
                        :key="order.id"
                        class="bg-default border border-orange-500/30 rounded-lg p-3 shadow-sm hover:border-orange-500 transition-all flex flex-col justify-between gap-3"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-mono font-black text-xs bg-black text-amber-300 px-2 py-0.5 rounded border border-amber-400/40">
                                    {{ order.plate_number }}
                                </span>
                                <span class="text-[11px] font-mono text-muted">{{ formatTimeAgo(order.checkin_at) }}</span>
                            </div>

                            <p class="font-bold text-sm text-highlighted line-clamp-1">
                                {{ order.vehicle_brand || '' }} {{ order.vehicle_model || 'Motor/Mobil' }}
                            </p>
                            <p class="text-xs text-muted font-medium">Pelanggan: <strong class="text-highlighted">{{ order.customer_name }}</strong></p>
                            <p class="text-xs text-muted mt-1 bg-elevated/60 p-1.5 rounded text-[11px] line-clamp-2">
                                {{ order.general_complaint || 'Servis Rutin' }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-default/70 flex items-center justify-end">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-bold px-3 py-1 rounded bg-amber-500 text-white hover:bg-amber-600 transition-colors shadow-sm"
                                title="Pilih Mekanik & Mulai Pengerjaan"
                                @click="handleStatusChange(order, 'in_progress')"
                            >
                                <UIcon name="i-lucide-play" class="size-3.5" /> Assign Mekanik & Kerjakan
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 3: Dalam Pengerjaan (In Progress) -->
            <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-3.5 flex flex-col min-h-[520px] shadow-sm">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-amber-500/20">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-amber-500 animate-ping"></span>
                        <h3 class="font-extrabold text-sm text-amber-600 dark:text-amber-400 uppercase tracking-wider">3. Sedang Dikerjakan</h3>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-black rounded-full bg-amber-500/10 text-amber-600 border border-amber-500/20">
                        {{ kanbanInProgress.length }}
                    </span>
                </div>

                <div v-if="kanbanInProgress.length === 0" class="flex-1 flex flex-col items-center justify-center text-muted py-12 text-xs">
                    <UIcon name="i-lucide-wrench" class="size-8 mb-2 opacity-50" />
                    Belum ada kendaraan sedang dikerjakan.
                </div>

                <div v-else class="space-y-3 flex-1 overflow-y-auto pr-0.5">
                    <div
                        v-for="order in kanbanInProgress"
                        :key="order.id"
                        class="bg-default border border-amber-500/30 rounded-lg p-3 shadow-sm hover:border-amber-500 transition-all flex flex-col justify-between gap-3"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-mono font-black text-xs bg-black text-amber-300 px-2 py-0.5 rounded border border-amber-400/40">
                                    {{ order.plate_number }}
                                </span>
                                <span class="text-[11px] font-mono text-amber-600 font-semibold flex items-center gap-1">
                                    <UIcon name="i-lucide-clock" class="size-3" />
                                    {{ formatTimeAgo(order.checkin_at) }}
                                </span>
                            </div>

                            <p class="font-bold text-sm text-highlighted line-clamp-1">
                                {{ order.vehicle_brand || '' }} {{ order.vehicle_model || 'Motor/Mobil' }}
                            </p>
                            <p class="text-xs text-muted font-medium">Mekanik: <strong class="text-amber-600 font-bold">{{ getMechanicName(order) || 'Belum di-assign' }}</strong></p>
                            <p class="text-xs text-muted mt-1 bg-elevated/60 p-1.5 rounded text-[11px] line-clamp-2">
                                {{ order.general_complaint || 'Servis Rutin' }}
                            </p>
                        </div>

                        <div class="pt-2 border-t border-default/70 flex items-center justify-between gap-1.5 flex-wrap">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded bg-orange-500/10 text-orange-600 border border-orange-500/30 hover:bg-orange-500/20"
                                title="Kembalikan / Menunggu Tambahan Sparepart"
                                @click="handleStatusChange(order, 'waiting_parts')"
                            >
                                <UIcon name="i-lucide-package" class="size-3" /> Tunggu Part
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm"
                                title="Servis Selesai"
                                @click="handleStatusChange(order, 'ready')"
                            >
                                <UIcon name="i-lucide-check-circle" class="size-3.5" /> Tandai Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 4: Siap Diambil (Ready) -->
            <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-3.5 flex flex-col min-h-[520px] shadow-sm">
                <div class="flex items-center justify-between pb-3 mb-3 border-b border-emerald-500/20">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-emerald-500"></span>
                        <h3 class="font-extrabold text-sm text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">4. Siap Diambil</h3>
                    </div>
                    <span class="px-2 py-0.5 text-xs font-black rounded-full bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                        {{ kanbanReady.length }}
                    </span>
                </div>

                <div v-if="kanbanReady.length === 0" class="flex-1 flex flex-col items-center justify-center text-muted py-12 text-xs">
                    <UIcon name="i-lucide-check-circle" class="size-8 mb-2 opacity-50" />
                    Belum ada kendaraan siap diambil.
                </div>

                <div v-else class="space-y-3 flex-1 overflow-y-auto pr-0.5">
                    <div
                        v-for="order in kanbanReady"
                        :key="order.id"
                        class="bg-default border border-emerald-500/40 rounded-lg p-3 shadow-sm hover:border-emerald-500 transition-all flex flex-col justify-between gap-3"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-mono font-black text-xs bg-black text-amber-300 px-2 py-0.5 rounded border border-amber-400/40">
                                    {{ order.plate_number }}
                                </span>
                                <span class="text-[11px] font-bold text-emerald-600">SIAP!</span>
                            </div>

                            <p class="font-bold text-sm text-highlighted line-clamp-1">
                                {{ order.vehicle_brand || '' }} {{ order.vehicle_model || 'Motor/Mobil' }}
                            </p>
                            <p class="text-xs text-muted font-medium">Pelanggan: <strong class="text-highlighted">{{ order.customer_name }}</strong></p>
                            <p class="text-xs font-bold text-highlighted mt-1">Biaya: {{ formatCurrency(order.estimated_total) }}</p>
                        </div>

                        <div class="pt-2 border-t border-default/70 flex items-center justify-between">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-1 rounded border border-default text-muted hover:bg-elevated"
                                @click="router.visit(`/services/${order.id}`)"
                            >
                                Detail
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 text-xs font-bold px-2.5 py-1 rounded bg-teal-600 text-white hover:bg-teal-700 transition-colors shadow-sm"
                                title="Lanjut Pembayaran di Kasir"
                                @click="handleStatusChange(order, 'invoiced')"
                            >
                                <UIcon name="i-lucide-receipt" class="size-3.5" /> Pelunasan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABLE VIEW -->
        <UCard v-else :ui="{ root: 'overflow-hidden', body: 'p-0!' }">
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

        <PaginationLinks v-if="viewMode === 'table'" :links="serviceOrders.meta.links" />

        <!-- Assign Mechanic Modal (Triggered when moving status to 'in_progress') -->
        <UModal v-model:open="statusModalOpen" title="Pilih Mekanik untuk Mulai Pengerjaan">
            <template #content>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-default">
                        <div>
                            <span class="font-mono font-black text-sm bg-black text-amber-300 px-2.5 py-1 rounded border border-amber-400/40">
                                {{ selectedOrder?.plate_number }}
                            </span>
                            <p class="text-sm font-bold text-highlighted mt-1">
                                {{ selectedOrder?.vehicle_brand }} {{ selectedOrder?.vehicle_model }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-mono text-muted">{{ selectedOrder?.number }}</p>
                            <p class="text-xs text-muted">{{ selectedOrder?.customer_name }}</p>
                        </div>
                    </div>

                    <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg text-xs text-amber-700 dark:text-amber-400 font-medium">
                        Status kendaraan akan diubah menjadi <strong class="uppercase font-bold">Sedang Dikerjakan</strong>. Pilih mekanik yang akan bertugas:
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-highlighted">Penanggung Jawab Mekanik</label>
                        <select
                            v-model="selectedMechanicId"
                            class="w-full rounded-md border border-default bg-default p-2.5 text-sm outline-none focus:border-primary font-semibold"
                        >
                            <option value="">-- Pilih Mekanik --</option>
                            <option v-for="mech in mechanicsList" :key="mech.id" :value="String(mech.id)">
                                {{ mech.name }} ({{ mech.email }})
                            </option>
                        </select>
                    </div>

                    <div class="pt-4 flex justify-end gap-2 border-t border-default">
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-semibold rounded-md border border-default text-muted hover:bg-elevated"
                            @click="statusModalOpen = false"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            class="px-4 py-2 text-xs font-bold rounded-md bg-amber-500 text-white hover:bg-amber-600 transition-colors shadow-sm disabled:opacity-50 flex items-center gap-1.5"
                            :disabled="updatingStatus"
                            @click="submitStatusUpdate"
                        >
                            <UIcon name="i-lucide-play" class="size-4" />
                            {{ updatingStatus ? 'Menyimpan...' : 'Mulai Pengerjaan Servis' }}
                        </button>
                    </div>
                </div>
            </template>
        </UModal>

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            title="Hapus SPK Servis?"
            :description="`Surat Perintah Kerja ${itemToDelete?.number || ''} (${itemToDelete?.plate_number || ''}) akan dihapus.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
