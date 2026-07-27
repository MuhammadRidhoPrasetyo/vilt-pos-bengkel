<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Transaksi Pembelian', panelId: 'purchases' }],
});

const props = defineProps({
    purchase: Object,
});

const data = computed(() => props.purchase?.data || {});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Top Toolbar -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <UButton color="neutral" variant="outline" icon="i-lucide-arrow-left" label="Kembali" @click="router.visit('/purchases')" />
                <div>
                    <h1 class="text-xl font-bold text-highlighted flex items-center gap-2">
                        Nota Pembelian #{{ data.number }}
                        <span class="rounded bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-600 border border-emerald-500/20">Diposting</span>
                    </h1>
                    <p class="text-xs text-muted">Dibuat pada {{ data.created_at }} oleh {{ data.creator?.name || 'Sistem' }}</p>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Left 2 Columns: Items & Cashflow -->
            <div class="space-y-6 lg:col-span-2">
                <!-- Items Card -->
                <UCard :ui="{ body: 'p-4 space-y-4' }">
                    <h2 class="text-sm font-semibold text-highlighted">Rincian Barang restok</h2>

                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-xs">
                            <thead>
                                <tr class="border-b border-default bg-elevated/50 text-muted">
                                    <th class="p-2.5 font-medium">Varian Produk</th>
                                    <th class="p-2.5 font-medium">Tipe Harga</th>
                                    <th class="p-2.5 font-medium text-center">Qty</th>
                                    <th class="p-2.5 font-medium text-right">Harga Unit</th>
                                    <th class="p-2.5 font-medium text-right">Diskon</th>
                                    <th class="p-2.5 font-medium text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-default">
                                <tr v-for="item in data.items" :key="item.id" class="hover:bg-elevated/20">
                                    <td class="p-2.5 font-medium text-highlighted">
                                        {{ item.product_variant?.product_name || item.product_variant?.name }}
                                        <span class="block text-[11px] font-mono text-muted">SKU: {{ item.product_variant?.sku || '-' }}</span>
                                    </td>
                                    <td class="p-2.5 capitalize text-muted">{{ item.price_type }}</td>
                                    <td class="p-2.5 text-center font-mono font-semibold">{{ item.quantity_ordered }}</td>
                                    <td class="p-2.5 text-right font-mono">{{ formatCurrency(item.unit_purchase_price) }}</td>
                                    <td class="p-2.5 text-right font-mono text-amber-600">- {{ formatCurrency(item.discount_amount) }}</td>
                                    <td class="p-2.5 text-right font-mono font-semibold text-highlighted">{{ formatCurrency(item.total_price) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </UCard>

                <!-- Audit Ledger & Cash Flow Card -->
                <UCard :ui="{ body: 'p-4 space-y-3' }">
                    <h2 class="text-sm font-semibold text-highlighted flex items-center gap-2">
                        <UIcon name="i-lucide-receipt-text" class="size-4 text-primary" /> Log Bukti Arus Kas & Batch HPP
                    </h2>

                    <div v-if="data.cash_flows && data.cash_flows.length > 0" class="space-y-2">
                        <div v-for="cf in data.cash_flows" :key="cf.id" class="rounded-lg border border-default bg-elevated/30 p-3 text-xs flex items-center justify-between">
                            <div>
                                <span class="font-semibold text-highlighted">Pengeluaran Kas (#{{ cf.id.substring(0, 8) }})</span>
                                <p class="text-muted mt-0.5">{{ cf.description }}</p>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-bold text-error text-sm">- {{ formatCurrency(cf.amount) }}</span>
                                <span class="block text-[11px] text-muted">{{ cf.date }}</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-xs text-muted">Tidak ada rekaman pengeluaran kas terpisah.</p>
                </UCard>
            </div>

            <!-- Right Column: Meta Info & Financial Summary -->
            <div class="space-y-6">
                <!-- Meta Info Card -->
                <UCard :ui="{ body: 'p-4 space-y-3 text-xs' }">
                    <h2 class="text-sm font-semibold text-highlighted border-b border-default pb-2">Informasi Transaksi</h2>

                    <div class="space-y-2">
                        <div>
                            <span class="text-muted block">Supplier / Vendor:</span>
                            <span class="font-semibold text-highlighted text-sm">{{ data.supplier?.name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-muted block">Cabang Toko / Bengkel:</span>
                            <span class="font-medium text-highlighted">{{ data.store?.name || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-muted block">Tanggal Pembelian:</span>
                            <span class="font-medium text-highlighted">{{ data.purchase_date }}</span>
                        </div>
                        <div>
                            <span class="text-muted block">No. Invoice Supplier:</span>
                            <span class="font-mono text-highlighted">{{ data.invoice_number || '-' }}</span>
                        </div>
                        <div>
                            <span class="text-muted block">Catatan:</span>
                            <span class="text-highlighted italic">{{ data.notes || '-' }}</span>
                        </div>
                    </div>
                </UCard>

                <!-- Financial Summary Card -->
                <UCard :ui="{ body: 'p-4 space-y-3 text-xs' }">
                    <h2 class="text-sm font-semibold text-highlighted border-b border-default pb-2">Ringkasan Pembayaran</h2>

                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-muted">Diskon Header Nota:</span>
                            <span class="font-mono font-medium text-amber-600">
                                {{ data.discount_type === 'percent' ? `${data.discount_value}%` : formatCurrency(data.discount_value) }}
                            </span>
                        </div>

                        <div class="flex justify-between border-t border-default pt-3 text-sm font-bold text-highlighted">
                            <span>TOTAL PEMBELIAN:</span>
                            <span class="font-mono text-primary text-base">{{ formatCurrency(data.price) }}</span>
                        </div>
                    </div>
                </UCard>
            </div>
        </div>
    </div>
</template>
