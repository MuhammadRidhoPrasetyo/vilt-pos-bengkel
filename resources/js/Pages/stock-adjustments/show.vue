<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Detail Stock Adjustment', panelId: 'stock-adjustments' }],
});

const props = defineProps({ record: Object });
const record = computed(() => props.record?.data || props.record || {});
const currency = (value) => Number(value || 0).toLocaleString('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
const postRecord = () => router.post(`/stock-adjustments/${record.value.id}/post`);
const cancelRecord = () => router.post(`/stock-adjustments/${record.value.id}/cancel`);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 border-b border-default pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm text-muted">Stock Adjustment</p>
                <h1 class="text-2xl font-bold text-highlighted">{{ record.reference_number }}</h1>
                <p class="text-sm text-muted">{{ record.store?.name || '-' }} · {{ record.occurred_at || '-' }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link href="/stock-adjustments" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">Kembali</Link>
                <Link v-if="record.status === 'draft'" :href="`/stock-adjustments/${record.id}/edit`" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated">Edit</Link>
                <button v-if="record.status === 'draft'" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted" type="button" @click="postRecord">Posting</button>
                <button v-if="record.status === 'draft'" class="rounded-md border border-default px-4 py-2 text-sm font-medium hover:bg-elevated" type="button" @click="cancelRecord">Cancel Draft</button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">Status</p>
                <p class="mt-1 text-xl font-bold text-highlighted">{{ record.status }}</p>
            </div>
            <div class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">Posted By</p>
                <p class="mt-1 text-xl font-bold text-highlighted">{{ record.posted_by?.name || '-' }}</p>
            </div>
            <div class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">Catatan</p>
                <p class="mt-1 text-sm text-highlighted">{{ record.note || '-' }}</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-default bg-default">
            <table class="min-w-full divide-y divide-default">
                <thead class="bg-elevated/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Varian</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Gudang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Tipe</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">Unit Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    <tr v-for="item in record.items || []" :key="item.id">
                        <td class="px-4 py-3 text-sm font-semibold text-highlighted">{{ item.product_variant?.name || '-' }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ item.warehouse?.name || '-' }} / {{ item.warehouse_location?.name || 'Tanpa lokasi' }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ item.adjustment_type }}</td>
                        <td class="px-4 py-3 text-right text-sm font-semibold">{{ item.quantity }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ currency(item.unit_cost) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="overflow-x-auto rounded-lg border border-default bg-default">
            <div class="border-b border-default px-4 py-3 font-semibold text-highlighted">Inventory Movement Ledger</div>
            <table class="min-w-full divide-y divide-default">
                <tbody class="divide-y divide-default">
                    <tr v-for="movement in record.movements || []" :key="movement.id">
                        <td class="px-4 py-3 text-sm font-semibold" :class="movement.type === 'in' ? 'text-emerald-600' : 'text-red-600'">{{ movement.type }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ movement.product_variant?.name || '-' }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ movement.warehouse?.name || '-' }}</td>
                        <td class="px-4 py-3 text-right text-sm">{{ movement.quantity }}</td>
                        <td class="px-4 py-3 text-right text-sm text-muted">Saldo {{ movement.balance_after }}</td>
                    </tr>
                    <tr v-if="(record.movements || []).length === 0"><td class="px-4 py-6 text-center text-sm text-muted">Ledger akan muncul setelah dokumen diposting.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
