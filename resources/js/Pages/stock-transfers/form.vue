<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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

const blankItem = () => ({
    product_variant_id: variants.value[0]?.value || '',
    from_warehouse_id: '',
    from_warehouse_location_id: '',
    to_warehouse_id: '',
    to_warehouse_location_id: '',
    quantity: 1,
    unit_cost: 0,
    product_price_id: '',
});

const form = useForm({
    from_store_id: source.value?.from_store_id || stores.value[0]?.value || '',
    to_store_id: source.value?.to_store_id || stores.value[1]?.value || '',
    reference_number: source.value?.reference_number || '',
    occurred_at: source.value?.occurred_at ? source.value.occurred_at.slice(0, 16) : '',
    note: source.value?.note || '',
    items: source.value?.items?.map((item) => ({
        product_variant_id: item.product_variant_id || '',
        from_warehouse_id: item.from_warehouse_id || '',
        from_warehouse_location_id: item.from_warehouse_location_id || '',
        to_warehouse_id: item.to_warehouse_id || '',
        to_warehouse_location_id: item.to_warehouse_location_id || '',
        quantity: item.quantity || 1,
        unit_cost: item.unit_cost || 0,
        product_price_id: item.product_price_id || '',
    })) || [blankItem()],
});

const fromWarehouses = computed(() => warehouses.value.filter((warehouse) => warehouse.store_id === form.from_store_id));
const toWarehouses = computed(() => warehouses.value.filter((warehouse) => warehouse.store_id === form.to_store_id));
const locationOptions = (warehouseId) => [{ label: 'Tanpa Lokasi', value: '' }, ...locations.value.filter((location) => location.warehouse_id === warehouseId)];
const storeOptions = computed(() => stores.value);

const applyVariantCost = (item) => {
    const variant = variants.value.find((candidate) => candidate.value === item.product_variant_id);
    if (variant && !Number(item.unit_cost)) {
        item.unit_cost = variant.default_purchase_price || 0;
    }
};

const addItem = () => form.items.push(blankItem());
const removeItem = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
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
                    <button type="button" class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-inverted" @click="addItem">
                        <UIcon name="i-lucide-plus" class="size-4" />
                        Tambah Item
                    </button>
                </div>

                <div v-for="(item, index) in form.items" :key="index" class="grid gap-3 rounded-lg border border-default bg-default p-4 xl:grid-cols-12">
                    <label class="grid gap-1 text-sm xl:col-span-3">
                        <span class="font-medium">Varian</span>
                        <USelect v-model="item.product_variant_id" :items="variants" @update:model-value="applyVariantCost(item)" />
                    </label>
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
                        <span class="font-medium">Qty</span>
                        <input v-model="item.quantity" type="number" min="1" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" />
                    </label>
                    <label class="grid gap-1 text-sm xl:col-span-2">
                        <span class="font-medium">Unit Cost</span>
                        <input v-model="item.unit_cost" type="number" min="0" step="0.01" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" />
                    </label>
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
    </div>
</template>
