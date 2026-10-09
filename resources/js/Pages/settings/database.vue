<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
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
const showLocalRestoreModal = ref(false);
const selectedLocalBackup = ref(null);
const fileInputRef = ref(null);
const selectedFile = ref(null);
const isDragging = ref(false);
const clientError = ref('');

const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadStatusText = ref('');

const localRestoreForm = useForm({
    filename: '',
});

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

const parseSizeToBytes = (sizeStr) => {
    if (!sizeStr) return 128 * 1024 * 1024;
    const units = { B: 1, K: 1024, M: 1024 * 1024, G: 1024 * 1024 * 1024 };
    const match = sizeStr.toString().match(/^(\d+)([KMG]?)$/i);
    if (!match) return 128 * 1024 * 1024;
    const value = parseInt(match[1], 10);
    const unit = match[2]?.toUpperCase() || 'B';
    return value * (units[unit] || 1);
};

const maxUploadBytes = computed(() => parseSizeToBytes(props.info?.upload_max_filesize));
const maxUploadLabel = computed(() => props.info?.upload_max_filesize || '128M');

const validateAndSetFile = (file) => {
    clientError.value = '';
    if (!file) return;

    const extension = file.name.split('.').pop()?.toLowerCase();
    if (!['sqlite', 'db'].includes(extension)) {
        clientError.value = 'Format file harus bertipe .sqlite atau .db';
        return;
    }

    if (file.size > maxUploadBytes.value) {
        clientError.value = `Ukuran file (${formatBytes(file.size)}) melebihi batas upload server (${maxUploadLabel.value}).`;
        return;
    }

    selectedFile.value = file;
};

const handleFileChange = (e) => {
    const file = e.target.files?.[0];
    validateAndSetFile(file);
};

const handleDrop = (e) => {
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    validateAndSetFile(file);
};

const handleDragOver = () => {
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const removeSelectedFile = () => {
    selectedFile.value = null;
    clientError.value = '';
    if (fileInputRef.value) fileInputRef.value.value = '';
};

const triggerFileInput = () => {
    fileInputRef.value?.click();
};

const confirmRestore = () => {
    if (!selectedFile.value) return;
    clientError.value = '';
    showConfirmModal.value = true;
};

const readSliceAsBase64 = (blob) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
            const res = reader.result;
            const base64 = typeof res === 'string' && res.includes(',') ? res.split(',')[1] : res;
            resolve(base64);
        };
        reader.onerror = () => reject(new Error('Gagal membaca potongan file lokal.'));
        reader.readAsDataURL(blob);
    });
};

const executeRestore = async () => {
    showConfirmModal.value = false;
    clientError.value = '';

    const file = selectedFile.value;
    if (!file) return;

    // Setiap chunk berukuran 64 KB (65.536 bytes).
    // Ukuran super kecil ini JAUH di bawah ambang batas buffer server apa pun,
    // sehingga diproses 100% di RAM tanpa pernah memicu error folder sementara!
    const CHUNK_SIZE = 64 * 1024;
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const uploadId = 'up_' + Date.now() + '_' + Math.random().toString(36).substring(2, 8);

    isUploading.value = true;
    uploadProgress.value = 0;
    uploadStatusText.value = 'Mempersiapkan potongan berkas database...';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    try {
        for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
            const start = chunkIndex * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunkBlob = file.slice(start, end);

            uploadStatusText.value = `Mengunggah potongan ${chunkIndex + 1} dari ${totalChunks}...`;

            const base64Chunk = await readSliceAsBase64(chunkBlob);

            const payload = {
                upload_id: uploadId,
                chunk_index: chunkIndex,
                total_chunks: totalChunks,
                file_name: file.name,
                chunk_data: base64Chunk,
            };

            const response = await fetch('/settings/database/upload-chunk', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const responseText = await response.text();
            let resJson = null;

            try {
                resJson = JSON.parse(responseText);
            } catch (jsonErr) {
                const cleanError = responseText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                throw new Error(cleanError || `Respon server pada potongan ke-${chunkIndex + 1} tidak valid (Status ${response.status}).`);
            }

            if (!response.ok || !resJson?.success) {
                throw new Error(resJson?.message || `Gagal mengunggah potongan ke-${chunkIndex + 1} (Status ${response.status}).`);
            }

            uploadProgress.value = Math.round(((chunkIndex + 1) / totalChunks) * 100);

            if (resJson.is_completed) {
                uploadStatusText.value = 'Verifikasi dan restorasi database berhasil!';
                selectedFile.value = null;
                if (fileInputRef.value) fileInputRef.value.value = '';
                router.reload({ preserveScroll: true });
                return;
            }
        }
    } catch (err) {
        clientError.value = err.message || 'Terjadi kesalahan saat memproses restorasi database.';
    } finally {
        isUploading.value = false;
        uploadStatusText.value = '';
    }
};

const confirmLocalRestore = (backup) => {
    selectedLocalBackup.value = backup;
    localRestoreForm.filename = backup.name;
    showLocalRestoreModal.value = true;
};

const executeLocalRestore = () => {
    showLocalRestoreModal.value = false;
    localRestoreForm.post('/settings/database/restore-backup', {
        preserveScroll: true,
        onSuccess: () => {
            selectedLocalBackup.value = null;
            localRestoreForm.reset();
        },
    });
};

const deleteLocalBackup = (backup) => {
    if (!confirm(`Hapus file backup "${backup.name}"?`)) return;
    router.delete(`/settings/database/delete-backup/${backup.name}`, {
        preserveScroll: true,
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
                        v-if="$can('database-backup.export')"
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
                        class="border-2 border-dashed rounded-xl p-5 text-center cursor-pointer transition-all duration-150 select-none"
                        :class="[
                            isDragging
                                ? 'border-primary bg-primary/10 ring-2 ring-primary/20 scale-[0.99]'
                                : selectedFile
                                    ? 'border-emerald-500/40 bg-emerald-500/5'
                                    : 'border-default hover:border-primary/60 bg-elevated/30 hover:bg-elevated/50'
                        ]"
                        @dragover.prevent="handleDragOver"
                        @dragenter.prevent="handleDragOver"
                        @dragleave.prevent="handleDragLeave"
                        @drop.prevent="handleDrop"
                        @click="triggerFileInput"
                    >
                        <div v-if="selectedFile" class="space-y-2">
                            <div class="size-10 rounded-xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center mx-auto">
                                <UIcon name="i-lucide-file-check" class="size-5" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-highlighted truncate max-w-xs mx-auto">{{ selectedFile.name }}</p>
                                <p class="text-[10px] text-muted font-mono mt-0.5">{{ formatBytes(selectedFile.size) }}</p>
                            </div>
                            <div class="pt-1">
                                <button
                                    type="button"
                                    class="text-[11px] font-semibold text-rose-500 hover:text-rose-600 hover:underline px-2 py-0.5"
                                    @click.stop="removeSelectedFile"
                                >
                                    Hapus & Ganti File
                                </button>
                            </div>
                        </div>
                        <div v-else class="space-y-1.5 py-1">
                            <div class="size-10 rounded-xl bg-elevated text-muted/80 flex items-center justify-center mx-auto">
                                <UIcon name="i-lucide-upload-cloud" class="size-5" />
                            </div>
                            <p class="text-xs font-semibold text-highlighted">
                                {{ isDragging ? 'Lepaskan file di sini' : 'Klik atau seret file SQLite ke sini' }}
                            </p>
                            <p class="text-[10px] text-muted">Format yang didukung: <span class="font-mono">.sqlite</span> atau <span class="font-mono">.db</span> (Maks {{ maxUploadLabel }})</p>
                        </div>
                    </div>

                    <!-- Upload Progress Bar (Chunked Uploading to Storage) -->
                    <div v-if="isUploading" class="space-y-2 py-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-primary flex items-center gap-1.5">
                                <UIcon name="i-lucide-loader-2" class="size-3.5 animate-spin" />
                                <span>{{ uploadStatusText }}</span>
                            </span>
                            <span class="font-mono font-bold text-highlighted">{{ uploadProgress }}%</span>
                        </div>
                        <div class="w-full bg-elevated rounded-full h-2 overflow-hidden border border-default">
                            <div
                                class="bg-primary h-full transition-all duration-200 rounded-full"
                                :style="{ width: `${uploadProgress}%` }"
                            ></div>
                        </div>
                        <p class="text-[10px] text-muted text-center">
                            Menyimpan potongan data sementara ke internal project storage...
                        </p>
                    </div>

                    <p v-if="clientError" class="text-xs text-rose-500 font-medium flex items-center gap-1.5">
                        <UIcon name="i-lucide-alert-circle" class="size-3.5 shrink-0" />
                        <span>{{ clientError }}</span>
                    </p>
                </div>

                <div class="pt-3 border-t border-default/60">
                    <button
                        v-if="$can('database-backup.import')"
                        type="button"
                        :disabled="!selectedFile || isUploading"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors shadow-xs"
                        @click="confirmRestore"
                    >
                        <UIcon v-if="isUploading" name="i-lucide-loader-2" class="size-4 animate-spin" />
                        <UIcon v-else name="i-lucide-refresh-cw" class="size-4" />
                        <span>{{ isUploading ? `Memproses (${uploadProgress}%)...` : 'Restore Database' }}</span>
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
                            <th class="px-3.5 py-2.5">Jumlah Tabel</th>
                            <th class="px-3.5 py-2.5">Tanggal Dibuat</th>
                            <th class="px-3.5 py-2.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-default font-mono">
                        <tr v-for="b in backups" :key="b.name" class="hover:bg-elevated/30">
                            <td class="px-3.5 py-2.5 font-bold text-highlighted flex items-center gap-2">
                                <UIcon name="i-lucide-shield-check" class="size-4 text-emerald-500 shrink-0" />
                                <span>{{ b.name }}</span>
                            </td>
                            <td class="px-3.5 py-2.5 text-muted">{{ formatBytes(b.size) }}</td>
                            <td class="px-3.5 py-2.5 font-bold text-primary">{{ b.table_count || 0 }} Tabel</td>
                            <td class="px-3.5 py-2.5 text-muted">{{ b.modified_at }}</td>
                            <td class="px-3.5 py-2.5 text-right">
                                <div class="inline-flex items-center gap-1.5 font-sans">
                                    <a
                                        :href="`/settings/database/download-backup/${b.name}`"
                                        class="px-2 py-1 rounded-lg bg-elevated hover:bg-elevated/80 text-muted hover:text-highlighted text-[11px] font-semibold transition-colors inline-flex items-center gap-1"
                                        title="Unduh Salinan ke Komputer"
                                    >
                                        <UIcon name="i-lucide-download" class="size-3.5" />
                                        <span>Unduh</span>
                                    </a>
                                    <button
                                        v-if="$can('database-backup.import')"
                                        type="button"
                                        :disabled="localRestoreForm.processing"
                                        class="px-2 py-1 rounded-lg bg-amber-500/10 hover:bg-amber-500/20 text-amber-600 dark:text-amber-400 text-[11px] font-semibold transition-colors inline-flex items-center gap-1"
                                        @click="confirmLocalRestore(b)"
                                        title="Pulihkan database langsung dari salinan file ini"
                                    >
                                        <UIcon name="i-lucide-history" class="size-3.5" />
                                        <span>Restore</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="p-1 rounded-lg text-muted/60 hover:text-rose-500 hover:bg-rose-500/10 transition-colors"
                                        @click="deleteLocalBackup(b)"
                                        title="Hapus berkas backup dari server"
                                    >
                                        <UIcon name="i-lucide-trash-2" class="size-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal Upload / Import -->
    <div
        v-if="showConfirmModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="showConfirmModal = false"
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
                    :disabled="isUploading"
                    class="w-full px-4 py-2 rounded-xl border border-default text-xs font-bold text-highlighted hover:bg-elevated disabled:opacity-50 transition-colors"
                    @click="showConfirmModal = false"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="isUploading"
                    class="w-full px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-bold hover:bg-amber-700 disabled:opacity-50 transition-colors shadow-xs"
                    @click="executeRestore"
                >
                    {{ isUploading ? 'Memproses...' : 'Ya, Timpa & Restore' }}
                </button>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal Restore from Local Backup -->
    <div
        v-if="showLocalRestoreModal"
        class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4"
        @click.self="showLocalRestoreModal = false"
    >
        <div class="bg-default border border-default rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4 animate-in fade-in zoom-in-95 duration-150">
            <div class="size-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center mx-auto">
                <UIcon name="i-lucide-history" class="size-6" />
            </div>

            <div class="text-center space-y-1.5">
                <h3 class="text-base font-extrabold text-highlighted">Restore dari Backup Local</h3>
                <p class="text-xs text-muted leading-relaxed">
                    Anda yakin ingin memulihkan database dari file cadangan server:
                </p>
                <p class="text-xs font-mono font-bold text-primary bg-elevated/80 p-2 rounded-lg border border-default break-all">
                    {{ selectedLocalBackup?.name }}
                </p>
                <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold bg-amber-500/10 p-2.5 rounded-lg border border-amber-500/20 text-left mt-2">
                    * Catatan: Data saat ini tetap akan dicadangkan secara otomatis sebelum restore dilakukan.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2">
                <button
                    type="button"
                    :disabled="localRestoreForm.processing"
                    class="w-full px-4 py-2 rounded-xl border border-default text-xs font-bold text-highlighted hover:bg-elevated disabled:opacity-50 transition-colors"
                    @click="showLocalRestoreModal = false"
                >
                    Batal
                </button>
                <button
                    type="button"
                    :disabled="localRestoreForm.processing"
                    class="w-full px-4 py-2 rounded-xl bg-amber-600 text-white text-xs font-bold hover:bg-amber-700 disabled:opacity-50 transition-colors shadow-xs"
                    @click="executeLocalRestore"
                >
                    {{ localRestoreForm.processing ? 'Memproses...' : 'Ya, Pulihkan Database' }}
                </button>
            </div>
        </div>
    </div>
</template>
