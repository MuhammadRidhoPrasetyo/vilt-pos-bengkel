<script setup>
import DashboardLayout from '../Layouts/DashboardLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarDate, DateFormatter, getLocalTimeZone, today } from '@internationalized/date';
import { computed, ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Beranda & Arus Kas', panelId: 'dashboard' }],
});

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({
            total_income: 0,
            total_expense: 0,
            net_balance: 0,
            total_transactions: 0,
            total_revenue: 0,
        }),
    },
    recentCashFlows: {
        type: Object,
        default: () => ({ data: [] }),
    },
    stockAlerts: {
        type: Array,
        default: () => [],
    },
    stockSummary: {
        type: Object,
        default: () => ({
            out_of_stock_count: 0,
            below_min_count: 0,
            approaching_min_count: 0,
            total_alert_count: 0,
        }),
    },
    canFilterStore: {
        type: Boolean,
        default: false,
    },
    stockStoreId: {
        type: String,
        default: '',
    },
    options: {
        type: Object,
        default: () => ({ stores: [], incomeCategories: [], expenseCategories: [] }),
    },
});

const period = ref('daily');

const df = new DateFormatter('id-ID', {
    dateStyle: 'medium',
});

const selectedRange = ref({
    start: new Date(Date.now() - 14 * 24 * 60 * 60 * 1000),
    end: new Date(),
});

const ranges = [
    { label: '7 Hari Terakhir', days: 7 },
    { label: '14 Hari Terakhir', days: 14 },
    { label: '30 Hari Terakhir', days: 30 },
    { label: '3 Bulan Terakhir', months: 3 },
    { label: '6 Bulan Terakhir', months: 6 },
    { label: '1 Tahun Terakhir', years: 1 },
];

const toCalendarDate = (date) => new CalendarDate(
    date.getFullYear(),
    date.getMonth() + 1,
    date.getDate(),
);

const calendarRange = computed({
    get: () => ({
        start: selectedRange.value.start ? toCalendarDate(selectedRange.value.start) : undefined,
        end: selectedRange.value.end ? toCalendarDate(selectedRange.value.end) : undefined,
    }),
    set: (newValue) => {
        selectedRange.value = {
            start: newValue.start ? newValue.start.toDate(getLocalTimeZone()) : new Date(),
            end: newValue.end ? newValue.end.toDate(getLocalTimeZone()) : new Date(),
        };
    },
});

const rangeLabel = computed(() => {
    if (!selectedRange.value.start) {
        return 'Pilih tanggal';
    }

    if (!selectedRange.value.end) {
        return df.format(selectedRange.value.start);
    }

    return `${df.format(selectedRange.value.start)} - ${df.format(selectedRange.value.end)}`;
});

const isRangeSelected = (range) => {
    if (!selectedRange.value.start || !selectedRange.value.end) {
        return false;
    }

    const currentDate = today(getLocalTimeZone());
    let startDate = currentDate.copy();

    if (range.days) {
        startDate = startDate.subtract({ days: range.days });
    } else if (range.months) {
        startDate = startDate.subtract({ months: range.months });
    } else if (range.years) {
        startDate = startDate.subtract({ years: range.years });
    }

    return toCalendarDate(selectedRange.value.start).compare(startDate) === 0
        && toCalendarDate(selectedRange.value.end).compare(currentDate) === 0;
};

const selectRange = (range) => {
    const endDate = today(getLocalTimeZone());
    let startDate = endDate.copy();

    if (range.days) {
        startDate = startDate.subtract({ days: range.days });
    } else if (range.months) {
        startDate = startDate.subtract({ months: range.months });
    } else if (range.years) {
        startDate = startDate.subtract({ years: range.years });
    }

    selectedRange.value = {
        start: startDate.toDate(getLocalTimeZone()),
        end: endDate.toDate(getLocalTimeZone()),
    };
};

const daysInRange = computed(() => {
    const start = selectedRange.value.start;
    const end = selectedRange.value.end;

    return Math.max(1, Math.ceil((end.getTime() - start.getTime()) / 86400000) + 1);
});

const periods = computed(() => {
    if (daysInRange.value <= 8) {
        return ['daily'];
    }

    if (daysInRange.value <= 31) {
        return ['daily', 'weekly'];
    }

    return ['weekly', 'monthly'];
});

watch(periods, () => {
    if (!periods.value.includes(period.value)) {
        [period.value] = periods.value;
    }
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }).format(d);
};

// Stock Alert Filter Logic
const selectedStockStore = ref(props.stockStoreId || '');
const activeStockTab = ref('all'); // 'all' | 'out_of_stock' | 'below_min' | 'approaching_min'

const changeStockStoreFilter = () => {
    router.get('/dashboard', {
        stock_store_id: selectedStockStore.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const filteredStockAlerts = computed(() => {
    if (activeStockTab.value === 'all') {
        return props.stockAlerts;
    }
    return props.stockAlerts.filter((item) => item.alert_status === activeStockTab.value);
});

// Modal Input Transaksi Kas Cepat dari Dashboard
const modalOpen = ref(false);
const modalType = ref('income'); // 'income' | 'expense'

const modalForm = useForm({
    store_id: props.options?.stores?.[0]?.value || '',
    category_id: '',
    type: 'income',
    amount: '',
    date: new Date().toISOString().substring(0, 10),
    description: '',
});

const availableCategories = computed(() => {
    if (modalType.value === 'income') {
        return props.options?.incomeCategories || [];
    }
    return props.options?.expenseCategories || [];
});

const openModal = (type = 'income') => {
    modalType.value = type;
    modalForm.type = type;
    modalForm.category_id = (type === 'income' ? props.options?.incomeCategories?.[0]?.value : props.options?.expenseCategories?.[0]?.value) || '';
    modalForm.amount = '';
    modalForm.date = new Date().toISOString().substring(0, 10);
    modalForm.description = '';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    modalForm.reset();
};

const submitModal = () => {
    modalForm.post('/cash-flows', {
        onSuccess: () => {
            closeModal();
            router.reload({ only: ['summary', 'recentCashFlows'] });
        },
    });
};

watch(modalType, (newType) => {
    modalForm.type = newType;
    modalForm.category_id = (newType === 'income' ? props.options?.incomeCategories?.[0]?.value : props.options?.expenseCategories?.[0]?.value) || '';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Toolbar & Filter -->
        <UDashboardToolbar>
            <template #left>
                <UPopover :content="{ align: 'start' }" :modal="true">
                    <UButton
                        color="neutral"
                        variant="ghost"
                        icon="i-lucide-calendar"
                        class="-ms-1 data-[state=open]:bg-elevated group"
                    >
                        <span class="truncate">{{ rangeLabel }}</span>

                        <template #trailing>
                            <UIcon name="i-lucide-chevron-down" class="size-5 shrink-0 text-dimmed transition-transform duration-200 group-data-[state=open]:rotate-180" />
                        </template>
                    </UButton>

                    <template #content>
                        <div class="flex items-stretch divide-default sm:divide-x">
                            <div class="hidden flex-col justify-center sm:flex">
                                <UButton
                                    v-for="(range, index) in ranges"
                                    :key="index"
                                    :label="range.label"
                                    color="neutral"
                                    variant="ghost"
                                    class="rounded-none px-4"
                                    :class="[isRangeSelected(range) ? 'bg-elevated' : 'hover:bg-elevated/50']"
                                    truncate
                                    @click="selectRange(range)"
                                />
                            </div>

                            <UCalendar
                                v-model="calendarRange"
                                class="p-2"
                                :number-of-months="2"
                                range
                            />
                        </div>
                    </template>
                </UPopover>

                <USelect
                    v-model="period"
                    :items="periods"
                    variant="ghost"
                    class="data-[state=open]:bg-elevated"
                    :ui="{ value: 'capitalize', itemLabel: 'capitalize', trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                />
            </template>

            <template #right>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 transition-colors shadow-xs"
                        @click="openModal('income')"
                    >
                        <UIcon name="i-lucide-arrow-down-left" class="size-3.5" />
                        + Pemasukan Kas
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-500 transition-colors shadow-xs"
                        @click="openModal('expense')"
                    >
                        <UIcon name="i-lucide-arrow-up-right" class="size-3.5" />
                        - Pengeluaran Kas
                    </button>
                </div>
            </template>
        </UDashboardToolbar>

        <!-- Dynamic Cash Flow Metric Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Pemasukan -->
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4 flex items-center justify-between shadow-xs">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Total Pemasukan Kas</p>
                    <p class="mt-1 text-2xl font-extrabold font-mono text-emerald-300">
                        {{ formatCurrency(summary.total_income) }}
                    </p>
                    <p class="mt-1 text-[11px] text-emerald-400/80">Termasuk POS & setoran manual</p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                    <UIcon name="i-lucide-trending-up" class="size-6" />
                </div>
            </div>

            <!-- Total Pengeluaran -->
            <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4 flex items-center justify-between shadow-xs">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-400">Total Pengeluaran Kas</p>
                    <p class="mt-1 text-2xl font-extrabold font-mono text-rose-300">
                        {{ formatCurrency(summary.total_expense) }}
                    </p>
                    <p class="mt-1 text-[11px] text-rose-400/80">Termasuk restok & biaya operasional</p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-400 shrink-0">
                    <UIcon name="i-lucide-trending-down" class="size-6" />
                </div>
            </div>

            <!-- Saldo Kas Bersih -->
            <div :class="[
                'rounded-xl border p-4 flex items-center justify-between shadow-xs',
                summary.net_balance >= 0 ? 'border-indigo-500/20 bg-indigo-500/5' : 'border-amber-500/20 bg-amber-500/5'
            ]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Arus Kas Bersih (Net)</p>
                    <p :class="['mt-1 text-2xl font-extrabold font-mono', summary.net_balance >= 0 ? 'text-indigo-300' : 'text-amber-400']">
                        {{ formatCurrency(summary.net_balance) }}
                    </p>
                    <p class="mt-1 text-[11px] text-muted">Saldo kumulatif kas toko</p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-400 shrink-0">
                    <UIcon name="i-lucide-wallet" class="size-6" />
                </div>
            </div>

            <!-- Total Penjualan POS -->
            <div class="rounded-xl border border-blue-500/20 bg-blue-500/5 p-4 flex items-center justify-between shadow-xs">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-400">Total Transaksi POS</p>
                    <p class="mt-1 text-2xl font-extrabold font-mono text-blue-300">
                        {{ summary.total_transactions }} <span class="text-xs font-normal text-muted">Transaksi</span>
                    </p>
                    <p class="mt-1 text-[11px] text-blue-400/80">Omset: {{ formatCurrency(summary.total_revenue) }}</p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-blue-500/20 flex items-center justify-center text-blue-400 shrink-0">
                    <UIcon name="i-lucide-shopping-cart" class="size-6" />
                </div>
            </div>
        </div>

        <!-- Stock Alert & Restock Section -->
        <div class="space-y-4 rounded-xl border border-default bg-default p-5 shadow-xs">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-b border-default pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-alert-triangle" class="size-5 text-amber-500" />
                        <h2 class="text-lg font-bold text-highlighted">Peringatan Stok Barang & Restok</h2>
                        <span class="rounded-full bg-rose-500/10 px-2.5 py-0.5 text-xs font-bold text-rose-400 border border-rose-500/20">
                            {{ stockSummary.total_alert_count }} Barang Perlu Perhatian
                        </span>
                    </div>
                    <p class="text-xs text-muted">Daftar produk yang habis (0), berada di bawah stok minimal, atau mendekati stok minimal.</p>
                </div>

                <!-- Store Filter Selector (Visible only for Owner / Multi-store Manager) -->
                <div v-if="canFilterStore" class="flex items-center gap-2">
                    <label class="text-xs font-medium text-muted shrink-0">Filter Cabang:</label>
                    <select
                        v-model="selectedStockStore"
                        class="rounded-lg border border-default bg-elevated/40 px-3 py-1.5 text-xs font-semibold outline-none focus:border-primary"
                        @change="changeStockStoreFilter"
                    >
                        <option value="">Semua Toko / Cabang</option>
                        <option v-for="st in options.stores" :key="st.value" :value="st.value">
                            {{ st.label }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Tab Status Filters -->
            <div class="flex flex-wrap gap-2 pt-1">
                <button
                    type="button"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors border',
                        activeStockTab === 'all'
                            ? 'bg-primary text-inverted border-primary'
                            : 'bg-elevated/40 text-muted border-default hover:text-highlighted'
                    ]"
                    @click="activeStockTab = 'all'"
                >
                    Semua Peringatan ({{ stockSummary.total_alert_count }})
                </button>
                <button
                    type="button"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors border flex items-center gap-1.5',
                        activeStockTab === 'out_of_stock'
                            ? 'bg-rose-600 text-white border-rose-600'
                            : 'bg-rose-500/10 text-rose-400 border-rose-500/20 hover:bg-rose-500/20'
                    ]"
                    @click="activeStockTab = 'out_of_stock'"
                >
                    <span class="size-2 rounded-full bg-rose-500 animate-pulse" />
                    Stok Habis ({{ stockSummary.out_of_stock_count }})
                </button>
                <button
                    type="button"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors border flex items-center gap-1.5',
                        activeStockTab === 'below_min'
                            ? 'bg-amber-600 text-white border-amber-600'
                            : 'bg-amber-500/10 text-amber-400 border-amber-500/20 hover:bg-amber-500/20'
                    ]"
                    @click="activeStockTab = 'below_min'"
                >
                    <span class="size-2 rounded-full bg-amber-500" />
                    Di Bawah Minimal ({{ stockSummary.below_min_count }})
                </button>
                <button
                    type="button"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors border flex items-center gap-1.5',
                        activeStockTab === 'approaching_min'
                            ? 'bg-blue-600 text-white border-blue-600'
                            : 'bg-blue-500/10 text-blue-400 border-blue-500/20 hover:bg-blue-500/20'
                    ]"
                    @click="activeStockTab = 'approaching_min'"
                >
                    <span class="size-2 rounded-full bg-blue-500" />
                    Mendekati Minimal ({{ stockSummary.approaching_min_count }})
                </button>
            </div>

            <!-- Table Data -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-default bg-elevated/50 text-muted">
                            <th class="p-3 font-semibold">Produk / Varian</th>
                            <th class="p-3 font-semibold">SKU / Barcode</th>
                            <th class="p-3 font-semibold">Toko / Gudang</th>
                            <th class="p-3 font-semibold text-center">Stok Saat Ini</th>
                            <th class="p-3 font-semibold text-center">Stok Minimal</th>
                            <th class="p-3 font-semibold text-center">Status Alert</th>
                            <th class="p-3 font-semibold text-center w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="item in filteredStockAlerts" :key="item.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="p-3 font-medium text-highlighted">
                                {{ item.variant_display_name }}
                            </td>
                            <td class="p-3 text-muted font-mono text-[11px]">
                                <div>{{ item.sku !== '-' ? item.sku : '' }}</div>
                                <div v-if="item.barcode && item.barcode !== '-'" class="text-muted/70">{{ item.barcode }}</div>
                            </td>
                            <td class="p-3 text-muted">
                                <span class="font-medium text-highlighted">{{ item.store_name }}</span>
                                <span class="block text-[11px] text-muted">{{ item.warehouse_name }}</span>
                            </td>
                            <td class="p-3 text-center font-mono font-bold text-sm">
                                <span :class="[
                                    item.quantity <= 0 ? 'text-rose-400 font-extrabold' : item.alert_status === 'below_min' ? 'text-amber-400' : 'text-blue-400'
                                ]">
                                    {{ item.quantity }}
                                </span>
                            </td>
                            <td class="p-3 text-center font-mono text-muted">
                                {{ item.minimum_stock }}
                            </td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <span v-if="item.alert_status === 'out_of_stock'" class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-rose-400 border border-rose-500/20">
                                    <UIcon name="i-lucide-x-circle" class="size-3" /> Stok Habis
                                </span>
                                <span v-else-if="item.alert_status === 'below_min'" class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-amber-400 border border-amber-500/20">
                                    <UIcon name="i-lucide-alert-circle" class="size-3" /> Di Bawah Min
                                </span>
                                <span v-else class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-2.5 py-0.5 text-[11px] font-semibold text-blue-400 border border-blue-500/20">
                                    <UIcon name="i-lucide-info" class="size-3" /> Mendekati Min
                                </span>
                            </td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <Link
                                    href="/purchases/create"
                                    class="inline-flex items-center gap-1 rounded-md bg-primary/10 px-2.5 py-1 text-[11px] font-semibold text-primary hover:bg-primary/20 transition-colors border border-primary/20"
                                >
                                    <UIcon name="i-lucide-plus" class="size-3" /> Restok
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="filteredStockAlerts.length === 0">
                            <td colspan="7" class="p-8 text-center text-muted">
                                <UIcon name="i-lucide-check-circle-2" class="mx-auto size-10 text-emerald-400/60" />
                                <p class="mt-2 text-sm font-medium text-highlighted">Semua stok barang dalam kondisi aman.</p>
                                <p class="text-xs text-muted">Tidak ada produk yang perlu restok saat ini.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cash Flows Index Section -->
        <div class="space-y-4 rounded-xl border border-default bg-default p-5 shadow-xs">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-default pb-4">
                <div>
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-receipt" class="size-5 text-primary" />
                        <h2 class="text-lg font-bold text-highlighted">Index Transaksi Arus Kas (Cash Flows Index)</h2>
                    </div>
                    <p class="text-xs text-muted">Daftar catatan pemasukan dan pengeluaran kas operasional terbaru toko.</p>
                </div>
                <Link
                    href="/cash-flows"
                    class="inline-flex items-center gap-1 text-xs font-semibold text-primary hover:underline"
                >
                    Lihat Semua & Filter Arus Kas
                    <UIcon name="i-lucide-arrow-right" class="size-4" />
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-default bg-elevated/50 text-muted">
                            <th class="p-3 font-semibold">Tanggal</th>
                            <th class="p-3 font-semibold">Toko / Cabang</th>
                            <th class="p-3 font-semibold">Kategori</th>
                            <th class="p-3 font-semibold">Tipe</th>
                            <th class="p-3 font-semibold">Deskripsi</th>
                            <th class="p-3 font-semibold text-right">Nominal</th>
                            <th class="p-3 font-semibold text-center">Sumber / Referensi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="item in recentCashFlows.data" :key="item.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="p-3 font-medium text-highlighted whitespace-nowrap">
                                {{ formatDate(item.date) }}
                            </td>
                            <td class="p-3 text-muted">
                                {{ item.store?.name || '-' }}
                            </td>
                            <td class="p-3 font-medium text-highlighted">
                                {{ item.category?.name || '-' }}
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <span v-if="item.type === 'income'" class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-400 border border-emerald-500/20">
                                    <UIcon name="i-lucide-arrow-down-left" class="size-3" /> Pemasukan
                                </span>
                                <span v-else class="inline-flex items-center gap-1 rounded-full bg-rose-500/10 px-2 py-0.5 text-[11px] font-semibold text-rose-400 border border-rose-500/20">
                                    <UIcon name="i-lucide-arrow-up-right" class="size-3" /> Pengeluaran
                                </span>
                            </td>
                            <td class="p-3 text-highlighted max-w-xs truncate">
                                {{ item.description || '-' }}
                            </td>
                            <td :class="['p-3 text-right font-mono font-bold text-sm whitespace-nowrap', item.type === 'income' ? 'text-emerald-400' : 'text-rose-400']">
                                {{ item.type === 'income' ? '+' : '-' }} {{ formatCurrency(item.amount) }}
                            </td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <span v-if="item.is_manual" class="rounded bg-indigo-500/10 px-2 py-0.5 text-[10px] font-medium text-indigo-300 border border-indigo-500/20">
                                    Input Manual
                                </span>
                                <span v-else-if="item.reference_type?.includes('Transaction')" class="rounded bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-300 border border-emerald-500/20">
                                    Penjualan POS
                                </span>
                                <span v-else-if="item.reference_type?.includes('Purchase')" class="rounded bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-300 border border-amber-500/20">
                                    Pembelian Stok
                                </span>
                                <span v-else class="rounded bg-zinc-500/10 px-2 py-0.5 text-[10px] font-medium text-zinc-400 border border-zinc-500/20">
                                    Sistem
                                </span>
                            </td>
                        </tr>
                        <tr v-if="recentCashFlows.data.length === 0">
                            <td colspan="7" class="p-8 text-center text-muted">
                                <UIcon name="i-lucide-wallet" class="mx-auto size-10 text-muted/50" />
                                <p class="mt-2 text-sm">Belum ada transaksi arus kas tercatat.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Input Modal (Pemasukan / Pengeluaran) -->
        <div v-if="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-xl bg-default p-6 shadow-2xl space-y-4 border border-default">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div class="flex items-center gap-2">
                        <div :class="['h-9 w-9 rounded-lg flex items-center justify-center', modalType === 'income' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400']">
                            <UIcon :name="modalType === 'income' ? 'i-lucide-arrow-down-left' : 'i-lucide-arrow-up-right'" class="size-5" />
                        </div>
                        <h2 class="text-lg font-bold text-highlighted">
                            {{ modalType === 'income' ? 'Catat Kas Masuk (Pemasukan)' : 'Catat Kas Keluar (Pengeluaran)' }}
                        </h2>
                    </div>
                    <button type="button" class="rounded-md p-1.5 text-muted hover:bg-elevated hover:text-highlighted" @click="closeModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitModal">
                    <div>
                        <label class="block text-xs font-medium text-highlighted mb-1">Toko / Cabang <span class="text-red-500">*</span></label>
                        <select v-model="modalForm.store_id" class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required>
                            <option v-for="st in options.stores" :key="st.value" :value="st.value">{{ st.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-highlighted mb-1">Kategori Arus Kas <span class="text-red-500">*</span></label>
                        <select v-model="modalForm.category_id" class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required>
                            <option v-for="cat in availableCategories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                        </select>
                        <span v-if="modalForm.errors.category_id" class="text-xs text-red-500">{{ modalForm.errors.category_id }}</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-highlighted mb-1">Tanggal <span class="text-red-500">*</span></label>
                            <input v-model="modalForm.date" type="date" class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required />
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-highlighted mb-1">Nominal (Rp) <span class="text-red-500">*</span></label>
                            <input v-model.number="modalForm.amount" type="number" min="0.01" step="any" placeholder="0" class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm font-mono outline-none focus:border-primary" required />
                            <span v-if="modalForm.errors.amount" class="text-xs text-red-500">{{ modalForm.errors.amount }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-highlighted mb-1">Deskripsi / Catatan</label>
                        <textarea v-model="modalForm.description" rows="3" placeholder="Deskripsi transaksi kas..." class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-default">
                        <button type="button" class="px-4 py-2 text-xs font-medium text-muted hover:text-highlighted border border-default rounded-md hover:bg-elevated" @click="closeModal">
                            Batal
                        </button>
                        <button
                            type="submit"
                            :class="['px-4 py-2 text-xs font-semibold text-white rounded-md transition-colors', modalType === 'income' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-rose-600 hover:bg-rose-500']"
                            :disabled="modalForm.processing"
                        >
                            Simpan Transaksi Kas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
