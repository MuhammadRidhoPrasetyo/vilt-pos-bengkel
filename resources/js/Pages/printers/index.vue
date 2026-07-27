<script setup>
import DeleteConfirmationModal from '../../Components/DeleteConfirmationModal.vue';
import PaginationLinks from '../../Components/PaginationLinks.vue';
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Konfigurasi Printer', panelId: 'printers' }],
});

const props = defineProps({
    printers: Object,
    summary: Object,
    filters: Object,
    options: Object,
});

const search = ref(props.filters?.search || '');
const storeFilter = ref(props.filters?.store_id || '');

const modalMode = ref(null); // 'create' | 'edit'
const selectedPrinter = ref(null);

const testModalOpen = ref(false);
const testPrinterData = ref(null);
const testing = ref(false);

const deleteModalOpen = ref(false);
const itemToDelete = ref(null);
const deleting = ref(false);

const form = useForm({
    store_id: props.options?.stores?.[0]?.value || '',
    name: '',
    connection_type: 'network',
    address: '192.168.1.200',
    is_default: false,
});

const storeOptions = computed(() => [
    { label: 'Semua Cabang', value: '' },
    ...(props.options?.stores || []),
]);

watch([search, storeFilter], () => {
    router.get('/printers', {
        search: search.value,
        store_id: storeFilter.value,
    }, { preserveState: true, replace: true });
});

const openCreate = () => {
    selectedPrinter.value = null;
    form.reset();
    form.clearErrors();
    form.store_id = props.options?.stores?.[0]?.value || '';
    form.connection_type = 'network';
    form.address = '192.168.1.200';
    form.is_default = false;
    modalMode.value = 'create';
};

const openEdit = (printer) => {
    selectedPrinter.value = printer;
    form.clearErrors();
    form.store_id = printer.store_id;
    form.name = printer.name;
    form.connection_type = printer.connection_type;
    form.address = printer.address;
    form.is_default = printer.is_default;
    modalMode.value = 'edit';
};

const closeModal = () => {
    modalMode.value = null;
};

const submit = () => {
    const options = {
        preserveScroll: true,
        onSuccess: closeModal,
    };

    if (modalMode.value === 'edit') {
        form.put(`/printers/${selectedPrinter.value.id}`, options);
        return;
    }

    form.post('/printers', options);
};

const openTestModal = async (printer) => {
    testing.value = true;
    testPrinterData.value = null;

    try {
        const response = await fetch(`/printers/${printer.id}/test`);
        const json = await response.json();
        if (json.status === 'success') {
            testPrinterData.value = json.test_data;
            testModalOpen.value = true;
        }
    } catch (e) {
        alert('Gagal mengambil data uji coba printer.');
    } finally {
        testing.value = false;
    }
};

const triggerBrowserPrint = () => {
    window.print();
};

const confirmDelete = () => {
    if (!itemToDelete.value) {
        return;
    }

    deleting.value = true;
    router.delete(`/printers/${itemToDelete.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteModalOpen.value = false;
            itemToDelete.value = null;
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
};

const openDelete = (printer) => {
    itemToDelete.value = printer;
    deleteModalOpen.value = true;
};

const rows = computed(() => props.printers?.data || []);

const columns = [
    {
        accessorKey: 'name',
        header: 'Nama Printer',
        meta: { class: { td: 'font-medium text-highlighted' } },
    },
    {
        accessorKey: 'store',
        header: 'Cabang Toko',
        cell: ({ row }) => row.original.store?.name || '-',
    },
    {
        accessorKey: 'connection_type',
        header: 'Koneksi',
    },
    {
        accessorKey: 'address',
        header: 'Alamat / IP',
        meta: { class: { td: 'font-mono text-xs' } },
    },
    {
        accessorKey: 'is_default',
        header: 'Status',
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { class: { th: 'w-28 text-right', td: 'w-28 text-right' } },
    },
];
</script>

<template>
    <div class="space-y-4">
        <!-- Summary Cards -->
        <div class="grid gap-3 sm:grid-cols-4">
            <UCard :ui="{ body: 'p-4' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Total Printer</p>
                        <p class="mt-1 text-2xl font-bold text-highlighted">{{ summary?.total_count || 0 }} Unit</p>
                    </div>
                    <div class="rounded-lg bg-primary/10 p-2.5 text-primary">
                        <UIcon name="i-lucide-printer" class="size-6" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-4' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Printer Default Toko</p>
                        <p class="mt-1 text-2xl font-bold text-highlighted">{{ summary?.default_count || 0 }} Aktif</p>
                    </div>
                    <div class="rounded-lg bg-amber-500/10 p-2.5 text-amber-500">
                        <UIcon name="i-lucide-star" class="size-6" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-4' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Printer LAN / IP Network</p>
                        <p class="mt-1 text-2xl font-bold text-highlighted">{{ summary?.network_count || 0 }} Unit</p>
                    </div>
                    <div class="rounded-lg bg-emerald-500/10 p-2.5 text-emerald-500">
                        <UIcon name="i-lucide-network" class="size-6" />
                    </div>
                </div>
            </UCard>

            <UCard :ui="{ body: 'p-4' }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted">Printer USB / Bluetooth</p>
                        <p class="mt-1 text-2xl font-bold text-highlighted">{{ (summary?.usb_count || 0) }} Unit</p>
                    </div>
                    <div class="rounded-lg bg-indigo-500/10 p-2.5 text-indigo-500">
                        <UIcon name="i-lucide-usb" class="size-6" />
                    </div>
                </div>
            </UCard>
        </div>

        <!-- Toolbar -->
        <UDashboardToolbar
            :ui="{
                root: 'min-h-0 flex-col items-stretch gap-3 overflow-visible border-b-0 px-0 sm:flex-row sm:items-center sm:px-0',
                left: 'w-full sm:w-auto flex-1',
                right: 'w-full flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center sm:justify-end',
            }"
        >
            <template #left>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center w-full">
                    <div class="relative flex-1 sm:w-64">
                        <UIcon name="i-lucide-search" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted" />
                        <input
                            v-model="search"
                            class="w-full rounded-md border border-default bg-default py-2 pl-9 pr-3 text-sm outline-none focus:border-primary"
                            type="search"
                            placeholder="Cari nama printer / IP..."
                        />
                    </div>
                    <USelect
                        v-model="storeFilter"
                        :items="storeOptions"
                        class="sm:w-44"
                        :ui="{ trailingIcon: 'group-data-[state=open]:rotate-180 transition-transform duration-200' }"
                    />
                </div>
            </template>

            <template #right>
                <UButton icon="i-lucide-plus" label="Tambah Printer" class="w-full justify-center sm:w-auto" @click="openCreate" />
            </template>
        </UDashboardToolbar>

        <!-- Table -->
        <UCard :ui="{ root: 'overflow-hidden', body: 'p-0!' }">
            <div class="overflow-x-auto">
                <UTable
                    :data="rows"
                    :columns="columns"
                    :get-row-id="(row) => String(row.id)"
                    class="min-w-[820px]"
                    :empty="'Belum ada printer dikonfigurasi.'"
                    :ui="{
                        base: 'table-fixed border-separate border-spacing-0',
                        thead: '[&>tr]:bg-elevated/50 [&>tr]:after:content-none',
                        tbody: '[&>tr]:last:[&>td]:border-b-0',
                        th: 'first:rounded-tl-lg last:rounded-tr-lg border-b border-default',
                        td: 'border-b border-default',
                    }"
                >
                    <template #connection_type-cell="{ row }">
                        <span
                            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium uppercase font-mono border"
                            :class="row.original.connection_type === 'network' ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : row.original.connection_type === 'bluetooth' ? 'bg-blue-500/10 text-blue-600 border-blue-500/20' : 'bg-purple-500/10 text-purple-600 border-purple-500/20'"
                        >
                            <UIcon :name="row.original.connection_type === 'network' ? 'i-lucide-network' : row.original.connection_type === 'bluetooth' ? 'i-lucide-bluetooth' : 'i-lucide-usb'" class="size-3.5" />
                            {{ row.original.connection_type }}
                        </span>
                    </template>

                    <template #is_default-cell="{ row }">
                        <span v-if="row.original.is_default" class="inline-flex items-center gap-1 rounded-md bg-amber-500/10 text-amber-600 px-2 py-0.5 text-xs font-semibold border border-amber-500/20">
                            <UIcon name="i-lucide-star" class="size-3.5" /> DEFAULT
                        </span>
                        <span v-else class="text-xs text-muted">Biasa</span>
                    </template>

                    <template #actions-cell="{ row }">
                        <div class="flex justify-end gap-1.5">
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-primary/30 text-primary hover:bg-primary/10"
                                type="button"
                                title="Test Print"
                                @click="openTestModal(row.original)"
                            >
                                <UIcon name="i-lucide-printer" class="size-4" />
                            </button>
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-default text-muted hover:bg-elevated hover:text-highlighted"
                                type="button"
                                title="Edit"
                                @click="openEdit(row.original)"
                            >
                                <UIcon name="i-lucide-pencil" class="size-4" />
                            </button>
                            <button
                                class="inline-flex size-8 items-center justify-center rounded-md border border-error/30 text-error hover:bg-error/10"
                                type="button"
                                title="Hapus"
                                @click="openDelete(row.original)"
                            >
                                <UIcon name="i-lucide-trash-2" class="size-4" />
                            </button>
                        </div>
                    </template>
                </UTable>
            </div>
        </UCard>

        <PaginationLinks :links="printers.meta.links" />

        <!-- Form Modal (Create / Edit) -->
        <div v-if="modalMode" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-default p-5 shadow-2xl">
                <div class="flex items-center justify-between border-b border-default pb-3">
                    <h2 class="text-lg font-bold text-highlighted flex items-center gap-2">
                        <UIcon name="i-lucide-printer" class="size-5 text-primary" />
                        {{ modalMode === 'create' ? 'Tambah Printer Toko' : 'Edit Printer Toko' }}
                    </h2>
                    <button class="rounded-md p-2 hover:bg-elevated text-muted" type="button" @click="closeModal">
                        <UIcon name="i-lucide-x" class="size-5" />
                    </button>
                </div>

                <form class="mt-4 space-y-4" @submit.prevent="submit">
                    <label class="grid gap-1 text-xs">
                        <span class="font-medium text-highlighted">Cabang Toko / Bengkel <span class="text-red-500">*</span></span>
                        <select v-model="form.store_id" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required>
                            <option v-for="opt in options.stores" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                        <span v-if="form.errors.store_id" class="text-red-500">{{ form.errors.store_id }}</span>
                    </label>

                    <label class="grid gap-1 text-xs">
                        <span class="font-medium text-highlighted">Nama Printer <span class="text-red-500">*</span></span>
                        <input v-model="form.name" type="text" placeholder="Contoh: Printer Kasir 58mm" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary" required />
                        <span v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</span>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="grid gap-1 text-xs">
                            <span class="font-medium text-highlighted">Tipe Koneksi <span class="text-red-500">*</span></span>
                            <select v-model="form.connection_type" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary">
                                <option value="network">Network / LAN IP</option>
                                <option value="usb">USB Port / Local</option>
                                <option value="bluetooth">Bluetooth</option>
                            </select>
                        </label>

                        <label class="grid gap-1 text-xs">
                            <span class="font-medium text-highlighted">Alamat IP / Port / MAC <span class="text-red-500">*</span></span>
                            <input v-model="form.address" type="text" placeholder="192.168.1.200 / LPT1" class="rounded-md border border-default bg-default px-3 py-2 text-sm outline-none focus:border-primary font-mono" required />
                            <span v-if="form.errors.address" class="text-red-500">{{ form.errors.address }}</span>
                        </label>
                    </div>

                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-default p-3 hover:bg-elevated/40">
                        <input v-model="form.is_default" type="checkbox" class="size-4 rounded text-primary accent-primary" />
                        <span class="text-xs font-medium text-highlighted">Jadikan Printer Default Toko Ini</span>
                    </label>

                    <div class="flex justify-end gap-2 pt-2">
                        <UButton color="neutral" variant="outline" label="Batal" type="button" @click="closeModal" />
                        <UButton type="submit" label="Simpan Printer" icon="i-lucide-check" :loading="form.processing" />
                    </div>
                </form>
            </div>
        </div>

        <!-- Test Print Modal (Receipt Thermal Preview) -->
        <div v-if="testModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-sm rounded-xl bg-default p-5 shadow-2xl space-y-4">
                <div class="flex items-center justify-between border-b border-default pb-2">
                    <h2 class="text-sm font-bold text-highlighted flex items-center gap-2">
                        <UIcon name="i-lucide-printer" class="size-4 text-primary" /> Uji Coba Printer POS
                    </h2>
                    <button class="rounded-md p-1.5 hover:bg-elevated text-muted" type="button" @click="testModalOpen = false">
                        <UIcon name="i-lucide-x" class="size-4" />
                    </button>
                </div>

                <!-- Thermal Paper Sheet Preview -->
                <div id="test-receipt-print-area" class="rounded-md border border-dashed border-default bg-white p-4 font-mono text-[11px] text-zinc-900 shadow-inner select-none leading-relaxed">
                    <div class="text-center font-bold uppercase border-b border-dashed border-zinc-300 pb-2">
                        <p class="text-xs">{{ testPrinterData?.store_name }}</p>
                        <p class="text-[10px] font-normal text-zinc-600">{{ testPrinterData?.store_address }}</p>
                        <p class="text-[10px] font-normal text-zinc-600">Telp: {{ testPrinterData?.store_phone }}</p>
                    </div>

                    <div class="my-2 text-center font-bold">
                        <p>*** TEST PRINT RECEIPT ***</p>
                        <p class="text-[10px] font-normal text-zinc-600">{{ testPrinterData?.timestamp }}</p>
                    </div>

                    <div class="border-y border-dashed border-zinc-300 py-1.5 space-y-0.5 text-[10px]">
                        <div class="flex justify-between">
                            <span>Printer:</span>
                            <span class="font-bold">{{ testPrinterData?.printer_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Tipe Koneksi:</span>
                            <span>{{ testPrinterData?.connection_type }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Alamat/IP:</span>
                            <span>{{ testPrinterData?.address }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Status:</span>
                            <span class="font-bold text-emerald-600">TERHUBUNG (OK)</span>
                        </div>
                    </div>

                    <!-- Character Test Lines -->
                    <div class="my-2 space-y-1">
                        <p class="font-bold">Item Uji Coba:</p>
                        <div class="flex justify-between">
                            <span>1x TEST THERMAL PRINT</span>
                            <span>Rp 1.000</span>
                        </div>
                        <div class="flex justify-between">
                            <span>1x POLA KARAKTER TES</span>
                            <span>Rp 1.000</span>
                        </div>
                        <p class="text-[9px] text-zinc-500 truncate mt-1">ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789</p>
                    </div>

                    <div class="border-t border-dashed border-zinc-300 pt-2 text-center text-[10px] text-zinc-600">
                        <p class="font-bold">Uji coba cetak berhasil!</p>
                        <p class="text-[9px]">Printer siap digunakan untuk Kasir & SPK.</p>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <UButton color="neutral" variant="outline" label="Tutup" @click="testModalOpen = false" />
                    <UButton icon="i-lucide-printer" label="Cetak Struk Tes" @click="triggerBrowserPrint" />
                </div>
            </div>
        </div>

        <DeleteConfirmationModal
            v-model:open="deleteModalOpen"
            title="Hapus Printer?"
            :description="`Konfigurasi printer ${itemToDelete?.name || ''} akan dihapus dari cabang toko.`"
            :loading="deleting"
            @confirm="confirmDelete"
        />
    </div>
</template>
