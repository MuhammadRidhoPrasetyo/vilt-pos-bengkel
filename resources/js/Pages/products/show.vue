<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import MultiImageUploader from '../../Components/MultiImageUploader.vue';
import ProductVariantForm from '../../Components/ProductVariantForm.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
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

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isOwner = computed(() => !currentUser.value.store_id || currentUser.value.roles?.includes('owner'));

const activeTab = ref('info');
const activeImageIndex = ref(0);

// Modal states
const showEditProductModal = ref(false);
const showManageAttributesModal = ref(false);
const editingAttribute = ref(null);
const showVariantModal = ref(false);
const editingVariant = ref(null);
const showDiscountModal = ref(false);
const editingDiscount = ref(null);
const showStockModal = ref(false);
const editingStock = ref(null);
const showPriceModal = ref(false);
const editingPrice = ref(null);

// Delete Confirmation Modal State
const showDeleteConfirmModal = ref(false);
const deleteConfirmData = ref({
    title: 'Konfirmasi Hapus Data',
    description: 'Data yang dihapus tidak dapat dikembalikan.',
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

const productData = computed(() => props.product?.data || props.product || {});
const variantList = computed(() => props.variants?.data || props.variants || []);
const productAttributes = computed(() => productData.value.attributes || []);
const mediaList = computed(() => productData.value.images || []);

const allProductPrices = computed(() => {
    return variantList.value.flatMap((variant) => {
        return (variant.prices || []).map((price) => ({
            ...price,
            variant_display_name: variant.display_receipt_name,
        }));
    });
});

const allProductPriceHistories = computed(() => {
    return variantList.value.flatMap((variant) => {
        return (variant.price_histories || []).map((history) => ({
            ...history,
            variant_display_name: variant.display_receipt_name,
        }));
    }).sort((a, b) => new Date(b.date || 0) - new Date(a.date || 0));
});

const allProductDiscounts = computed(() => {
    return variantList.value.flatMap((variant) => {
        return (variant.discounts || []).map((disc) => ({
            ...disc,
            variant_display_name: variant.display_receipt_name,
        }));
    });
});

const allProductStocks = computed(() => {
    return variantList.value.flatMap((variant) => {
        return (variant.stocks || []).map((stock) => ({
            ...stock,
            variant_display_name: variant.display_receipt_name,
        }));
    });
});

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
        { id: 'prices', label: `Harga Per Toko (${allProductPrices.value.length})`, icon: 'i-lucide-tag' },
        { id: 'stocks', label: `Stok Per Gudang (${allProductStocks.value.length})`, icon: 'i-lucide-warehouse' },
        { id: 'discounts', label: `Promo & Diskon (${allProductDiscounts.value.length})`, icon: 'i-lucide-percent' }
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

// Inline Product Attribute Form with Button-based Option Rows
const attributeModalForm = useForm({
    name: '',
    options: [''],
});

const addOptionRow = () => {
    attributeModalForm.options.push('');
};

const removeOptionRow = (index) => {
    if (attributeModalForm.options.length > 1) {
        attributeModalForm.options.splice(index, 1);
    } else {
        attributeModalForm.options = [''];
    }
};

const openCreateAttributeModal = () => {
    editingAttribute.value = null;
    attributeModalForm.clearErrors();
    attributeModalForm.name = '';
    attributeModalForm.options = [''];
    showManageAttributesModal.value = true;
};

const openEditAttributeModal = (attr) => {
    editingAttribute.value = attr;
    attributeModalForm.clearErrors();
    attributeModalForm.name = attr.name;
    attributeModalForm.options = (attr.options || []).map((o) => o.value);
    if (attributeModalForm.options.length === 0) {
        attributeModalForm.options = [''];
    }
    showManageAttributesModal.value = true;
};

const closeManageAttributesModal = () => {
    showManageAttributesModal.value = false;
    editingAttribute.value = null;
};

const submitAttributeForm = () => {
    const cleanedOptions = attributeModalForm.options
        .map((s) => s.trim())
        .filter(Boolean);

    if (cleanedOptions.length === 0) {
        attributeModalForm.setError('options', 'Masukkan minimal 1 opsi nilai atribut.');
        return;
    }

    if (editingAttribute.value) {
        attributeModalForm.transform(() => ({
            name: attributeModalForm.name,
            options: cleanedOptions,
        })).put(`/product-attributes/${editingAttribute.value.id}`, {
            preserveScroll: true,
            onSuccess: () => closeManageAttributesModal(),
        });
    } else {
        attributeModalForm.transform(() => ({
            name: attributeModalForm.name,
            options: cleanedOptions,
        })).post(`/products/${productData.value.id}/attributes`, {
            preserveScroll: true,
            onSuccess: () => closeManageAttributesModal(),
        });
    }
};

const deleteAttribute = (attr) => {
    triggerDeleteConfirm(
        'Hapus Atribut Produk',
        `Apakah Anda yakin ingin menghapus atribut "${attr.name}" beserta seluruh opsi nilainya?`,
        () => {
            router.delete(`/product-attributes/${attr.id}`, { preserveScroll: true });
        }
    );
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

const toggleVariantActive = (variant) => {
    router.post(`/product-variants/${variant.id}`, {
        _method: 'put',
        product_id: variant.product_id,
        sku: variant.sku || '',
        barcode: variant.barcode || '',
        name_suffix: variant.name_suffix || '',
        receipt_name: variant.receipt_name || '',
        default_purchase_price: variant.default_purchase_price || 0,
        default_selling_price: variant.default_selling_price || 0,
        is_active: !variant.is_active,
        attribute_option_ids: variant.attribute_option_ids || [],
    }, {
        preserveScroll: true,
    });
};

const deleteVariant = (variant) => {
    triggerDeleteConfirm(
        'Hapus Varian Produk',
        `Apakah Anda yakin ingin menghapus varian "${variant.display_receipt_name}"?`,
        () => {
            router.delete(`/product-variants/${variant.id}`, { preserveScroll: true });
        }
    );
};

// Product Prices Modal & Handlers
const priceForm = useForm({
    product_variant_id: '',
    store_id: '',
    purchase_price: 0,
    selling_price: 0,
    markup: 0,
    markup_type: 'amount',
    is_active: true,
});

const openCreatePriceModal = () => {
    editingPrice.value = null;
    priceForm.clearErrors();
    priceForm.product_variant_id = variantList.value[0]?.id || '';
    priceForm.store_id = isOwner.value ? '' : currentUser.value.store_id;
    priceForm.purchase_price = variantList.value[0]?.default_purchase_price || 0;
    priceForm.selling_price = variantList.value[0]?.default_selling_price || 0;
    priceForm.markup = 0;
    priceForm.markup_type = 'amount';
    priceForm.is_active = true;
    showPriceModal.value = true;
};

const openEditPriceModal = (price) => {
    editingPrice.value = price;
    priceForm.clearErrors();
    priceForm.product_variant_id = price.product_variant_id;
    priceForm.store_id = price.store_id || '';
    priceForm.purchase_price = price.purchase_price;
    priceForm.selling_price = price.selling_price;
    priceForm.markup = price.markup || 0;
    priceForm.markup_type = price.markup_type || 'amount';
    priceForm.is_active = !!price.is_active;
    showPriceModal.value = true;
};

const closePriceModal = () => {
    showPriceModal.value = false;
    editingPrice.value = null;
};

const submitPriceForm = () => {
    const payload = {
        product_variant_id: priceForm.product_variant_id,
        store_id: priceForm.store_id ? priceForm.store_id : null,
        purchase_price: priceForm.purchase_price,
        selling_price: priceForm.selling_price,
        markup: priceForm.markup,
        markup_type: priceForm.markup_type,
        is_active: priceForm.is_active,
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => closePriceModal(),
    };

    if (editingPrice.value) {
        priceForm.transform(() => ({
            ...payload,
            _method: 'put',
        })).post(`/product-prices/${editingPrice.value.id}`, options);
        return;
    }

    priceForm.transform(() => payload).post('/product-prices', options);
};

const togglePriceActive = (price) => {
    router.post(`/product-prices/${price.id}`, {
        _method: 'put',
        product_variant_id: price.product_variant_id,
        store_id: price.store_id ? price.store_id : null,
        purchase_price: price.purchase_price,
        selling_price: price.selling_price,
        markup: price.markup || 0,
        markup_type: price.markup_type || 'amount',
        is_active: !price.is_active,
    }, {
        preserveScroll: true,
    });
};

const deletePrice = (price) => {
    triggerDeleteConfirm(
        'Hapus Harga Toko',
        `Apakah Anda yakin ingin menghapus konfigurasi harga varian ${price.variant_display_name} untuk ${price.store_name}?`,
        () => {
            router.delete(`/product-prices/${price.id}`, { preserveScroll: true });
        }
    );
};

// Product Discounts Modal & Handlers
const discountForm = useForm({
    product_variant_id: '',
    store_id: '',
    discount_type_id: '',
    type: 'percent',
    value: 0,
});

const openCreateDiscountModal = () => {
    editingDiscount.value = null;
    discountForm.clearErrors();
    discountForm.product_variant_id = variantList.value[0]?.id || '';
    discountForm.store_id = isOwner.value ? '' : currentUser.value.store_id;
    discountForm.discount_type_id = discountTypeOptions.value[0]?.value || '';
    discountForm.type = 'percent';
    discountForm.value = 0;
    showDiscountModal.value = true;
};

const openEditDiscountModal = (discount) => {
    editingDiscount.value = discount;
    discountForm.clearErrors();
    discountForm.product_variant_id = discount.product_variant_id;
    discountForm.store_id = discount.store_id || '';
    discountForm.discount_type_id = discount.discount_type_id;
    discountForm.type = discount.type;
    discountForm.value = discount.value;
    showDiscountModal.value = true;
};

const closeDiscountModal = () => {
    showDiscountModal.value = false;
    editingDiscount.value = null;
};

const submitDiscountForm = () => {
    const payload = {
        product_variant_id: discountForm.product_variant_id,
        store_id: discountForm.store_id ? discountForm.store_id : null,
        discount_type_id: discountForm.discount_type_id,
        type: discountForm.type,
        value: discountForm.value,
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => closeDiscountModal(),
    };

    if (editingDiscount.value) {
        discountForm.transform(() => ({
            ...payload,
            _method: 'put',
        })).post(`/product-discounts/${editingDiscount.value.id}`, options);
        return;
    }

    discountForm.transform(() => payload).post('/product-discounts', options);
};

const deleteDiscount = (discount) => {
    triggerDeleteConfirm(
        'Hapus Promo & Diskon',
        `Apakah Anda yakin ingin menghapus promo "${discount.discount_type_name}" untuk varian ${discount.variant_display_name}?`,
        () => {
            router.delete(`/product-discounts/${discount.id}`, { preserveScroll: true });
        }
    );
};

// Product Stocks Modal & Handlers
const stockForm = useForm({
    product_variant_id: '',
    warehouse_id: '',
    warehouse_location_id: '',
    quantity: 0,
    minimum_stock: 0,
    is_hidden: false,
});

const openCreateStockModal = () => {
    editingStock.value = null;
    stockForm.clearErrors();
    stockForm.product_variant_id = variantList.value[0]?.id || '';
    stockForm.warehouse_id = warehouseOptions.value[0]?.value || '';
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

// Options for dropdown selects
const productCategoryOptions = computed(() => props.options?.productCategories || []);
const brandOptions = computed(() => props.options?.brands || []);
const unitOptions = computed(() => props.options?.units || []);
const pureStoreOptions = computed(() => props.options?.stores || []);
const storeOptions = computed(() => [{ label: 'Semua Toko (Global)', value: '' }, ...(props.options?.stores || [])]);
const discountTypeOptions = computed(() => props.options?.discountTypes || []);
const warehouseOptions = computed(() => props.options?.warehouses || []);
const filteredLocationOptions = computed(() => {
    const locs = props.options?.warehouseLocations || [];
    if (!stockForm.warehouse_id) return [{ label: 'Tanpa Lokasi Spesifik', value: '' }];
    const matched = locs.filter((l) => l.warehouse_id === stockForm.warehouse_id);
    return [{ label: 'Tanpa Lokasi Spesifik', value: '' }, ...matched];
});
const variantSelectOptions = computed(() => variantList.value.map((v) => ({ label: v.display_receipt_name, value: v.id })));
const productSelectOptions = computed(() => [{ label: productData.value.name, value: productData.value.id }]);
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
                    class="inline-flex items-center justify-center gap-2 rounded-md border border-default bg-elevated/50 px-3.5 py-2 text-sm font-medium text-highlighted hover:bg-elevated transition-all"
                    @click="openCreateAttributeModal"
                >
                    <UIcon name="i-lucide-plus" class="size-4 text-primary" />
                    Tambah Atribut Produk
                </button>
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

                    <!-- Product Attributes Section -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wider text-muted">Atribut Khusus Produk Ini</span>
                            <button type="button" class="text-xs text-primary font-medium hover:underline flex items-center gap-1" @click="openCreateAttributeModal">
                                <UIcon name="i-lucide-plus" class="size-3.5" />
                                Tambah Atribut Baru
                            </button>
                        </div>

                        <div v-if="productAttributes.length > 0" class="space-y-2.5">
                            <div v-for="attr in productAttributes" :key="attr.id" class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-default p-3 bg-elevated/20">
                                <div class="space-y-1">
                                    <span class="text-xs font-bold text-highlighted uppercase tracking-wider">{{ attr.name }}</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span v-for="opt in attr.options" :key="opt.id" class="inline-flex items-center rounded-md bg-primary/10 border border-primary/20 px-2 py-0.5 text-xs font-medium text-primary">
                                            {{ opt.value }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button" class="p-1 text-muted hover:text-highlighted rounded hover:bg-elevated" title="Edit Atribut" @click="openEditAttributeModal(attr)">
                                        <UIcon name="i-lucide-pencil" class="size-3.5" />
                                    </button>
                                    <button type="button" class="p-1 text-red-500 hover:text-red-600 rounded hover:bg-red-500/10" title="Hapus Atribut" @click="deleteAttribute(attr)">
                                        <UIcon name="i-lucide-trash-2" class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-default p-3 text-xs text-muted">
                            Belum ada atribut khusus yang dibuat untuk produk ini. Klik tombol di atas untuk menambah atribut (misal: Volume, Viskositas, Ukuran).
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
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Status POS</th>
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
                                <USwitch
                                    :model-value="!!variant.is_active"
                                    color="primary"
                                    size="sm"
                                    title="Ubah Status Aktif Kasir"
                                    @update:model-value="toggleVariantActive(variant)"
                                />
                                <span class="ml-2 text-xs font-medium" :class="variant.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted'">
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
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-500/20 transition-all"
                                        title="Hapus Varian"
                                        @click="deleteVariant(variant)"
                                    >
                                        <UIcon name="i-lucide-trash-2" class="size-4" />
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
        <div v-if="activeTab === 'prices'" class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Manajemen Harga Per Toko (Product Prices)</h3>
                    <p class="text-sm text-muted">Konfigurasi harga jual dan harga beli spesifik per cabang bengkel/toko.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 transition-all shadow-sm"
                    @click="openCreatePriceModal"
                >
                    <UIcon name="i-lucide-plus" class="size-4" />
                    Tambah / Set Harga Toko
                </button>
            </div>

            <!-- Tabel Main: Harga Aktif Per Toko -->
            <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Varian Produk</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Cabang Toko</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Harga Beli Modal</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Harga Jual Kasir</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Status POS</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="price in allProductPrices" :key="price.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-highlighted">{{ price.variant_display_name }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="!price.store_id ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'" class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                    {{ price.store_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-muted">Rp {{ Number(price.purchase_price || 0).toLocaleString('id-ID') }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ Number(price.selling_price || 0).toLocaleString('id-ID') }}</td>
                            <td class="px-4 py-3 text-sm">
                                <USwitch
                                    :model-value="!!price.is_active"
                                    color="primary"
                                    size="sm"
                                    title="Ubah Status Aktif Kasir"
                                    @update:model-value="togglePriceActive(price)"
                                />
                                <span class="ml-2 text-xs font-medium" :class="price.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted'">
                                    {{ price.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-default bg-elevated/50 text-muted hover:bg-elevated hover:text-highlighted transition-all"
                                        title="Edit Harga Toko"
                                        @click="openEditPriceModal(price)"
                                    >
                                        <UIcon name="i-lucide-pencil" class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-500/20 transition-all"
                                        title="Hapus Harga Toko"
                                        @click="deletePrice(price)"
                                    >
                                        <UIcon name="i-lucide-trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="allProductPrices.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-muted">Belum ada konfigurasi harga per toko.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Tabel Sub-Section: Riwayat Perubahan Harga (Product Price Histories) -->
            <div class="space-y-3 pt-4 border-t border-default">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <UIcon name="i-lucide-history" class="size-5 text-primary" />
                        <h4 class="text-base font-semibold text-highlighted">Riwayat Perubahan Harga (Price Histories)</h4>
                    </div>
                    <span class="text-xs text-muted">Audit trail perubahan harga modal & harga jual</span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                    <table class="min-w-full divide-y divide-default">
                        <thead class="bg-elevated/40">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-muted">Tanggal Log</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-muted">Varian Produk</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-muted">Cabang Toko</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-muted">Snapshot Harga Beli</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-muted">Snapshot Harga Jual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-default">
                            <tr v-for="history in allProductPriceHistories" :key="history.id" class="hover:bg-elevated/20 transition-colors">
                                <td class="px-4 py-2.5 text-xs font-medium text-highlighted">{{ history.date || history.created_at || '-' }}</td>
                                <td class="px-4 py-2.5 text-xs text-highlighted font-medium">{{ history.variant_display_name }}</td>
                                <td class="px-4 py-2.5 text-xs text-muted">
                                    <span :class="!history.store_id ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'" class="inline-flex rounded-full border px-2 py-0.5 text-xs font-medium">
                                        {{ history.store_name }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs text-muted">Rp {{ Number(history.purchase_price || 0).toLocaleString('id-ID') }}</td>
                                <td class="px-4 py-2.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">Rp {{ Number(history.selling_price || 0).toLocaleString('id-ID') }}</td>
                            </tr>
                            <tr v-if="allProductPriceHistories.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-xs text-muted">Belum ada catatan riwayat perubahan harga.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 4: STOK PER GUDANG -->
        <div v-if="activeTab === 'stocks'" class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-highlighted">Informasi Stok Gudang (Product Stocks)</h3>
                    <p class="text-sm text-muted">Monitoring jumlah stok fisik di setiap lokasi gudang penyimpan.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 transition-all shadow-sm"
                    @click="openCreateStockModal"
                >
                    <UIcon name="i-lucide-plus" class="size-4" />
                    Tambah / Inisialisasi Stok Gudang
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Varian Produk</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Nama Gudang</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Lokasi Spesifik Gudang</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Jumlah Stok Fisik</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Min Stock Alert</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="stock in allProductStocks" :key="stock.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-highlighted">{{ stock.variant_display_name }}</td>
                            <td class="px-4 py-3 text-sm text-highlighted font-medium">{{ stock.warehouse_name }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ stock.warehouse_location_name }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-blue-600 dark:text-blue-400">
                                {{ stock.quantity }} {{ productData.unit?.name || 'Pcs' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-muted">
                                {{ stock.minimum_stock }} {{ productData.unit?.name || 'Pcs' }}
                            </td>
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
                                        title="Hapus Stok Gudang"
                                        @click="deleteStock(stock)"
                                    >
                                        <UIcon name="i-lucide-trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="allProductStocks.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-muted">Belum ada stok gudang terdaftar untuk produk ini.</td>
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
                    <p class="text-sm text-muted">Daftar aturan diskon yang dikonfigurasikan pada varian produk ini.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 transition-all shadow-sm"
                    @click="openCreateDiscountModal"
                >
                    <UIcon name="i-lucide-plus" class="size-4" />
                    Tambah Promo / Diskon
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-default bg-default shadow-sm">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Varian Produk</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Berlaku di Toko</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Nama Promo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Tipe Potongan</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted">Potongan Diskon</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="discount in allProductDiscounts" :key="discount.id" class="hover:bg-elevated/20 transition-colors">
                            <td class="px-4 py-3 text-sm font-medium text-highlighted">{{ discount.variant_display_name }}</td>
                            <td class="px-4 py-3 text-sm">
                                <span :class="!discount.store_id ? 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20'" class="inline-flex rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                    {{ discount.store_name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-highlighted font-medium">{{ discount.discount_type_name }}</td>
                            <td class="px-4 py-3 text-sm text-muted">{{ discount.type_label }}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-emerald-600 dark:text-emerald-400">{{ discount.formatted_value }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-default bg-elevated/50 text-muted hover:bg-elevated hover:text-highlighted transition-all"
                                        title="Edit Diskon"
                                        @click="openEditDiscountModal(discount)"
                                    >
                                        <UIcon name="i-lucide-pencil" class="size-4" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 size-8 justify-center rounded-md border border-red-500/20 bg-red-500/10 text-red-600 dark:text-red-400 hover:bg-red-500/20 transition-all"
                                        title="Hapus Diskon"
                                        @click="deleteDiscount(discount)"
                                    >
                                        <UIcon name="i-lucide-trash-2" class="size-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="allProductDiscounts.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-muted">Belum ada promo atau diskon terdaftar.</td>
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

        <!-- MODAL TAMBAH / EDIT ATRIBUT PRODUK -->
        <div v-if="showManageAttributesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-xl bg-default p-6 shadow-2xl border border-default space-y-5">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-highlighted">
                            {{ editingAttribute ? 'Edit Atribut Produk' : 'Tambah Atribut Produk' }}
                        </h2>
                        <p class="text-xs text-muted">Input nama atribut dan opsi nilainya untuk produk "{{ productData.name }}".</p>
                    </div>
                    <button class="rounded-md p-1.5 hover:bg-elevated" type="button" @click="closeManageAttributesModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <form class="space-y-5" @submit.prevent="submitAttributeForm">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Nama Atribut *</span>
                        <input v-model="attributeModalForm.name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" placeholder="Contoh: Ukuran, Warna, Viskositas" required />
                        <span v-if="attributeModalForm.errors.name" class="text-xs text-red-600">{{ attributeModalForm.errors.name }}</span>
                    </label>

                    <!-- Interactive Option Rows -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium">Opsi Nilai Atribut *</span>
                            <button
                                type="button"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                                @click="addOptionRow"
                            >
                                <UIcon name="i-lucide-plus" class="size-3.5" />
                                Tambah Opsi Nilai
                            </button>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(option, idx) in attributeModalForm.options"
                                :key="idx"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-model="attributeModalForm.options[idx]"
                                    class="flex-1 rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary"
                                    type="text"
                                    :placeholder="`Opsi ${idx + 1} (Contoh: ${idx === 0 ? '0.8L' : idx === 1 ? '1L' : '1.2L'})`"
                                    required
                                />
                                <button
                                    type="button"
                                    class="rounded-md border border-default p-2 text-muted hover:bg-red-500/10 hover:text-red-500 transition-colors"
                                    title="Hapus Opsi"
                                    @click="removeOptionRow(idx)"
                                >
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                            </div>
                        </div>
                        <span v-if="attributeModalForm.errors.options" class="text-xs text-red-600">{{ attributeModalForm.errors.options }}</span>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-default pt-4">
                        <button type="button" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated" @click="closeManageAttributesModal">
                            Batal
                        </button>
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" :disabled="attributeModalForm.processing">
                            {{ editingAttribute ? 'Simpan Perubahan' : 'Tambah Atribut' }}
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
                    :attributes="productAttributes"
                    :existing-media="editingVariant ? (editingVariant.images || []) : []"
                    :submit-label="editingVariant ? 'Simpan Perubahan' : 'Buat Variant'"
                    :show-cancel="true"
                    cancel-label="Batal"
                    @submit="submitVariantForm"
                    @cancel="closeVariantModal"
                />
            </div>
        </div>

        <!-- MODAL TAMBAH / EDIT HARGA PER TOKO -->
        <div v-if="showPriceModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-xl bg-default p-6 shadow-2xl border border-default space-y-5">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-highlighted">
                            {{ editingPrice ? 'Edit Harga Per Toko' : 'Konfigurasi Harga Per Toko Baru' }}
                        </h2>
                        <p class="text-xs text-muted">Perubahan harga akan otomatis dicatat ke dalam Riwayat Perubahan Harga.</p>
                    </div>
                    <button class="rounded-md p-1.5 hover:bg-elevated" type="button" @click="closePriceModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitPriceForm">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Varian Produk *</span>
                        <USelect v-model="priceForm.product_variant_id" :items="variantSelectOptions" class="w-full" placeholder="Pilih Varian" required />
                        <span v-if="priceForm.errors.product_variant_id" class="text-xs text-red-600">{{ priceForm.errors.product_variant_id }}</span>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Cabang Toko / Bengkel</span>
                        <USelect v-model="priceForm.store_id" :items="isOwner ? storeOptions : pureStoreOptions" :disabled="!isOwner" class="w-full" placeholder="Pilih Toko Spesifik atau Global" />
                        <span v-if="!isOwner" class="text-xs text-muted">🔒 Lokasi toko ter-lock otomatis sesuai cabang tempat kerja Anda.</span>
                        <span v-else class="text-xs text-muted">Jika memilih "Semua Toko (Global)", harga ini berlaku untuk seluruh cabang toko.</span>
                        <span v-if="priceForm.errors.store_id" class="text-xs text-red-600">{{ priceForm.errors.store_id }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Harga Beli Modal (Rp) *</span>
                            <input
                                v-model="priceForm.purchase_price"
                                class="w-full rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                            <span v-if="priceForm.errors.purchase_price" class="text-xs text-red-600">{{ priceForm.errors.purchase_price }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Harga Jual Kasir (Rp) *</span>
                            <input
                                v-model="priceForm.selling_price"
                                class="w-full rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                            <span v-if="priceForm.errors.selling_price" class="text-xs text-red-600">{{ priceForm.errors.selling_price }}</span>
                        </label>
                    </div>

                    <label class="flex items-center gap-3 rounded-md border border-default p-3">
                        <input v-model="priceForm.is_active" class="size-4" type="checkbox" />
                        <span class="text-sm font-medium">Aktifkan harga ini untuk transaksi kasir</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 border-t border-default pt-4">
                        <button type="button" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated" @click="closePriceModal">
                            Batal
                        </button>
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" :disabled="priceForm.processing">
                            {{ editingPrice ? 'Simpan Perubahan' : 'Set Harga Toko' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL TAMBAH / EDIT PROMO & DISKON -->
        <div v-if="showDiscountModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-xs">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-xl bg-default p-6 shadow-2xl border border-default space-y-5">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <div>
                        <h2 class="text-lg font-bold text-highlighted">
                            {{ editingDiscount ? 'Edit Promo & Diskon' : 'Tambah Promo / Diskon Baru' }}
                        </h2>
                        <p class="text-xs text-muted">Konfigurasi potongan harga untuk varian produk ini.</p>
                    </div>
                    <button class="rounded-md p-1.5 hover:bg-elevated" type="button" @click="closeDiscountModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <form class="space-y-4" @submit.prevent="submitDiscountForm">
                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Varian Produk *</span>
                        <USelect v-model="discountForm.product_variant_id" :items="variantSelectOptions" class="w-full" placeholder="Pilih Varian" required />
                        <span v-if="discountForm.errors.product_variant_id" class="text-xs text-red-600">{{ discountForm.errors.product_variant_id }}</span>
                    </label>

                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Berlaku di Toko / Bengkel</span>
                        <USelect v-model="discountForm.store_id" :items="isOwner ? storeOptions : pureStoreOptions" :disabled="!isOwner" class="w-full" placeholder="Pilih Toko Spesifik atau Global" />
                        <span v-if="!isOwner" class="text-xs text-muted">🔒 Lokasi toko ter-lock otomatis sesuai cabang tempat kerja Anda.</span>
                        <span v-else class="text-xs text-muted">Jika memilih "Semua Toko (Global)", promo ini berlaku untuk seluruh cabang toko.</span>
                        <span v-if="discountForm.errors.store_id" class="text-xs text-red-600">{{ discountForm.errors.store_id }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Nama Promo (Jenis Diskon) *</span>
                            <USelect v-model="discountForm.discount_type_id" :items="discountTypeOptions" class="w-full" placeholder="Pilih Jenis Diskon" required />
                            <span v-if="discountForm.errors.discount_type_id" class="text-xs text-red-600">{{ discountForm.errors.discount_type_id }}</span>
                        </label>

                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Tipe Potongan *</span>
                            <USelect v-model="discountForm.type" :items="[{ label: 'Persentase (%)', value: 'percent' }, { label: 'Nominal (Rp)', value: 'amount' }]" class="w-full" required />
                            <span v-if="discountForm.errors.type" class="text-xs text-red-600">{{ discountForm.errors.type }}</span>
                        </label>
                    </div>

                    <label class="grid gap-1 text-sm">
                        <span class="font-medium">Nilai Diskon *</span>
                        <div class="relative">
                            <input
                                v-model="discountForm.value"
                                class="w-full rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary"
                                type="number"
                                min="0"
                                step="0.01"
                                required
                            />
                        </div>
                        <span class="text-xs text-muted">
                            {{ discountForm.type === 'percent' ? 'Masukkan angka persen (contoh: 10 untuk 10%)' : 'Masukkan jumlah nominal Rupiah (contoh: 15000)' }}
                        </span>
                        <span v-if="discountForm.errors.value" class="text-xs text-red-600">{{ discountForm.errors.value }}</span>
                    </label>

                    <div class="flex items-center justify-end gap-3 border-t border-default pt-4">
                        <button type="button" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated" @click="closeDiscountModal">
                            Batal
                        </button>
                        <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90" :disabled="discountForm.processing">
                            {{ editingDiscount ? 'Simpan Perubahan' : 'Tambah Diskon' }}
                        </button>
                    </div>
                </form>
            </div>
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
                        <USelect v-model="stockForm.product_variant_id" :items="variantSelectOptions" class="w-full" placeholder="Pilih Varian" required />
                        <span v-if="stockForm.errors.product_variant_id" class="text-xs text-red-600">{{ stockForm.errors.product_variant_id }}</span>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1 text-sm">
                            <span class="font-medium">Gudang *</span>
                            <USelect v-model="stockForm.warehouse_id" :items="warehouseOptions" class="w-full" placeholder="Pilih Gudang" required />
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
