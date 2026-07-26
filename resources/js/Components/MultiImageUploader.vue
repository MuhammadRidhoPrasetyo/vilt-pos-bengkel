<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    existingMedia: {
        type: Array,
        default: () => [],
    },
    deleteMediaIds: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'Gambar Produk (Galeri)',
    },
    description: {
        type: String,
        default: 'Format JPG, PNG, atau WebP. Maks 2MB per gambar. Bisa upload lebih dari 1 gambar.',
    },
    maxSize: {
        type: Number,
        default: 2 * 1024 * 1024,
    },
});

const emit = defineEmits(['update:modelValue', 'update:deleteMediaIds']);

const fileInputRef = ref(null);
const errorMessage = ref('');

const ACCEPTED_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

const formatBytes = (bytes, decimals = 2) => {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
};

const activeExistingMedia = computed(() => {
    return props.existingMedia.filter((media) => !props.deleteMediaIds.includes(media.id));
});

const createPreviewUrl = (file) => {
    return URL.createObjectURL(file);
};

const openFilePicker = () => {
    fileInputRef.value?.click();
};

const handleFileSelect = (event) => {
    errorMessage.value = '';
    const files = Array.from(event.target.files || []);

    const validFiles = [];
    for (const file of files) {
        if (!ACCEPTED_TYPES.includes(file.type)) {
            errorMessage.value = `Format file "${file.name}" tidak valid. Hanya JPG, PNG, dan WebP yang diperbolehkan.`;
            continue;
        }

        if (file.size > props.maxSize) {
            errorMessage.value = `Ukuran file "${file.name}" terlalu besar (${formatBytes(file.size)}). Maksimal ${formatBytes(props.maxSize)}.`;
            continue;
        }

        validFiles.push(file);
    }

    if (validFiles.length > 0) {
        emit('update:modelValue', [...props.modelValue, ...validFiles]);
    }

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const removeNewFile = (index) => {
    const updated = [...props.modelValue];
    updated.splice(index, 1);
    emit('update:modelValue', updated);
};

const removeExistingMedia = (mediaId) => {
    emit('update:deleteMediaIds', [...new Set([...props.deleteMediaIds, mediaId])]);
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <label class="block text-sm font-medium text-highlighted">{{ label }}</label>
                <p v-if="description" class="text-xs text-muted">{{ description }}</p>
            </div>
            <UButton
                type="button"
                color="neutral"
                variant="outline"
                icon="i-lucide-upload-cloud"
                label="Pilih Gambar"
                size="sm"
                @click="openFilePicker"
            />
        </div>

        <input
            ref="fileInputRef"
            type="file"
            multiple
            accept="image/jpeg,image/png,image/webp,image/jpg"
            class="hidden"
            @change="handleFileSelect"
        />

        <p v-if="errorMessage" class="text-xs font-medium text-error">
            {{ errorMessage }}
        </p>

        <div v-if="activeExistingMedia.length > 0 || modelValue.length > 0" class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-5">
            <!-- Existing Media from server -->
            <div
                v-for="media in activeExistingMedia"
                :key="`existing-${media.id}`"
                class="group relative aspect-square overflow-hidden rounded-lg border border-default bg-elevated/40"
            >
                <img :src="media.thumb_url || media.url" :alt="media.name" class="h-full w-full object-cover" />
                <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                    <UButton
                        type="button"
                        color="error"
                        variant="solid"
                        icon="i-lucide-trash-2"
                        size="xs"
                        label="Hapus"
                        @click="removeExistingMedia(media.id)"
                    />
                </div>
                <span class="absolute bottom-1 left-1 max-w-[calc(100%-0.5rem)] truncate rounded bg-black/60 px-1 py-0.5 text-[10px] text-white">
                    {{ media.name }}
                </span>
            </div>

            <!-- Newly selected local files -->
            <div
                v-for="(file, index) in modelValue"
                :key="`new-${index}`"
                class="group relative aspect-square overflow-hidden rounded-lg border border-primary/40 bg-primary/5"
            >
                <img :src="createPreviewUrl(file)" :alt="file.name" class="h-full w-full object-cover" />
                <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                    <UButton
                        type="button"
                        color="error"
                        variant="solid"
                        icon="i-lucide-trash-2"
                        size="xs"
                        label="Hapus"
                        @click="removeNewFile(index)"
                    />
                </div>
                <span class="absolute bottom-1 left-1 max-w-[calc(100%-0.5rem)] truncate rounded bg-black/60 px-1 py-0.5 text-[10px] text-white">
                    Baru: {{ file.name }}
                </span>
            </div>
        </div>
    </div>
</template>
