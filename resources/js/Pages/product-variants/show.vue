<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Product Variant', panelId: 'product-variants-show' }],
});

defineProps({
    productVariant: Object,
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">{{ productVariant.data.name_suffix || productVariant.data.sku || 'Product Variant' }}</h2>
                <p class="text-sm text-muted">Detail variant dan attribute yang terhubung.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-end">
                <Link href="/product-variants" class="inline-flex items-center justify-center gap-2 rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">
                    <UIcon name="i-lucide-arrow-left" class="size-4" />
                    Kembali
                </Link>
                <Link :href="`/product-variants/${productVariant.data.id}/edit`" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90">
                    <UIcon name="i-lucide-pencil" class="size-4" />
                    Edit
                </Link>
            </div>
        </div>

        <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.7fr)]">
            <UCard :ui="{ body: 'p-5!' }">
                <div class="mb-5">
                    <h3 class="text-base font-semibold text-highlighted">Informasi Variant</h3>
                    <p class="text-sm text-muted">Data utama variant produk.</p>
                </div>
                <dl class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-muted">Produk</dt>
                        <dd class="font-medium">{{ productVariant.data.product?.name || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Status</dt>
                        <dd class="font-medium">{{ productVariant.data.is_active ? 'Aktif' : 'Nonaktif' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">SKU</dt>
                        <dd class="font-medium">{{ productVariant.data.sku || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Barcode</dt>
                        <dd class="font-medium">{{ productVariant.data.barcode || '-' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-sm text-muted">Nama Tambahan</dt>
                        <dd class="font-medium">{{ productVariant.data.name_suffix || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Harga Beli Default</dt>
                        <dd class="font-medium">{{ productVariant.data.default_purchase_price }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted">Harga Jual Default</dt>
                        <dd class="font-medium">{{ productVariant.data.default_selling_price }}</dd>
                    </div>
                </dl>
            </UCard>

            <UCard :ui="{ body: 'p-5!' }">
                <div>
                    <h3 class="text-base font-semibold text-highlighted">Attribute Terpilih</h3>
                    <p class="text-sm text-muted">{{ productVariant.data.attribute_options?.length || 0 }} option terhubung</p>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span v-for="option in productVariant.data.attribute_options || []" :key="option.id" class="rounded-md bg-elevated px-2 py-1 text-xs text-highlighted">{{ option.label }}</span>
                    <span v-if="(productVariant.data.attribute_options || []).length === 0" class="text-sm text-muted">Tidak ada attribute dipilih.</span>
                </div>
            </UCard>
        </div>
    </div>
</template>
