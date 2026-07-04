<script setup>
const open = defineModel('open', {
    type: Boolean,
    default: false,
});

defineProps({
    title: {
        type: String,
        default: 'Konfirmasi hapus',
    },
    description: {
        type: String,
        default: 'Data yang sudah dihapus tidak dapat dikembalikan.',
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['confirm']);
</script>

<template>
    <UModal
        v-model:open="open"
        :title="title"
        :dismissible="!loading"
        :ui="{ footer: 'justify-end' }"
    >
        <template #body>
            <div class="flex gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <UIcon name="i-lucide-triangle-alert" class="size-5" />
                </div>
                <p class="text-sm text-muted">
                    {{ description }}
                </p>
            </div>
        </template>

        <template #footer>
            <UButton label="Batal" color="neutral" variant="outline" :disabled="loading" @click="open = false" />
            <UButton label="Hapus" color="error" icon="i-lucide-trash-2" :loading="loading" @click="$emit('confirm')" />
        </template>
    </UModal>
</template>
