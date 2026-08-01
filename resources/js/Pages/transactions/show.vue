<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Transaksi POS', panelId: 'transactions' }],
});

const props = defineProps({
    transaction: Object,
    paymentOptions: Array,
});

const tx = computed(() => props.transaction?.data || props.transaction || {});
const itemsList = computed(() => tx.value.items || []);
const paymentAttempts = computed(() => tx.value.payment_attempts || []);
const paymentOptions = computed(() => props.paymentOptions || []);

const paymentForm = useForm({
    payment_id: paymentOptions.value[0]?.value || null,
    amount_given: tx.value.outstanding_amount || 0,
    paid_at: new Date().toISOString().slice(0, 16),
    note: '',
});

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(val || 0);

const formatDate = (val) => {
    if (!val) return '-';

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(val));
};

const getPaymentStatusBadge = (status) => {
    switch (status) {
        case 'paid':
            return { label: 'Lunas', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' };
        case 'partial':
            return { label: 'Sebagian', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' };
        case 'unpaid':
            return { label: 'Belum Bayar', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' };
        default:
            return { label: status || '-', class: 'bg-gray-500/10 text-gray-600 border-gray-500/20' };
    }
};

const submitPayment = () => {
    if (!tx.value.id || paymentForm.processing) return;

    paymentForm.post(`/transactions/${tx.value.id}/payment-attempts`, {
        preserveScroll: true,
        onSuccess: () => {
            paymentForm.reset('note');
            paymentForm.amount_given = 0;
        },
    });
};
</script>

<template>
    <Head :title="`Detail Transaksi ${tx.number}`" />

    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <button
                type="button"
                class="inline-flex items-center gap-1.5 self-start rounded-lg border border-default bg-default px-3 py-1.5 text-xs font-bold text-muted hover:bg-elevated hover:text-highlighted"
                @click="router.visit('/transactions')"
            >
                <UIcon name="i-lucide-arrow-left" class="size-4" />
                Kembali
            </button>

            <a
                :href="`/transactions/${tx.id}/print`"
                target="_blank"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition-colors hover:bg-emerald-700"
            >
                <UIcon name="i-lucide-printer" class="size-4" />
                Cetak Nota
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <section class="space-y-4 xl:col-span-8">
                <UCard :ui="{ body: 'p-4 sm:p-5' }">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-muted">Transaksi</span>
                                <span
                                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-bold uppercase"
                                    :class="tx.type === 'service' ? 'border-blue-500/20 bg-blue-500/10 text-blue-600' : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600'"
                                >
                                    {{ tx.type === 'service' ? 'Pelunasan Servis' : 'Retail Sparepart' }}
                                </span>
                            </div>
                            <h1 class="font-mono text-2xl font-black text-primary">{{ tx.number }}</h1>
                            <p class="text-xs text-muted">{{ formatDate(tx.transaction_date) }} - {{ tx.store?.name || '-' }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:min-w-[520px]">
                            <div class="rounded-lg border border-default bg-elevated/40 p-3">
                                <p class="text-[11px] font-semibold text-muted">Grand Total</p>
                                <p class="mt-1 font-mono text-sm font-black text-highlighted">{{ formatCurrency(tx.grand_total) }}</p>
                            </div>
                            <div class="rounded-lg border border-default bg-elevated/40 p-3">
                                <p class="text-[11px] font-semibold text-muted">Terbayar Net</p>
                                <p class="mt-1 font-mono text-sm font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(tx.paid_net_amount) }}</p>
                            </div>
                            <div class="rounded-lg border border-default bg-elevated/40 p-3">
                                <p class="text-[11px] font-semibold text-muted">Sisa</p>
                                <p class="mt-1 font-mono text-sm font-black text-amber-600 dark:text-amber-400">{{ formatCurrency(tx.outstanding_amount) }}</p>
                            </div>
                            <div class="rounded-lg border border-default bg-elevated/40 p-3">
                                <p class="text-[11px] font-semibold text-muted">Status</p>
                                <span class="mt-1 inline-flex rounded-md border px-2 py-0.5 text-xs font-bold" :class="getPaymentStatusBadge(tx.payment_status).class">
                                    {{ getPaymentStatusBadge(tx.payment_status).label }}
                                </span>
                            </div>
                        </div>
                    </div>
                </UCard>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <UCard :ui="{ body: 'p-4' }">
                        <h2 class="mb-3 text-sm font-bold text-highlighted">Informasi Pelanggan</h2>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-muted">Nama</span>
                                <span class="font-semibold text-highlighted">{{ tx.customer?.name || 'Walk-In Customer' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-muted">Telepon</span>
                                <span class="font-mono text-highlighted">{{ tx.customer?.phone || '-' }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-muted">Kasir</span>
                                <span class="font-semibold text-highlighted">{{ tx.user?.name || '-' }}</span>
                            </div>
                        </div>
                    </UCard>

                    <UCard :ui="{ body: 'p-4' }">
                        <h2 class="mb-3 text-sm font-bold text-highlighted">Referensi Servis</h2>
                        <div v-if="tx.service_order" class="space-y-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-muted">Nomor SPK</span>
                                <span class="font-mono font-bold text-blue-600">{{ tx.service_order.number }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-muted">Plat</span>
                                <span class="font-mono font-bold text-highlighted">{{ tx.service_order.plate_number }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-muted">Kendaraan</span>
                                <span class="font-semibold text-highlighted">{{ tx.service_order.vehicle_brand }} {{ tx.service_order.vehicle_model }}</span>
                            </div>
                        </div>
                        <div v-else class="flex min-h-20 items-center justify-center rounded-lg border border-dashed border-default text-xs text-muted">
                            Tidak terkait SPK servis.
                        </div>
                    </UCard>
                </div>

                <UCard :ui="{ body: 'p-0' }">
                    <div class="border-b border-default p-4">
                        <h2 class="text-sm font-bold text-highlighted">Item dan Batch FIFO</h2>
                        <p class="mt-1 text-xs text-muted">Batch hanya muncul untuk item part yang sudah dialokasikan dari inventory batch.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-xs">
                            <thead class="bg-elevated/50 text-muted">
                                <tr>
                                    <th class="p-3">Item</th>
                                    <th class="p-3 text-center">Qty</th>
                                    <th class="p-3 text-right">Harga</th>
                                    <th class="p-3 text-right">Total</th>
                                    <th class="p-3 text-right">HPP</th>
                                    <th class="p-3 text-right">Laba</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-default">
                                <template v-for="item in itemsList" :key="item.id">
                                    <tr>
                                        <td class="p-3">
                                            <div class="flex items-start gap-2">
                                                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase" :class="item.item_type === 'labor' ? 'bg-purple-500/10 text-purple-600' : 'bg-emerald-500/10 text-emerald-600'">
                                                    {{ item.item_type === 'labor' ? 'Jasa' : 'Part' }}
                                                </span>
                                                <div>
                                                    <p class="font-semibold text-highlighted">{{ item.description }}</p>
                                                    <p class="font-mono text-[11px] text-muted">{{ item.product_variant?.sku || item.product_variant_id || '-' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-3 text-center font-mono font-bold">{{ item.quantity }}</td>
                                        <td class="p-3 text-right font-mono">{{ formatCurrency(item.unit_price) }}</td>
                                        <td class="p-3 text-right font-mono font-bold text-highlighted">{{ formatCurrency(item.line_total) }}</td>
                                        <td class="p-3 text-right font-mono text-muted">{{ formatCurrency(item.line_cost_total) }}</td>
                                        <td class="p-3 text-right font-mono font-bold" :class="item.line_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600'">
                                            {{ formatCurrency(item.line_profit) }}
                                        </td>
                                    </tr>
                                    <tr v-if="item.batches?.length" class="bg-elevated/20">
                                        <td colspan="6" class="px-3 pb-3">
                                            <div class="rounded-lg border border-default bg-default p-3">
                                                <div class="mb-2 flex items-center gap-2 text-[11px] font-bold uppercase tracking-wide text-muted">
                                                    <UIcon name="i-lucide-layers-3" class="size-3.5" />
                                                    Batch FIFO
                                                </div>
                                                <div class="grid gap-2 md:grid-cols-2">
                                                    <div v-for="batch in item.batches" :key="batch.id" class="rounded-md border border-default bg-elevated/40 p-2 text-[11px]">
                                                        <div class="flex justify-between gap-3">
                                                            <span class="text-muted">Batch</span>
                                                            <span class="font-mono text-highlighted">{{ batch.inventory_batch_id }}</span>
                                                        </div>
                                                        <div class="mt-1 flex justify-between gap-3">
                                                            <span class="text-muted">Gudang</span>
                                                            <span class="font-semibold text-highlighted">{{ batch.inventory_batch?.warehouse?.name || '-' }}</span>
                                                        </div>
                                                        <div class="mt-1 grid grid-cols-3 gap-2 font-mono">
                                                            <span>Qty: <strong>{{ batch.quantity }}</strong></span>
                                                            <span>HPP: <strong>{{ formatCurrency(batch.unit_cost) }}</strong></span>
                                                            <span>Total: <strong>{{ formatCurrency(batch.total_cost) }}</strong></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </UCard>
            </section>

            <aside class="space-y-4 xl:col-span-4">
                <UCard v-if="tx.outstanding_amount > 0" :ui="{ body: 'p-4' }">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-bold text-highlighted">Pelunasan Sisa Pembayaran</h2>
                            <p class="mt-1 text-xs text-muted">Sisa tagihan {{ formatCurrency(tx.outstanding_amount) }}</p>
                        </div>
                        <UIcon name="i-lucide-wallet-cards" class="size-5 text-amber-500" />
                    </div>

                    <form class="space-y-3" @submit.prevent="submitPayment">
                        <div>
                            <label class="mb-1 block text-xs font-bold text-muted">Metode Pembayaran</label>
                            <select v-model="paymentForm.payment_id" class="w-full rounded-lg border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary">
                                <option :value="null">Tanpa metode</option>
                                <option v-for="payment in paymentOptions" :key="payment.value" :value="payment.value">{{ payment.label }}</option>
                            </select>
                            <p v-if="paymentForm.errors.payment_id" class="mt-1 text-xs text-rose-600">{{ paymentForm.errors.payment_id }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold text-muted">Nominal Diterima</label>
                            <input v-model.number="paymentForm.amount_given" type="number" min="1" class="w-full rounded-lg border border-default bg-default px-3 py-2 text-sm font-mono font-bold outline-none focus:border-primary" />
                            <p v-if="paymentForm.errors.amount_given" class="mt-1 text-xs text-rose-600">{{ paymentForm.errors.amount_given }}</p>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold text-muted">Tanggal Bayar</label>
                            <input v-model="paymentForm.paid_at" type="datetime-local" class="w-full rounded-lg border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" />
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold text-muted">Catatan</label>
                            <textarea v-model="paymentForm.note" rows="3" class="w-full resize-none rounded-lg border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" placeholder="Opsional" />
                        </div>

                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-60" :disabled="paymentForm.processing">
                            <UIcon name="i-lucide-check-circle-2" class="size-4" />
                            {{ paymentForm.processing ? 'Menyimpan...' : 'Catat Pembayaran' }}
                        </button>
                    </form>
                </UCard>

                <UCard :ui="{ body: 'p-4' }">
                    <h2 class="mb-3 text-sm font-bold text-highlighted">Ringkasan Keuangan</h2>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3 text-muted">
                            <span>Subtotal</span>
                            <span class="font-mono text-highlighted">{{ formatCurrency(tx.subtotal) }}</span>
                        </div>
                        <div class="flex justify-between gap-3 text-muted">
                            <span>Diskon Item</span>
                            <span class="font-mono text-rose-600">-{{ formatCurrency(tx.item_discount_total) }}</span>
                        </div>
                        <div class="flex justify-between gap-3 text-muted">
                            <span>Diskon Transaksi</span>
                            <span class="font-mono text-rose-600">-{{ formatCurrency(tx.universal_discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between gap-3 text-muted">
                            <span>Pajak</span>
                            <span class="font-mono text-highlighted">{{ formatCurrency(tx.tax_total) }}</span>
                        </div>
                        <div class="border-t border-default pt-2">
                            <div class="flex justify-between gap-3 font-bold">
                                <span class="text-highlighted">Grand Total</span>
                                <span class="font-mono text-highlighted">{{ formatCurrency(tx.grand_total) }}</span>
                            </div>
                            <div class="mt-1 flex justify-between gap-3 text-muted">
                                <span>Uang Diterima</span>
                                <span class="font-mono text-highlighted">{{ formatCurrency(tx.paid_amount) }}</span>
                            </div>
                            <div class="mt-1 flex justify-between gap-3 text-muted">
                                <span>Kembalian</span>
                                <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ formatCurrency(tx.change_amount) }}</span>
                            </div>
                            <div class="mt-1 flex justify-between gap-3 text-muted">
                                <span>Total HPP</span>
                                <span class="font-mono text-highlighted">{{ formatCurrency(tx.total_cost) }}</span>
                            </div>
                            <div class="mt-1 flex justify-between gap-3 font-bold">
                                <span class="text-highlighted">Laba Kotor</span>
                                <span class="font-mono" :class="tx.total_profit >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600'">{{ formatCurrency(tx.total_profit) }}</span>
                            </div>
                        </div>
                    </div>
                </UCard>

                <UCard :ui="{ body: 'p-0' }">
                    <div class="border-b border-default p-4">
                        <h2 class="text-sm font-bold text-highlighted">Riwayat Pembayaran</h2>
                        <p class="mt-1 text-xs text-muted">{{ paymentAttempts.length }} catatan pembayaran</p>
                    </div>

                    <div v-if="paymentAttempts.length === 0" class="p-6 text-center text-xs text-muted">
                        Belum ada payment attempt.
                    </div>
                    <div v-else class="divide-y divide-default">
                        <div v-for="attempt in paymentAttempts" :key="attempt.id" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold text-highlighted">{{ attempt.payment?.name || 'Tanpa metode' }}</p>
                                    <p class="mt-0.5 text-xs text-muted">{{ formatDate(attempt.paid_at || attempt.created_at) }} - {{ attempt.user?.name || '-' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-mono text-sm font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(attempt.amount) }}</p>
                                    <p v-if="attempt.change > 0" class="mt-0.5 text-xs font-mono text-muted">Kembali {{ formatCurrency(attempt.change) }}</p>
                                </div>
                            </div>
                            <p v-if="attempt.metadata?.note" class="mt-2 rounded-md bg-elevated/50 px-2 py-1 text-xs text-muted">
                                {{ attempt.metadata.note }}
                            </p>
                        </div>
                    </div>
                </UCard>
            </aside>
        </div>
    </div>
</template>
