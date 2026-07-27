<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail SPK Servis', panelId: 'services' }],
});

const props = defineProps({
    serviceOrder: Object,
});

const so = computed(() => props.serviceOrder?.data || props.serviceOrder || {});

const formatCurrency = (val) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(val || 0);
};

const triggerPrint = () => {
    window.print();
};

const getStatusBadge = (status) => {
    switch (status) {
        case 'checkin':
            return { label: 'Check-in', class: 'bg-blue-500/10 text-blue-600 border-blue-500/20' };
        case 'diagnosis':
            return { label: 'Diagnosis', class: 'bg-purple-500/10 text-purple-600 border-purple-500/20' };
        case 'in_progress':
            return { label: 'Dalam Pengerjaan', class: 'bg-amber-500/10 text-amber-600 border-amber-500/20' };
        case 'waiting_parts':
            return { label: 'Menunggu Part', class: 'bg-orange-500/10 text-orange-600 border-orange-500/20' };
        case 'ready':
            return { label: 'Selesai (Siap Ambil)', class: 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' };
        case 'invoiced':
            return { label: 'Sudah Dilunasi POS', class: 'bg-teal-500/10 text-teal-600 border-teal-500/20' };
        case 'cancelled':
            return { label: 'Dibatalkan', class: 'bg-rose-500/10 text-rose-600 border-rose-500/20' };
        default:
            return { label: status, class: 'bg-gray-500/10 text-gray-600 border-gray-500/20' };
    }
};
</script>

<template>
    <div class="space-y-4">
        <!-- Action Header -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <button class="inline-flex size-9 items-center justify-center rounded-lg border border-default text-muted hover:bg-elevated hover:text-highlighted" type="button" @click="router.visit('/services')">
                    <UIcon name="i-lucide-arrow-left" class="size-5" />
                </button>
                <div>
                    <h1 class="text-xl font-bold text-highlighted flex items-center gap-2">
                        SPK: {{ so.number }}
                        <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold border" :class="getStatusBadge(so.status).class">
                            {{ getStatusBadge(so.status).label }}
                        </span>
                    </h1>
                    <p class="text-xs text-muted">Dibuat pada: {{ so.checkin_at }} | Cabang: {{ so.store?.name || '-' }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <UButton icon="i-lucide-printer" color="neutral" variant="outline" label="Cetak Struk SPK" @click="triggerPrint" />
                <UButton icon="i-lucide-pencil" color="primary" label="Edit di Workspace" @click="router.visit(`/services/${so.id}/edit`)" />
            </div>
        </div>

        <!-- Printable SPK Sheet Card -->
        <div id="spk-print-area" class="rounded-xl border border-default bg-default p-6 shadow-sm space-y-6">
            <!-- Header Bengkel Info -->
            <div class="flex flex-col sm:flex-row justify-between border-b border-default pb-4 gap-4">
                <div>
                    <h2 class="text-lg font-bold text-highlighted">{{ so.store?.name || 'POS BENGKEL SYSTEM' }}</h2>
                    <p class="text-xs text-muted">{{ so.store?.address || 'Jl. Workshop Bengkel Utama No. 1' }}</p>
                    <p class="text-xs text-muted">Telp/WA: {{ so.store?.phone || '0812-3456-7890' }}</p>
                </div>
                <div class="sm:text-right font-mono">
                    <p class="text-xs font-bold text-primary">SURAT PERINTAH KERJA (SPK)</p>
                    <p class="text-sm font-black text-highlighted">{{ so.number }}</p>
                    <p class="text-[11px] text-muted">Tanggal: {{ so.checkin_at }}</p>
                </div>
            </div>

            <!-- Customer & Vehicle Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-lg bg-elevated/40 p-4 border border-default">
                <!-- Vehicle Info -->
                <div class="space-y-1.5 text-xs">
                    <p class="font-bold text-highlighted text-sm flex items-center gap-1.5">
                        <UIcon name="i-lucide-car-front" class="size-4 text-primary" /> Data Kendaraan
                    </p>
                    <div class="flex justify-between border-b border-default/50 pb-1">
                        <span class="text-muted">Nomor Polisi:</span>
                        <span class="font-mono font-bold text-amber-500 bg-black px-2 py-0.5 rounded text-xs">{{ so.plate_number }}</span>
                    </div>
                    <div class="flex justify-between border-b border-default/50 pb-1">
                        <span class="text-muted">Merek / Model:</span>
                        <span class="font-medium text-highlighted">{{ so.vehicle_brand || '-' }} {{ so.vehicle_model || '' }}</span>
                    </div>
                    <div class="flex justify-between border-b border-default/50 pb-1">
                        <span class="text-muted">Kilometer Odometer:</span>
                        <span class="font-mono font-semibold text-highlighted">{{ so.odometer ? `${so.odometer} KM` : '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Tahun / Warna:</span>
                        <span class="font-medium text-highlighted">{{ so.year || '-' }} / {{ so.color || '-' }}</span>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="space-y-1.5 text-xs">
                    <p class="font-bold text-highlighted text-sm flex items-center gap-1.5">
                        <UIcon name="i-lucide-user" class="size-4 text-primary" /> Data Pelanggan
                    </p>
                    <div class="flex justify-between border-b border-default/50 pb-1">
                        <span class="text-muted">Nama Pelanggan:</span>
                        <span class="font-semibold text-highlighted">{{ so.customer_name }}</span>
                    </div>
                    <div class="flex justify-between border-b border-default/50 pb-1">
                        <span class="text-muted">No. Telepon / WA:</span>
                        <span class="font-mono text-highlighted">{{ so.customer_phone || '-' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Waktu Selesai:</span>
                        <span class="font-mono text-highlighted">{{ so.completed_at || 'Dalam Proses' }}</span>
                    </div>
                </div>
            </div>

            <!-- Complaint & Diagnosis Notes -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="rounded-lg border border-default p-3 bg-default">
                    <p class="font-bold text-highlighted mb-1 flex items-center gap-1">
                        <UIcon name="i-lucide-alert-circle" class="size-3.5 text-amber-500" /> Keluhan Utama Pelanggan:
                    </p>
                    <p class="text-muted leading-relaxed whitespace-pre-line">{{ so.general_complaint || 'Tidak ada catatan keluhan.' }}</p>
                </div>

                <div class="rounded-lg border border-default p-3 bg-default">
                    <p class="font-bold text-highlighted mb-1 flex items-center gap-1">
                        <UIcon name="i-lucide-stethoscope" class="size-3.5 text-indigo-500" /> Diagnosis & Catatan Perbaikan:
                    </p>
                    <p class="text-muted leading-relaxed whitespace-pre-line">{{ so.diagnosis || 'Belum ada catatan diagnosis.' }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <div class="space-y-2">
                <h3 class="text-sm font-bold text-highlighted">Rincian Suku Cadang & Jasa Pekerjaan</h3>
                <div class="overflow-x-auto rounded-lg border border-default">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-elevated/60 text-muted font-semibold border-b border-default">
                                <th class="p-2.5">No</th>
                                <th class="p-2.5">Tipe</th>
                                <th class="p-2.5">Deskripsi Pekerjaan / Barang</th>
                                <th class="p-2.5">Montir / Mekanik</th>
                                <th class="p-2.5 text-center">Qty</th>
                                <th class="p-2.5 text-right">Harga Satuan</th>
                                <th class="p-2.5 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-default">
                            <tr v-for="(item, idx) in so.items" :key="item.id || idx" class="hover:bg-elevated/30">
                                <td class="p-2.5 text-muted">{{ idx + 1 }}</td>
                                <td class="p-2.5">
                                    <span
                                        class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase font-mono border"
                                        :class="item.item_type === 'labor' ? 'bg-amber-500/10 text-amber-600 border-amber-500/20' : 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20'"
                                    >
                                        {{ item.item_type === 'labor' ? 'JASA' : 'PART' }}
                                    </span>
                                </td>
                                <td class="p-2.5 font-medium text-highlighted">{{ item.description }}</td>
                                <td class="p-2.5 text-muted">
                                    <span v-if="item.mechanic" class="inline-flex items-center gap-1 font-medium text-amber-500">
                                        <UIcon name="i-lucide-user" class="size-3" /> {{ item.mechanic.name }}
                                    </span>
                                    <span v-else class="text-slate-400 font-mono text-[11px]">-</span>
                                </td>
                                <td class="p-2.5 text-center font-mono font-bold">{{ item.quantity }}</td>
                                <td class="p-2.5 text-right font-mono">{{ formatCurrency(item.unit_price) }}</td>
                                <td class="p-2.5 text-right font-mono font-bold text-highlighted">{{ formatCurrency(item.line_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Total Bar -->
            <div class="flex justify-end border-t border-default pt-4">
                <div class="w-full sm:w-72 space-y-1 text-xs">
                    <div class="flex justify-between font-bold text-sm text-highlighted border-t border-default pt-2">
                        <span>Estimasi Total Biaya:</span>
                        <span class="font-mono text-emerald-500 text-lg">{{ formatCurrency(so.estimated_total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
