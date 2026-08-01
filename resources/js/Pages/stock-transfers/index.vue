<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Stock Transfer', panelId: 'stock-transfers' }],
});

const props = defineProps({ records: Object, summary: Object, filters: Object, options: Object });
const rows = computed(() => props.records?.data || []);
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const storeId = ref(props.filters?.storeId || '');
const showDelete = ref(false);
const deleteTarget = ref(null);

const statusItems = [
    { label: 'Semua Status', value: '' },
    { label: 'Draft', value: 'draft' },
    { label: 'Posted', value: 'posted' },
    { label: 'Cancelled', value: 'cancelled' },
];
const storeItems = computed(() => [{ label: 'Semua Toko', value: '' }, ...(props.options?.stores || [])]);

const applyFilters = () => router.get('/stock-transfers', {
    search: search.value || undefined,
    status: status.value || undefined,
    store_id: storeId.value || undefined,
}, { preserveState: true, replace: true });
const postRecord = (record) => router.post(`/stock-transfers/${record.id}/post`);
const cancelRecord = (record) => router.post(`/stock-transfers/${record.id}/cancel`);
const askDelete = (record) => {
    deleteTarget.value = record;
    showDelete.value = true;
};
const confirmDelete = () => {
    if (deleteTarget.value) {
        router.delete(`/stock-transfers/${deleteTarget.value.id}`);
    }
    showDelete.value = false;
};

watch([search, status, storeId], applyFilters);
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 border-b border-default pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-highlighted">Stock Transfer</h1>
                <p class="text-sm text-muted">Pemindahan stok antar toko/gudang dengan batch FIFO.</p>
            </div>
            <Link href="/stock-transfers/create" class="inline-flex items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90">
                <UIcon name="i-lucide-plus" class="size-4" />
                Buat Transfer
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
                <input v-model="search" class="w-full rounded-md border border-default bg-elevated/30 py-2 pl-9 pr-3 text-sm outline-none focus:border-primary" placeholder="Cari referensi atau catatan" />
            </div>
            <USelect v-model="status" :items="statusItems" class="w-full lg:w-48" />
            <USelect v-model="storeId" :items="storeItems" class="w-full lg:w-56" />
        </div>

        <div class="overflow-x-auto rounded-lg border border-default bg-default">
            <table class="min-w-full divide-y divide-default">
                <thead class="bg-elevated/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Referensi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Asal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Tujuan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-muted">Item</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-muted">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-default">
                    <tr v-for="record in rows" :key="record.id" class="hover:bg-elevated/20">
                        <td class="px-4 py-3 text-sm font-semibold text-highlighted">{{ record.reference_number }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ record.from_store?.name || '-' }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ record.to_store?.name || '-' }}</td>
                        <td class="px-4 py-3 text-sm">{{ record.status }}</td>
                        <td class="px-4 py-3 text-sm text-muted">{{ record.items_count || record.items?.length || 0 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/stock-transfers/${record.id}`" class="rounded-md border border-default p-2 hover:bg-elevated"><UIcon name="i-lucide-eye" class="size-4" /></Link>
                                <Link v-if="record.status === 'draft'" :href="`/stock-transfers/${record.id}/edit`" class="rounded-md border border-default p-2 hover:bg-elevated"><UIcon name="i-lucide-pencil" class="size-4" /></Link>
                                <button v-if="record.status === 'draft'" type="button" class="rounded-md border border-emerald-500/20 p-2 text-emerald-600 hover:bg-emerald-500/10" @click="postRecord(record)"><UIcon name="i-lucide-send" class="size-4" /></button>
                                <button v-if="record.status === 'draft'" type="button" class="rounded-md border border-zinc-500/20 p-2 text-zinc-600 hover:bg-zinc-500/10" @click="cancelRecord(record)"><UIcon name="i-lucide-ban" class="size-4" /></button>
                                <button v-if="record.status === 'draft'" type="button" class="rounded-md border border-red-500/20 p-2 text-red-600 hover:bg-red-500/10" @click="askDelete(record)"><UIcon name="i-lucide-trash-2" class="size-4" /></button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="rows.length === 0"><td colspan="6" class="px-4 py-8 text-center text-sm text-muted">Belum ada stock transfer.</td></tr>
                </tbody>
            </table>
        </div>

        <DeleteConfirmationModal v-model:open="showDelete" title="Hapus Stock Transfer" description="Draft stock transfer akan dihapus permanen." @confirm="confirmDelete" />
    </div>
</template>
