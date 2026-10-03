<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { usePermission } from '../../composables/usePermission';

defineOptions({
    layout: [DashboardLayout, { title: 'Stock Opname', panelId: 'stock-opnames' }],
});

const { can } = usePermission();

const props = defineProps({
    records: Object,
    summary: Object,
    filters: Object,
    options: Object,
});

const rows = computed(() => props.records?.data || []);
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const storeId = ref(props.filters?.storeId || '');
const warehouseId = ref(props.filters?.warehouseId || '');
const showDelete = ref(false);
const deleteTarget = ref(null);

const statusItems = [
    { label: 'Semua Status', value: 'all' },
    { label: 'Draft', value: 'draft' },
    { label: 'Posted', value: 'posted' },
    { label: 'Cancelled', value: 'cancelled' },
];

const storeItems = computed(() => [{ label: 'Semua Toko', value: 'all' }, ...(props.options?.stores || []).map((s) => ({ label: s.label, value: String(s.value) }))]);
const warehouseItems = computed(() => {
    const list = (props.options?.warehouses || []).map((w) => ({ label: w.label, value: String(w.value), store_id: w.store_id }));
    if (!storeId.value || storeId.value === 'all') {
        return [{ label: 'Semua Gudang', value: 'all' }, ...list];
    }
    return [{ label: 'Semua Gudang', value: 'all' }, ...list.filter((w) => w.store_id === storeId.value)];
});

const badgeClass = (value) => ({
    draft: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
    posted: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
    cancelled: 'bg-zinc-500/10 text-zinc-600 dark:text-zinc-300',
}[value] || 'bg-zinc-500/10 text-zinc-600');

const formatRupiah = (val) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0);
};

const applyFilters = () => router.get('/stock-opnames', {
    search: search.value || undefined,
    status: (status.value && status.value !== 'all') ? status.value : undefined,
    store_id: (storeId.value && storeId.value !== 'all') ? storeId.value : undefined,
    warehouse_id: (warehouseId.value && warehouseId.value !== 'all') ? warehouseId.value : undefined,
}, { preserveState: true, replace: true });

const postRecord = (record) => {
    if (confirm(`Apakah Anda yakin ingin memposting sesi stock opname ${record.opname_number}? Kuantitas stok persediaan akan otomatis disesuaikan.`)) {
        router.post(`/stock-opnames/${record.id}/post`);
    }
};

const cancelRecord = (record) => {
    if (confirm(`Batalkan draft stock opname ${record.opname_number}?`)) {
        router.post(`/stock-opnames/${record.id}/cancel`);
    }
};

const askDelete = (record) => {
    deleteTarget.value = record;
    showDelete.value = true;
};

const confirmDelete = () => {
    if (deleteTarget.value) {
        router.delete(`/stock-opnames/${deleteTarget.value.id}`);
    }
    showDelete.value = false;
};

watch([search, status, storeId, warehouseId], applyFilters);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 border-b border-default pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-highlighted">Stock Opname</h1>
                <p class="text-sm text-muted">Penghitungan fisik persediaan gudang, rekonsiliasi selisih stok buku vs fisik, dan posting mutasi.</p>
            </div>
            <Link
                v-if="can('stock-opnames.create')"
                href="/stock-opnames/create"
                class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90"
            >
                <UIcon name="i-lucide-plus" class="size-4" />
                Mulai Sesi Opname
            </Link>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div v-for="item in ['draft', 'posted', 'cancelled']" :key="item" class="rounded-lg border border-default bg-default p-4">
                <p class="text-xs font-medium uppercase text-muted">{{ item }}</p>
                <p class="mt-1 text-2xl font-bold text-highlighted">{{ summary?.[item] || 0 }}</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 rounded-lg border border-default bg-default p-4 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <UIcon name="i-lucide-search" class="absolute left-3 top-2.5 size-4 text-muted" />
                <input v-model="search" class="w-full rounded-md border border-default bg-elevated/30 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" placeholder="Cari nomor opname atau catatan" />
            </div>
            <USelect v-model="status" :items="statusItems" class="w-full lg:w-44" />
            <USelect v-model="storeId" :items="storeItems" class="w-full lg:w-48" />
            <USelect v-model="warehouseId" :items="warehouseItems" class="w-full lg:w-48" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-default bg-default">
            <table class="min-w-full divide-y divide-default">
                <thead class="bg-elevated/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">No. Dokumen</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Toko & Gudang</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Status</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Item</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-muted">Selisih Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">Nilai Selisih</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    <tr v-for="record in rows" :key="record.id" class="hover:bg-elevated/20">
                        <td class="px-4 py-3 text-sm font-semibold text-highlighted font-mono">{{ record.opname_number }}</td>
                        <td class="px-4 py-3 text-sm">
                            <p class="font-medium text-highlighted">{{ record.store?.name || '-' }}</p>
                            <p class="text-xs text-muted">{{ record.warehouse?.name || '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm text-muted">{{ record.occurred_at || '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase" :class="badgeClass(record.status)">{{ record.status }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-mono text-muted">{{ record.items_count || record.items?.length || 0 }}</td>
                        <td class="px-4 py-3 text-center text-sm font-mono font-medium" :class="record.total_difference_qty < 0 ? 'text-red-500' : record.total_difference_qty > 0 ? 'text-emerald-500' : 'text-muted'">
                            {{ record.total_difference_qty > 0 ? '+' : '' }}{{ record.total_difference_qty }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm font-mono font-medium" :class="record.total_difference_value < 0 ? 'text-red-500' : record.total_difference_value > 0 ? 'text-emerald-500' : 'text-muted'">
                            {{ formatRupiah(record.total_difference_value) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Link
                                    v-if="can('stock-opnames.view')"
                                    :href="`/stock-opnames/${record.id}`"
                                    class="rounded-md border border-default p-2 hover:bg-elevated"
                                    title="Detail Hasil Opname"
                                >
                                    <UIcon name="i-lucide-eye" class="size-4" />
                                </Link>
                                <Link
                                    v-if="record.status === 'draft' && can('stock-opnames.edit')"
                                    :href="`/stock-opnames/${record.id}/edit`"
                                    class="rounded-md border border-default p-2 hover:bg-elevated"
                                    title="Input Hitungan Fisik"
                                >
                                    <UIcon name="i-lucide-pencil" class="size-4" />
                                </Link>
                                <button
                                    v-if="record.status === 'draft' && can('stock-opnames.post')"
                                    type="button"
                                    class="rounded-md border border-emerald-500/20 p-2 text-emerald-600 hover:bg-emerald-500/10"
                                    title="Posting / Sesuaikan Stok"
                                    @click="postRecord(record)"
                                >
                                    <UIcon name="i-lucide-send" class="size-4" />
                                </button>
                                <button
                                    v-if="record.status === 'draft' && can('stock-opnames.cancel')"
                                    type="button"
                                    class="rounded-md border border-zinc-500/20 p-2 text-zinc-600 hover:bg-zinc-500/10"
                                    title="Batalkan Draft"
                                    @click="cancelRecord(record)"
                                >
                                    <UIcon name="i-lucide-ban" class="size-4" />
                                </button>
                                <button
                                    v-if="record.status === 'draft' && can('stock-opnames.delete')"
                                    type="button"
                                    class="rounded-md border border-red-500/20 p-2 text-red-600 hover:bg-red-500/10"
                                    title="Hapus Draft"
                                    @click="askDelete(record)"
                                >
                                    <UIcon name="i-lucide-trash-2" class="size-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0">
                        <td colspan="8" class="p-8 text-center text-sm text-muted">
                            Belum ada dokumen sesi stock opname yang tercatat.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <DeleteConfirmationModal
            :open="showDelete"
            title="Hapus Draft Stock Opname"
            :message="`Apakah Anda yakin ingin menghapus draft sesi stock opname ${deleteTarget?.opname_number}? Tindakan ini tidak dapat dibatalkan.`"
            @close="showDelete = false"
            @confirm="confirmDelete"
        />
    </div>
</template>
