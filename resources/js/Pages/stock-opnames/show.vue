<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Stock Opname', panelId: 'stock-opnames' }],
});

const props = defineProps({ record: Object });
const record = computed(() => props.record?.data || props.record || {});

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const badgeClass = (value) => ({
    draft: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    posted: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    cancelled: 'bg-zinc-500/10 text-zinc-600 dark:text-zinc-300',
}[value] || 'bg-zinc-500/10 text-zinc-600');

const postRecord = () => {
    if (confirm(`Posting stock opname ${record.value.opname_number}? Kuantitas pada kartu stok gudang akan disesuaikan otomatis.`)) {
        router.post(`/stock-opnames/${record.value.id}/post`);
    }
};

const cancelRecord = () => {
    if (confirm(`Batalkan draft sesi stock opname ${record.value.opname_number}?`)) {
        router.post(`/stock-opnames/${record.value.id}/cancel`);
    }
};

const printDocument = () => {
    window.print();
};
</script>

<template>
    <div class="space-y-6">
        <!-- Print Header (only visible on print) -->
        <div class="hidden print:block mb-6 border-b pb-4">
            <h1 class="text-2xl font-bold">BERITA ACARA HASIL STOCK OPNAME</h1>
            <p class="text-sm">Dokumen Rekonsiliasi & Audit Persediaan Fisik Gudang</p>
            <div class="grid grid-cols-2 gap-2 mt-4 text-xs">
                <div><strong>No. Dokumen:</strong> {{ record.opname_number }}</div>
                <div><strong>Tanggal:</strong> {{ record.occurred_at || '-' }}</div>
                <div><strong>Toko:</strong> {{ record.store?.name || '-' }}</div>
                <div><strong>Gudang:</strong> {{ record.warehouse?.name || '-' }}</div>
                <div><strong>Status:</strong> {{ record.status?.toUpperCase() }}</div>
                <div><strong>Catatan:</strong> {{ record.notes || '-' }}</div>
            </div>
        </div>

        <!-- Normal Screen Header -->
        <div class="flex flex-col gap-4 border-b border-default pb-4 lg:flex-row lg:items-center lg:justify-between print:hidden">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold font-mono text-highlighted">{{ record.opname_number }}</h1>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase" :class="badgeClass(record.status)">
                        {{ record.status }}
                    </span>
                </div>
                <p class="text-sm text-muted mt-1">
                    {{ record.store?.name || '-' }} · {{ record.warehouse?.name || '-' }} · {{ record.occurred_at || '-' }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-md border border-default px-3 py-2 text-sm font-medium hover:bg-elevated transition-colors"
                    title="Cetak Berita Acara Opname"
                    @click="printDocument"
                >
                    <UIcon name="i-lucide-printer" class="size-4" />
                    Cetak Berita Acara
                </button>
                <Link
                    v-if="record.status === 'draft' && $can('stock-opnames.edit')"
                    :href="`/stock-opnames/${record.id}/edit`"
                    class="inline-flex items-center gap-1.5 rounded-md border border-default px-3 py-2 text-sm font-medium hover:bg-elevated transition-colors"
                >
                    <UIcon name="i-lucide-pencil" class="size-4" />
                    Edit Hitungan
                </Link>
                <button
                    v-if="record.status === 'draft' && $can('stock-opnames.post')"
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-inverted hover:bg-primary/90 transition-colors"
                    type="button"
                    @click="postRecord"
                >
                    <UIcon name="i-lucide-send" class="size-4" />
                    Posting Penyesuaian
                </button>
                <button
                    v-if="record.status === 'draft' && $can('stock-opnames.cancel')"
                    class="inline-flex items-center gap-1.5 rounded-md border border-zinc-500/20 px-3 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-500/10 transition-colors"
                    type="button"
                    @click="cancelRecord"
                >
                    <UIcon name="i-lucide-ban" class="size-4" />
                    Batal Draft
                </button>
                <Link href="/stock-opnames" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">
                    Kembali
                </Link>
            </div>
        </div>

        <!-- Metrics Overview Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 print:grid-cols-4">
            <div class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">Total Qty Sistem (Buku)</p>
                <p class="mt-1 text-2xl font-bold font-mono text-highlighted">{{ record.total_system_qty || 0 }}</p>
            </div>
            <div class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">Total Qty Fisik (Dihitung)</p>
                <p class="mt-1 text-2xl font-bold font-mono text-highlighted">{{ record.total_physical_qty || 0 }}</p>
            </div>
            <div class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">Total Selisih Kuantitas</p>
                <p class="mt-1 text-2xl font-bold font-mono" :class="(record.total_difference_qty || 0) < 0 ? 'text-red-500' : (record.total_difference_qty || 0) > 0 ? 'text-emerald-500' : 'text-muted'">
                    {{ (record.total_difference_qty || 0) > 0 ? '+' : '' }}{{ record.total_difference_qty || 0 }} Pcs
                </p>
            </div>
            <div class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">Total Nilai Selisih</p>
                <p class="mt-1 text-2xl font-bold font-mono" :class="(record.total_difference_value || 0) < 0 ? 'text-red-500' : (record.total_difference_value || 0) > 0 ? 'text-emerald-500' : 'text-muted'">
                    {{ formatRupiah(record.total_difference_value) }}
                </p>
            </div>
        </div>

        <!-- Metadata Information -->
        <div class="grid gap-4 md:grid-cols-3 rounded-lg border border-default bg-default p-4 print:hidden">
            <div>
                <span class="text-xs text-muted uppercase">Dibuat Oleh</span>
                <p class="text-sm font-semibold text-highlighted mt-0.5">{{ record.created_by?.name || '-' }}</p>
                <p class="text-xs text-muted">{{ record.created_at || '-' }}</p>
            </div>
            <div>
                <span class="text-xs text-muted uppercase">Diposting Oleh</span>
                <p class="text-sm font-semibold text-highlighted mt-0.5">{{ record.posted_by?.name || 'Belum diposting' }}</p>
                <p class="text-xs text-muted">{{ record.posted_at || '-' }}</p>
            </div>
            <div>
                <span class="text-xs text-muted uppercase">Catatan / Alasan</span>
                <p class="text-sm text-highlighted mt-0.5">{{ record.notes || 'Tidak ada catatan.' }}</p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="overflow-x-auto rounded-lg border border-default bg-default">
            <div class="border-b border-default px-4 py-3 font-semibold text-highlighted flex items-center justify-between">
                <span>Rincian Barang yang Diopname ({{ (record.items || []).length }} SKU)</span>
            </div>
            <table class="min-w-full divide-y divide-default">
                <thead class="bg-elevated/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Produk & SKU</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Rak / Lokasi</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Qty Sistem</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Qty Fisik</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Selisih</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">HPP (Modal)</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">Nilai Selisih</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    <tr v-for="(item, index) in record.items || []" :key="item.id" class="hover:bg-elevated/20">
                        <td class="px-4 py-3 text-xs text-muted">{{ index + 1 }}</td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-highlighted">{{ item.product_variant?.name || '-' }}</p>
                            <p class="font-mono text-xs text-muted">SKU: {{ item.product_variant?.sku || '-' }} · {{ item.product_variant?.unit_name || 'Pcs' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-muted">
                            {{ item.warehouse_location?.name || 'Tanpa Rak/Lokasi' }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-sm text-muted">
                            {{ item.system_quantity }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-sm font-bold text-highlighted">
                            {{ item.physical_quantity }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-sm font-semibold">
                            <span
                                class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="item.difference_quantity < 0 ? 'bg-red-500/10 text-red-600 dark:text-red-400' : item.difference_quantity > 0 ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' : 'bg-zinc-500/10 text-zinc-600'"
                            >
                                {{ item.difference_quantity > 0 ? '+' : '' }}{{ item.difference_quantity }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs text-muted">
                            {{ formatRupiah(item.unit_cost) }}
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-sm font-semibold" :class="item.difference_value < 0 ? 'text-red-500' : item.difference_value > 0 ? 'text-emerald-500' : 'text-muted'">
                            {{ formatRupiah(item.difference_value) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-muted">
                            {{ item.note || '-' }}
                        </td>
                    </tr>
                    <tr v-if="(record.items || []).length === 0">
                        <td colspan="9" class="p-8 text-center text-sm text-muted">Tidak ada item dalam dokumen ini.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Inventory Movements Ledger -->
        <div v-if="record.status === 'posted'" class="overflow-x-auto rounded-lg border border-default bg-default print:hidden">
            <div class="border-b border-default px-4 py-3 font-semibold text-highlighted flex items-center justify-between">
                <span>Audit Mutasi Kartu Stok (Inventory Movements)</span>
                <span class="text-xs text-emerald-600 font-medium">Terposting ke Ledger Persediaan</span>
            </div>
            <table class="min-w-full divide-y divide-default">
                <thead class="bg-elevated/40">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-muted">Tipe</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-muted">Produk</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase text-muted">Gudang</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-muted">Qty Mutasi</th>
                        <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase text-muted">Saldo Akhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    <tr v-for="movement in record.movements || []" :key="movement.id">
                        <td class="px-4 py-3 text-sm font-semibold" :class="movement.type === 'in' ? 'text-emerald-600' : 'text-red-600'">
                            {{ movement.type === 'in' ? '+ Penyesuaian Masuk' : '- Penyesuaian Keluar' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-highlighted">{{ movement.product_variant?.name || '-' }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ movement.warehouse?.name || '-' }}</td>
                        <td class="px-4 py-3 text-right font-mono text-sm font-semibold">{{ movement.quantity }}</td>
                        <td class="px-4 py-3 text-right font-mono text-sm text-muted">{{ movement.balance_after }} Pcs</td>
                    </tr>
                    <tr v-if="(record.movements || []).length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-muted">Tidak ada mutasi yang dihasilkan (stok fisik cocok dengan sistem).</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Print Signatures Block -->
        <div class="hidden print:grid grid-cols-3 gap-8 mt-12 pt-8 text-center text-xs">
            <div>
                <p>Petugas Penghitung Fisik,</p>
                <div class="h-20"></div>
                <p class="font-bold underline">( _______________________ )</p>
                <p class="text-muted">Staff Gudang</p>
            </div>
            <div>
                <p>Diperiksa Oleh,</p>
                <div class="h-20"></div>
                <p class="font-bold underline">( _______________________ )</p>
                <p class="text-muted">Kepala Gudang / Supervisor</p>
            </div>
            <div>
                <p>Disetujui Oleh,</p>
                <div class="h-20"></div>
                <p class="font-bold underline">( _______________________ )</p>
                <p class="text-muted">Kepala Toko / Owner</p>
            </div>
        </div>
    </div>
</template>
