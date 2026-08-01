<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Transaksi Arus Kas', panelId: 'cash-flows' }],
});

const props = defineProps({
    records: { type: Object, required: true },
    summary: { type: Object, default: () => ({ total_income: 0, total_expense: 0, net_balance: 0 }) },
    filters: { type: Object, default: () => ({}) },
    options: { type: Object, default: () => ({ stores: [], categories: [], incomeCategories: [], expenseCategories: [] }) },
});

const search = ref(props.filters.search || '');
const selectedType = ref(props.filters.type || '');
const selectedCategory = ref(props.filters.category_id || '');
const selectedStore = ref(props.filters.store_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const modalOpen = ref(false);
const modalType = ref('income'); // 'income' or 'expense'

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
        },
    });
};

const applyFilters = () => {
    router.get('/cash-flows', {
        search: search.value || undefined,
        type: selectedType.value || undefined,
        category_id: selectedCategory.value || undefined,
        store_id: selectedStore.value || undefined,
        start_date: startDate.value || undefined,
        end_date: endDate.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    search.value = '';
    selectedType.value = '';
    selectedCategory.value = '';
    selectedStore.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilters();
};

const deleteRecord = (id) => {
    if (confirm('Apakah Anda yakin ingin menghapus catatan arus kas ini?')) {
        router.delete(`/cash-flows/${id}`);
    }
};

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

watch(modalType, (newType) => {
    modalForm.type = newType;
    modalForm.category_id = (newType === 'income' ? props.options?.incomeCategories?.[0]?.value : props.options?.expenseCategories?.[0]?.value) || '';
});
</script>

<template>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 border-b border-default pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-highlighted">Transaksi Arus Kas (Cash Flow)</h1>
                <p class="text-xs sm:text-sm text-muted">Kelola pencatatan pemasukan, pengeluaran kas operasional, dan ringkasan saldo keuangan toko.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-emerald-500 transition-colors shadow-sm"
                    @click="openModal('income')"
                >
                    <UIcon name="i-lucide-arrow-down-left" class="size-4" />
                    + Catat Pemasukan
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3.5 py-2 text-xs sm:text-sm font-semibold text-white hover:bg-rose-500 transition-colors shadow-sm"
                    @click="openModal('expense')"
                >
                    <UIcon name="i-lucide-arrow-up-right" class="size-4" />
                    - Catat Pengeluaran
                </button>
            </div>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Pemasukan -->
            <div class="rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4 flex items-center justify-between shadow-xs">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-400">Total Pemasukan</p>
                    <p class="mt-1 text-2xl font-extrabold font-mono text-emerald-300">
                        {{ formatCurrency(summary.total_income) }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                    <UIcon name="i-lucide-trending-up" class="size-6" />
                </div>
            </div>

            <!-- Pengeluaran -->
            <div class="rounded-xl border border-rose-500/20 bg-rose-500/5 p-4 flex items-center justify-between shadow-xs">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-rose-400">Total Pengeluaran</p>
                    <p class="mt-1 text-2xl font-extrabold font-mono text-rose-300">
                        {{ formatCurrency(summary.total_expense) }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-rose-500/20 flex items-center justify-center text-rose-400">
                    <UIcon name="i-lucide-trending-down" class="size-6" />
                </div>
            </div>

            <!-- Saldo Bersih -->
            <div :class="[
                'rounded-xl border p-4 flex items-center justify-between shadow-xs',
                summary.net_balance >= 0 ? 'border-indigo-500/20 bg-indigo-500/5' : 'border-amber-500/20 bg-amber-500/5'
            ]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-400">Arus Kas Bersih (Net)</p>
                    <p :class="['mt-1 text-2xl font-extrabold font-mono', summary.net_balance >= 0 ? 'text-indigo-300' : 'text-amber-400']">
                        {{ formatCurrency(summary.net_balance) }}
                    </p>
                </div>
                <div class="h-12 w-12 rounded-xl bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                    <UIcon name="i-lucide-wallet" class="size-6" />
                </div>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="rounded-xl border border-default bg-default p-4 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                <!-- Search -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-medium text-muted mb-1">Pencarian Deskripsi</label>
                    <div class="relative">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input
                            v-model="search"
                            type="search"
                            class="w-full rounded-md border border-default bg-default py-1.5 pl-9 pr-3 text-xs outline-none focus:border-primary"
                            placeholder="Cari deskripsi / referensi..."
                            @keyup.enter="applyFilters"
                        />
                    </div>
                </div>

                <!-- Tipe -->
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Tipe</label>
                    <select v-model="selectedType" class="w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-xs outline-none focus:border-primary" @change="applyFilters">
                        <option value="">Semua Tipe</option>
                        <option value="income">Pemasukan (Kas Masuk)</option>
                        <option value="expense">Pengeluaran (Kas Keluar)</option>
                    </select>
                </div>

                <!-- Toko -->
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Cabang Toko</label>
                    <select v-model="selectedStore" class="w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-xs outline-none focus:border-primary" @change="applyFilters">
                        <option value="">Semua Toko</option>
                        <option v-for="st in options.stores" :key="st.value" :value="st.value">{{ st.label }}</option>
                    </select>
                </div>

                <!-- Start Date -->
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Dari Tanggal</label>
                    <input v-model="startDate" type="date" class="w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-xs outline-none focus:border-primary" @change="applyFilters" />
                </div>

                <!-- End Date -->
                <div>
                    <label class="block text-xs font-medium text-muted mb-1">Sampai Tanggal</label>
                    <input v-model="endDate" type="date" class="w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-xs outline-none focus:border-primary" @change="applyFilters" />
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-1 border-t border-default/40">
                <button type="button" class="px-3 py-1.5 text-xs font-medium text-muted hover:text-highlighted border border-default rounded-md hover:bg-elevated" @click="resetFilters">
                    Reset Filter
                </button>
                <button type="button" class="px-3 py-1.5 text-xs font-medium bg-primary text-inverted rounded-md hover:bg-primary/90" @click="applyFilters">
                    Terapkan Filter
                </button>
            </div>
        </div>

        <!-- Table Data Card -->
        <div class="rounded-xl border border-default bg-default overflow-hidden">
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
                            <th class="p-3 font-semibold text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="item in records.data" :key="item.id" class="hover:bg-elevated/20 transition-colors">
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
                            <td class="p-3 text-center whitespace-nowrap">
                                <button
                                    v-if="item.is_manual"
                                    type="button"
                                    class="rounded p-1 text-red-500 hover:bg-red-500/10 transition-colors"
                                    title="Hapus"
                                    @click="deleteRecord(item.id)"
                                >
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                                <span v-else class="text-zinc-600 text-xs">-</span>
                            </td>
                        </tr>
                        <tr v-if="records.data.length === 0">
                            <td colspan="8" class="p-8 text-center text-muted">
                                <UIcon name="i-lucide-wallet" class="mx-auto size-10 text-muted/50" />
                                <p class="mt-2 text-sm">Belum ada data transaksi arus kas.</p>
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
                    <!-- Store -->
                    <div>
                        <label class="block text-xs font-medium text-highlighted mb-1">Toko / Cabang <span class="text-red-500">*</span></label>
                        <select v-model="modalForm.store_id" class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required>
                            <option v-for="st in options.stores" :key="st.value" :value="st.value">{{ st.label }}</option>
                        </select>
                    </div>

                    <!-- Category -->
                    <div>
                        <label class="block text-xs font-medium text-highlighted mb-1">Kategori Arus Kas <span class="text-red-500">*</span></label>
                        <select v-model="modalForm.category_id" class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required>
                            <option v-for="cat in availableCategories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                        </select>
                        <span v-if="modalForm.errors.category_id" class="text-xs text-red-500">{{ modalForm.errors.category_id }}</span>
                    </div>

                    <!-- Date & Amount -->
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

                    <!-- Description -->
                    <div>
                        <label class="block text-xs font-medium text-highlighted mb-1">Deskripsi / Catatan</label>
                        <textarea v-model="modalForm.description" rows="3" placeholder="Deskripsi transaksi kas..." class="w-full rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary"></textarea>
                    </div>

                    <!-- Modal Actions -->
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
