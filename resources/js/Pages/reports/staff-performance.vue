<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Laporan Kinerja Karyawan', panelId: 'reports-staff-performance' }],
});

const props = defineProps({
    cashiers: {
        type: Array,
        default: () => [],
    },
    mechanics: {
        type: Array,
        default: () => [],
    },
    stores: {
        type: Array,
        default: () => [],
    },
    summary: {
        type: Object,
        default: () => ({
            total_cashier_sales: 0,
            total_cashier_transactions: 0,
            total_mechanic_revenue: 0,
            total_mechanic_jobs: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            start_date: '',
            end_date: '',
            store_id: '',
            role: 'all',
            search: '',
        }),
    },
});

// Active tab ('cashier' or 'mechanic')
const activeTab = ref(props.filters.role === 'mekanik' ? 'mechanic' : 'cashier');

// Filter state
const filterForm = ref({
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    store_id: props.filters.store_id || '',
    role: props.filters.role || 'all',
    search: props.filters.search || '',
});

const applyFilter = () => {
    router.get('/reports/staff-performance', {
        ...filterForm.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilter = () => {
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const formatDate = (d) => d.toISOString().split('T')[0];

    filterForm.value = {
        start_date: formatDate(firstDay),
        end_date: formatDate(today),
        store_id: '',
        role: 'all',
        search: '',
    };
    applyFilter();
};

const setDatePreset = (preset) => {
    const today = new Date();
    const formatDate = (d) => d.toISOString().split('T')[0];

    if (preset === 'today') {
        filterForm.value.start_date = formatDate(today);
        filterForm.value.end_date = formatDate(today);
    } else if (preset === 'week') {
        const last7 = new Date();
        last7.setDate(today.getDate() - 6);
        filterForm.value.start_date = formatDate(last7);
        filterForm.value.end_date = formatDate(today);
    } else if (preset === 'month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        filterForm.value.start_date = formatDate(firstDay);
        filterForm.value.end_date = formatDate(today);
    } else if (preset === 'last_month') {
        const firstDayLastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        const lastDayLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
        filterForm.value.start_date = formatDate(firstDayLastMonth);
        filterForm.value.end_date = formatDate(lastDayLastMonth);
    }

    applyFilter();
};

// Currency formatter
const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

// Detail Modal state
const modalOpen = ref(false);
const modalType = ref(''); // 'cashier' or 'mechanic'
const modalLoading = ref(false);
const modalStaff = ref(null);
const modalDetails = ref([]);

const openCashierDetails = async (staff) => {
    modalStaff.value = staff;
    modalType.value = 'cashier';
    modalOpen.value = true;
    modalLoading.value = true;
    modalDetails.value = [];

    try {
        const query = new URLSearchParams({
            start_date: filterForm.value.start_date,
            end_date: filterForm.value.end_date,
            store_id: filterForm.value.store_id || '',
        });
        const res = await fetch(`/reports/staff-performance/cashier/${staff.id}?${query.toString()}`);
        const data = await res.json();
        modalDetails.value = data.transactions || [];
    } catch (e) {
        console.error('Error fetching cashier details:', e);
    } finally {
        modalLoading.value = false;
    }
};

const openMechanicDetails = async (staff) => {
    modalStaff.value = staff;
    modalType.value = 'mechanic';
    modalOpen.value = true;
    modalLoading.value = true;
    modalDetails.value = [];

    try {
        const query = new URLSearchParams({
            start_date: filterForm.value.start_date,
            end_date: filterForm.value.end_date,
            store_id: filterForm.value.store_id || '',
        });
        const res = await fetch(`/reports/staff-performance/mechanic/${staff.id}?${query.toString()}`);
        const data = await res.json();
        modalDetails.value = data.items || [];
    } catch (e) {
        console.error('Error fetching mechanic details:', e);
    } finally {
        modalLoading.value = false;
    }
};

const closeModal = () => {
    modalOpen.value = false;
    modalStaff.value = null;
    modalDetails.value = [];
};
</script>

<template>
    <Head title="Laporan Kinerja Karyawan" />

    <div class="space-y-6">
        <!-- Page Title & Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-highlighted flex items-center gap-2">
                    <UIcon name="i-lucide-bar-chart-3" class="size-6 text-primary" />
                    <span>Laporan Kinerja & Produktivitas Karyawan</span>
                </h1>
                <p class="text-xs text-muted mt-0.5">
                    Evaluasi omzet penjualan kasir dan pencapaian pengerjaan servis mekanik berdasarkan periode dan cabang bengkel.
                </p>
            </div>

            <!-- Quick Date Presets -->
            <div class="flex flex-wrap items-center gap-1.5 bg-elevated/60 border border-default p-1 rounded-xl">
                <button
                    type="button"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg hover:bg-default transition text-muted hover:text-highlighted cursor-pointer"
                    @click="setDatePreset('today')"
                >
                    Hari Ini
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg hover:bg-default transition text-muted hover:text-highlighted cursor-pointer"
                    @click="setDatePreset('week')"
                >
                    7 Hari
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg hover:bg-default transition text-muted hover:text-highlighted cursor-pointer"
                    @click="setDatePreset('month')"
                >
                    Bulan Ini
                </button>
                <button
                    type="button"
                    class="px-2.5 py-1 text-xs font-medium rounded-lg hover:bg-default transition text-muted hover:text-highlighted cursor-pointer"
                    @click="setDatePreset('last_month')"
                >
                    Bulan Lalu
                </button>
            </div>
        </div>

        <!-- Filter Card -->
        <UCard :ui="{ body: 'p-4 sm:p-5' }">
            <form class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end" @submit.prevent="applyFilter">
                <!-- Dari Tanggal -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-highlighted">Dari Tanggal</label>
                    <input
                        v-model="filterForm.start_date"
                        type="date"
                        class="w-full rounded-lg border border-default bg-default px-3 py-2 text-xs text-highlighted outline-none focus:border-primary"
                    />
                </div>

                <!-- Sampai Tanggal -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-highlighted">Sampai Tanggal</label>
                    <input
                        v-model="filterForm.end_date"
                        type="date"
                        class="w-full rounded-lg border border-default bg-default px-3 py-2 text-xs text-highlighted outline-none focus:border-primary"
                    />
                </div>

                <!-- Cabang Toko -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-highlighted">Cabang Toko</label>
                    <select
                        v-model="filterForm.store_id"
                        class="w-full rounded-lg border border-default bg-default px-3 py-2 text-xs text-highlighted outline-none focus:border-primary"
                    >
                        <option value="">Semua Cabang Toko</option>
                        <option v-for="store in stores" :key="store.id" :value="store.id">
                            {{ store.name }} ({{ store.code }})
                        </option>
                    </select>
                </div>

                <!-- Filter Peran -->
                <div class="space-y-1">
                    <label class="block text-xs font-semibold text-highlighted">Peran Karyawan</label>
                    <select
                        v-model="filterForm.role"
                        class="w-full rounded-lg border border-default bg-default px-3 py-2 text-xs text-highlighted outline-none focus:border-primary"
                    >
                        <option value="all">Semua Karyawan</option>
                        <option value="kasir">Khusus Kasir</option>
                        <option value="mekanik">Khusus Mekanik</option>
                    </select>
                </div>

                <!-- Actions: Cari & Reset -->
                <div class="flex items-center gap-2">
                    <button
                        type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg bg-primary px-3 py-2 text-xs font-bold text-inverted shadow-xs hover:bg-primary/90 transition cursor-pointer"
                    >
                        <UIcon name="i-lucide-filter" class="size-3.5" />
                        <span>Filter</span>
                    </button>
                    <button
                        type="button"
                        class="rounded-lg border border-default bg-default px-3 py-2 text-xs font-medium text-muted hover:text-highlighted hover:bg-elevated transition cursor-pointer"
                        @click="resetFilter"
                    >
                        Reset
                    </button>
                </div>
            </form>
        </UCard>

        <!-- KPI Executive Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Omzet Kasir -->
            <div class="bg-default border border-default rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                <div class="size-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <UIcon name="i-lucide-badge-dollar-sign" class="size-6" />
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-medium text-muted block">Total Omzet Penjualan (Kasir)</span>
                    <span class="text-base font-extrabold text-highlighted tracking-tight block truncate mt-0.5">
                        {{ formatRupiah(summary.total_cashier_sales) }}
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">
                        Dari {{ summary.total_cashier_transactions }} transaksi struk
                    </span>
                </div>
            </div>

            <!-- Total Transaksi Kasir -->
            <div class="bg-default border border-default rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                <div class="size-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                    <UIcon name="i-lucide-shopping-cart" class="size-6" />
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-medium text-muted block">Jumlah Transaksi Kasir</span>
                    <span class="text-base font-extrabold text-highlighted tracking-tight block truncate mt-0.5">
                        {{ summary.total_cashier_transactions }} Transaksi
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">
                        Rata-rata: {{ summary.total_cashier_transactions > 0 ? formatRupiah(summary.total_cashier_sales / summary.total_cashier_transactions) : 'Rp 0' }} / nota
                    </span>
                </div>
            </div>

            <!-- Total Nilai Jasa Servis Mekanik -->
            <div class="bg-default border border-default rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                <div class="size-12 rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
                    <UIcon name="i-lucide-wrench" class="size-6" />
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-medium text-muted block">Total Nilai Jasa Servis (Mekanik)</span>
                    <span class="text-base font-extrabold text-highlighted tracking-tight block truncate mt-0.5">
                        {{ formatRupiah(summary.total_mechanic_revenue) }}
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">
                        Dari {{ summary.total_mechanic_jobs }} item pengerjaan
                    </span>
                </div>
            </div>

            <!-- Total Pengerjaan Servis Mekanik -->
            <div class="bg-default border border-default rounded-2xl p-4 shadow-xs flex items-center gap-3.5">
                <div class="size-12 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <UIcon name="i-lucide-check-check" class="size-6" />
                </div>
                <div class="min-w-0">
                    <span class="text-[11px] font-medium text-muted block">Jumlah Pekerjaan Servis</span>
                    <span class="text-base font-extrabold text-highlighted tracking-tight block truncate mt-0.5">
                        {{ summary.total_mechanic_jobs }} Pekerjaan
                    </span>
                    <span class="text-[10px] text-muted block mt-0.5">
                        Rata-rata: {{ summary.total_mechanic_jobs > 0 ? formatRupiah(summary.total_mechanic_revenue / summary.total_mechanic_jobs) : 'Rp 0' }} / jasa
                    </span>
                </div>
            </div>
        </div>

        <!-- Tab Selector: Kasir vs Mekanik -->
        <div class="flex items-center gap-3 border-b border-default pb-2">
            <button
                type="button"
                class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition cursor-pointer"
                :class="activeTab === 'cashier' ? 'bg-primary text-inverted shadow-xs' : 'text-muted hover:text-highlighted hover:bg-elevated'"
                @click="activeTab = 'cashier'"
            >
                <UIcon name="i-lucide-shopping-bag" class="size-4" />
                <span>Kinerja Kasir (Penjualan)</span>
                <span
                    class="px-2 py-0.5 text-xs rounded-full font-bold"
                    :class="activeTab === 'cashier' ? 'bg-white/20 text-white' : 'bg-elevated text-muted'"
                >
                    {{ cashiers.length }}
                </span>
            </button>

            <button
                type="button"
                class="flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition cursor-pointer"
                :class="activeTab === 'mechanic' ? 'bg-primary text-inverted shadow-xs' : 'text-muted hover:text-highlighted hover:bg-elevated'"
                @click="activeTab = 'mechanic'"
            >
                <UIcon name="i-lucide-wrench" class="size-4" />
                <span>Kinerja Mekanik (Servis & SPK)</span>
                <span
                    class="px-2 py-0.5 text-xs rounded-full font-bold"
                    :class="activeTab === 'mechanic' ? 'bg-white/20 text-white' : 'bg-elevated text-muted'"
                >
                    {{ mechanics.length }}
                </span>
            </button>
        </div>

        <!-- TAB 1: KINERJA KASIR TABLE -->
        <div v-show="activeTab === 'cashier'" class="space-y-4">
            <UCard :ui="{ body: 'p-0!' }">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-elevated/60 text-muted uppercase tracking-wider text-[11px] border-b border-default">
                            <tr>
                                <th class="px-4 py-3.5 font-semibold">Peringkat & Kasir</th>
                                <th class="px-4 py-3.5 font-semibold">Cabang Toko</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Jumlah Transaksi</th>
                                <th class="px-4 py-3.5 font-semibold text-right">Rata-rata / Nota</th>
                                <th class="px-4 py-3.5 font-semibold text-right">Total Omzet Penjualan</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-default">
                            <tr
                                v-for="(staff, index) in cashiers"
                                :key="staff.id"
                                class="hover:bg-elevated/40 transition-colors"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="size-6 rounded-full flex items-center justify-center font-bold text-xs"
                                            :class="index === 0 && staff.total_sales > 0 ? 'bg-amber-400/20 text-amber-600' : 'bg-elevated text-muted'"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <div>
                                            <p class="font-bold text-highlighted text-sm">{{ staff.name }}</p>
                                            <p class="text-[11px] text-muted">
                                                NIK: {{ staff.nik || '-' }}
                                                <span v-if="staff.phone"> &bull; {{ staff.phone }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-muted">
                                    {{ staff.store ? `${staff.store.name} (${staff.store.code})` : 'Pusat' }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-primary/10 text-primary">
                                        {{ staff.total_transactions }} Transaksi
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-muted">
                                    {{ formatRupiah(staff.average_sales) }}
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <span class="font-bold text-sm font-mono text-emerald-600 dark:text-emerald-400">
                                        {{ formatRupiah(staff.total_sales) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-default bg-default text-xs font-semibold text-highlighted hover:bg-elevated transition shadow-2xs cursor-pointer"
                                        @click="openCashierDetails(staff)"
                                    >
                                        <UIcon name="i-lucide-receipt" class="size-3.5 text-primary" />
                                        <span>Rincian</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="cashiers.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-muted">
                                    <UIcon name="i-lucide-inbox" class="size-8 mx-auto text-muted/60 mb-2" />
                                    <p class="text-sm font-medium">Tidak ada data transaksi kasir pada periode ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </UCard>
        </div>

        <!-- TAB 2: KINERJA MEKANIK TABLE -->
        <div v-show="activeTab === 'mechanic'" class="space-y-4">
            <UCard :ui="{ body: 'p-0!' }">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-elevated/60 text-muted uppercase tracking-wider text-[11px] border-b border-default">
                            <tr>
                                <th class="px-4 py-3.5 font-semibold">Peringkat & Mekanik</th>
                                <th class="px-4 py-3.5 font-semibold">Cabang Toko</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Unit SPK / Kendaraan</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Total Item Pekerjaan</th>
                                <th class="px-4 py-3.5 font-semibold text-right">Rata-rata / Jasa</th>
                                <th class="px-4 py-3.5 font-semibold text-right">Total Nilai Jasa Servis</th>
                                <th class="px-4 py-3.5 font-semibold text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-default">
                            <tr
                                v-for="(staff, index) in mechanics"
                                :key="staff.id"
                                class="hover:bg-elevated/40 transition-colors"
                            >
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="size-6 rounded-full flex items-center justify-center font-bold text-xs"
                                            :class="index === 0 && staff.total_revenue > 0 ? 'bg-sky-400/20 text-sky-600' : 'bg-elevated text-muted'"
                                        >
                                            {{ index + 1 }}
                                        </span>
                                        <div>
                                            <p class="font-bold text-highlighted text-sm">{{ staff.name }}</p>
                                            <p class="text-[11px] text-muted">
                                                NIK: {{ staff.nik || '-' }}
                                                <span v-if="staff.phone"> &bull; {{ staff.phone }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-muted">
                                    {{ staff.store ? `${staff.store.name} (${staff.store.code})` : 'Pusat' }}
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-elevated text-highlighted">
                                        {{ staff.total_vehicles }} Kendaraan
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-sky-500/10 text-sky-600 dark:text-sky-400">
                                        {{ staff.total_jobs }} Pekerjaan
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right font-mono text-muted">
                                    {{ formatRupiah(staff.average_job) }}
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <span class="font-bold text-sm font-mono text-sky-600 dark:text-sky-400">
                                        {{ formatRupiah(staff.total_revenue) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-default bg-default text-xs font-semibold text-highlighted hover:bg-elevated transition shadow-2xs cursor-pointer"
                                        @click="openMechanicDetails(staff)"
                                    >
                                        <UIcon name="i-lucide-wrench" class="size-3.5 text-primary" />
                                        <span>Rincian</span>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="mechanics.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-muted">
                                    <UIcon name="i-lucide-inbox" class="size-8 mx-auto text-muted/60 mb-2" />
                                    <p class="text-sm font-medium">Tidak ada data pengerjaan servis mekanik pada periode ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </UCard>
        </div>

        <!-- DETAIL SLIDEOVER / MODAL -->
        <UModal v-model:open="modalOpen" :title="modalType === 'cashier' ? 'Rincian Penjualan Kasir' : 'Rincian Pekerjaan Servis Mekanik'">
            <template #content>
                <div class="p-5 space-y-4 max-h-[80vh] overflow-y-auto">
                    <!-- Staff Header in Modal -->
                    <div class="flex items-center justify-between pb-3 border-b border-default">
                        <div>
                            <h3 class="text-base font-bold text-highlighted">{{ modalStaff?.name }}</h3>
                            <p class="text-xs text-muted">
                                NIK: {{ modalStaff?.nik || '-' }} &bull; Periode: {{ filterForm.start_date }} s/d {{ filterForm.end_date }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="size-8 rounded-lg border border-default hover:bg-elevated flex items-center justify-center text-muted hover:text-highlighted cursor-pointer"
                            @click="closeModal"
                        >
                            <UIcon name="i-lucide-x" class="size-4" />
                        </button>
                    </div>

                    <!-- Loading State -->
                    <div v-if="modalLoading" class="py-12 text-center text-muted">
                        <UIcon name="i-lucide-loader-2" class="size-6 animate-spin mx-auto text-primary mb-2" />
                        <p class="text-xs">Memuat rincian aktivitas...</p>
                    </div>

                    <!-- Cashier Transactions List -->
                    <div v-else-if="modalType === 'cashier'" class="space-y-3">
                        <div v-if="modalDetails.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-elevated/70 text-muted uppercase text-[10px]">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">Nomor Nota</th>
                                        <th class="px-3 py-2 font-semibold">Tanggal</th>
                                        <th class="px-3 py-2 font-semibold">Pelanggan</th>
                                        <th class="px-3 py-2 font-semibold">Metode</th>
                                        <th class="px-3 py-2 font-semibold text-right">Total Transaksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default">
                                    <tr v-for="tx in modalDetails" :key="tx.id" class="hover:bg-elevated/30">
                                        <td class="px-3 py-2 font-mono font-bold text-primary">{{ tx.number }}</td>
                                        <td class="px-3 py-2 text-muted">{{ tx.transaction_date }}</td>
                                        <td class="px-3 py-2 text-highlighted">{{ tx.customer_name }}</td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-elevated font-medium text-muted">
                                                {{ tx.payment_name }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ formatRupiah(tx.grand_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="py-8 text-center text-xs text-muted">Tidak ada riwayat transaksi ditemukan.</p>
                    </div>

                    <!-- Mechanic Jobs List -->
                    <div v-else-if="modalType === 'mechanic'" class="space-y-3">
                        <div v-if="modalDetails.length > 0" class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-elevated/70 text-muted uppercase text-[10px]">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">No. SPK & Plat</th>
                                        <th class="px-3 py-2 font-semibold">Kendaraan / Pelanggan</th>
                                        <th class="px-3 py-2 font-semibold">Deskripsi Jasa / Servis</th>
                                        <th class="px-3 py-2 font-semibold text-center">Qty</th>
                                        <th class="px-3 py-2 font-semibold text-right">Nilai Jasa</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-default">
                                    <tr v-for="item in modalDetails" :key="item.id" class="hover:bg-elevated/30">
                                        <td class="px-3 py-2">
                                            <span class="font-mono font-bold text-primary block">{{ item.spk_number || '-' }}</span>
                                            <span class="text-[10px] text-muted block">{{ item.plate_number || '-' }}</span>
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="text-highlighted font-medium block">{{ item.vehicle || '-' }}</span>
                                            <span class="text-[10px] text-muted block">{{ item.customer_name }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-highlighted">{{ item.description }}</td>
                                        <td class="px-3 py-2 text-center text-muted font-bold">{{ item.quantity }}</td>
                                        <td class="px-3 py-2 text-right font-mono font-bold text-sky-600 dark:text-sky-400">
                                            {{ formatRupiah(item.line_total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="py-8 text-center text-xs text-muted">Tidak ada riwayat pekerjaan ditemukan.</p>
                    </div>
                </div>
            </template>
        </UModal>
    </div>
</template>
