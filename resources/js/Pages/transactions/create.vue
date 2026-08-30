<script setup>
import PosWorkspaceLayout from '../../Layouts/PosWorkspaceLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

defineOptions({
    layout: [PosWorkspaceLayout, { title: 'POS Kasir Bengkel', subtitle: 'Terminal Penjualan & Pelunasan Servis' }],
});

const props = defineProps({
    activeStoreId: String,
    isStoreLocked: Boolean,
    options: Object,
    readyServiceOrders: Object,
    variants: Array,
    preselectedServiceOrderId: String,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});
const isOwner = computed(() => !currentUser.value.store_id || currentUser.value.roles?.includes('owner'));

const form = useForm({
    store_id: props.activeStoreId || currentUser.value.store_id || props.options?.stores?.[0]?.value || '',
    customer_id: null,
    payment_id: props.options?.payments?.[0]?.value || null,
    type: 'retail', // 'retail' | 'service'
    service_order_id: null,
    universal_discount_mode: 'amount',
    universal_discount_value: 0,
    tax_rate: 0,
    paid_amount: 0,
    note: '',
    items: [],
});

// Service Order Selection Modal State
const serviceModalOpen = ref(false);
const serviceSearch = ref('');
const selectedServiceOrder = ref(null);

// Catalog Search State
const catalogSearch = ref('');
const categoryFilter = ref('');

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const variantsList = computed(() => {
    if (Array.isArray(props.variants)) return props.variants;
    if (Array.isArray(props.variants?.data)) return props.variants.data;
    return [];
});

const readyOrdersList = computed(() => {
    if (Array.isArray(props.readyServiceOrders)) return props.readyServiceOrders;
    if (Array.isArray(props.readyServiceOrders?.data)) return props.readyServiceOrders.data;
    return [];
});

const storesList = computed(() => props.options?.stores || []);
const customersList = computed(() => props.options?.customers || []);
const paymentOptions = computed(() => props.options?.payments || []);
const discountTypesList = computed(() => props.options?.discountTypes || []);

const currentStoreLabel = computed(() => {
    const found = storesList.value.find((s) => String(s.value) === String(form.store_id || props.activeStoreId));
    return found ? found.label : 'Toko Utama';
});

const handleStoreChange = (newStoreId) => {
    if (!newStoreId || String(newStoreId) === String(form.store_id)) return;

    if (form.items.length > 0) {
        if (!confirm('Mengubah toko akan mengosongkan item keranjang transaksi saat ini. Lanjutkan?')) {
            return;
        }
        form.items = [];
        form.service_order_id = null;
        form.type = 'retail';
        selectedServiceOrder.value = null;
    }

    form.store_id = newStoreId;

    router.get(
        route('transactions.create'),
        { store_id: newStoreId },
        {
            preserveState: true,
            preserveScroll: true,
            only: ['readyServiceOrders', 'activeStoreId'],
        }
    );
};

const onDiscountTypeChange = (item) => {
    if (!item.discount_type_id) {
        item.item_discount_mode = 'amount';
        item.item_discount_value = 0;
        return;
    }

    const variantId = item.product_variant_id;
    let variantObj = item.variant_ref;
    if (!variantObj && variantId) {
        variantObj = variantsList.value.find(v => String(v.id) === String(variantId));
    }

    if (variantObj && variantObj.discounts && variantObj.discounts.length > 0) {
        const matchingRule = variantObj.discounts.find(
            d => String(d.discount_type_id) === String(item.discount_type_id) &&
                (!d.store_id || String(d.store_id) === String(form.store_id))
        ) || variantObj.discounts.find(
            d => String(d.discount_type_id) === String(item.discount_type_id)
        );

        if (matchingRule) {
            item.item_discount_mode = matchingRule.type || 'amount';
            item.item_discount_value = parseFloat(matchingRule.value || 0);
        }
    }
};

const derivedCategories = computed(() => {
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

const filteredVariants = computed(() => {
    const searchVal = catalogSearch.value.trim().toLowerCase();
    const catVal = categoryFilter.value;

    return variantsList.value.filter((v) => {
        const vCatId = v.product?.category_id || v.category_id || v.product?.category?.id;
        const matchesCategory = !catVal || vCatId === catVal;

        const name = (v.display_receipt_name || v.name || v.product?.name || '').toLowerCase();
        const sku = (v.sku || '').toLowerCase();
        const barcode = (v.barcode || '').toLowerCase();

        const matchesSearch = !searchVal || name.includes(searchVal) || sku.includes(searchVal) || barcode.includes(searchVal);
        return matchesCategory && matchesSearch;
    });
});

const filteredServiceOrders = computed(() => {
    const searchVal = serviceSearch.value.trim().toLowerCase();
    if (!searchVal) return readyOrdersList.value;

    return readyOrdersList.value.filter((so) => {
        const num = (so.number || '').toLowerCase();
        const plate = (so.plate_number || '').toLowerCase();
        const name = (so.customer_name || '').toLowerCase();
        return num.includes(searchVal) || plate.includes(searchVal) || name.includes(searchVal);
    });
});

const getItemStock = (variant) => {
    if (!variant.stocks || !variant.stocks.length) return 0;
    const storeStocks = variant.stocks.filter(s => String(s.store_id) === String(form.store_id));
    if (storeStocks.length === 0) return 0;
    return storeStocks.reduce((sum, s) => sum + Number(s.quantity || 0), 0);
};

const getVariantImage = (variant) => {
    if (variant.image_url) return variant.image_url;
    if (variant.images && variant.images.length > 0) {
        return variant.images[0].thumb_url || variant.images[0].url;
    }
    if (variant.product?.images && variant.product.images.length > 0) {
        return variant.product.images[0].thumb_url || variant.product.images[0].url;
    }
    return null;
};

const isVariantLabor = (variant) => {
    const itemType = variant.product?.item_type || variant.item_type || 'part';
    return itemType === 'labor';
};

const isVariantOutOfStock = (variant) => {
    if (isVariantLabor(variant)) return false;
    return getItemStock(variant) <= 0;
};

// Add product variant to cart
const addVariantToCart = (variant) => {
    if (isVariantOutOfStock(variant)) return;

    const itemType = isVariantLabor(variant) ? 'labor' : 'part';

    const existingIndex = form.items.findIndex(
        i => i.item_type === itemType && String(i.product_variant_id) === String(variant.id)
    );

    if (existingIndex >= 0) {
        form.items[existingIndex].quantity += 1;
    } else {
        const variantName = variant.display_receipt_name || variant.name || variant.product?.name || (itemType === 'labor' ? 'Jasa Servis' : 'Sparepart');
        form.items.push({
            item_type: itemType,
            product_variant_id: variant.id,
            description: variantName,
            quantity: 1,
            unit_price: Number(variant.default_selling_price || variant.price || variant.retail_price || 0),
            discount_type_id: null,
            item_discount_mode: 'amount',
            item_discount_value: 0,
            variant_ref: variant,
        });
    }
};

// Import Service Order to Cart
const importServiceOrder = (so) => {
    selectedServiceOrder.value = so;
    form.service_order_id = so.id;
    form.type = 'service';
    form.customer_id = so.customer_id || null;

    // Reset items and populate from SO
    form.items = [];
    if (so.items && so.items.length > 0) {
        so.items.forEach((item) => {
            form.items.push({
                item_type: item.item_type || 'part',
                product_variant_id: item.product_variant_id || null,
                description: item.description || 'Jasa Servis',
                quantity: item.quantity || 1,
                unit_price: item.unit_price || 0,
                item_discount_mode: 'amount',
                item_discount_value: 0,
            });
        });
    }

    serviceModalOpen.value = false;
};

const clearServiceOrderImport = () => {
    selectedServiceOrder.value = null;
    form.service_order_id = null;
    form.type = 'retail';
};

const getItemLineTotal = (item) => {
    const qty = item.quantity || 1;
    const price = item.unit_price || 0;
    const discVal = item.item_discount_value || 0;
    let disc = 0;
    if (item.item_discount_mode === 'percent') {
        disc = (price * (discVal / 100)) * qty;
    } else {
        disc = discVal * qty;
    }
    return Math.max(0, (price * qty) - disc);
};

const removeCartItem = (index) => {
    form.items.splice(index, 1);
};

// Calculated Totals
const cartSubtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + ((item.unit_price || 0) * (item.quantity || 1)), 0);
});

const itemDiscountTotal = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = item.quantity || 1;
        const price = item.unit_price || 0;
        const discVal = item.item_discount_value || 0;
        let disc = 0;
        if (item.item_discount_mode === 'percent') {
            disc = (price * (discVal / 100)) * qty;
        } else {
            disc = discVal * qty;
        }
        return sum + disc;
    }, 0);
});

const subtotalAfterItemDisc = computed(() => Math.max(0, cartSubtotal.value - itemDiscountTotal.value));

const universalDiscountAmount = computed(() => {
    const val = form.universal_discount_value || 0;
    if (form.universal_discount_mode === 'percent') {
        return subtotalAfterItemDisc.value * (val / 100);
    }
    return val;
});

const taxableAmount = computed(() => Math.max(0, subtotalAfterItemDisc.value - universalDiscountAmount.value));

const taxTotal = computed(() => {
    return taxableAmount.value * ((form.tax_rate || 0) / 100);
});

const grandTotal = computed(() => taxableAmount.value + taxTotal.value);

const changeAmount = computed(() => {
    return Math.max(0, (form.paid_amount || 0) - grandTotal.value);
});

const setExactPayment = () => {
    form.paid_amount = grandTotal.value;
};

const setQuickCash = (amount) => {
    form.paid_amount = amount;
};

const submitCheckout = () => {
    if (form.items.length === 0) return;

    form.post('/transactions', {
        preserveScroll: true,
    });
};

onMounted(() => {
    if (props.preselectedServiceOrderId) {
        const targetSo = readyOrdersList.value.find(o => String(o.id) === String(props.preselectedServiceOrderId));
        if (targetSo) {
            importServiceOrder(targetSo);
        }
    }
});
</script>

<template>
    <div class="h-full flex flex-col gap-3 overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 flex-1 overflow-hidden min-h-0">
            <!-- Left Panel: Product Catalog & Service Import (7 Cols) -->
            <div class="lg:col-span-7 flex flex-col bg-elevated/40 border border-default rounded-xl p-3 overflow-hidden shadow-sm">
                <!-- Search & Filters Bar -->
                <div class="flex items-center gap-2 mb-3">
                    <div class="relative flex-1">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input
                            v-model="catalogSearch"
                            type="search"
                            placeholder="Cari sparepart, oli, SKU..."
                            class="w-full rounded-lg border border-default bg-default py-1.5 pl-9 pr-3 text-xs outline-none focus:border-primary"
                        />
                    </div>

                    <!-- Store Selection: Locked Badge for Staff, Switcher Dropdown for Owner/Admin -->
                    <div v-if="props.isStoreLocked" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-default bg-elevated/70 text-xs font-semibold text-muted shrink-0 select-none" title="Cabang Terkunci">
                        <UIcon name="i-lucide-store" class="size-3.5 text-primary" />
                        <span>{{ currentStoreLabel }}</span>
                        <UIcon name="i-lucide-lock" class="size-3 text-muted/60" />
                    </div>

                    <select
                        v-else-if="storesList.length > 0"
                        :value="form.store_id"
                        @change="handleStoreChange($event.target.value)"
                        class="rounded-lg border border-default bg-default px-2.5 py-1.5 text-xs font-semibold outline-none focus:border-primary shrink-0"
                    >
                        <option v-for="st in storesList" :key="st.value" :value="st.value">{{ st.label }}</option>
                    </select>

                    <select
                        v-model="categoryFilter"
                        class="rounded-lg border border-default bg-default px-2.5 py-1.5 text-xs outline-none focus:border-primary"
                    >
                        <option value="">Semua Kategori</option>
                        <option v-for="cat in derivedCategories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                    </select>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition-colors shrink-0 shadow-sm"
                        @click="serviceModalOpen = true"
                    >
                        <UIcon name="i-lucide-wrench" class="size-4" />
                        <span>Impor SPK Servis</span>
                        <span v-if="readyOrdersList.length > 0" class="size-5 rounded-full bg-white text-blue-600 text-[10px] font-black flex items-center justify-center">
                            {{ readyOrdersList.length }}
                        </span>
                    </button>
                </div>

                <!-- Products Grid -->
                <div v-if="filteredVariants.length === 0" class="flex-1 flex flex-col items-center justify-center text-muted py-12">
                    <UIcon name="i-lucide-package-search" class="size-10 mb-2 opacity-50" />
                    <p class="text-xs">Tidak ada produk ditemukan.</p>
                </div>

                <div v-else class="flex-1 overflow-y-auto grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 auto-rows-max content-start gap-2.5 pr-1">
                    <div
                        v-for="variant in filteredVariants"
                        :key="variant.id"
                        class="bg-default border border-default rounded-xl p-2.5 flex flex-col justify-between transition-all group h-full select-none"
                        :class="[
                            isVariantOutOfStock(variant)
                                ? 'opacity-50 grayscale bg-elevated/40 cursor-not-allowed border-dashed'
                                : 'hover:border-emerald-500/60 cursor-pointer hover:shadow-md'
                        ]"
                        @click="addVariantToCart(variant)"
                    >
                        <div class="space-y-1.5">
                            <!-- Image Thumbnail Preview -->
                            <div class="w-full h-24 rounded-lg overflow-hidden bg-elevated/60 border border-default flex items-center justify-center relative shrink-0">
                                <!-- Promo Discount Badge Overlay -->
                                <div v-if="variant.discounts && variant.discounts.length > 0" class="absolute top-1 left-1 z-10 bg-amber-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded shadow-xs uppercase tracking-wider flex items-center gap-0.5">
                                    <UIcon name="i-lucide-tag" class="size-3" />
                                    <span>Promo</span>
                                </div>

                                <img
                                    v-if="getVariantImage(variant)"
                                    :src="getVariantImage(variant)"
                                    :alt="variant.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200"
                                />
                                <div v-else class="flex flex-col items-center justify-center text-muted/30">
                                    <UIcon name="i-lucide-package" class="size-8" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between gap-1">
                                <span class="text-[10px] font-mono text-muted uppercase truncate max-w-[65%]">{{ variant.sku || 'N/A' }}</span>
                                <span
                                    v-if="isVariantLabor(variant)"
                                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0 bg-purple-500/10 text-purple-600 border border-purple-500/20"
                                >
                                    Jasa
                                </span>
                                <span
                                    v-else-if="getItemStock(variant) > 0"
                                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0 bg-emerald-500/10 text-emerald-600 border border-emerald-500/20"
                                >
                                    Stok: {{ getItemStock(variant) }}
                                </span>
                                <span
                                    v-else
                                    class="text-[10px] font-bold px-1.5 py-0.5 rounded-full shrink-0 bg-rose-500/10 text-rose-600 border border-rose-500/20"
                                >
                                    Stok Habis
                                </span>
                            </div>

                            <p class="text-xs font-bold text-highlighted line-clamp-2 leading-tight group-hover:text-emerald-600 transition-colors">
                                {{ variant.display_receipt_name || variant.name || variant.product?.name }}
                            </p>

                            <span v-if="variant.product?.category?.name || variant.product?.category_name" class="inline-block text-[9px] font-medium text-muted bg-elevated px-1.5 py-0.5 rounded border border-default">
                                {{ variant.product?.category?.name || variant.product?.category_name }}
                            </span>
                        </div>

                        <div class="mt-3 pt-2 border-t border-default/60 flex items-center justify-between">
                            <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 font-mono">
                                {{ formatCurrency(variant.default_selling_price || variant.price) }}
                            </span>
                            <span
                                v-if="isVariantOutOfStock(variant)"
                                class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-600 font-bold text-[10px] border border-rose-500/20"
                            >
                                Habis
                            </span>
                            <span
                                v-else
                                class="size-6 rounded-lg bg-emerald-500/10 text-emerald-600 flex items-center justify-center font-bold text-xs group-hover:bg-emerald-600 group-hover:text-white transition-all shadow-xs"
                            >
                                +
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Cart, Customer, Payment & Checkout (5 Cols) -->
            <div class="lg:col-span-5 flex flex-col bg-elevated/40 border border-default rounded-xl p-3 overflow-hidden shadow-sm">
                <!-- Customer & SPK Header Badge -->
                <div class="space-y-2 mb-3 pb-3 border-b border-default">
                    <!-- SPK Badge if imported -->
                    <div v-if="selectedServiceOrder" class="flex items-center justify-between bg-blue-500/10 border border-blue-500/30 px-3 py-1.5 rounded-lg text-xs">
                        <div class="flex items-center gap-2">
                            <UIcon name="i-lucide-wrench" class="size-4 text-blue-500" />
                            <div>
                                <span class="font-mono font-bold text-blue-600">{{ selectedServiceOrder.number }}</span>
                                <span class="text-[11px] text-muted ml-1">({{ selectedServiceOrder.plate_number }})</span>
                            </div>
                        </div>
                        <button type="button" class="text-rose-500 hover:text-rose-600 text-xs font-bold" @click="clearServiceOrderImport">
                            Batal Impor
                        </button>
                    </div>

                    <!-- Customer Selector -->
                    <div class="flex items-center gap-2">
                        <select
                            v-model="form.customer_id"
                            class="flex-1 rounded-lg border border-default bg-default px-2.5 py-1.5 text-xs text-highlighted outline-none focus:border-primary font-semibold"
                        >
                            <option :value="null">-- Walk-In Customer (Umum) --</option>
                            <option v-for="cust in customersList" :key="cust.id" :value="cust.id">
                                {{ cust.name }} {{ cust.phone ? `(${cust.phone})` : '' }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Cart Items List -->
                <div class="flex-1 overflow-y-auto space-y-2 pr-0.5">
                    <div v-if="form.items.length === 0" class="h-full flex flex-col items-center justify-center text-muted py-12">
                        <UIcon name="i-lucide-shopping-cart" class="size-12 mb-2 opacity-40" />
                        <p class="text-xs">Keranjang transaksi masih kosong.</p>
                        <p class="text-[11px] text-muted">Pilih barang dari katalog atau impor SPK Servis.</p>
                    </div>

                    <div
                        v-for="(item, idx) in form.items"
                        :key="idx"
                        class="bg-default border border-default rounded-lg p-2.5 flex flex-col gap-2 shadow-xs"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span
                                    class="inline-block text-[10px] font-bold px-1.5 py-0.2 rounded uppercase"
                                    :class="item.item_type === 'labor' ? 'bg-purple-500/10 text-purple-600' : 'bg-emerald-500/10 text-emerald-600'"
                                >
                                    {{ item.item_type === 'labor' ? 'Jasa' : 'Part' }}
                                </span>
                                <span class="text-xs font-bold text-highlighted">{{ item.description }}</span>
                            </div>
                            <button type="button" class="text-rose-500 hover:text-rose-600 p-0.5" @click="removeCartItem(idx)">
                                <UIcon name="i-lucide-trash-2" class="size-3.5" />
                            </button>
                        </div>

                        <!-- Price & Qty Adjust -->
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <div class="flex items-center gap-1">
                                <span class="text-muted font-mono text-[11px]">Rp</span>
                                <input
                                    v-model.number="item.unit_price"
                                    type="number"
                                    min="0"
                                    class="w-24 rounded border border-default bg-elevated px-2 py-0.5 text-xs font-mono font-bold text-highlighted outline-none focus:border-primary"
                                />
                            </div>

                            <!-- Qty Control -->
                            <div class="flex items-center gap-1 border border-default rounded bg-elevated p-0.5">
                                <button
                                    type="button"
                                    class="size-5 rounded flex items-center justify-center hover:bg-default text-muted"
                                    @click="item.quantity = Math.max(1, item.quantity - 1)"
                                >
                                    -
                                </button>
                                <span class="w-6 text-center font-mono font-bold text-xs">{{ item.quantity }}</span>
                                <button
                                    type="button"
                                    class="size-5 rounded flex items-center justify-center hover:bg-default text-muted"
                                    @click="item.quantity += 1"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Line Total -->
                            <div class="text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(getItemLineTotal(item)) }}
                            </div>
                        </div>

                        <!-- Item Discount Selection Row -->
                        <div class="pt-1.5 border-t border-default/50 flex items-center justify-between gap-2 text-[11px]">
                            <div class="flex items-center gap-1 min-w-0 flex-1">
                                <span class="text-muted text-[10px] shrink-0">Diskon:</span>
                                <select
                                    v-model="item.discount_type_id"
                                    class="w-full rounded border border-default bg-elevated px-1.5 py-0.5 text-[10px] outline-none font-medium text-highlighted truncate"
                                    @change="onDiscountTypeChange(item)"
                                >
                                    <option :value="null">-- Pilih Diskon --</option>
                                    <option v-for="dt in discountTypesList" :key="dt.id" :value="dt.id">
                                        {{ dt.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center gap-1 shrink-0">
                                <select v-model="item.item_discount_mode" class="rounded border border-default bg-elevated px-1 py-0.5 text-[10px] outline-none font-bold">
                                    <option value="amount">Rp</option>
                                    <option value="percent">%</option>
                                </select>
                                <input
                                    v-model.number="item.item_discount_value"
                                    type="number"
                                    min="0"
                                    class="w-16 rounded border border-default bg-elevated px-1 py-0.5 text-right font-mono text-xs outline-none font-bold"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary & Calculator -->
                <div class="mt-3 pt-3 border-t border-default space-y-2.5">
                    <!-- Totals Breakdown -->
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between text-muted">
                            <span>Subtotal Items</span>
                            <span class="font-mono font-bold text-highlighted">{{ formatCurrency(cartSubtotal) }}</span>
                        </div>

                        <!-- Universal Discount -->
                        <div class="flex items-center justify-between text-muted">
                            <div class="flex items-center gap-1">
                                <span>Diskon Transaksi:</span>
                                <select v-model="form.universal_discount_mode" class="rounded border border-default bg-default text-[10px] p-0.5">
                                    <option value="amount">Rp</option>
                                    <option value="percent">%</option>
                                </select>
                            </div>
                            <input
                                v-model.number="form.universal_discount_value"
                                type="number"
                                min="0"
                                class="w-24 rounded border border-default bg-default px-1.5 py-0.5 text-right font-mono text-xs outline-none"
                            />
                        </div>

                        <!-- Grand Total Highlight -->
                        <div class="flex justify-between items-center pt-2 border-t border-default/80">
                            <span class="text-sm font-black text-highlighted uppercase">Total Bayar</span>
                            <span class="text-xl font-black font-mono text-emerald-600 dark:text-emerald-400">{{ formatCurrency(grandTotal) }}</span>
                        </div>
                    </div>

                    <!-- Payment Method Select -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-[11px] font-bold text-muted block mb-1">Metode Pembayaran</label>
                            <select v-model="form.payment_id" class="w-full rounded-lg border border-default bg-default p-1.5 text-xs font-bold text-highlighted outline-none">
                                <option v-for="p in paymentOptions" :key="p.value" :value="p.value">{{ p.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[11px] font-bold text-muted block mb-1">Uang Dibayar (Rp)</label>
                            <input
                                v-model.number="form.paid_amount"
                                type="number"
                                min="0"
                                class="w-full rounded-lg border border-emerald-500/50 bg-default p-1.5 text-xs font-mono font-black text-emerald-600 dark:text-emerald-400 outline-none focus:border-emerald-500"
                            />
                        </div>
                    </div>

                    <!-- Quick Cash Suggestion Chips -->
                    <div class="flex items-center gap-1 overflow-x-auto pb-1">
                        <button
                            type="button"
                            class="px-2 py-0.5 rounded border border-emerald-500/40 bg-emerald-500/10 text-emerald-600 text-[10px] font-bold hover:bg-emerald-500/20 shrink-0"
                            @click="setExactPayment"
                        >
                            Uang Pas
                        </button>
                        <button
                            v-for="cash in [10000, 20000, 50000, 100000]"
                            :key="cash"
                            type="button"
                            class="px-2 py-0.5 rounded border border-default bg-default text-[10px] font-mono text-muted hover:border-primary shrink-0"
                            @click="setQuickCash(cash)"
                        >
                            {{ formatCurrency(cash) }}
                        </button>
                    </div>

                    <!-- Change Amount Display -->
                    <div class="flex justify-between items-center p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-xs">
                        <span class="font-bold text-emerald-700 dark:text-emerald-300">Kembalian:</span>
                        <span class="font-mono font-black text-base text-emerald-600 dark:text-emerald-400">{{ formatCurrency(changeAmount) }}</span>
                    </div>

                    <!-- Submit Checkout Button -->
                    <button
                        type="button"
                        class="w-full py-2.5 rounded-xl bg-emerald-600 text-white font-extrabold text-sm hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-600/20 disabled:opacity-50 flex items-center justify-center gap-2"
                        :disabled="form.items.length === 0 || form.processing"
                        @click="submitCheckout"
                    >
                        <UIcon name="i-lucide-check-circle-2" class="size-5" />
                        <span>{{ form.processing ? 'Memproses POS...' : 'Bayar & Proses Transaksi' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Service Order Selection Modal -->
        <UModal v-model:open="serviceModalOpen" title="Pilih SPK Servis untuk Pelunasan">
            <template #content>
                <div class="p-6 space-y-4">
                    <div class="relative">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input
                            v-model="serviceSearch"
                            type="search"
                            placeholder="Cari Plat Nomor, Nama Pelanggan, No. SPK..."
                            class="w-full rounded-lg border border-default bg-default py-2 pl-9 pr-3 text-xs outline-none focus:border-primary"
                        />
                    </div>

                    <div v-if="filteredServiceOrders.length === 0" class="py-8 text-center text-muted text-xs">
                        Tidak ada SPK Servis siap bayar ditemukan.
                    </div>

                    <div v-else class="space-y-2.5 max-h-[350px] overflow-y-auto pr-1">
                        <div
                            v-for="so in filteredServiceOrders"
                            :key="so.id"
                            class="bg-default border border-default rounded-xl p-3 flex items-center justify-between hover:border-blue-500 transition-all shadow-xs"
                        >
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="bg-black text-amber-300 font-mono font-black text-xs px-2 py-0.5 rounded border border-amber-400/40">
                                        {{ so.plate_number }}
                                    </span>
                                    <span class="font-mono font-bold text-xs text-primary">{{ so.number }}</span>
                                </div>
                                <p class="text-xs font-bold text-highlighted mt-1">{{ so.vehicle_brand }} {{ so.vehicle_model }}</p>
                                <p class="text-[11px] text-muted">Pelanggan: {{ so.customer_name }}</p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs font-black text-emerald-600 font-mono mb-1.5">{{ formatCurrency(so.estimated_total) }}</p>
                                <button
                                    type="button"
                                    class="px-3 py-1 rounded bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition-colors shadow-xs"
                                    @click="importServiceOrder(so)"
                                >
                                    Impor ke Kasir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </UModal>
    </div>
</template>
