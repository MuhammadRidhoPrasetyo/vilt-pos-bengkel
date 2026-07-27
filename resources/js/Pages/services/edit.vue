<script setup>
import ServiceWorkspaceLayout from '../../Layouts/ServiceWorkspaceLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: [ServiceWorkspaceLayout, { title: 'Edit SPK Servis Workspace', subtitle: 'Work Order Terminal' }],
});

const props = defineProps({
    serviceOrder: Object,
    options: Object,
    variants: Array,
});

const so = computed(() => props.serviceOrder?.data || props.serviceOrder || {});

const form = useForm({
    store_id: so.value.store_id || '',
    customer_id: so.value.customer_id || null,
    customer_name: so.value.customer_name || '',
    customer_phone: so.value.customer_phone || '',
    vehicle_id: so.value.vehicle_id || null,
    plate_number: so.value.plate_number || '',
    vehicle_brand: so.value.vehicle_brand || '',
    vehicle_model: so.value.vehicle_model || '',
    year: so.value.year || new Date().getFullYear(),
    color: so.value.color || '',
    odometer: so.value.odometer || null,
    status: so.value.status || 'checkin',
    general_complaint: so.value.general_complaint || '',
    diagnosis: so.value.diagnosis || '',
    items: (so.value.items || []).map((item) => ({
        id: item.id,
        item_type: item.item_type,
        product_variant_id: item.product_variant_id,
        description: item.description,
        quantity: item.quantity,
        unit_price: item.unit_price,
        mechanic_id: item.mechanic_id,
    })),
});

// Product Catalog Multi-Select Modal State
const productModalOpen = ref(false);
const catalogSearch = ref('');
const selectedVariantIds = ref([]);

const filteredVariants = computed(() => {
    if (!catalogSearch.value) {
        return props.variants || [];
    }
    const q = catalogSearch.value.toLowerCase();
    return (props.variants || []).filter((v) => {
        const name = (v.product?.name || '').toLowerCase();
        const sku = (v.sku || '').toLowerCase();
        const brand = (v.product?.brand?.name || '').toLowerCase();
        return name.includes(q) || sku.includes(q) || brand.includes(q);
    });
});

const toggleSelectVariant = (id) => {
    const idx = selectedVariantIds.value.indexOf(id);
    if (idx > -1) {
        selectedVariantIds.value.splice(idx, 1);
    } else {
        selectedVariantIds.value.push(id);
    }
};

const addSelectedVariantsToItems = () => {
    const list = (props.variants || []).filter((v) => selectedVariantIds.value.includes(v.id));
    list.forEach((variant) => {
        const exists = form.items.some((i) => i.product_variant_id === variant.id);
        if (!exists) {
            form.items.push({
                item_type: variant.product?.item_type || 'part',
                product_variant_id: variant.id,
                description: `${variant.product?.name || ''} - ${variant.name}`,
                quantity: 1,
                unit_price: Number(variant.price || 0),
                mechanic_id: null,
            });
        }
    });
    selectedVariantIds.value = [];
    productModalOpen.value = false;
};

const addCustomLaborItem = () => {
    form.items.push({
        item_type: 'labor',
        product_variant_id: null,
        description: 'Jasa Service Custom',
        quantity: 1,
        unit_price: 50000,
        mechanic_id: props.options?.mechanics?.[0]?.id || null,
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const estimatedTotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.unit_price || 0)), 0);
});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const submit = () => {
    form.put(`/services/${so.value.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-col gap-3">
        <!-- Main Dual Pane Layout -->
        <div class="grid flex-1 grid-cols-1 gap-3 overflow-hidden lg:grid-cols-12">
            <!-- Left Pane: Customer & Vehicle Check-in Info (5 Cols) -->
            <div class="flex flex-col overflow-y-auto rounded-xl border border-slate-800 bg-slate-900 p-4 space-y-4 lg:col-span-5">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div>
                        <h2 class="text-sm font-bold text-white flex items-center gap-2">
                            <UIcon name="i-lucide-car-front" class="size-4 text-primary-400" />
                            SPK: {{ so.number }}
                        </h2>
                        <p class="text-[11px] text-slate-400">Diperbarui pada: {{ so.created_at }}</p>
                    </div>
                    <span class="rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20 px-2.5 py-0.5 text-xs font-semibold uppercase">
                        {{ so.status }}
                    </span>
                </div>

                <!-- Cabang Toko -->
                <div class="grid gap-1">
                    <label class="text-xs font-medium text-slate-300">Cabang Bengkel <span class="text-red-400">*</span></label>
                    <select v-model="form.store_id" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white outline-none focus:border-primary-500" required>
                        <option v-for="opt in options.stores" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                </div>

                <!-- Plat Nomor & Status -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">No. Polisi / Plat <span class="text-red-400">*</span></label>
                        <input
                            v-model="form.plate_number"
                            type="text"
                            placeholder="KT 1234 AB"
                            class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-amber-300 font-mono font-bold uppercase tracking-wider outline-none focus:border-primary-500"
                            required
                        />
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">Status SPK <span class="text-red-400">*</span></label>
                        <select v-model="form.status" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white outline-none focus:border-primary-500 font-semibold">
                            <option value="checkin">Check-in</option>
                            <option value="diagnosis">Diagnosis</option>
                            <option value="in_progress">Dalam Pengerjaan</option>
                            <option value="waiting_parts">Menunggu Sparepart</option>
                            <option value="ready">Selesai (Siap Ambil)</option>
                            <option value="invoiced">Sudah Dilunasi POS</option>
                            <option value="cancelled">Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <!-- Vehicle Specs -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">Merek Kendaraan</label>
                        <input v-model="form.vehicle_brand" type="text" placeholder="Honda / Yamaha" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white outline-none focus:border-primary-500" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">Model Kendaraan</label>
                        <input v-model="form.vehicle_model" type="text" placeholder="Vario 150 / NMAX" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white outline-none focus:border-primary-500" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">KM Odometer saat Masuk</label>
                        <input v-model.number="form.odometer" type="number" placeholder="25400" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white font-mono outline-none focus:border-primary-500" />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">Tahun / Warna</label>
                        <input v-model="form.color" type="text" placeholder="2022 / Hitam Doff" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white outline-none focus:border-primary-500" />
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="grid grid-cols-2 gap-3 border-t border-slate-800 pt-3">
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">Nama Pelanggan <span class="text-red-400">*</span></label>
                        <input v-model="form.customer_name" type="text" placeholder="Nama lengkap..." class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white outline-none focus:border-primary-500" required />
                    </div>
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-slate-300">No. HP / WhatsApp</label>
                        <input v-model="form.customer_phone" type="text" placeholder="081234567890" class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs text-white font-mono outline-none focus:border-primary-500" />
                    </div>
                </div>

                <!-- Complaint & Diagnosis -->
                <div class="grid gap-1 border-t border-slate-800 pt-3">
                    <label class="text-xs font-medium text-slate-300">Keluhan Utama Pelanggan</label>
                    <textarea v-model="form.general_complaint" rows="2" placeholder="Sampaikan keluhan atau masalah kendaraan..." class="rounded-lg border border-slate-800 bg-slate-950 p-2.5 text-xs text-white outline-none focus:border-primary-500"></textarea>
                </div>

                <div class="grid gap-1">
                    <label class="text-xs font-medium text-slate-300">Hasil Diagnosis / Catatan Perbaikan</label>
                    <textarea v-model="form.diagnosis" rows="2" placeholder="Catatan analisa mekanik / tindakan..." class="rounded-lg border border-slate-800 bg-slate-950 p-2.5 text-xs text-white outline-none focus:border-primary-500"></textarea>
                </div>
            </div>

            <!-- Right Pane: Service & Part Item Basket (7 Cols) -->
            <div class="flex flex-col overflow-hidden rounded-xl border border-slate-800 bg-slate-900 lg:col-span-7">
                <!-- Basket Toolbar Header -->
                <div class="flex items-center justify-between border-b border-slate-800 p-3 bg-slate-900/50">
                    <h2 class="text-sm font-bold text-white flex items-center gap-2">
                        <UIcon name="i-lucide-wrench-screwdriver" class="size-4 text-emerald-400" />
                        Rincian Suku Cadang & Jasa Servis ({{ form.items.length }})
                    </h2>

                    <div class="flex items-center gap-2">
                        <button
                            class="inline-flex items-center gap-1 rounded-lg border border-primary-500/40 bg-primary-600/20 px-2.5 py-1.5 text-xs font-semibold text-primary-300 hover:bg-primary-600/30"
                            type="button"
                            @click="productModalOpen = true"
                        >
                            <UIcon name="i-lucide-plus" class="size-3.5" />
                            <span>Pilih Sparepart / Jasa</span>
                        </button>
                        <button
                            class="inline-flex items-center gap-1 rounded-lg border border-slate-700 bg-slate-800 px-2.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-700"
                            type="button"
                            @click="addCustomLaborItem"
                        >
                            <UIcon name="i-lucide-user-plus" class="size-3.5" />
                            <span>+ Jasa Custom</span>
                        </button>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="flex-1 overflow-y-auto p-3">
                    <div v-if="form.items.length === 0" class="flex flex-col items-center justify-center h-48 text-slate-500 border border-dashed border-slate-800 rounded-xl p-6 text-center">
                        <UIcon name="i-lucide-shopping-bag" class="size-10 text-slate-600 mb-2" />
                        <p class="text-xs font-medium">Belum ada suku cadang atau jasa ditambahkan.</p>
                        <p class="text-[11px] text-slate-600 mt-0.5">Klik tombol di atas untuk memilih barang atau menugaskan jasa mekanik.</p>
                    </div>

                    <div v-else class="space-y-2">
                        <div
                            v-for="(item, idx) in form.items"
                            :key="idx"
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950 p-3 hover:border-slate-700 transition-colors"
                        >
                            <div class="flex-1 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase font-mono border"
                                        :class="item.item_type === 'labor' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'"
                                    >
                                        {{ item.item_type === 'labor' ? 'JASA' : 'PART' }}
                                    </span>
                                    <input
                                        v-model="item.description"
                                        type="text"
                                        class="w-full bg-transparent text-xs font-medium text-white outline-none focus:border-b focus:border-primary-500"
                                        placeholder="Deskripsi pekerjaan / barang..."
                                    />
                                </div>

                                <!-- Mechanic Selection for Labor -->
                                <div v-if="item.item_type === 'labor'" class="flex items-center gap-2 pt-1">
                                    <UIcon name="i-lucide-user-check" class="size-3.5 text-amber-400 shrink-0" />
                                    <span class="text-[11px] text-slate-400">Montir:</span>
                                    <select v-model="item.mechanic_id" class="rounded border border-slate-800 bg-slate-900 px-2 py-0.5 text-xs text-slate-200 outline-none focus:border-primary-500">
                                        <option :value="null">-- Belum Ditunjuk --</option>
                                        <option v-for="m in options.mechanics" :key="m.id" :value="m.id">{{ m.name }}</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Qty & Price Inputs -->
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="flex items-center gap-1">
                                    <span class="text-[11px] text-slate-400">Qty:</span>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                        class="w-14 rounded border border-slate-800 bg-slate-900 px-2 py-1 text-xs text-center font-mono font-bold text-white outline-none focus:border-primary-500"
                                    />
                                </div>

                                <div class="flex items-center gap-1">
                                    <span class="text-[11px] text-slate-400">Rp</span>
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        min="0"
                                        step="500"
                                        class="w-24 rounded border border-slate-800 bg-slate-900 px-2 py-1 text-xs text-right font-mono font-bold text-white outline-none focus:border-primary-500"
                                    />
                                </div>

                                <div class="w-24 text-right font-mono text-xs font-bold text-emerald-400">
                                    {{ formatCurrency(Number(item.quantity || 0) * Number(item.unit_price || 0)) }}
                                </div>

                                <button
                                    class="rounded p-1 text-slate-500 hover:bg-red-500/20 hover:text-red-400"
                                    type="button"
                                    title="Hapus Baris"
                                    @click="removeItem(idx)"
                                >
                                    <UIcon name="i-lucide-x" class="size-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Summary Bar -->
                <div class="border-t border-slate-800 bg-slate-950 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs text-slate-400">Estimasi Total SPK:</p>
                            <p class="text-2xl font-black text-emerald-400 font-mono tracking-tight">
                                {{ formatCurrency(estimatedTotal) }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <UButton color="neutral" variant="outline" label="Batal" type="button" @click="router.visit('/services')" />
                            <UButton
                                type="button"
                                color="primary"
                                icon="i-lucide-check"
                                label="Update SPK Servis"
                                class="shadow-lg shadow-primary-600/30"
                                :loading="form.processing"
                                @click="submit"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Catalog Multi-Select Modal -->
        <div v-if="productModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
            <div class="w-full max-w-2xl rounded-xl border border-slate-800 bg-slate-900 p-5 shadow-2xl space-y-4 max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white flex items-center gap-2">
                        <UIcon name="i-lucide-boxes" class="size-5 text-primary-400" />
                        Pilih Suku Cadang & Jasa Katalog
                    </h3>
                    <button class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white" type="button" @click="productModalOpen = false">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <!-- Search -->
                <div class="relative">
                    <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="catalogSearch"
                        type="search"
                        placeholder="Cari nama barang, SKU, merek..."
                        class="w-full rounded-lg border border-slate-800 bg-slate-950 py-2 pl-9 pr-3 text-xs text-white outline-none focus:border-primary-500"
                    />
                </div>

                <!-- Items List -->
                <div class="flex-1 overflow-y-auto space-y-2 pr-1">
                    <div
                        v-for="v in filteredVariants"
                        :key="v.id"
                        class="flex items-center justify-between rounded-lg border p-3 cursor-pointer transition-colors"
                        :class="selectedVariantIds.includes(v.id) ? 'border-primary-500 bg-primary-600/10' : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
                        @click="toggleSelectVariant(v.id)"
                    >
                        <div class="flex items-center gap-3">
                            <input type="checkbox" :checked="selectedVariantIds.includes(v.id)" class="size-4 rounded accent-primary-500" />
                            <div>
                                <p class="text-xs font-bold text-white">{{ v.product?.name }} - {{ v.name }}</p>
                                <p class="text-[11px] text-slate-400 font-mono">SKU: {{ v.sku || '-' }} | {{ v.product?.brand?.name || 'No Brand' }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-mono font-bold text-emerald-400">{{ formatCurrency(v.price) }}</p>
                            <span class="text-[10px] rounded px-1.5 py-0.5 bg-slate-800 text-slate-300 font-mono uppercase">{{ v.product?.item_type || 'part' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="flex justify-between items-center border-t border-slate-800 pt-3">
                    <span class="text-xs text-slate-400 font-medium">{{ selectedVariantIds.length }} item dipilih</span>
                    <div class="flex gap-2">
                        <UButton color="neutral" variant="outline" label="Batal" @click="productModalOpen = false" />
                        <UButton color="primary" label="Tambahkan Ke SPK" icon="i-lucide-plus" @click="addSelectedVariantsToItems" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
