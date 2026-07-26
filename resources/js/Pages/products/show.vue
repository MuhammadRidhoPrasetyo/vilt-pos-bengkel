<script setup>
import MultiImageUploader from '../../Components/MultiImageUploader.vue';
import ProductVariantForm from '../../Components/ProductVariantForm.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Produk', panelId: 'products-show' }],
});

const props = defineProps({
    product: Object,
    variants: Object,
    options: Object,
    attributes: Array,
});

const activeTab = ref('info');
const activeImageIndex = ref(0);

// Modal states
const showEditProductModal = ref(false);
const showVariantModal = ref(false);
const editingVariant = ref(null);

const productData = computed(() => props.product?.data || props.product || {});
const variantList = computed(() => props.variants?.data || props.variants || []);
const mediaList = computed(() => productData.value.images || []);

const currentImage = computed(() => {
    if (mediaList.value.length > 0) {
        return mediaList.value[activeImageIndex.value] || mediaList.value[0];
    }
    return null;
});

const tabs = computed(() => {
    const list = [
        { id: 'info', label: 'Informasi & Galeri', icon: 'i-lucide-info' },
    ];

    if (productData.value.has_variants) {
        list.push({ id: 'variants', label: `Daftar Varian (${variantList.value.length})`, icon: 'i-lucide-layers' });
    }

    list.push(
        { id: 'prices', label: 'Harga Per Toko', icon: 'i-lucide-tag' },
        { id: 'stocks', label: 'Stok Per Gudang', icon: 'i-lucide-warehouse' },
        { id: 'discounts', label: 'Promo & Diskon', icon: 'i-lucide-percent' }
    );

    return list;
});

// Product Edit Form
const productForm = useForm({
    product_category_id: '',
    brand_id: '',
    unit_id: '',
    name: '',
    receipt_name: '',
    item_type: 'part',
    has_variants: false,
    description: '',
    images: [],
    delete_media_ids: [],
});

const openEditProductModal = () => {
    productForm.clearErrors();
    productForm.product_category_id = productData.value.product_category_id || '';
    productForm.brand_id = productData.value.brand_id || '';
    productForm.unit_id = productData.value.unit_id || '';
    productForm.name = productData.value.name || '';
    productForm.receipt_name = productData.value.receipt_name || '';
    productForm.item_type = productData.value.item_type || 'part';
    productForm.has_variants = !!productData.value.has_variants;
    productForm.description = productData.value.description || '';
    productForm.images = [];
    productForm.delete_media_ids = [];
    showEditProductModal.value = true;
};

const closeEditProductModal = () => {
    showEditProductModal.value = false;
};

const submitProductEdit = () => {
    productForm.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/products/${productData.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditProductModal();
        },
    });
};

// Variant Create / Edit Form
const variantForm = useForm({
    product_id: '',
    sku: '',
    barcode: '',
    name_suffix: '',
    receipt_name: '',
    default_purchase_price: 0,
    default_selling_price: 0,
    is_active: true,
    attribute_option_ids: [],
    images: [],
    delete_media_ids: [],
});

const openCreateVariantModal = () => {
    editingVariant.value = null;
    variantForm.clearErrors();
    variantForm.product_id = productData.value.id;
    variantForm.sku = '';
    variantForm.barcode = '';
    variantForm.name_suffix = '';
    variantForm.receipt_name = '';
    variantForm.default_purchase_price = 0;
    variantForm.default_selling_price = 0;
    variantForm.is_active = true;
    variantForm.attribute_option_ids = [];
    variantForm.images = [];
    variantForm.delete_media_ids = [];
    showVariantModal.value = true;
};

const openEditVariantModal = (variant) => {
    editingVariant.value = variant;
    variantForm.clearErrors();
    variantForm.product_id = productData.value.id;
    variantForm.sku = variant.sku || '';
    variantForm.barcode = variant.barcode || '';
    variantForm.name_suffix = variant.name_suffix || '';
    variantForm.receipt_name = variant.receipt_name || '';
    variantForm.default_purchase_price = variant.default_purchase_price || 0;
    variantForm.default_selling_price = variant.default_selling_price || 0;
    variantForm.is_active = !!variant.is_active;
    variantForm.attribute_option_ids = variant.attribute_option_ids || [];
    variantForm.images = [];
    variantForm.delete_media_ids = [];
    showVariantModal.value = true;
};

const closeVariantModal = () => {
    showVariantModal.value = false;
    editingVariant.value = null;
};

const submitVariantForm = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            closeVariantModal();
        },
    };

    if (editingVariant.value) {
        variantForm.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(`/product-variants/${editingVariant.value.id}`, options);
        return;
    }

    variantForm.post('/product-variants', options);
};

// Options for dropdown selects
const productCategoryOptions = computed(() => props.options?.productCategories || []);
const brandOptions = computed(() => props.options?.brands || []);
const unitOptions = computed(() => props.options?.units || []);
const productSelectOptions = computed(() => [{ label: productData.value.name, value: productData.value.id }]);

// Dummy data for Prices, Stocks, Discounts preview
const dummyPrices = [
    { id: 1, store_name: 'Bengkel Utama (Pusat)', price_type: 'Toko', purchase_price: 'Rp 35.000', selling_price: 'Rp 45.000', is_active: true },
    { id: 2, store_name: 'Cabang Jakarta Selatan', price_type: 'Toko', purchase_price: 'Rp 35.000', selling_price: 'Rp 48.000', is_active: true },
];

const dummyStocks = [
    { id: 1, warehouse_name: 'Gudang Utama (Pusat)', location: 'Rak A-01', quantity: 120, min_stock: 15 },
    { id: 2, warehouse_name: 'Gudang Depan POS', location: 'Display 02', quantity: 18, min_stock: 5 },
];

const dummyDiscounts = [
    { id: 1, store_name: 'Semua Toko (Global)', discount_name: 'Promo Pelanggan Setia 10%', type: 'Persentase', value: '10%', is_active: true },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Top Navigation Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-default pb-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs text-muted">
                    <Link href="/products" class="hover:text-primary transition-colors">Products</Link>
                    <span>/</span>
                    <span class="text-highlighted font-medium">Detail Produk</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold text-highlighted">{{ productData.name }}</h1>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-semibold"
                        :class="productData.item_type === 'part' ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20'"
                    >
                        <UIcon :name="productData.item_type === 'part' ? 'i-lucide-box' : 'i-lucide-wrench'" class="size-3.5" />
                        {{ productData.item_type === 'part' ? 'Part (Barang)' : 'Labor (Jasa)' }}
                    </span>
                    <span v-if="productData.has_variants" class="inline-flex items-center gap-1.5 rounded-full bg-purple-500/10 px-3 py-0.5 text-xs font-semibold text-purple-600 dark:text-purple-400 border border-purple-500/20">
                        <UIcon name="i-lucide-layers" class="size-3.5" />
                        Memiliki Varian
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 transition-all shadow-sm"
                    @click="openEditProductModal"
                >
                    <UIcon name="i-lucide-pencil" class="size-4" />
                    Edit Produk
                </button>
                <Link href="/products" class="inline-flex items-center justify-center gap-2 rounded-md border border-default bg-elevated/50 px-4 py-2 text-sm font-medium text-highlighted hover:bg-elevated">
                    <UIcon name="i-lucide-arrow-left" class="size-4" />
                    Kembali
                </Link>
            </div>
        </div>

        <!-- Custom Tab Bar Navigation -->
        <div class="flex flex-wrap gap-2 border-b border-default pb-1">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-all"
                :class="activeTab === tab.id ? 'border-primary text-primary bg-primary/5 rounded-t-md' : 'border-transparent text-muted hover:border-default hover:text-highlighted'"
                @click="activeTab = tab.id"
            >
                <UIcon :name="tab.icon" class="size-4" />
                {{ tab.label }}
            </button>
        </div>

        <!-- TAB 1: INFORMASI UMUM & GALERI -->
        <div v-if="activeTab === 'info'" class="grid gap-6 lg:grid-cols-12">
            <!-- Left Column: Gallery Viewer -->
            <div class="lg:col-span-5 space-y-4">
                <div class="relative aspect-square overflow-hidden rounded-xl border border-default bg-elevated/40 shadow-inner flex items-center justify-center">
                    <img
                        v-if="currentImage"
                        :src="currentImage.url"
                        :alt="productData.name"
                        class="h-full w-full object-cover transition-all duration-300 hover:scale-105"
                    />
                    <div v-else class="flex flex-col items-center justify-center gap-3 text-muted">
                        <div class="rounded-full bg-default/80 p-4">
                            <UIcon name="i-lucide-image" class="size-10 text-muted" />
                        </div>
                        <p class="text-sm font-medium">Belum ada foto galeri</p>
                    </div>
                </div>

                <!-- Thumbnail selector -->
                <div v-if="mediaList.length > 1" class="flex items-center gap-3 overflow-x-auto pb-2">
                    <button
                        v-for="(media, index) in mediaList"
                        :key="media.id"
                        type="button"
                        class="relative aspect-square size-16 shrink-0 overflow-hidden rounded-lg border-2 transition-all"
                        :class="activeImageIndex === index ? 'border-primary ring-2 ring-primary/30' : 'border-default opacity-70 hover:opacity-100'"
                        @click="activeImageIndex = index"
                    >
                        <img :src="media.thumb_url || media.url" :alt="media.name" class="h-full w-full object-cover" />
                    </button>
                </div>
            </div>

            <!-- Right Column: Product Overview Cards -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Info Overview Card -->
                <div class="rounded-xl border border-default bg-default p-6 shadow-sm space-y-5">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Nama Resmi Produk</span>
                        <h2 class="text-xl font-bold text-highlighted mt-0.5">{{ productData.name }}</h2>
                    </div>

                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-elevated/30 p-4 border border-default/50 sm:grid-cols-4">
                        <div>
                            <p class="text-xs text-muted font-medium">Nama Struk</p>
                            <p class="text-sm font-semibold text-highlighted mt-0.5">{{ productData.display_receipt_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted font-medium">Kategori</p>
                            <p class="text-sm font-semibold text-highlighted mt-0.5">{{ productData.category?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted font-medium">Merek</p>
                            <p class="text-sm font-semibold text-highlighted mt-0.5">{{ productData.brand?.name || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted font-medium">Satuan Dasar</p>
                            <p class="text-sm font-semibold text-highlighted mt-0.5">{{ productData.unit?.name || '-' }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Deskripsi Produk</span>
                        <div class="rounded-lg border border-default/60 bg-elevated/20 p-4 text-sm text-highlighted leading-relaxed min-h-24">
                            {{ productData.description || 'Tidak ada deskripsi untuk produk ini.' }}
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between border-t border-default/60 pt-4 text-xs text-muted">
                        <span>Dibuat pada: <strong class="text-highlighted font-medium">{{ productData.created_at || '-' }}</strong></span>
                        <button type="button" class="text-primary font-semibold hover:underline flex items-center gap-1" @click="openEditProductModal">
                            <UIcon name="i-lucide-pencil" class="size-3.5" />
                            Edit Informasi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: DAFTAR VARIAN -->
        <div v-if="activeTab === 'variants'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Daftar Varian Produk</h3>
                    <p class="text-sm text-muted">Seluruh kombinasi varian yang terdaftar pada produk induk ini.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 transition-all shadow-sm"
                    @click="openCreateVariantModal"
                >
                    <UIcon name="i-lucide-plus" class="size-4" />
                    Tambah Varian Baru
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">SKU / Barcode</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Suffix Varian</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Nama Struk</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Atribut</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Harga Jual Default</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="variant in variantList" :key="variant.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="px-4 py-3 text-sm">
                                <div class="font-medium text-highlighted">{{ variant.sku || '-' }}</div>
                                <div class="text-xs text-muted">{{ variant.barcode || 'No barcode' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-highlighted">{{ variant.name_suffix || '-' }}</td>
                            <td class="px-4 py-3 text-sm text-highlighted">{{ variant.display_receipt_name }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ (variant.attribute_option_labels || []).join(', ') || '-' }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                Rp {{ Number(variant.default_selling_price || 0).toLocaleString('id-ID') }}
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="variant.is_active ? 'text-emerald-500 bg-emerald-500/10 border-emerald-500/20' : 'text-red-500 bg-red-500/10 border-red-500/20'" class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                    {{ variant.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-default bg-elevated/50 text-muted hover:bg-elevated hover:text-highlighted transition-all"
                                        title="Edit Varian"
                                        @click="openEditVariantModal(variant)"
                                    >
                                        <UIcon name="i-lucide-pencil" class="size-4" />
                                    </button>
                                    <Link
                                        :href="`/product-variants/${variant.id}`"
                                        class="inline-flex items-center gap-1.5 rounded-md border border-default bg-elevated px-3 py-1.5 text-xs font-medium text-highlighted hover:bg-primary hover:text-inverted transition-all"
                                    >
                                        <UIcon name="i-lucide-eye" class="size-3.5" />
                                        Detail Variant
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="variantList.length === 0">
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-muted">Belum ada varian terdaftar.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 3: HARGA PER TOKO -->
        <div v-if="activeTab === 'prices'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Manajemen Harga Per Toko (Product Prices)</h3>
                    <p class="text-sm text-muted">Konfigurasi harga jual dan harga beli spesifik per cabang bengkel/toko.</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Cabang Toko</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Tipe Harga</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Harga Beli</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Harga Jual</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="price in dummyPrices" :key="price.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-highlighted">{{ price.store_name }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ price.price_type }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ price.purchase_price }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ price.selling_price }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-500">
                                    Aktif
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 4: STOK PER GUDANG -->
        <div v-if="activeTab === 'stocks'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Informasi Stok Gudang (Product Stocks)</h3>
                    <p class="text-sm text-muted">Monitoring jumlah stok fisik di setiap lokasi gudang penyimpan.</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Nama Gudang</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Lokasi Spesifik</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Jumlah Stok</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Min Stock Alert</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="stock in dummyStocks" :key="stock.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-highlighted">{{ stock.warehouse_name }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ stock.location }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400">{{ stock.quantity }} {{ productData.unit?.name || 'Pcs' }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ stock.min_stock }} {{ productData.unit?.name || 'Pcs' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 5: PROMO & DISKON -->
        <div v-if="activeTab === 'discounts'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Promo & Diskon (Product Discounts)</h3>
                    <p class="text-sm text-muted">Daftar aturan diskon yang dikonfigurasikan pada produk/varian ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Berlaku di Toko</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Nama Promo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Tipe Nilai</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Potongan Diskon</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="discount in dummyDiscounts" :key="discount.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-highlighted">{{ discount.store_name }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ discount.discount_name }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ discount.type }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-purple-600 dark:text-purple-400">{{ discount.value }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="inline-flex rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-0.5 text-xs font-medium text-emerald-500">
                                    Aktif
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL EDIT PRODUK -->
        <div v-if="showEditProductModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-xl bg-default p-6 shadow-2xl border border-default space-y-5">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <h2 class="text-lg font-bold text-highlighted">Edit Informasi Produk</h2>
                    <button class="rounded-md p-1.5 hover:bg-elevated" type="button" @click="closeEditProductModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitProductEdit">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Kategori *</span>
                            <USelect v-model="productForm.product_category_id" :items="productCategoryOptions" class="w-full" placeholder="Pilih Kategori" />
                            <span v-if="productForm.errors.product_category_id" class="text-xs text-red-600">{{ productForm.errors.product_category_id }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Merek</span>
                            <USelect v-model="productForm.brand_id" :items="brandOptions" class="w-full" placeholder="Pilih Merek" />
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Satuan Dasar</span>
                            <USelect v-model="productForm.unit_id" :items="unitOptions" class="w-full" placeholder="Pilih Satuan" />
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Tipe Item *</span>
                            <USelect v-model="productForm.item_type" :items="[{ label: 'Part (Barang)', value: 'part' }, { label: 'Labor (Jasa)', value: 'labor' }]" class="w-full" />
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Nama Produk *</span>
                        <input v-model="productForm.name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" required />
                        <span v-if="productForm.errors.name" class="text-xs text-red-600">{{ productForm.errors.name }}</span>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Nama Struk (Optional)</span>
                        <input v-model="productForm.receipt_name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" placeholder="Contoh: Oli Yamalube" />
                        <span v-if="productForm.errors.receipt_name" class="text-xs text-red-600">{{ productForm.errors.receipt_name }}</span>
                    </label>

                    <label class="flex items-center gap-3 rounded-md border border-default p-3">
                        <input v-model="productForm.has_variants" class="size-4" type="checkbox" />
                        <span class="text-sm font-medium">Produk memiliki banyak varian (misal: beda warna/ukuran)</span>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Deskripsi</span>
                        <textarea v-model="productForm.description" class="min-h-20 rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" />
                    </label>

                    <div class="border-t border-default pt-4">
                        <MultiImageUploader
                            v-model="productForm.images"
                            v-model:delete-media-ids="productForm.delete_media_ids"
                            :existing-media="productData.images || []"
                            label="Gambar Produk (Galeri)"
                            description="Upload foto galeri produk induk."
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-default pt-4">
                        <button type="button" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated" @click="closeEditProductModal">
                            Batal
                        </button>
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" :disabled="productForm.processing">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL TAMBAH / EDIT VARIAN -->
        <div v-if="showVariantModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-4xl overflow-y-auto rounded-xl bg-default p-6 shadow-2xl border border-default space-y-5">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <h2 class="text-lg font-bold text-highlighted">
                        {{ editingVariant ? 'Edit Product Variant' : 'Tambah Product Variant Baru' }}
                    </h2>
                    <button class="rounded-md p-1.5 hover:bg-elevated" type="button" @click="closeVariantModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <ProductVariantForm
                    :form="variantForm"
                    :products="productSelectOptions"
                    :attributes="attributes"
                    :existing-media="editingVariant ? (editingVariant.images || []) : []"
                    :submit-label="editingVariant ? 'Simpan Perubahan' : 'Buat Variant'"
                    :show-cancel="true"
                    cancel-label="Batal"
                    @submit="submitVariantForm"
                    @cancel="closeVariantModal"
                />
            </div>
        </div>
    </div>
</template>
