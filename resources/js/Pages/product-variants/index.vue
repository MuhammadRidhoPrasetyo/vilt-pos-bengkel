<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({
    layout: [DashboardLayout, { title: 'Product Variants', panelId: 'product-variants' }],
});

defineProps({
    records: Object,
    filters: Object,
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold">Product Variants</h2>
                <p class="text-sm text-muted">Kelola variant produk dan kombinasi attribute.</p>
            </div>
            <Link href="/product-variants/create" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90">
                <UIcon name="i-lucide-plus" class="size-4" />
                Tambah Product Variant
            </Link>
        </div>

        <UCard :ui="{ body: 'p-0!' }">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-default">
                    <thead class="bg-elevated/40">
                        <tr>
                            <th class="px-4 py-3 text-left text-sm font-medium">Produk</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">SKU</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Barcode</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Nama Tambahan</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Atribut</th>
                            <th class="px-4 py-3 text-left text-sm font-medium">Status</th>
                            <th class="px-4 py-3 text-right text-sm font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default">
                        <tr v-for="variant in records?.data || []" :key="variant.id">
                            <td class="px-4 py-3 text-sm">{{ variant.product?.name || '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ variant.sku || '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ variant.barcode || '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ variant.name_suffix || '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ (variant.attribute_option_labels || []).join(', ') || '-' }}</td>
                            <td class="px-4 py-3 text-sm">{{ variant.is_active ? 'Aktif' : 'Nonaktif' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Link :href="`/product-variants/${variant.id}`" class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted">
                                        <UIcon name="i-lucide-eye" class="size-4" />
                                    </Link>
                                    <Link :href="`/product-variants/${variant.id}/edit`" class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted">
                                        <UIcon name="i-lucide-pencil" class="size-4" />
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="(records?.data || []).length === 0">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-muted">Belum ada product variant.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </UCard>
    </div>
</template>
