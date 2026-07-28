<script setup>
import ServiceWorkspaceLayout from '../../Layouts/ServiceWorkspaceLayout.vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: [ServiceWorkspaceLayout, { title: 'Buat SPK Servis Baru', subtitle: 'Work Order Workspace' }],
});

const props = defineProps({
    options: Object,
    variants: Array,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isOwner = computed(() => !currentUser.value.store_id || currentUser.value.roles?.includes('owner'));

const activeMobileTab = ref('unit'); // 'unit' | 'items'

const form = useForm({
    store_id: currentUser.value.store_id || props.options?.stores?.[0]?.value || '',
    customer_id: null,
    customer_name: '',
    customer_phone: '',
    vehicle_id: null,
    plate_number: '',
    vehicle_brand: '',
    vehicle_model: '',
    year: new Date().getFullYear(),
    color: '',
    odometer: null,
    status: 'checkin',
    general_complaint: '',
    diagnosis: '',
    items: [],
});

// Product Catalog Multi-Select Modal State
const productModalOpen = ref(false);
const catalogSearch = ref('');
const categoryFilter = ref('');
const selectedVariantIds = ref([]);

const openProductModal = () => {
    catalogSearch.value = '';
    categoryFilter.value = '';
    selectedVariantIds.value = [];
    productModalOpen.value = true;
};

const closeProductModal = () => {
    productModalOpen.value = false;
};

const variantsList = computed(() => {
    if (Array.isArray(props.variants)) {
        return props.variants;
    }
    if (Array.isArray(props.variants?.data)) {
        return props.variants.data;
    }
    return [];
});

const derivedCategories = computed(() => {
    if (props.options?.categories?.length) {
        return props.options.categories;
    }
    const map = new Map();
    variantsList.value.forEach((v) => {
        const catId = v.product?.category_id || v.category_id;
        const catName = v.product?.category_name || v.product?.category?.name || v.category_name;
        if (catId && catName && !map.has(catId)) {
            map.set(catId, { label: catName, value: catId });
        }
    });
    return Array.from(map.values());
});

const categoryOptions = computed(() => [
    { label: 'Semua Kategori', value: '' },
    ...derivedCategories.value,
]);

const getVariantName = (v) => {
    const vName = v.display_receipt_name || v.name || v.name_suffix || '';
    const pName = v.product?.name || v.product_name || '';
    if (pName && vName && !vName.toLowerCase().includes(pName.toLowerCase())) {
        return `${pName} - ${vName}`;
    }
    return vName || pName || 'Varian Produk';
};

const getVariantImageUrl = (v) => {
    return v.image_url
        || v.images?.[0]?.thumb_url
        || v.images?.[0]?.url
        || v.product?.images?.[0]?.thumb_url
        || v.product?.images?.[0]?.url
        || null;
};

const filteredVariants = computed(() => {
    const searchVal = catalogSearch.value.trim().toLowerCase();
    const catVal = categoryFilter.value;

    return variantsList.value.filter((v) => {
        const vCatId = v.product?.category_id || v.category_id || v.product?.category?.id;
        const matchesCategory = !catVal || vCatId === catVal;

        const name = getVariantName(v).toLowerCase();
        const pName = (v.product?.name || v.product_name || '').toLowerCase();
        const sku = (v.sku || '').toLowerCase();
        const barcode = (v.barcode || '').toLowerCase();
        const brand = (v.product?.brand_name || v.product?.brand?.name || v.brand_name || '').toLowerCase();
        const category = (v.product?.category_name || v.product?.category?.name || v.category_name || '').toLowerCase();

        const matchesSearch = !searchVal
            || name.includes(searchVal)
            || pName.includes(searchVal)
            || sku.includes(searchVal)
            || barcode.includes(searchVal)
            || brand.includes(searchVal)
            || category.includes(searchVal);

        return matchesCategory && matchesSearch;
    });
});

const isVariantSelected = (id) => selectedVariantIds.value.includes(id);

const toggleSelectVariant = (id) => {
    const idx = selectedVariantIds.value.indexOf(id);
    if (idx > -1) {
        selectedVariantIds.value.splice(idx, 1);
    } else {
        selectedVariantIds.value.push(id);
    }
};

const allFilteredSelected = computed(() => {
    return filteredVariants.value.length > 0
        && filteredVariants.value.every((v) => isVariantSelected(v.id));
});

const toggleSelectAllFiltered = () => {
    const filteredIds = filteredVariants.value.map((v) => v.id);

    if (allFilteredSelected.value) {
        selectedVariantIds.value = selectedVariantIds.value.filter((id) => !filteredIds.includes(id));
    } else {
        selectedVariantIds.value = [...new Set([...selectedVariantIds.value, ...filteredIds])];
    }
};

const addSelectedVariantsToItems = () => {
    const list = variantsList.value.filter((v) => selectedVariantIds.value.includes(v.id));
    list.forEach((variant) => {
        const exists = form.items.some((i) => i.product_variant_id === variant.id);
        if (!exists) {
            form.items.push({
                item_type: variant.product?.item_type || variant.item_type || 'part',
                product_variant_id: variant.id,
                description: getVariantName(variant),
                quantity: 1,
                unit_price: Number(variant.price ?? variant.default_selling_price ?? 0),
                mechanic_id: null,
            });
        }
    });
    selectedVariantIds.value = [];
    closeProductModal();
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

const appendComplaint = (text) => {
    if (!form.general_complaint) {
        form.general_complaint = text;
    } else if (!form.general_complaint.includes(text)) {
        form.general_complaint += `, ${text}`;
    }
};

const submit = () => {
    form.post('/services', {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="flex h-full flex-col gap-4 overflow-y-auto pr-1">
        <!-- TOP ROW: 2 Cards Side-by-Side (Unit & Pelanggan | Keluhan & Diagnosis) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Card 1: Data Unit Kendaraan & Pelanggan -->
            <div class="flex flex-col rounded-xl border border-default bg-elevated/40 p-4 space-y-3.5 shadow-sm">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <h2 class="text-sm font-bold text-highlighted flex items-center gap-2">
                        <UIcon name="i-lucide-car-front" class="size-4 text-primary shrink-0" />
                        <span>Data Unit Kendaraan & Pelanggan</span>
                    </h2>
                    <span class="rounded-full bg-blue-500/10 text-blue-600 border border-blue-500/20 px-2.5 py-0.5 text-xs font-semibold">
                        CHECK-IN
                    </span>
                </div>

                <!-- License Plate Preview Badge + Store Branch -->
                <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center bg-default p-3 rounded-xl border border-default">
                    <div class="sm:col-span-5 flex items-center justify-center">
                        <div class="relative flex items-center justify-between w-full rounded-lg border-2 border-slate-700 bg-zinc-950 px-3.5 py-1.5 text-white shadow-md">
                            <div class="flex flex-col">
                                <span class="font-mono text-base font-black tracking-widest text-amber-300 uppercase">
                                    {{ form.plate_number || 'KT ---- XX' }}
                                </span>
                                <span class="text-[8px] font-mono text-zinc-400 font-bold">POS BENGKEL</span>
                            </div>
                            <div class="text-right font-mono text-[9px] text-amber-400 font-bold border-l border-zinc-800 pl-2">
                                07.30
                            </div>
                        </div>
                    </div>

                    <div class="sm:col-span-7 grid gap-1">
                        <label class="text-xs font-medium text-highlighted flex items-center justify-between">
                            <span>Cabang Bengkel <span class="text-red-500">*</span></span>
                            <span v-if="!isOwner" class="text-[10px] text-amber-500 font-normal">(Cabang Bertugas)</span>
                        </label>
                        <select
                            v-model="form.store_id"
                            :disabled="!isOwner"
                            class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted outline-none focus:border-primary disabled:opacity-75 disabled:bg-elevated/80 disabled:cursor-not-allowed"
                            required
                        >
                            <option v-for="opt in options.stores" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Fields Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">No. Polisi / Plat Motor <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.plate_number"
                            type="text"
                            placeholder="KT 1234 AB"
                            class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-amber-500 font-mono font-bold uppercase tracking-wider outline-none focus:border-primary"
                            required
                        />
                        <span v-if="form.errors.plate_number" class="text-xs text-red-500">{{ form.errors.plate_number }}</span>
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">Status SPK <span class="text-red-500">*</span></label>
                        <select v-model="form.status" class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted outline-none focus:border-primary font-semibold">
                            <option value="checkin">Check-in</option>
                            <option value="diagnosis">Diagnosis</option>
                            <option value="in_progress">Dalam Pengerjaan</option>
                            <option value="waiting_parts">Menunggu Sparepart</option>
                            <option value="ready">Selesai (Siap Ambil)</option>
                        </select>
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">Nama Pelanggan <span class="text-red-500">*</span></label>
                        <input v-model="form.customer_name" type="text" placeholder="Nama lengkap..." class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted outline-none focus:border-primary" required />
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">No. HP / WhatsApp</label>
                        <input v-model="form.customer_phone" type="text" placeholder="081234567890" class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted font-mono outline-none focus:border-primary" />
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">Merek Kendaraan</label>
                        <input v-model="form.vehicle_brand" type="text" placeholder="Honda / Yamaha" class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted outline-none focus:border-primary" />
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">Model Kendaraan</label>
                        <input v-model="form.vehicle_model" type="text" placeholder="Vario 150 / NMAX" class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted outline-none focus:border-primary" />
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">KM Odometer Masuk</label>
                        <input v-model.number="form.odometer" type="number" placeholder="25400" class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted font-mono outline-none focus:border-primary" />
                    </div>

                    <div class="grid gap-1">
                        <label class="text-xs font-medium text-highlighted">Tahun / Warna</label>
                        <input v-model="form.color" type="text" placeholder="2022 / Hitam Doff" class="rounded-lg border border-default bg-default px-3 py-1.5 text-xs text-highlighted outline-none focus:border-primary" />
                    </div>
                </div>
            </div>

            <!-- Card 2: Card Khusus Keluhan & Diagnosis -->
            <div class="flex flex-col rounded-xl border border-default bg-elevated/40 p-4 space-y-3.5 shadow-sm justify-between">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <h2 class="text-sm font-bold text-highlighted flex items-center gap-2">
                        <UIcon name="i-lucide-stethoscope" class="size-4 text-amber-500 shrink-0" />
                        <span>Keluhan Utama & Catatan Diagnosis</span>
                    </h2>
                    <span class="text-[11px] text-muted font-medium">Anamnesis Kendaraan</span>
                </div>

                <!-- Quick Complaint Chips -->
                <div class="space-y-1.5">
                    <span class="text-[11px] text-muted font-medium block">Pilih Keluhan Umum (Quick Add):</span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            v-for="chip in ['Servis Rutin', 'Ganti Oli', 'Rem Blong / Bunyi', 'Mesin Mati', 'Bunyi Kasar / CVT', 'Lampu Mati']"
                            :key="chip"
                            type="button"
                            class="rounded-full border border-default bg-default px-2.5 py-1 text-[11px] font-medium text-muted hover:border-primary hover:text-primary transition-colors select-none"
                            @click="appendComplaint(chip)"
                        >
                            + {{ chip }}
                        </button>
                    </div>
                </div>

                <!-- Keluhan Utama -->
                <div class="grid gap-1 flex-1">
                    <label class="text-xs font-medium text-highlighted flex items-center gap-1">
                        <span>Keluhan Utama Pelanggan</span>
                    </label>
                    <textarea
                        v-model="form.general_complaint"
                        rows="3"
                        placeholder="Tuliskan detail keluhan pelanggan..."
                        class="w-full rounded-lg border border-default bg-default p-2.5 text-xs text-highlighted outline-none focus:border-primary resize-none flex-1"
                    ></textarea>
                </div>

                <!-- Hasil Diagnosis -->
                <div class="grid gap-1 flex-1">
                    <label class="text-xs font-medium text-highlighted flex items-center gap-1">
                        <span>Hasil Diagnosis / Analisa Mekanik</span>
                    </label>
                    <textarea
                        v-model="form.diagnosis"
                        rows="3"
                        placeholder="Tuliskan catatan analisa mekanik atau rekomendasi perbaikan..."
                        class="w-full rounded-lg border border-default bg-default p-2.5 text-xs text-highlighted outline-none focus:border-primary resize-none flex-1"
                    ></textarea>
                </div>
            </div>
        </div>

        <!-- BOTTOM ROW: Full Width Card (Rincian Part & Jasa Servis) -->
        <div class="flex flex-col rounded-xl border border-default bg-elevated/40 shadow-sm overflow-hidden min-h-[380px]">
            <!-- Basket Toolbar Header -->
            <div class="flex flex-wrap items-center justify-between border-b border-default p-3 gap-2 bg-elevated/80">
                <h2 class="text-xs sm:text-sm font-bold text-highlighted flex items-center gap-2">
                    <UIcon name="i-lucide-wrench-screwdriver" class="size-4 text-emerald-500 shrink-0" />
                    <span>Rincian Part & Jasa Servis ({{ form.items.length }})</span>
                </h2>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-lg border border-primary/40 bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/20 shadow-xs transition-all"
                        type="button"
                        @click="openProductModal"
                    >
                        <UIcon name="i-lucide-plus" class="size-3.5" />
                        <span>Pilih Katalog</span>
                    </button>
                    <button
                        class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-lg border border-default bg-default px-3 py-1.5 text-xs font-semibold text-highlighted hover:bg-elevated transition-all"
                        type="button"
                        @click="addCustomLaborItem"
                    >
                        <UIcon name="i-lucide-user-plus" class="size-3.5" />
                        <span>+ Jasa Custom</span>
                    </button>
                </div>
            </div>

            <!-- Items List -->
            <div class="flex-1 overflow-y-auto p-3">
                <div v-if="form.items.length === 0" class="flex flex-col items-center justify-center h-48 text-muted border border-dashed border-default rounded-xl p-6 text-center">
                    <UIcon name="i-lucide-shopping-bag" class="size-10 text-muted mb-2" />
                    <p class="text-xs font-medium">Belum ada suku cadang atau jasa ditambahkan.</p>
                    <p class="text-[11px] text-muted mt-0.5">Klik tombol di atas untuk memilih barang dari katalog atau menugaskan jasa mekanik.</p>
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="(item, idx) in form.items"
                        :key="idx"
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-lg border border-default bg-default p-3 hover:border-primary/50 transition-colors"
                    >
                        <div class="flex-1 space-y-1.5">
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase font-mono border shrink-0 flex items-center gap-1"
                                    :class="item.item_type === 'labor' ? 'bg-amber-500/10 text-amber-600 border-amber-500/30' : 'bg-emerald-500/10 text-emerald-600 border-emerald-500/30'"
                                >
                                    <UIcon :name="item.item_type === 'labor' ? 'i-lucide-user-cog' : 'i-lucide-box'" class="size-3" />
                                    {{ item.item_type === 'labor' ? 'JASA' : 'PART' }}
                                </span>
                                <input
                                    v-model="item.description"
                                    type="text"
                                    class="w-full bg-transparent text-xs font-medium text-highlighted outline-none focus:border-b focus:border-primary"
                                    placeholder="Deskripsi pekerjaan / barang..."
                                />
                            </div>

                            <!-- Mechanic Selection for Labor -->
                            <div v-if="item.item_type === 'labor'" class="flex items-center gap-2 pt-0.5">
                                <UIcon name="i-lucide-user-check" class="size-3.5 text-amber-500 shrink-0" />
                                <span class="text-[11px] text-muted">Montir:</span>
                                <select v-model="item.mechanic_id" class="rounded border border-default bg-default px-2 py-0.5 text-xs text-highlighted outline-none focus:border-primary max-w-[180px] truncate">
                                    <option :value="null">-- Belum Ditunjuk --</option>
                                    <option v-for="m in options.mechanics" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Qty & Price Inputs -->
                        <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 border-t sm:border-t-0 border-default/80 pt-2 sm:pt-0">
                            <div class="flex items-center gap-1">
                                <span class="text-[11px] text-muted">Qty:</span>
                                <input
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="1"
                                    class="w-14 rounded border border-default bg-default px-1.5 py-1 text-xs text-center font-mono font-bold text-highlighted outline-none focus:border-primary"
                                />
                            </div>

                            <div class="flex items-center gap-1">
                                <span class="text-[11px] text-muted">Rp</span>
                                <input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    min="0"
                                    step="500"
                                    class="w-24 sm:w-28 rounded border border-default bg-default px-1.5 py-1 text-xs text-right font-mono font-bold text-highlighted outline-none focus:border-primary"
                                />
                            </div>

                            <div class="text-right font-mono text-xs font-bold text-emerald-500 min-w-[80px]">
                                {{ formatCurrency(Number(item.quantity || 0) * Number(item.unit_price || 0)) }}
                            </div>

                            <button
                                class="rounded p-1 text-muted hover:bg-red-500/20 hover:text-red-500 shrink-0"
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
            <div class="border-t border-default bg-default p-3 sm:p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] text-muted">Estimasi Total SPK Servis:</p>
                        <p class="text-xl sm:text-2xl font-black text-emerald-500 font-mono tracking-tight">
                            {{ formatCurrency(estimatedTotal) }}
                        </p>
                    </div>

                    <div class="flex gap-2 w-full sm:w-auto">
                        <UButton color="neutral" variant="outline" label="Batal" type="button" class="flex-1 sm:flex-initial justify-center" @click="router.visit('/services')" />
                        <UButton
                            type="button"
                            color="primary"
                            icon="i-lucide-check-circle-2"
                            label="Simpan SPK Servis"
                            class="flex-1 sm:flex-initial justify-center shadow-lg"
                            :loading="form.processing"
                            @click="submit"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Catalog Multi-Select Modal -->
        <div v-if="productModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-4xl rounded-xl bg-default p-5 shadow-2xl flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-highlighted flex items-center gap-2">
                            <UIcon name="i-lucide-package-search" class="size-5 text-primary shrink-0" />
                            <span>Pilih Produk, Suku Cadang & Jasa Katalog</span>
                        </h2>
                        <p class="text-xs text-muted">Pilih satu atau beberapa barang/jasa dari katalog untuk dimasukkan ke daftar transaksi SPK.</p>
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
                            v-model="catalogSearch"
                            type="search"
                            class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary"
                            placeholder="Cari nama produk, SKU, barcode, merek..."
                        />
                    </div>
                    <select
                        v-model="categoryFilter"
                        class="rounded-md border border-default bg-default px-3 py-2 text-xs text-highlighted outline-none focus:border-primary sm:w-52"
                    >
                        <option v-for="opt in categoryOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                    </select>
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
                            @click="toggleSelectVariant(variant.id)"
                        >
                            <!-- Thumbnail Image -->
                            <div class="size-16 shrink-0 rounded-md border border-default bg-elevated/50 flex items-center justify-center overflow-hidden">
                                <img v-if="getVariantImageUrl(variant)" :src="getVariantImageUrl(variant)" :alt="getVariantName(variant)" class="size-full object-cover" />
                                <UIcon v-else :name="(variant.product?.item_type || variant.item_type) === 'labor' ? 'i-lucide-wrench' : 'i-lucide-package'" class="size-7 text-muted/60" />
                            </div>

                            <!-- Variant Details -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-semibold text-xs text-highlighted line-clamp-1">{{ getVariantName(variant) }}</span>
                                    <span v-if="isVariantSelected(variant.id)" class="text-primary font-bold">
                                        <UIcon name="i-lucide-check-circle-2" class="size-5" />
                                    </span>
                                </div>

                                <p class="text-[11px] text-muted font-mono mt-0.5">SKU: {{ variant.sku || '-' }} | Barcode: {{ variant.barcode || '-' }}</p>

                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[10px]">
                                    <span v-if="variant.product?.category_name || variant.category_name" class="rounded bg-elevated px-1.5 py-0.5 text-muted font-medium border border-default">
                                        {{ variant.product?.category_name || variant.category_name }}
                                    </span>
                                    <span v-if="variant.product?.brand_name || variant.brand_name" class="rounded bg-elevated px-1.5 py-0.5 text-muted font-medium border border-default">
                                        {{ variant.product?.brand_name || variant.brand_name }}
                                    </span>
                                    <span class="rounded bg-primary/10 px-1.5 py-0.5 text-primary font-medium">
                                        {{ variant.product?.unit_name || variant.unit_name || 'Pcs' }}
                                    </span>
                                    <span
                                        class="rounded px-1.5 py-0.5 font-semibold font-mono uppercase"
                                        :class="(variant.product?.item_type || variant.item_type) === 'labor' ? 'bg-amber-500/10 text-amber-600' : 'bg-emerald-500/10 text-emerald-600'"
                                    >
                                        {{ (variant.product?.item_type || variant.item_type) === 'labor' ? 'JASA' : 'PART' }}
                                    </span>
                                </div>

                                <p class="mt-2 font-mono text-xs font-bold text-primary">
                                    Harga: {{ formatCurrency(variant.price ?? variant.default_selling_price) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center">
                        <UIcon name="i-lucide-package-x" class="mx-auto size-10 text-muted/50" />
                        <p class="mt-2 text-sm text-muted">Tidak ada katalog yang cocok dengan kriteria pencarian.</p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-4 flex items-center justify-between border-t border-default pt-3">
                    <span class="text-xs font-medium text-muted">
                        <strong class="text-highlighted font-mono">{{ selectedVariantIds.length }}</strong> item dipilih
                    </span>

                    <div class="flex gap-2">
                        <UButton color="neutral" variant="outline" label="Batal" @click="closeProductModal" />
                        <UButton
                            icon="i-lucide-plus"
                            :label="`Tambahkan (${selectedVariantIds.length}) Ke SPK`"
                            :disabled="selectedVariantIds.length === 0"
                            @click="addSelectedVariantsToItems"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
