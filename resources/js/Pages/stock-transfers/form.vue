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

const form = useForm({
    from_store_id: source.value?.from_store_id || stores.value[0]?.value || '',
    to_store_id: source.value?.to_store_id || stores.value[1]?.value || '',
    reference_number: source.value?.reference_number || '',
    occurred_at: source.value?.occurred_at ? source.value.occurred_at.slice(0, 16) : '',
    note: source.value?.note || '',
    items: source.value?.items?.map((item) => ({
        product_variant_id: item.product_variant_id || '',
        variant_name: item.product_variant?.name || '',
        sku: item.product_variant?.sku || '',
        unit_name: 'Pcs',
        from_warehouse_id: item.from_warehouse_id || '',
        from_warehouse_location_id: item.from_warehouse_location_id || '',
        to_warehouse_id: item.to_warehouse_id || '',
        to_warehouse_location_id: item.to_warehouse_location_id || '',
        quantity: item.quantity || 1,
        unit_cost: item.unit_cost || 0,
        product_price_id: item.product_price_id || '',
    })) || [],
});

const fromWarehouses = computed(() => warehouses.value.filter((warehouse) => warehouse.store_id === form.from_store_id));
const toWarehouses = computed(() => warehouses.value.filter((warehouse) => warehouse.store_id === form.to_store_id));
const locationOptions = (warehouseId) => [{ label: 'Tanpa Lokasi', value: '' }, ...locations.value.filter((location) => location.warehouse_id === warehouseId)];
const storeOptions = computed(() => stores.value);
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
            existing.quantity = Number(existing.quantity || 0) + 1;
            return;
        }

        form.items.push({
            product_variant_id: variant.value,
            variant_name: variant.name || variant.label,
            sku: variant.sku || '',
            unit_name: variant.unit_name || 'Pcs',
            from_warehouse_id: fromWarehouses.value[0]?.value || '',
            from_warehouse_location_id: '',
            to_warehouse_id: toWarehouses.value[0]?.value || '',
            to_warehouse_location_id: '',
            quantity: 1,
            unit_cost: variant.default_purchase_price || 0,
            product_price_id: '',
        });
    });
    closeProductModal();
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const normalizeItem = (item) => ({
    ...item,
    from_warehouse_location_id: item.from_warehouse_location_id || null,
    to_warehouse_location_id: item.to_warehouse_location_id || null,
    product_price_id: item.product_price_id || null,
    quantity: Number(item.quantity || 0),
    unit_cost: Number(item.unit_cost || 0),
});

const submit = () => {
    const payload = {
        ...form.data(),
        occurred_at: form.occurred_at || null,
        items: form.items.map(normalizeItem),
    };

    if (props.mode === 'edit') {
        form.transform(() => ({ ...payload, _method: 'put' })).post(`/stock-transfers/${source.value.id}`);
        return;
    }

    form.transform(() => payload).post('/stock-transfers');
};

watch([() => form.from_store_id, () => form.to_store_id], () => {
    form.items.forEach((item) => {
        if (!fromWarehouses.value.some((warehouse) => warehouse.value === item.from_warehouse_id)) {
            item.from_warehouse_id = fromWarehouses.value[0]?.value || '';
            item.from_warehouse_location_id = '';
        }

        if (!toWarehouses.value.some((warehouse) => warehouse.value === item.to_warehouse_id)) {
            item.to_warehouse_id = toWarehouses.value[0]?.value || '';
            item.to_warehouse_location_id = '';
        }
    });
}, { immediate: true });
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 border-b border-default pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-highlighted">{{ mode === 'edit' ? 'Edit Stock Transfer' : 'Buat Stock Transfer' }}</h1>
                <p class="text-sm text-muted">Transfer stok antar gudang diposting dengan FIFO batch cost.</p>
            </div>
            <Link href="/stock-transfers" class="inline-flex items-center justify-center gap-2 rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">
                <UIcon name="i-lucide-arrow-left" class="size-4" />
                Kembali
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="grid gap-4 rounded-lg border border-default bg-default p-4 md:grid-cols-2">
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Toko Asal</span>
                    <USelect v-model="form.from_store_id" :items="storeOptions" class="w-full" />
                    <span v-if="form.errors.from_store_id" class="text-xs text-red-600">{{ form.errors.from_store_id }}</span>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Toko Tujuan</span>
                    <USelect v-model="form.to_store_id" :items="storeOptions" class="w-full" />
                    <span v-if="form.errors.to_store_id" class="text-xs text-red-600">{{ form.errors.to_store_id }}</span>
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">No Referensi</span>
                    <input v-model="form.reference_number" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" placeholder="Auto jika kosong" />
                </label>
                <label class="grid gap-1 text-sm">
                    <span class="font-medium">Tanggal</span>
                    <input v-model="form.occurred_at" type="datetime-local" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" />
                </label>
                <label class="grid gap-1 text-sm md:col-span-2">
                    <span class="font-medium">Catatan</span>
                    <input v-model="form.note" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" />
                </label>
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-highlighted">Item Transfer</h2>
                    <button type="button" class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-inverted" @click="openProductModal">
                        <UIcon name="i-lucide-package-search" class="size-4" />
                        Pilih Produk
                    </button>
                </div>

                <div v-for="(item, index) in form.items" :key="index" class="grid gap-3 rounded-lg border border-default bg-default p-4 xl:grid-cols-12">
                    <div class="xl:col-span-3">
                        <p class="text-sm font-semibold text-highlighted">{{ item.variant_name || '-' }}</p>
                        <p class="font-mono text-xs text-muted">SKU: {{ item.sku || '-' }} · {{ item.unit_name || 'Pcs' }}</p>
                    </div>
                    <label class="grid gap-1 text-sm xl:col-span-2">
                        <span class="font-medium">Gudang Asal</span>
                        <USelect v-model="item.from_warehouse_id" :items="fromWarehouses" />
                    </label>
                    <label class="grid gap-1 text-sm xl:col-span-2">
                        <span class="font-medium">Lokasi Asal</span>
                        <USelect v-model="item.from_warehouse_location_id" :items="locationOptions(item.from_warehouse_id)" />
                    </label>
                    <label class="grid gap-1 text-sm xl:col-span-2">
                        <span class="font-medium">Gudang Tujuan</span>
                        <USelect v-model="item.to_warehouse_id" :items="toWarehouses" />
                    </label>
                    <label class="grid gap-1 text-sm xl:col-span-2">
                        <span class="font-medium">Lokasi Tujuan</span>
                        <USelect v-model="item.to_warehouse_location_id" :items="locationOptions(item.to_warehouse_id)" />
                    </label>
                    <div class="flex items-end justify-end xl:col-span-1">
                        <button type="button" class="size-10 rounded-md border border-red-500/20 text-red-600 hover:bg-red-500/10" @click="removeItem(index)">
                            <UIcon name="i-lucide-trash-2" class="mx-auto size-4" />
                        </button>
                    </div>
                    <label class="grid gap-1 text-sm xl:col-span-2">
                        <span class="text-xs font-medium text-muted">Qty</span>
                        <input v-model.number="item.quantity" type="number" min="1" class="w-full rounded-md border border-default bg-default px-3 py-1.5 font-mono text-sm outline-none focus:border-primary" />
                    </label>
                    <label class="grid gap-1 text-sm xl:col-span-2">
                        <span class="text-xs font-medium text-muted">Unit Cost</span>
                        <div class="relative flex items-center">
                            <span class="absolute left-2.5 text-xs font-mono text-muted select-none">Rp</span>
                            <input v-model.number="item.unit_cost" type="number" min="0" step="any" class="w-full rounded-md border border-default bg-default py-1.5 pl-8 pr-2.5 font-mono text-sm outline-none focus:border-primary" />
                        </div>
                    </label>
                </div>
                <div v-if="form.items.length === 0" class="rounded-lg border border-dashed border-default bg-default p-8 text-center text-sm text-muted">
                    <UIcon name="i-lucide-package-search" class="mx-auto size-8 text-muted/60" />
                    <button type="button" class="mt-2 font-medium text-primary underline" @click="openProductModal">Pilih produk untuk transfer</button>
                </div>
                <p v-if="form.errors.items" class="text-sm text-red-600">{{ form.errors.items }}</p>
            </div>

            <div class="flex justify-end gap-3">
                <Link href="/stock-transfers" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">Batal</Link>
                <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" :disabled="form.processing">
                    Simpan Draft
                </button>
            </div>
        </form>

        <div v-if="productModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="flex max-h-[90vh] w-full max-w-4xl flex-col rounded-xl bg-default p-5 shadow-2xl">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-bold text-highlighted">
                            <UIcon name="i-lucide-package-search" class="size-5 text-primary" />
                            Pilih Produk & Suku Cadang
                        </h2>
                        <p class="text-xs text-muted">Pilih satu atau beberapa barang untuk dimasukkan ke dokumen transfer.</p>
                    </div>
                    <button type="button" class="rounded-md p-2 text-muted hover:bg-elevated hover:text-highlighted" @click="closeProductModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <div class="my-4">
                    <div class="relative">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input v-model="productSearch" type="search" class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" placeholder="Cari nama produk, SKU, barcode, merek..." />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto pr-1">
                    <div v-if="filteredVariants.length > 0" class="grid gap-3 sm:grid-cols-2">
                        <div v-for="variant in filteredVariants" :key="variant.id" class="relative flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition-all select-none" :class="isVariantSelected(variant.id) ? 'border-primary bg-primary/5 shadow-xs' : 'border-default bg-default hover:bg-elevated/40'" @click="toggleVariantSelection(variant.id)">
                            <div class="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-md border border-default bg-elevated/50">
                                <img v-if="variant.image_url" :src="variant.image_url" :alt="variant.name" class="size-full object-cover" />
                                <UIcon v-else name="i-lucide-package" class="size-7 text-muted/60" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="line-clamp-1 text-xs font-semibold text-highlighted">{{ variant.name }}</span>
                                    <UIcon v-if="isVariantSelected(variant.id)" name="i-lucide-check-circle-2" class="size-5 text-primary" />
                                </div>
                                <p class="mt-0.5 font-mono text-[11px] text-muted">SKU: {{ variant.sku || '-' }} | Barcode: {{ variant.barcode || '-' }}</p>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px]">
                                    <span class="rounded border border-default bg-elevated px-1.5 py-0.5 font-medium text-muted">{{ variant.category_name }}</span>
                                    <span class="rounded border border-default bg-elevated px-1.5 py-0.5 font-medium text-muted">{{ variant.brand_name }}</span>
                                    <span class="rounded bg-primary/10 px-1.5 py-0.5 font-medium text-primary">{{ variant.unit_name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="py-12 text-center text-sm text-muted">Tidak ada produk yang cocok.</div>
                </div>

                <div class="mt-4 flex items-center justify-between border-t border-default pt-3">
                    <span class="text-xs font-medium text-muted"><strong class="font-mono text-highlighted">{{ selectedModalVariantIds.length }}</strong> produk dipilih</span>
                    <div class="flex gap-2">
                        <UButton color="neutral" variant="outline" label="Batal" @click="closeProductModal" />
                        <UButton icon="i-lucide-plus" :label="`Tambahkan (${selectedModalVariantIds.length}) Produk`" :disabled="selectedModalVariantIds.length === 0" @click="confirmProductSelection" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
