<script setup>
import ProductVariantForm from '../../Components/ProductVariantForm.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Product Variant', panelId: 'product-variants-show' }],
});

const props = defineProps({
    productVariant: Object,
    products: Array,
    attributes: Array,
});

const activeTab = ref('info');
const activeImageIndex = ref(0);
const showEditVariantModal = ref(false);

const variantData = computed(() => props.productVariant?.data || props.productVariant || {});
const parentProduct = computed(() => variantData.value.product || {});

// Active images fallback: Variant images first, then parent product images
const mediaList = computed(() => {
    if ((variantData.value.images || []).length > 0) {
        return variantData.value.images;
    }
    return parentProduct.value.images || [];
});

const currentImage = computed(() => {
    if (mediaList.value.length > 0) {
        return mediaList.value[activeImageIndex.value] || mediaList.value[0];
    }
    return null;
});

const tabs = [
    { id: 'info', label: 'Informasi & Galeri Varian', icon: 'i-lucide-info' },
    { id: 'prices', label: 'Harga Per Toko', icon: 'i-lucide-tag' },
    { id: 'stocks', label: 'Stok Per Gudang', icon: 'i-lucide-warehouse' },
    { id: 'discounts', label: 'Promo & Diskon', icon: 'i-lucide-percent' },
];

// Edit Variant Form
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

const openEditVariantModal = () => {
    variantForm.clearErrors();
    variantForm.product_id = variantData.value.product_id || '';
    variantForm.sku = variantData.value.sku || '';
    variantForm.barcode = variantData.value.barcode || '';
    variantForm.name_suffix = variantData.value.name_suffix || '';
    variantForm.receipt_name = variantData.value.receipt_name || '';
    variantForm.default_purchase_price = variantData.value.default_purchase_price || 0;
    variantForm.default_selling_price = variantData.value.default_selling_price || 0;
    variantForm.is_active = !!variantData.value.is_active;
    variantForm.attribute_option_ids = variantData.value.attribute_option_ids || [];
    variantForm.images = [];
    variantForm.delete_media_ids = [];
    showEditVariantModal.value = true;
};

const closeEditVariantModal = () => {
    showEditVariantModal.value = false;
};

const submitVariantEdit = () => {
    variantForm.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(`/product-variants/${variantData.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditVariantModal();
        },
    });
};

const productOptions = computed(() => props.products || [{ label: parentProduct.value.name, value: variantData.value.product_id }]);

// Dummy data for Prices, Stocks, Discounts preview
const dummyPrices = [
    { id: 1, store_name: 'Bengkel Utama (Pusat)', price_type: 'Toko', purchase_price: `Rp ${Number(variantData.value.default_purchase_price || 0).toLocaleString('id-ID')}`, selling_price: `Rp ${Number(variantData.value.default_selling_price || 0).toLocaleString('id-ID')}`, is_active: true },
    { id: 2, store_name: 'Cabang Jakarta Selatan', price_type: 'Toko', purchase_price: `Rp ${Number(variantData.value.default_purchase_price || 0).toLocaleString('id-ID')}`, selling_price: `Rp ${Number((variantData.value.default_selling_price || 0) * 1.05).toLocaleString('id-ID')}`, is_active: true },
];

const dummyStocks = [
    { id: 1, warehouse_name: 'Gudang Utama (Pusat)', location: 'Rak A-01', quantity: 45, min_stock: 10 },
    { id: 2, warehouse_name: 'Gudang Depan POS', location: 'Display 01', quantity: 12, min_stock: 5 },
];

const dummyDiscounts = [
    { id: 1, store_name: 'Semua Toko (Global)', discount_name: 'Diskon Member 5%', type: 'Persentase', value: '5%', is_active: true },
];
</script>

<template>
    <div class="space-y-6">
        <!-- Top Navigation Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-default pb-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-xs text-muted">
                    <Link :href="parentProduct.id ? `/products/${parentProduct.id}` : '/products'" class="hover:text-primary transition-colors">
                        {{ parentProduct.name || 'Detail Produk' }}
                    </Link>
                    <span>/</span>
                    <span class="text-highlighted font-medium">Detail Varian</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold text-highlighted">
                        {{ parentProduct.name || 'Produk' }} <span v-if="variantData.name_suffix" class="text-primary">- {{ variantData.name_suffix }}</span>
                    </h1>
                    <span :class="variantData.is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20' : 'bg-red-500/10 text-red-600 dark:text-red-400 border-red-500/20'" class="inline-flex items-center gap-1.5 rounded-full border px-3 py-0.5 text-xs font-semibold">
                        {{ variantData.is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 transition-all shadow-sm"
                    @click="openEditVariantModal"
                >
                    <UIcon name="i-lucide-pencil" class="size-4" />
                    Edit Varian
                </button>
                <Link :href="parentProduct.id ? `/products/${parentProduct.id}` : '/products'" class="inline-flex items-center justify-center gap-2 rounded-md border border-default bg-elevated/50 px-4 py-2 text-sm font-medium text-highlighted hover:bg-elevated">
                    <UIcon name="i-lucide-arrow-left" class="size-4" />
                    Kembali Ke Produk
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

        <!-- TAB 1: INFORMASI VARIAN & GALERI -->
        <div v-if="activeTab === 'info'" class="grid gap-6 lg:grid-cols-12">
            <!-- Left Column: Gallery Viewer -->
            <div class="lg:col-span-5 space-y-4">
                <div class="relative aspect-square overflow-hidden rounded-xl border border-default bg-elevated/40 shadow-inner flex items-center justify-center">
                    <img
                        v-if="currentImage"
                        :src="currentImage.url"
                        :alt="variantData.display_receipt_name"
                        class="h-full w-full object-cover transition-all duration-300 hover:scale-105"
                    />
                    <div v-else class="flex flex-col items-center justify-center gap-3 text-muted">
                        <div class="rounded-full bg-default/80 p-4">
                            <UIcon name="i-lucide-image" class="size-10 text-muted" />
                        </div>
                        <p class="text-sm font-medium">Belum ada foto varian</p>
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

            <!-- Right Column: Variant Specifications & Pricing Cards -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Info Overview Card -->
                <div class="rounded-xl border border-default bg-default p-6 shadow-sm space-y-5">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Nama Cetak Struk</span>
                        <h2 class="text-xl font-bold text-highlighted mt-0.5">{{ variantData.display_receipt_name }}</h2>
                    </div>

                    <div class="grid grid-cols-2 gap-4 rounded-lg bg-elevated/30 p-4 border border-default/50 sm:grid-cols-3">
                        <div>
                            <p class="text-xs text-muted font-medium">SKU (Kode Unik)</p>
                            <p class="text-sm font-semibold text-highlighted mt-0.5">{{ variantData.sku || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted font-medium">Barcode Scanner</p>
                            <p class="text-sm font-semibold text-highlighted mt-0.5">{{ variantData.barcode || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted font-medium">Suffix Tambahan</p>
                            <p class="text-sm font-semibold text-highlighted mt-0.5">{{ variantData.name_suffix || '-' }}</p>
                        </div>
                    </div>

                    <!-- Attribute Badges -->
                    <div class="space-y-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-muted">Opsi Atribut Varian</span>
                        <div v-if="(variantData.attribute_options || []).length > 0" class="flex flex-wrap gap-2">
                            <span v-for="opt in variantData.attribute_options" :key="opt.id" class="inline-flex items-center gap-1.5 rounded-lg border border-primary/20 bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                <UIcon name="i-lucide-tag" class="size-3.5" />
                                <strong>{{ opt.name }}:</strong> {{ opt.value }}
                            </span>
                        </div>
                        <p v-else class="text-sm text-muted">Tidak ada opsi atribut yang dikonfigurasikan.</p>
                    </div>

                    <!-- Pricing Highlights -->
                    <div class="grid grid-cols-2 gap-4 rounded-xl border border-emerald-500/20 bg-emerald-500/5 p-4">
                        <div>
                            <p class="text-xs font-medium text-muted">Harga Beli Default</p>
                            <p class="text-lg font-bold text-highlighted mt-0.5">
                                Rp {{ Number(variantData.default_purchase_price || 0).toLocaleString('id-ID') }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted">Harga Jual Default</p>
                            <p class="text-xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">
                                Rp {{ Number(variantData.default_selling_price || 0).toLocaleString('id-ID') }}
                            </p>
                        </div>
                    </div>

                    <!-- Parent Product Link -->
                    <div class="flex items-center justify-between border-t border-default/60 pt-4 text-xs text-muted">
                        <span>Induk Produk: <Link :href="`/products/${parentProduct.id}`" class="text-primary font-semibold hover:underline">{{ parentProduct.name }}</Link></span>
                        <span>Dibuat pada: <strong class="text-highlighted font-medium">{{ variantData.created_at || '-' }}</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: HARGA PER TOKO -->
        <div v-if="activeTab === 'prices'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Harga Varian Per Toko (Product Prices)</h3>
                    <p class="text-sm text-muted">Daftar penetapan harga spesifik untuk varian ini pada masing-masing bengkel.</p>
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

        <!-- TAB 3: STOK PER GUDANG -->
        <div v-if="activeTab === 'stocks'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Stok Varian Per Gudang (Product Stocks)</h3>
                    <p class="text-sm text-muted">Monitoring stok fisik varian di setiap lokasi gudang.</p>
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
                            <td class="px-4 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400">{{ stock.quantity }} Pcs</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ stock.min_stock }} Pcs</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 4: PROMO & DISKON -->
        <div v-if="activeTab === 'discounts'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Promo & Diskon Varian (Product Discounts)</h3>
                    <p class="text-sm text-muted">Aturan diskon yang berlaku secara khusus untuk varian ini.</p>
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

        <!-- MODAL EDIT VARIAN -->
        <div v-if="showEditVariantModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-4xl overflow-y-auto rounded-xl bg-default p-6 shadow-2xl border border-default space-y-5">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <h2 class="text-lg font-bold text-highlighted">Edit Product Variant</h2>
                    <button class="rounded-md p-1.5 hover:bg-elevated" type="button" @click="closeEditVariantModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <ProductVariantForm
                    :form="variantForm"
                    :products="productOptions"
                    :attributes="attributes || []"
                    :existing-media="variantData.images || []"
                    submit-label="Simpan Perubahan"
                    :show-cancel="true"
                    cancel-label="Batal"
                    @submit="submitVariantEdit"
                    @cancel="closeEditVariantModal"
                />
            </div>
        </div>
    </div>
</template>
