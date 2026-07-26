<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Stok Produk', panelId: 'product-stocks' }],
});

const props = defineProps({
    records: Object,
    summary: Object,
    filters: Object,
    options: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isOwner = computed(() => !currentUser.value.store_id || currentUser.value.roles?.includes('owner'));

const search = ref(props.filters?.search || '');
const selectedWarehouseId = ref(props.filters?.warehouse_id || '');

const stockList = computed(() => props.records?.data || props.records || []);
const warehouses = computed(() => props.options?.warehouses || []);
const variants = computed(() => props.options?.variants || []);
const warehouseLocations = computed(() => props.options?.warehouseLocations || []);

const warehouseSelectOptions = computed(() => [
    { label: 'Semua Gudang', value: '' },
    ...warehouses.value,
]);

// Modal states
const showStockModal = ref(false);
const editingStock = ref(null);

// Delete Confirmation Modal State
const showDeleteConfirmModal = ref(false);
const deleteConfirmData = ref({
    title: 'Konfirmasi Hapus Stok',
    description: 'Data stok yang dihapus tidak dapat dikembalikan.',
    action: null,
});

const triggerDeleteConfirm = (title, description, action) => {
    deleteConfirmData.value = { title, description, action };
    showDeleteConfirmModal.value = true;
};

const handleConfirmDelete = () => {
    if (typeof deleteConfirmData.value.action === 'function') {
        deleteConfirmData.value.action();
    }
    showDeleteConfirmModal.value = false;
};

const stockForm = useForm({
    product_variant_id: '',
    warehouse_id: '',
    warehouse_location_id: '',
    quantity: 0,
    minimum_stock: 0,
    is_hidden: false,
});

const filteredLocationOptions = computed(() => {
    if (!stockForm.warehouse_id) return [{ label: 'Tanpa Lokasi Spesifik', value: '' }];
    const matched = warehouseLocations.value.filter((l) => l.warehouse_id === stockForm.warehouse_id);
    return [{ label: 'Tanpa Lokasi Spesifik', value: '' }, ...matched];
});

const openCreateStockModal = () => {
    editingStock.value = null;
    stockForm.clearErrors();
    stockForm.product_variant_id = variants.value[0]?.value || '';
    stockForm.warehouse_id = warehouses.value[0]?.value || '';
    stockForm.warehouse_location_id = '';
    stockForm.quantity = 0;
    stockForm.minimum_stock = 0;
    stockForm.is_hidden = false;
    showStockModal.value = true;
};

const openEditStockModal = (stock) => {
    editingStock.value = stock;
    stockForm.clearErrors();
    stockForm.product_variant_id = stock.product_variant_id;
    stockForm.warehouse_id = stock.warehouse_id;
    stockForm.warehouse_location_id = stock.warehouse_location_id || '';
    stockForm.quantity = stock.quantity;
    stockForm.minimum_stock = stock.minimum_stock;
    stockForm.is_hidden = !!stock.is_hidden;
    showStockModal.value = true;
};

const closeStockModal = () => {
    showStockModal.value = false;
    editingStock.value = null;
};

const submitStockForm = () => {
    const payload = {
        product_variant_id: stockForm.product_variant_id,
        warehouse_id: stockForm.warehouse_id,
        warehouse_location_id: stockForm.warehouse_location_id ? stockForm.warehouse_location_id : null,
        quantity: stockForm.quantity,
        minimum_stock: stockForm.minimum_stock,
        is_hidden: stockForm.is_hidden,
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => closeStockModal(),
    };

    if (editingStock.value) {
        stockForm.transform(() => ({
            ...payload,
            _method: 'put',
        })).post(`/product-stocks/${editingStock.value.id}`, options);
        return;
    }

    stockForm.transform(() => payload).post('/product-stocks', options);
};

const deleteStock = (stock) => {
    triggerDeleteConfirm(
        'Hapus Stok Gudang',
        `Apakah Anda yakin ingin menghapus stok varian ${stock.variant_display_name} di gudang ${stock.warehouse_name}?`,
        () => {
            router.delete(`/product-stocks/${stock.id}`, { preserveScroll: true });
        }
    );
};

const applyFilters = () => {
    router.get(
        '/product-stocks',
        {
            search: search.value || undefined,
            warehouse_id: selectedWarehouseId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

watch([search, selectedWarehouseId], () => {
    applyFilters();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header Title & Action -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-default pb-4">
            <div>
                <h1 class="text-2xl font-bold text-highlighted">Stok Produk (Inventory Stocks)</h1>
                <p class="text-sm text-muted">Monitoring jumlah stok fisik varian produk di seluruh lokasi gudang penyimpan.</p>
            </div>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-inverted hover:bg-primary/90 transition-all shadow-sm"
                @click="openCreateStockModal"
            >
                <UIcon name="i-lucide-plus" class="size-4" />
                Tambah / Adjust Stok
            </button>
        </div>

        <!-- Summary Metric Cards -->
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-default bg-default p-4 shadow-xs flex items-center gap-4">
                <div class="rounded-lg bg-blue-500/10 p-3 text-blue-600 dark:text-blue-400">
                    <UIcon name="i-lucide-boxes" class="size-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted">Total Unit Fisik Stok</p>
                    <h3 class="text-xl font-bold text-highlighted mt-0.5">{{ Number(props.summary?.total_quantity || 0).toLocaleString('id-ID') }} Unit</h3>
                </div>
            </div>

            <div class="rounded-xl border border-default bg-default p-4 shadow-xs flex items-center gap-4">
                <div class="rounded-lg bg-purple-500/10 p-3 text-purple-600 dark:text-purple-400">
                    <UIcon name="i-lucide-layers" class="size-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted">Total Entri Gudang</p>
                    <h3 class="text-xl font-bold text-highlighted mt-0.5">{{ props.summary?.total_items || 0 }} Item</h3>
                </div>
            </div>

            <div class="rounded-xl border border-default bg-default p-4 shadow-xs flex items-center gap-4">
                <div class="rounded-lg bg-amber-500/10 p-3 text-amber-600 dark:text-amber-400">
                    <UIcon name="i-lucide-alert-triangle" class="size-6" />
                </div>
                <div>
                    <p class="text-xs font-medium text-muted">Peringatan Minimum Stok</p>
                    <h3 class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-0.5">{{ props.summary?.low_stock_count || 0 }} Item Low Stock</h3>
                </div>
            </div>
        </div>

        <!-- Search & Header Filter Bar -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-xl border border-default bg-default p-4 shadow-xs">
            <div class="relative flex-1 max-w-md">
                <UIcon name="i-lucide-search" class="absolute left-3 top-2.5 size-4 text-muted" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari SKU, barcode, nama produk, gudang..."
                    class="w-full rounded-md border border-default bg-elevated/30 pl-9 pr-4 py-2 text-sm outline-none focus:border-primary focus:bg-default transition-all"
                />
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-xs font-medium text-muted">
                    <span>Gudang:</span>
                    <USelect v-model="selectedWarehouseId" :items="warehouseSelectOptions" class="w-48 text-sm" />
                </label>
            </div>
        </div>

        <!-- Main Product Stock Data Table -->
        <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
            <table class="min-w-full divide-y divide-default">
                <thead class="bg-elevated/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">SKU / Barcode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Varian Produk</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Gudang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Lokasi Spesifik</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Jumlah Stok Fisik</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Min Stock Alert</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    <tr v-for="stock in stockList" :key="stock.id" class="hover:bg-elevated/20 transition-colors">
                        <td class="px-4 py-3 text-sm">
                            <div class="font-medium text-highlighted">{{ stock.sku }}</div>
                            <div class="text-xs text-muted">{{ stock.barcode !== '-' ? stock.barcode : 'No barcode' }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-highlighted">{{ stock.variant_display_name }}</td>
                        <td class="px-4 py-3 text-sm text-highlighted font-medium">{{ stock.warehouse_name }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ stock.warehouse_location_name }}</td>
                        <td class="px-4 py-3 text-sm font-bold">
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="stock.quantity <= stock.minimum_stock ? 'bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20' : 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'"
                            >
                                <UIcon :name="stock.quantity <= stock.minimum_stock ? 'i-lucide-alert-circle' : 'i-lucide-check-circle'" class="size-3.5" />
                                {{ stock.quantity }} Pcs
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-muted">{{ stock.minimum_stock }} Pcs</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-default bg-elevated/50 text-muted hover:bg-elevated hover:text-highlighted transition-all"
                                    title="Edit / Adjust Stok"
                                    @click="openEditStockModal(stock)"
                                >
                                    <UIcon name="i-lucide-pencil" class="size-4" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-500/20 transition-all"
                                    title="Hapus Stok"
                                    @click="deleteStock(stock)"
                                >
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="stockList.length === 0">
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-muted">Belum ada data stok produk ditemukan.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MODAL TAMBAH / EDIT STOK GUDANG -->
        <div v-if="showStockModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-xl bg-default p-6 shadow-2xl border border-default space-y-5">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-highlighted">
                            {{ editingStock ? 'Edit / Adjust Stok Gudang' : 'Inisialisasi Stok Gudang Baru' }}
                        </h2>
                        <p class="text-xs text-muted">Stok awal akan otomatis dicatat sebagai barang masuk di Ledger Mutasi Stok.</p>
                    </div>
                    <button class="rounded-md p-1.5 hover:bg-elevated" type="button" @click="closeStockModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitStockForm">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Varian Produk *</span>
                        <USelect v-model="stockForm.product_variant_id" :items="variants" class="w-full" placeholder="Pilih Varian" required />
                        <span v-if="stockForm.errors.product_variant_id" class="text-xs text-red-600">{{ stockForm.errors.product_variant_id }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Gudang *</span>
                            <USelect v-model="stockForm.warehouse_id" :items="warehouses" class="w-full" placeholder="Pilih Gudang" required />
                            <span v-if="stockForm.errors.warehouse_id" class="text-xs text-red-600">{{ stockForm.errors.warehouse_id }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Lokasi Spesifik Gudang</span>
                            <USelect v-model="stockForm.warehouse_location_id" :items="filteredLocationOptions" class="w-full" placeholder="Pilih Rak / Titik Lokasi" />
                            <span v-if="stockForm.errors.warehouse_location_id" class="text-xs text-red-600">{{ stockForm.errors.warehouse_location_id }}</span>
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Jumlah Stok Fisik *</span>
                            <input
                                v-model="stockForm.quantity"
                                class="w-full rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                                type="number"
                                min="0"
                                required
                            />
                            <span v-if="stockForm.errors.quantity" class="text-xs text-red-600">{{ stockForm.errors.quantity }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Min Stock Alert *</span>
                            <input
                                v-model="stockForm.minimum_stock"
                                class="w-full rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                                type="number"
                                min="0"
                                required
                            />
                            <span v-if="stockForm.errors.minimum_stock" class="text-xs text-red-600">{{ stockForm.errors.minimum_stock }}</span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-default pt-4">
                        <button type="button" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated" @click="closeStockModal">
                            Batal
                        </button>
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" :disabled="stockForm.processing">
                            {{ editingStock ? 'Simpan Perubahan' : 'Inisialisasi Stok' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- GLOBAL DELETE CONFIRMATION MODAL -->
        <DeleteConfirmationModal
            v-model:open="showDeleteConfirmModal"
            :title="deleteConfirmData.title"
            :description="deleteConfirmData.description"
            @confirm="handleConfirmDelete"
        />
    </div>
</template>
