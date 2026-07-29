<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

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

onMounted(() => {
    setTimeout(() => {
        window.print();
    }, 500);
});
</script>

<template>
    <Head :title="`Struk ${tx.number}`" />

    <div class="min-h-screen bg-white text-black p-4 flex flex-col items-center font-mono text-xs select-none">
        <!-- Print Button for Manual Click if auto-print blocked -->
        <div class="mb-4 print:hidden flex gap-2">
            <button
                type="button"
                class="px-4 py-2 bg-emerald-600 text-white font-sans text-xs font-bold rounded shadow hover:bg-emerald-700"
                @click="window.print()"
            >
                Cetak Struk Now
            </button>
            <button
                type="button"
                class="px-4 py-2 bg-gray-200 text-black font-sans text-xs font-bold rounded hover:bg-gray-300"
                @click="window.close()"
            >
                Tutup
            </button>
        </div>

        <!-- 80mm / 58mm Thermal Receipt Box -->
        <div class="w-[80mm] max-w-full p-2 bg-white text-black border border-dashed border-gray-300 print:border-none">
            <!-- Header -->
            <div class="text-center pb-2 border-b border-black border-dashed">
                <h1 class="text-sm font-black uppercase">{{ tx.store?.name || 'POS BENGKEL' }}</h1>
                <p class="text-[10px] text-gray-700">Nota Penjualan & Pelunasan Servis</p>
                <p class="text-[10px] text-gray-600 mt-0.5 font-sans">{{ tx.store?.address || '' }}</p>
            </div>

            <!-- Receipt Info -->
            <div class="py-2 border-b border-black border-dashed space-y-0.5 text-[11px]">
                <div class="flex justify-between">
                    <span>No. Nota:</span>
                    <span class="font-bold">{{ tx.number }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Waktu:</span>
                    <span>{{ tx.transaction_date }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Kasir:</span>
                    <span>{{ tx.user?.name || '-' }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Pelanggan:</span>
                    <span>{{ tx.customer?.name || 'Walk-In' }}</span>
                </div>

                <div v-if="tx.service_order" class="pt-1 border-t border-gray-300 border-dotted mt-1">
                    <div class="flex justify-between font-bold">
                        <span>Plat Motor:</span>
                        <span>{{ tx.service_order.plate_number }}</span>
                    </div>
                    <div class="flex justify-between text-[10px]">
                        <span>Kendaraan:</span>
                        <span>{{ tx.service_order.vehicle_brand }} {{ tx.service_order.vehicle_model }}</span>
                    </div>
                </div>
            </div>

            <!-- Items List -->
            <div class="py-2 border-b border-black border-dashed">
                <div v-for="item in itemsList" :key="item.id" class="mb-1.5 text-[11px]">
                    <div class="font-bold leading-tight uppercase">{{ item.description }}</div>
                    <div class="flex justify-between text-[10px]">
                        <span>{{ item.quantity }} x {{ formatCurrency(item.unit_price) }}</span>
                        <span class="font-bold">{{ formatCurrency(item.line_total) }}</span>
                    </div>
                    <div v-if="item.item_discount_amount > 0" class="text-[9px] text-gray-600">
                        Diskon: -{{ formatCurrency(item.item_discount_amount) }}
                    </div>
                </div>
            </div>

            <!-- Totals -->
            <div class="py-2 border-b border-black border-dashed space-y-1 text-[11px]">
                <div class="flex justify-between">
                    <span>Subtotal:</span>
                    <span>{{ formatCurrency(tx.subtotal) }}</span>
                </div>
                <div v-if="tx.universal_discount_amount > 0" class="flex justify-between">
                    <span>Diskon Nota:</span>
                    <span>-{{ formatCurrency(tx.universal_discount_amount) }}</span>
                </div>
                <div v-if="tx.tax_total > 0" class="flex justify-between">
                    <span>Pajak ({{ tx.tax_rate }}%):</span>
                    <span>{{ formatCurrency(tx.tax_total) }}</span>
                </div>
                <div class="flex justify-between font-black text-xs pt-1 border-t border-black border-dotted">
                    <span>GRAND TOTAL:</span>
                    <span>{{ formatCurrency(tx.grand_total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Dibayar ({{ tx.payment?.name || 'Tunai' }}):</span>
                    <span>{{ formatCurrency(tx.paid_amount) }}</span>
                </div>
                <div class="flex justify-between font-bold">
                    <span>Kembalian:</span>
                    <span>{{ formatCurrency(tx.change_amount) }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center pt-3 text-[10px] space-y-0.5">
                <p class="font-bold">Terima Kasih Atas Kunjungan Anda!</p>
                <p class="text-gray-600">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.</p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    body {
        background: white !important;
    }
}
</style>
