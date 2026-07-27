<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Buat Transaksi Pembelian', panelId: 'purchases' }],
});

const props = defineProps({
    options: Object,
});

const productModalOpen = ref(false);
const productSearch = ref('');
const categoryFilter = ref('');
const selectedModalVariantIds = ref([]);

const form = useForm({
    store_id: props.options?.stores?.[0]?.value || '',
    supplier_id: props.options?.suppliers?.[0]?.value || '',
    purchase_date: new Date().toISOString().substring(0, 10),
    invoice_number: '',
    discount_type: 'amount',
    discount_value: 0,
    notes: '',
    items: [],
});

const variantsList = computed(() => props.options?.variants || []);
const categoryOptions = computed(() => [
    { label: 'Semua Kategori', value: '' },
    ...(props.options?.categories || []),
]);

const filteredVariants = computed(() => {
    const searchVal = productSearch.value.trim().toLowerCase();

    return variantsList.value.filter((v) => {
        const matchesCategory = !categoryFilter.value || v.category_id === categoryFilter.value;
        const matchesSearch = !searchVal
            || (v.name && v.name.toLowerCase().includes(searchVal))
            || (v.product_name && v.product_name.toLowerCase().includes(searchVal))
            || (v.sku && v.sku.toLowerCase().includes(searchVal))
            || (v.barcode && v.barcode.toLowerCase().includes(searchVal))
            || (v.brand_name && v.brand_name.toLowerCase().includes(searchVal))
            || (v.category_name && v.category_name.toLowerCase().includes(searchVal));

        return matchesCategory && matchesSearch;
    });
});

const openProductModal = () => {
    productSearch.value = '';
    categoryFilter.value = '';
    selectedModalVariantIds.value = [];
    productModalOpen.value = true;
};

const closeProductModal = () => {
    productModalOpen.value = false;
};

const isVariantSelected = (id) => selectedModalVariantIds.value.includes(id);

const toggleVariantSelection = (id) => {
    if (isVariantSelected(id)) {
        selectedModalVariantIds.value = selectedModalVariantIds.value.filter((item) => item !== id);
    } else {
        selectedModalVariantIds.value.push(id);
    }
};

const allFilteredSelected = computed(() => {
    return filteredVariants.value.length > 0
        && filteredVariants.value.every((v) => isVariantSelected(v.id));
});

const toggleSelectAllFiltered = () => {
    const filteredIds = filteredVariants.value.map((v) => v.id);

    if (allFilteredSelected.value) {
        selectedModalVariantIds.value = selectedModalVariantIds.value.filter((id) => !filteredIds.includes(id));
    } else {
        selectedModalVariantIds.value = [...new Set([...selectedModalVariantIds.value, ...filteredIds])];
    }
};

const confirmProductSelection = () => {
    const variantsMap = new Map();
    variantsList.value.forEach((v) => variantsMap.set(v.id, v));

    selectedModalVariantIds.value.forEach((id) => {
        const variant = variantsMap.get(id);
        if (!variant) {
            return;
        }

        const existingIndex = form.items.findIndex((item) => item.product_variant_id === variant.id);
        if (existingIndex !== -1) {
            form.items[existingIndex].quantity_ordered += 1;
        } else {
            form.items.push({
                product_variant_id: variant.id,
                variant_name: variant.name,
                sku: variant.sku,
                unit_name: variant.unit_name || 'Pcs',
                price_type: 'toko',
                quantity_ordered: 1,
                unit_purchase_price: variant.default_purchase_price || 0,
                item_discount_type: 'amount',
                item_discount_value: 0,
            });
        }
    });

    closeProductModal();
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const calculateLineSubtotal = (item) => {
    const qty = Number(item.quantity_ordered) || 0;
    const price = Number(item.unit_purchase_price) || 0;
    return qty * price;
};

const calculateLineDiscount = (item) => {
    const subtotal = calculateLineSubtotal(item);
    const discVal = Number(item.item_discount_value) || 0;
    if (item.item_discount_type === 'percent') {
        return subtotal * (discVal / 100);
    }
    return discVal;
};

const calculateLineTotal = (item) => {
    return Math.max(0, calculateLineSubtotal(item) - calculateLineDiscount(item));
};

const itemsNetSubtotal = computed(() => {
    return form.items.reduce((acc, item) => acc + calculateLineTotal(item), 0);
});

const headerDiscountAmount = computed(() => {
    const discVal = Number(form.discount_value) || 0;
    if (form.discount_type === 'percent') {
        return itemsNetSubtotal.value * (discVal / 100);
    }
    return discVal;
});

const grandTotal = computed(() => {
    return Math.max(0, itemsNetSubtotal.value - headerDiscountAmount.value);
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const submit = () => {
    if (form.items.length === 0) {
        alert('Harap tambahkan minimal 1 item produk.');
        return;
    }

    form.post('/purchases');
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-highlighted">Buat Transaksi Pembelian Baru</h1>
                <p class="text-xs text-muted">Input transaksi stok masuk dari supplier dan pencatatan kas keluar otomatis.</p>
            </div>
            <div class="flex gap-2">
                <UButton color="neutral" variant="outline" label="Batal" @click="router.visit('/purchases')" />
                <UButton type="submit" label="Simpan Transaksi" icon="i-lucide-check" :loading="form.processing" />
            </div>
        </div>

        <!-- Header Info Card -->
        <UCard :ui="{ body: 'p-4 space-y-4' }">
            <h2 class="text-sm font-semibold text-highlighted">Informasi Header Pembelian</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="grid gap-1 text-xs">
                    <span class="font-medium text-highlighted">Cabang Toko / Bengkel <span class="text-red-500">*</span></span>
                    <select v-model="form.store_id" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required>
                        <option v-for="opt in options.stores" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <span v-if="form.errors.store_id" class="text-red-500">{{ form.errors.store_id }}</span>
                </label>

                <label class="grid gap-1 text-xs">
                    <span class="font-medium text-highlighted">Supplier <span class="text-red-500">*</span></span>
                    <select v-model="form.supplier_id" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required>
                        <option v-for="opt in options.suppliers" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <span v-if="form.errors.supplier_id" class="text-red-500">{{ form.errors.supplier_id }}</span>
                </label>

                <label class="grid gap-1 text-xs">
                    <span class="font-medium text-highlighted">Tanggal Pembelian <span class="text-red-500">*</span></span>
                    <input v-model="form.purchase_date" type="date" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required />
                    <span v-if="form.errors.purchase_date" class="text-red-500">{{ form.errors.purchase_date }}</span>
                </label>

                <label class="grid gap-1 text-xs">
                    <span class="font-medium text-highlighted">No. Invoice Supplier (Opsional)</span>
                    <input v-model="form.invoice_number" type="text" placeholder="Contoh: INV-SUP/2026/001" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" />
                </label>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-xs">
                    <span class="font-medium text-highlighted">Catatan Nota</span>
                    <input v-model="form.notes" type="text" placeholder="Catatan transaksi..." class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" />
                </label>

                <div class="grid grid-cols-2 gap-2">
                    <label class="grid gap-1 text-xs">
                        <span class="font-medium text-highlighted">Tipe Diskon Nota</span>
                        <select v-model="form.discount_type" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary">
                            <option value="amount">Nominal (Rp)</option>
                            <option value="percent">Persentase (%)</option>
                        </select>
                    </label>

                    <label class="grid gap-1 text-xs">
                        <span class="font-medium text-highlighted">Nilai Diskon Nota</span>
                        <input v-model.number="form.discount_value" type="number" step="any" min="0" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" />
                    </label>
                </div>
            </div>
        </UCard>

        <!-- Items Table Card -->
        <UCard :ui="{ body: 'p-4 space-y-4' }">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-highlighted">Detail Item Pembelian & Restok</h2>
                    <p class="text-xs text-muted">Buka katalog produk untuk memilih barang yang dibeli secara multi-select.</p>
                </div>

                <UButton
                    icon="i-lucide-package-search"
                    label="Pilih Produk & Suku Cadang"
                    type="button"
                    class="w-full sm:w-auto"
                    @click="openProductModal"
                />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-xs">
                    <thead>
                        <tr class="border-b border-default bg-elevated/50 text-muted">
                            <th class="p-2.5 font-medium">Varian Produk</th>
                            <th class="p-2.5 font-medium w-32">Tipe Harga</th>
                            <th class="p-2.5 font-medium w-28">Qty</th>
                            <th class="p-2.5 font-medium w-36">Harga Beli Unit</th>
                            <th class="p-2.5 font-medium w-40">Diskon Item</th>
                            <th class="p-2.5 font-medium w-36 text-right">Total Line</th>
                            <th class="p-2.5 font-medium w-12 text-center">Hapus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-elevated/20">
                            <td class="p-2.5 font-medium text-highlighted">
                                {{ item.variant_name }}
                                <span class="block text-[11px] font-mono text-muted">SKU: {{ item.sku || '-' }} ({{ item.unit_name }})</span>
                            </td>
                            <td class="p-2.5">
                                <select v-model="item.price_type" class="w-full rounded border border-default bg-default p-1.5 outline-none">
                                    <option value="toko">Toko</option>
                                    <option value="distributor">Distributor</option>
                                </select>
                            </td>
                            <td class="p-2.5">
                                <input v-model.number="item.quantity_ordered" type="number" min="1" class="w-full rounded border border-default bg-default p-1.5 outline-none font-mono" />
                            </td>
                            <td class="p-2.5">
                                <input v-model.number="item.unit_purchase_price" type="number" step="any" min="0" class="w-full rounded border border-default bg-default p-1.5 outline-none font-mono" />
                            </td>
                            <td class="p-2.5">
                                <div class="flex gap-1">
                                    <select v-model="item.item_discount_type" class="w-16 rounded border border-default bg-default p-1 outline-none text-[11px]">
                                        <option value="amount">Rp</option>
                                        <option value="percent">%</option>
                                    </select>
                                    <input v-model.number="item.item_discount_value" type="number" step="any" min="0" class="w-full rounded border border-default bg-default p-1.5 outline-none font-mono" />
                                </div>
                            </td>
                            <td class="p-2.5 text-right font-semibold font-mono text-highlighted">
                                {{ formatCurrency(calculateLineTotal(item)) }}
                            </td>
                            <td class="p-2.5 text-center">
                                <button type="button" class="text-error hover:text-red-700" @click="removeItem(index)">
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="form.items.length === 0">
                            <td colspan="7" class="p-8 text-center text-muted">
                                <UIcon name="i-lucide-shopping-bag" class="mx-auto size-8 text-muted/60" />
                                <p class="mt-2 text-xs">Belum ada barang dalam daftar pembelian.</p>
                                <button type="button" class="mt-2 text-xs text-primary underline font-medium" @click="openProductModal">
                                    Klik di sini untuk memilih produk
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Calculation Summary Footer -->
            <div class="flex flex-col gap-4 border-t border-default pt-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="rounded-lg bg-elevated/40 p-3 text-xs text-muted max-w-md">
                    <p class="font-medium text-highlighted flex items-center gap-1">
                        <UIcon name="i-lucide-info" class="size-4 text-primary" /> Integrasi Otomatis
                    </p>
                    <p class="mt-1">
                        Menyimpan transaksi ini akan otomatis memutakhirkan <strong>Stok Produk</strong>, mencatat <strong>Batch HPP FIFO</strong>, serta membukukan <strong>Pengeluaran Kas</strong> di kategori <em>Pembelian Sparepart & Stok</em>.
                    </p>
                </div>

                <div class="space-y-1.5 text-right text-xs">
                    <div class="flex justify-between gap-6">
                        <span class="text-muted">Subtotal Item:</span>
                        <span class="font-mono font-medium">{{ formatCurrency(itemsNetSubtotal) }}</span>
                    </div>
                    <div class="flex justify-between gap-6">
                        <span class="text-muted">Diskon Nota:</span>
                        <span class="font-mono font-medium text-amber-600">- {{ formatCurrency(headerDiscountAmount) }}</span>
                    </div>
                    <div class="flex justify-between gap-6 border-t border-default pt-2 text-sm font-bold text-highlighted">
                        <span>GRAND TOTAL:</span>
                        <span class="font-mono text-primary text-base">{{ formatCurrency(grandTotal) }}</span>
                    </div>
                </div>
            </div>
        </UCard>

        <!-- Product Selector Modal -->
        <div v-if="productModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-4xl rounded-xl bg-default p-5 shadow-2xl flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-highlighted flex items-center gap-2">
                            <UIcon name="i-lucide-package-search" class="size-5 text-primary" /> Pilih Produk & Suku Cadang
                        </h2>
                        <p class="text-xs text-muted">Pilih satu atau beberapa barang sekaligus untuk dimasukkan ke daftar transaksi pembelian.</p>
                    </div>
                    <button class="rounded-md p-2 hover:bg-elevated text-muted hover:text-highlighted" type="button" @click="closeProductModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <!-- Modal Filters Bar -->
                <div class="my-4 flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input
                            v-model="productSearch"
                            type="search"
                            class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary"
                            placeholder="Cari nama produk, SKU, barcode, merek..."
                        />
                    </div>
                    <USelect
                        v-model="categoryFilter"
                        :items="categoryOptions"
                        class="sm:w-52"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />
                    <button
                        type="button"
                        class="rounded-md border border-default px-3 py-2 text-xs font-medium hover:bg-elevated transition-colors"
                        @click="toggleSelectAllFiltered"
                    >
                        {{ allFilteredSelected ? 'Batalkan Semua' : 'Pilih Semua Hasil' }}
                    </button>
                </div>

                <!-- Modal Product Cards Grid -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div v-if="filteredVariants.length > 0" class="grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="variant in filteredVariants"
                            :key="variant.id"
                            class="relative flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all select-none"
                            :class="isVariantSelected(variant.id) ? 'border-primary bg-primary/5 shadow-xs' : 'border-default bg-default hover:border-default/80 hover:bg-elevated/40'"
                            @click="toggleVariantSelection(variant.id)"
                        >
                            <!-- Thumbnail Image -->
                            <div class="size-16 shrink-0 rounded-md border border-default bg-elevated/50 flex items-center justify-center overflow-hidden">
                                <img v-if="variant.image_url" :src="variant.image_url" :alt="variant.name" class="size-full object-cover" />
                                <UIcon v-else name="i-lucide-package" class="size-7 text-muted/60" />
                            </div>

                            <!-- Variant Details -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-semibold text-xs text-highlighted line-clamp-1">{{ variant.name }}</span>
                                    <span v-if="isVariantSelected(variant.id)" class="text-primary font-bold">
                                        <UIcon name="i-lucide-check-circle-2" class="size-5" />
                                    </span>
                                </div>

                                <p class="text-[11px] text-muted font-mono mt-0.5">SKU: {{ variant.sku || '-' }} | Barcode: {{ variant.barcode || '-' }}</p>

                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px]">
                                    <span class="rounded bg-elevated px-1.5 py-0.5 text-muted font-medium border border-default">{{ variant.category_name }}</span>
                                    <span class="rounded bg-elevated px-1.5 py-0.5 text-muted font-medium border border-default">{{ variant.brand_name }}</span>
                                    <span class="rounded bg-primary/10 px-1.5 py-0.5 text-primary font-medium">{{ variant.unit_name }}</span>
                                </div>

                                <p class="mt-2 font-mono text-xs font-bold text-primary">
                                    Harga Default: {{ formatCurrency(variant.default_purchase_price) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center">
                        <UIcon name="i-lucide-package-x" class="mx-auto size-10 text-muted/50" />
                        <p class="mt-2 text-sm text-muted">Tidak ada produk yang cocok dengan kriteria pencarian.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-4 flex items-center justify-between border-t border-default pt-3">
                    <span class="text-xs font-medium text-muted">
                        <strong class="text-highlighted font-mono">{{ selectedModalVariantIds.length }}</strong> produk dipilih
                    </span>

                    <div class="flex gap-2">
                        <UButton color="neutral" variant="outline" label="Batal" @click="closeProductModal" />
                        <UButton
                            icon="i-lucide-plus"
                            :label="`Tambahkan (${selectedModalVariantIds.length}) Produk`"
                            :disabled="selectedModalVariantIds.length === 0"
                            @click="confirmProductSelection"
                        />
                    </div>
                </div>
            </div>
        </div>
    </form>
</template>
