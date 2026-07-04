<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap items-center gap-2">
        <component
            :is="link.url ? Link : 'span'"
            v-for="link in links"
            :key="link.label"
            :href="link.url || undefined"
            class="rounded-md border border-default px-3 py-2 text-sm"
            :class="[
                link.active ? 'bg-primary text-inverted' : 'text-muted hover:bg-elevated',
                !link.url ? 'cursor-not-allowed opacity-50' : '',
            ]"
            preserve-scroll
            v-html="link.label"
        />
    </div>
</template>
