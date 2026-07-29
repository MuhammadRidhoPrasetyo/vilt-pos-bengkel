<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: DashboardLayout,
});

const props = defineProps({
    info: Object,
    backups: Array,
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const showConfirmModal = ref(false);
const fileInputRef = ref(null);
const selectedFile = ref(null);

const form = useForm({
    database_file: null,
});

const formatBytes = (bytes, decimals = 2) => {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        selectedFile.value = file;
        form.database_file = file;
    }
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const confirmRestore = () => {
    if (!form.database_file) return;
    showConfirmModal.value = true;
};

const executeRestore = () => {
    showConfirmModal.value = false;
    form.post('/settings/database/import', {
        preserveScroll: true,
        onSuccess: () => {
            selectedFile.value = null;
            form.reset();
            if (fileInputRef.value) fileInputRef.value.value = '';
        },
    });
};
</script>

<template>
    <Head title="Export & Import Database SQLite" />

    <div class="space-y-6 max-w-5xl mx-auto pb-12">
        <!-- Header Title -->
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-extrabold text-highlighted tracking-tight flex items-center gap-2">
                    <UIcon name="i-lucide-database-backup" class="size-6 text-primary" />
                    <span>Backup & Restore Database SQLite</span>
                </h1>
                <p class="text-xs text-muted mt-0.5">
                    Kelola ekspor salinan cadangan (*export backup*) dan pemulihan (*restore import*) data aplikasi POS Bengkel.
                </p>
            </div>
        </div>

        <!-- Flash Messages -->
        <div v-if="flashSuccess" class="p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 dark:text-emerald-400 text-xs font-semibold flex items-center gap-2">
            <UIcon name="i-lucide-check-circle-2" class="size-5 shrink-0" />
            <span>{{ flashSuccess }}</span>
        </div>

        <div v-if="flashError" class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-600 dark:text-rose-400 text-xs font-semibold flex items-center gap-2">
            <UIcon name="i-lucide-alert-triangle" class="size-5 shrink-0" />
            <span>{{ flashError }}</span>
        </div>

        <!-- Top Overview Stats Card -->
        <div class="bg-default border border-default rounded-2xl p-5 shadow-xs">
            <h2 class="text-sm font-bold text-highlighted mb-3 flex items-center gap-2">
                <UIcon name="i-lucide-hard-drive" class="size-4 text-primary" />
                <span>Informasi Database SQLite Aktif</span>
            </h2>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-elevated/60 border border-default rounded-xl p-3">
                    <span class="text-[11px] text-muted block font-medium">Driver Database</span>
                    <span class="text-sm font-bold font-mono text-highlighted mt-0.5 block">{{ info?.driver || 'SQLite' }}</span>
                </div>
                <div class="bg-elevated/60 border border-default rounded-xl p-3">
                    <span class="text-[11px] text-muted block font-medium">Ukuran File</span>
                    <span class="text-sm font-bold font-mono text-emerald-600 dark:text-emerald-400 mt-0.5 block">{{ formatBytes(info?.file_size) }}</span>
                </div>
                <div class="bg-elevated/60 border border-default rounded-xl p-3">
                    <span class="text-[11px] text-muted block font-medium">Jumlah Tabel</span>
                    <span class="text-sm font-bold font-mono text-primary mt-0.5 block">{{ info?.table_count || 0 }} Tabel</span>
                </div>
                <div class="bg-elevated/60 border border-default rounded-xl p-3">
                    <span class="text-[11px] text-muted block font-medium">Edit Terakhir</span>
                    <span class="text-xs font-bold font-mono text-highlighted mt-1 block truncate">{{ info?.last_modified }}</span>
                </div>
            </div>
        </div>

        <!-- Action Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Export Database Card -->
            <div class="bg-default border border-default rounded-2xl p-5 shadow-xs flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <div class="size-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center">
                        <UIcon name="i-lucide-download" class="size-5" />
                    </div>
                    <h2 class="text-sm font-bold text-highlighted">Export / Unduh Database (.sqlite)</h2>
                    <p class="text-xs text-muted leading-relaxed">
                        Unduh salinan berkas database SQLite saat ini. File ini dapat Anda simpan secara lokal sebagai cadangan berkala atau dipindahkan ke komputer lain.
                    </p>
                </div>

                <div class="pt-3 border-t border-default/60">
                    <a
                        href="/settings/database/export"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 transition-colors shadow-xs"
                    >
                        <UIcon name="i-lucide-download-cloud" class="size-4" />
                        <span>Unduh Backup Database (.sqlite)</span>
                    </a>
                </div>
            </div>

            <!-- Import Database Card -->
            <div class="bg-default border border-default rounded-2xl p-5 shadow-xs flex flex-col justify-between space-y-4">
                <div class="space-y-2">
                    <div class="size-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                        <UIcon name="i-lucide-upload" class="size-5" />
                    </div>
                    <h2 class="text-sm font-bold text-highlighted">Import / Restore Database</h2>
                    <p class="text-xs text-muted leading-relaxed">
                        Unggah berkas database SQLite (`.sqlite` / `.db`) untuk memulihkan seluruh data aplikasi. Sistem akan membuat backup otomatis sebelum melakukan restore.
                    </p>

                    <!-- File Drop/Upload Area -->
                    <input
                        ref="fileInputRef"
                        type="file"
                        accept=".sqlite,.db"
                        class="hidden"
                        @change="handleFileChange"
                    />

                    <div
                        class="border-2 border-dashed border-default hover:border-primary rounded-xl p-4 text-center cursor-pointer transition-colors bg-elevated/30"
                        @click="triggerFileInput"
                    >
                        <div v-if="selectedFile" class="space-y-1">
                            <UIcon name="i-lucide-file-check" class="size-6 text-emerald-600 mx-auto" />
                            <p class="text-xs font-bold text-highlighted truncate">{{ selectedFile.name }}</p>
                            <p class="text-[10px] text-muted font-mono">{{ formatBytes(selectedFile.size) }}</p>
                        </div>
                        <div v-else class="space-y-1">
                            <UIcon name="i-lucide-file-up" class="size-6 text-muted/60 mx-auto" />
                            <p class="text-xs font-semibold text-highlighted">Klik untuk memilih file database</p>
                            <p class="text-[10px] text-muted">Format yang didukung: .sqlite atau .db (Maks 100MB)</p>
                        </div>
                    </div>

                    <p v-if="form.errors.database_file" class="text-xs text-rose-500 font-medium">
                        {{ form.errors.database_file }}
                    </p>
                </div>

                <div class="pt-3 border-t border-default/60">
                    <button
                        type="button"
                        :disabled="!selectedFile || form.processing"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-xs"
                        @click="confirmRestore"
                    >
                        <UIcon v-if="form.processing" name="i-lucide-loader-2" class="size-4 animate-spin" />
                        <UIcon v-else name="i-lucide-refresh-cw" class="size-4" />
                        <span>{{ form.processing ? 'Memproses Restore...' : 'Restore Database' }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- History Safety Backups Table -->
        <div v-if="backups && backups.length > 0" class="bg-default border border-default rounded-2xl p-5 shadow-xs space-y-3">
            <h3 class="text-sm font-bold text-highlighted flex items-center gap-2">
                <UIcon name="i-lucide-history" class="size-4 text-muted" />
                <span>Riwayat Automatic Safety Backup Local</span>
            </h3>
            <p class="text-xs text-muted">
                Salinan database yang otomatis dibuat oleh sistem sebelum Anda melakukan overwrite / restore terakhir.
            </p>

            <div class="overflow-x-auto rounded-xl border border-default">
                <table class="w-full text-left text-xs">
                    <thead class="bg-elevated/70 text-muted uppercase font-bold text-[10px] border-b border-default">
                        <tr>
                            <th class="px-3.5 py-2.5">Nama Berkas Backup</th>
                            <th class="px-3.5 py-2.5">Ukuran</th>
                            <th class="px-3.5 py-2.5">Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default font-mono">
                        <tr v-for="b in backups" :key="b.name" class="hover:bg-elevated/30">
                            <td class="px-3.5 py-2.5 font-bold text-highlighted flex items-center gap-2">
                                <UIcon name="i-lucide-shield-check" class="size-4 text-emerald-500 shrink-0" />
                                <span>{{ b.name }}</span>
                            </td>
                            <td class="px-3.5 py-2.5 text-muted">{{ formatBytes(b.size) }}</td>
                            <td class="px-3.5 py-2.5 text-muted">{{ b.modified_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div
        v-if="showConfirmModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div class="bg-default border border-default rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
            <div class="size-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center mx-auto">
                <UIcon name="i-lucide-alert-triangle" class="size-6" />
            </div>

            <div class="text-center space-y-1.5">
                <h3 class="text-base font-extrabold text-highlighted">Konfirmasi Restore Database</h3>
                <p class="text-xs text-muted leading-relaxed">
                    Anda yakin ingin mengganti seluruh database saat ini dengan file <span class="font-bold font-mono text-highlighted">{{ selectedFile?.name }}</span>?
                </p>
                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold bg-amber-500/10 p-2.5 rounded-lg border border-amber-500/20 text-left mt-2">
                    * Catatan: Salinan database aktif saat ini akan otomatis dibuatkan backup di folder local server sebelum penggantian dilakukan.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2">
                <button
                    type="button"
                    class="w-full px-4 py-2 rounded-xl border border-default text-xs font-bold text-highlighted hover:bg-elevated transition-colors"
                    @click="showConfirmModal = false"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="w-full px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-bold hover:bg-amber-700 transition-colors shadow-xs"
                    @click="executeRestore"
                >
                    Ya, Timpa & Restore
                </button>
            </div>
        </div>
    </div>
</template>
