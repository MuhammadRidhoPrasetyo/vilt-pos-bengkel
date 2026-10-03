<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    record: { type: Object, default: null },
    options: { type: Object, required: true },
    mode: { type: String, default: 'create' },
});

const source = computed(() => props.record?.data || props.record || {});
const stores = computed(() => props.options?.stores || []);
const warehouses = computed(() => props.options?.warehouses || []);
const locations = computed(() => props.options?.warehouseLocations || []);
const variants = computed(() => props.options?.variants || []);

const productModalOpen = ref(false);
const productSearch = ref('');
const selectedModalVariantIds = ref([]);
const isLoadingWarehouseStock = ref(false);

const initialItems = source.value?.items?.map((item) => ({
    product_variant_id: item.product_variant_id || item.product_variant?.id || '',
    variant_name: item.product_variant?.name || '',
    sku: item.product_variant?.sku || '',
    unit_name: item.product_variant?.unit_name || 'Pcs',
    warehouse_location_id: item.warehouse_location_id || '',
    system_quantity: item.system_quantity ?? 0,
    physical_quantity: item.physical_quantity ?? 0,
    unit_cost: item.unit_cost ?? 0,
    note: item.note || '',
})) || [];

const form = useForm({
    store_id: source.value?.store_id || stores.value[0]?.value || '',
    warehouse_id: source.value?.warehouse_id || '',
    opname_number: source.value?.opname_number || '',
    occurred_at: source.value?.occurred_at ? source.value.occurred_at.slice(0, 16) : new Date().toISOString().slice(0, 16),
    notes: source.value?.notes || '',
    items: initialItems,
});

const warehouseOptions = computed(() => {
    if (!form.store_id) {
        return warehouses.value;
    }
    return warehouses.value.filter((warehouse) => warehouse.store_id === form.store_id);
});

// Auto-select first warehouse if not set
watch(warehouseOptions, (newOptions) => {
    if (newOptions.length > 0 && (!form.warehouse_id || !newOptions.some((w) => w.value === form.warehouse_id))) {
        form.warehouse_id = newOptions[0].value;
    }
}, { immediate: true });

const locationOptions = (warehouseId) => [
    { label: 'Tanpa Rak/Lokasi', value: '' },
    ...locations.value.filter((loc) => loc.warehouse_id === warehouseId),
];

const filteredVariants = computed(() => {
    const searchValue = productSearch.value.trim().toLowerCase();

    return variants.value.filter((variant) => {
        return !searchValue
            || variant.name?.toLowerCase().includes(searchValue)
            || variant.product_name?.toLowerCase().includes(searchValue)
            || variant.sku?.toLowerCase().includes(searchValue)
            || variant.barcode?.toLowerCase().includes(searchValue)
            || variant.brand_name?.toLowerCase().includes(searchValue)
            || variant.category_name?.toLowerCase().includes(searchValue);
    });
});

const openProductModal = () => {
    productSearch.value = '';
    selectedModalVariantIds.value = [];
    productModalOpen.value = true;
};

const closeProductModal = () => {
    productModalOpen.value = false;
};

const isVariantSelected = (id) => selectedModalVariantIds.value.includes(id);

const toggleVariantSelection = (id) => {
    selectedModalVariantIds.value = isVariantSelected(id)
        ? selectedModalVariantIds.value.filter((item) => item !== id)
        : [...selectedModalVariantIds.value, id];
};

const confirmProductSelection = () => {
    selectedModalVariantIds.value.forEach((id) => {
        const variant = variants.value.find((candidate) => candidate.id === id || candidate.value === id);
        if (!variant) {
            return;
        }

        const existing = form.items.find((item) => item.product_variant_id === variant.value);
        if (existing) {
            return;
        }

        form.items.push({
            product_variant_id: variant.value,
            variant_name: variant.name || variant.label,
            sku: variant.sku || '',
            unit_name: variant.unit_name || 'Pcs',
            warehouse_location_id: '',
            system_quantity: 0,
            physical_quantity: 0,
            unit_cost: variant.default_purchase_price || 0,
            note: '',
        });
    });
    closeProductModal();
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Fetch warehouse stock automatically to populate counting sheet
const loadWarehouseStock = async () => {
    if (!form.warehouse_id) {
        alert('Silakan pilih gudang terlebih dahulu.');
        return;
    }

    if (form.items.length > 0 && !confirm('Memuat stok gudang akan mengganti daftar item saat ini. Lanjutkan?')) {
        return;
    }

    isLoadingWarehouseStock.value = true;
    try {
        const response = await fetch(`/stock-opnames/warehouses/${form.warehouse_id}/stock`);
        const data = await response.json();
        if (data.items && Array.isArray(data.items)) {
            form.items = data.items.map((item) => ({
                product_variant_id: item.product_variant_id,
                variant_name: item.variant_name,
                sku: item.sku,
                unit_name: item.unit_name,
                warehouse_location_id: item.warehouse_location_id || '',
                system_quantity: item.system_quantity,
                physical_quantity: item.physical_quantity,
                unit_cost: item.unit_cost,
                note: '',
            }));
        }
    } catch (err) {
        console.error('Failed to load warehouse stock:', err);
        alert('Gagal memuat stok barang dari gudang.');
    } finally {
        isLoadingWarehouseStock.value = false;
    }
};

const totalSystemQty = computed(() => form.items.reduce((sum, item) => sum + (Number(item.system_quantity) || 0), 0));
const totalPhysicalQty = computed(() => form.items.reduce((sum, item) => sum + (Number(item.physical_quantity) || 0), 0));
const totalDifferenceQty = computed(() => totalPhysicalQty.value - totalSystemQty.value);
const totalDifferenceValue = computed(() => {
    return form.items.reduce((sum, item) => {
        const diff = (Number(item.physical_quantity) || 0) - (Number(item.system_quantity) || 0);
        return sum + (diff * (Number(item.unit_cost) || 0));
    }, 0);
});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const submit = () => {
    if (form.items.length === 0) {
        alert('Daftar item opname tidak boleh kosong.');
        return;
    }

    const payload = {
        store_id: form.store_id,
        warehouse_id: form.warehouse_id,
        opname_number: form.opname_number || null,
        occurred_at: form.occurred_at ? form.occurred_at.replace('T', ' ') : null,
        notes: form.notes || null,
        items: form.items.map((item) => ({
            product_variant_id: item.product_variant_id,
            warehouse_location_id: item.warehouse_location_id || null,
            system_quantity: Number(item.system_quantity) || 0,
            physical_quantity: Number(item.physical_quantity) || 0,
            unit_cost: Number(item.unit_cost) || 0,
            note: item.note || null,
        })),
    };

    if (props.mode === 'edit') {
        form.transform(() => ({ ...payload, _method: 'put' })).post(`/stock-opnames/${source.value.id}`);
        return;
    }

    form.transform(() => payload).post('/stock-opnames');
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 border-b border-default pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-highlighted">{{ mode === 'edit' ? 'Edit Hitungan Stock Opname' : 'Mulai Sesi Stock Opname Baru' }}</h1>
                <p class="text-sm text-muted">Input hasil hitungan fisik gudang dan verifikasi selisih dengan stok sistem.</p>
            </div>
            <Link href="/stock-opnames" class="inline-flex items-center justify-center gap-2 rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">
                <UIcon name="i-lucide-arrow-left" class="size-4" />
                Kembali
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <!-- Header Data Form -->
            <div class="grid gap-4 rounded-lg border border-default bg-default p-4 md:grid-cols-2 lg:grid-cols-4">
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Toko</span>
                    <USelect v-model="form.store_id" :items="stores" class="w-full" :disabled="mode === 'edit'" />
                    <span v-if="form.errors.store_id" class="text-xs text-red-600">{{ form.errors.store_id }}</span>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Gudang Diopname</span>
                    <USelect v-model="form.warehouse_id" :items="warehouseOptions" class="w-full" :disabled="mode === 'edit'" />
                    <span v-if="form.errors.warehouse_id" class="text-xs text-red-600">{{ form.errors.warehouse_id }}</span>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Tanggal Pelaksanaan</span>
                    <input v-model="form.occurred_at" type="datetime-local" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" />
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Catatan Sesi Opname</span>
                    <input v-model="form.notes" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" placeholder="Contoh: Opname Akhir Bulan / Triwulan" />
                </label>
            </div>

            <!-- Action Buttons for Items -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-elevated/30 p-4 rounded-lg border border-default">
                <div>
                    <h2 class="text-base font-semibold text-highlighted">Daftar Item Fisik</h2>
                    <p class="text-xs text-muted">Isi kolom <strong class="text-highlighted">Qty Fisik</strong> sesuai hasil hitung di lapangan. Selisih akan terhitung otomatis.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="mode === 'create'"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md border border-default bg-default px-3 py-2 text-sm font-medium hover:bg-elevated transition-colors"
                        :disabled="isLoadingWarehouseStock"
                        @click="loadWarehouseStock"
                    >
                        <UIcon :name="isLoadingWarehouseStock ? 'i-lucide-loader-2' : 'i-lucide-boxes'" class="size-4" :class="{ 'animate-spin': isLoadingWarehouseStock }" />
                        Tarik Stok Gudang Otomatis
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-inverted hover:bg-primary/90 transition-colors"
                        @click="openProductModal"
                    >
                        <UIcon name="i-lucide-plus" class="size-4" />
                        Tambah Item Manual
                    </button>
                </div>
            </div>

            <!-- Table of Items -->
            <div class="overflow-x-auto rounded-lg border border-default bg-default">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Produk & SKU</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Rak / Lokasi</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Qty Sistem</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted w-32">Qty Fisik</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Selisih</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">HPP (Modal)</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">Nilai Selisih</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Keterangan</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-elevated/20">
                            <td class="px-4 py-2.5 text-xs text-muted">{{ index + 1 }}</td>
                            <td class="px-4 py-2.5">
                                <p class="text-sm font-semibold text-highlighted">{{ item.variant_name || '-' }}</p>
                                <p class="font-mono text-xs text-muted">SKU: {{ item.sku || '-' }} · {{ item.unit_name || 'Pcs' }}</p>
                            </td>
                            <td class="px-4 py-2.5">
                                <USelect v-model="item.warehouse_location_id" :items="locationOptions(form.warehouse_id)" class="w-40" />
                            </td>
                            <td class="px-4 py-2.5 text-center font-mono text-sm font-medium text-muted">
                                {{ item.system_quantity }}
                            </td>
                            <td class="px-4 py-2.5">
                                <input
                                    v-model.number="item.physical_quantity"
                                    type="number"
                                    min="0"
                                    class="w-full rounded-md border border-default bg-elevated/40 px-3 py-1.5 text-center font-mono text-sm font-semibold outline-none focus:border-primary focus:bg-default"
                                />
                            </td>
                            <td class="px-4 py-2.5 text-center font-mono text-sm font-semibold">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs"
                                    :class="(item.physical_quantity - item.system_quantity) < 0 ? 'bg-red-500/10 text-red-600 dark:text-red-400' : (item.physical_quantity - item.system_quantity) > 0 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-zinc-500/10 text-zinc-600'"
                                >
                                    {{ (item.physical_quantity - item.system_quantity) > 0 ? '+' : '' }}{{ item.physical_quantity - item.system_quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs text-muted">
                                {{ formatRupiah(item.unit_cost) }}
                            </td>
                            <td class="px-4 py-2.5 text-right font-mono text-sm font-semibold" :class="((item.physical_quantity - item.system_quantity) * item.unit_cost) < 0 ? 'text-red-500' : ((item.physical_quantity - item.system_quantity) * item.unit_cost) > 0 ? 'text-emerald-500' : 'text-muted'">
                                {{ formatRupiah((item.physical_quantity - item.system_quantity) * item.unit_cost) }}
                            </td>
                            <td class="px-4 py-2.5">
                                <input
                                    v-model="item.note"
                                    class="w-full rounded-md border border-default bg-default px-2.5 py-1 text-xs outline-none focus:border-primary"
                                    placeholder="Alasan selisih..."
                                />
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <button
                                    type="button"
                                    class="rounded p-1 text-red-600 hover:bg-red-500/10 transition-colors"
                                    title="Hapus baris"
                                    @click="removeItem(index)"
                                >
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="form.items.length === 0">
                            <td colspan="10" class="p-8 text-center text-sm text-muted">
                                Belum ada item yang ditambahkan. Klik tombol <strong>"Tarik Stok Gudang Otomatis"</strong> atau <strong>"Tambah Item Manual"</strong>.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Summary Bar -->
            <div class="grid gap-4 rounded-lg border border-default bg-elevated/40 p-4 sm:grid-cols-4">
                <div>
                    <span class="text-xs uppercase text-muted">Total Item Terdaftar</span>
                    <p class="text-xl font-bold text-highlighted">{{ form.items.length }} SKU</p>
                </div>
                <div>
                    <span class="text-xs uppercase text-muted">Total Qty (Sistem vs Fisik)</span>
                    <p class="text-xl font-bold text-highlighted">{{ totalSystemQty }} vs {{ totalPhysicalQty }}</p>
                </div>
                <div>
                    <span class="text-xs uppercase text-muted">Total Selisih Kuantitas</span>
                    <p class="text-xl font-bold" :class="totalDifferenceQty < 0 ? 'text-red-500' : totalDifferenceQty > 0 ? 'text-emerald-500' : 'text-muted'">
                        {{ totalDifferenceQty > 0 ? '+' : '' }}{{ totalDifferenceQty }} Pcs
                    </p>
                </div>
                <div>
                    <span class="text-xs uppercase text-muted">Total Valuasi Selisih</span>
                    <p class="text-xl font-bold" :class="totalDifferenceValue < 0 ? 'text-red-500' : totalDifferenceValue > 0 ? 'text-emerald-500' : 'text-muted'">
                        {{ formatRupiah(totalDifferenceValue) }}
                    </p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Link href="/stock-opnames" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">Batal</Link>
                <button
                    type="submit"
                    class="rounded-md bg-primary px-5 py-2 text-sm font-semibold text-inverted hover:bg-primary/90 transition-colors"
                    :disabled="form.processing || form.items.length === 0"
                >
                    {{ form.processing ? 'Menyimpan...' : (mode === 'edit' ? 'Perbarui Draft Opname' : 'Simpan Draft Opname') }}
                </button>
            </div>
        </form>

        <!-- Product Selector Modal -->
        <UModal v-model:open="productModalOpen" title="Pilih Produk untuk Ditambahkan">
            <template #content>
                <div class="space-y-4">
                    <div class="relative">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-2.5 size-4 text-muted" />
                        <input v-model="productSearch" class="w-full rounded-md border border-default bg-elevated/40 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" placeholder="Cari nama, SKU, atau kategori produk..." />
                    </div>

                    <div class="max-h-80 overflow-y-auto divide-y divide-default rounded-md border border-default">
                        <div
                            v-for="variant in filteredVariants"
                            :key="variant.id"
                            class="flex items-center justify-between p-3 hover:bg-elevated/40 cursor-pointer"
                            @click="toggleVariantSelection(variant.id)"
                        >
                            <div class="space-y-0.5">
                                <p class="text-sm font-semibold text-highlighted">{{ variant.name }}</p>
                                <p class="text-xs text-muted">SKU: {{ variant.sku }} · Kategori: {{ variant.category_name }}</p>
                            </div>
                            <input type="checkbox" :checked="isVariantSelected(variant.id)" class="size-4 rounded text-primary" />
                        </div>
                        <div v-if="filteredVariants.length === 0" class="p-6 text-center text-sm text-muted">
                            Tidak ada produk yang cocok dengan pencarian.
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated" @click="closeProductModal">Tutup</button>
                        <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" @click="confirmProductSelection">
                            Tambahkan ({{ selectedModalVariantIds.length }})
                        </button>
                    </div>
                </div>
            </template>
        </UModal>
    </div>
</template>
