<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Transaksi POS', panelId: 'transactions' }],
});

const props = defineProps({
    transaction: Object,
});

const tx = computed(() => props.transaction?.data || props.transaction || {});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const itemsList = computed(() => tx.value.items || []);
</script>

<template>
    <Head :title="`Detail Transaksi ${tx.number}`" />

    <div class="max-w-4xl mx-auto space-y-4">
        <!-- Header Controls -->
        <div class="flex items-center justify-between">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg border border-default bg-default text-muted hover:bg-elevated hover:text-highlighted"
                @click="router.visit('/transactions')"
            >
                <UIcon name="i-lucide-arrow-left" class="size-4" />
                Kembali ke Daftar Transaksi
            </button>

            <div class="flex items-center gap-2">
                <a
                    :href="`/transactions/${tx.id}/print`"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition-colors shadow-sm"
                >
                    <UIcon name="i-lucide-printer" class="size-4" />
                    Cetak Nota Thermal
                </a>
            </div>
        </div>

        <!-- Main Card -->
        <UCard>
            <!-- Transaction Info Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-default gap-3">
                <div>
                    <span class="text-xs uppercase tracking-widest text-muted font-mono font-bold block mb-1">POS RECEIPT</span>
                    <h1 class="text-2xl font-black font-mono text-primary flex items-center gap-2">
                        {{ tx.number }}
                        <span
                            class="text-xs px-2.5 py-0.5 rounded-full font-bold uppercase"
                            :class="tx.type === 'service' ? 'bg-blue-500/10 text-blue-600 border border-blue-500/30' : 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/30'"
                        >
                            {{ tx.type === 'service' ? 'Pelunasan Servis' : 'Retail Sparepart' }}
                        </span>
                    </h1>
                    <p class="text-xs text-muted mt-1">{{ tx.transaction_date }} | Cabang: <strong class="text-highlighted">{{ tx.store?.name }}</strong></p>
                </div>

                <div class="text-left sm:text-right">
                    <span class="text-xs font-bold text-muted block mb-1">Status Pembayaran:</span>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-500/10 text-emerald-600 border border-emerald-500/30">
                        {{ tx.payment_status }}
                    </span>
                    <p class="text-xs text-muted mt-1">Kasir: <strong class="text-highlighted">{{ tx.user?.name || '-' }}</strong></p>
                </div>
            </div>

            <!-- Customer & SPK Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 py-4 border-b border-default text-xs">
                <div>
                    <span class="text-muted block font-semibold mb-1">Informasi Pelanggan:</span>
                    <p class="font-bold text-highlighted text-sm">{{ tx.customer?.name || 'Walk-In Customer (Umum)' }}</p>
                    <p class="text-muted font-mono">{{ tx.customer?.phone || '-' }}</p>
                </div>

                <div v-if="tx.service_order">
                    <span class="text-muted block font-semibold mb-1">Terhubung SPK Servis:</span>
                    <p class="font-mono font-bold text-blue-600">{{ tx.service_order.number }}</p>
                    <p class="text-muted">Kendaraan: <strong class="text-highlighted">{{ tx.service_order.plate_number }}</strong> ({{ tx.service_order.vehicle_brand }} {{ tx.service_order.vehicle_model }})</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="py-4">
                <h3 class="text-xs font-bold text-muted uppercase tracking-wider mb-2">Rincian Item Transaksi</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-default bg-elevated/50 text-muted">
                                <th class="p-2">Tipe</th>
                                <th class="p-2">Deskripsi Item</th>
                                <th class="p-2 text-right">Harga Satuan</th>
                                <th class="p-2 text-center">Qty</th>
                                <th class="p-2 text-right">Diskon</th>
                                <th class="p-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-default">
                            <tr v-for="item in itemsList" :key="item.id">
                                <td class="p-2">
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase"
                                        :class="item.item_type === 'labor' ? 'bg-purple-500/10 text-purple-600' : 'bg-emerald-500/10 text-emerald-600'"
                                    >
                                        {{ item.item_type === 'labor' ? 'Jasa' : 'Part' }}
                                    </span>
                                </td>
                                <td class="p-2 font-semibold text-highlighted">{{ item.description }}</td>
                                <td class="p-2 text-right font-mono">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="p-2 text-center font-mono font-bold">{{ item.quantity }}</td>
                                <td class="p-2 text-right font-mono text-muted">{{ formatCurrency(item.item_discount_amount) }}</td>
                                <td class="p-2 text-right font-mono font-bold text-highlighted">{{ formatCurrency(item.line_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Financial Totals Summary -->
            <div class="pt-4 border-t border-default flex flex-col sm:flex-row justify-between items-start gap-4">
                <div class="text-xs text-muted space-y-1">
                    <p>Metode Pembayaran: <strong class="text-highlighted">{{ tx.payment?.name || 'Tunai / Cash' }}</strong></p>
                    <p v-if="tx.note">Catatan: <span class="italic text-highlighted">{{ tx.note }}</span></p>
                </div>

                <div class="w-full sm:w-72 space-y-1.5 text-xs">
                    <div class="flex justify-between text-muted">
                        <span>Subtotal Item:</span>
                        <span class="font-mono font-bold text-highlighted">{{ formatCurrency(tx.subtotal) }}</span>
                    </div>
                    <div v-if="tx.item_discount_total > 0" class="flex justify-between text-muted">
                        <span>Diskon Item:</span>
                        <span class="font-mono text-rose-500">-{{ formatCurrency(tx.item_discount_total) }}</span>
                    </div>
                    <div v-if="tx.universal_discount_amount > 0" class="flex justify-between text-muted">
                        <span>Diskon Transaksi:</span>
                        <span class="font-mono text-rose-500">-{{ formatCurrency(tx.universal_discount_amount) }}</span>
                    </div>
                    <div v-if="tx.tax_total > 0" class="flex justify-between text-muted">
                        <span>Pajak ({{ tx.tax_rate }}%):</span>
                        <span class="font-mono text-highlighted">{{ formatCurrency(tx.tax_total) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-black pt-2 border-t border-default">
                        <span class="text-highlighted uppercase">Grand Total:</span>
                        <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ formatCurrency(tx.grand_total) }}</span>
                    </div>
                    <div class="flex justify-between text-muted pt-1">
                        <span>Uang Dibayar:</span>
                        <span class="font-mono font-bold text-highlighted">{{ formatCurrency(tx.paid_amount) }}</span>
                    </div>
                    <div class="flex justify-between text-muted">
                        <span>Kembalian:</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(tx.change_amount) }}</span>
                    </div>
                </div>
            </div>
        </UCard>
    </div>
</template>
