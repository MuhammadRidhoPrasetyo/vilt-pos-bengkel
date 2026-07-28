<script setup>
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

defineProps({
    title: {
        type: String,
        default: 'Service Workstation',
    },
    subtitle: {
        type: String,
        default: '',
    },
});

const currentTime = ref('');
let timer = null;

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};

onMounted(() => {
    updateClock();
    timer = setInterval(updateClock, 1000);
});

onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <Head :title="title" />
    <div class="flex min-h-screen w-full flex-col bg-default text-highlighted antialiased font-sans lg:h-screen lg:overflow-hidden">
        <!-- Top Workspace Bar -->
        <header class="sticky top-0 z-30 flex h-14 shrink-0 items-center justify-between border-b border-default bg-elevated/90 px-3 sm:px-4 backdrop-blur-md">
            <!-- Left Info -->
            <div class="flex items-center gap-2.5 sm:gap-3">
                <div class="flex size-8 sm:size-9 items-center justify-center rounded-lg bg-primary text-white shadow-lg shadow-primary/20 shrink-0">
                    <UIcon name="i-lucide-wrench" class="size-4 sm:size-5" />
                </div>
                <div class="min-w-0">
                    <h1 class="text-xs sm:text-sm font-bold tracking-tight text-highlighted flex items-center gap-1.5 truncate">
                        <span class="truncate">{{ title }}</span>
                        <span v-if="subtitle" class="hidden sm:inline-block rounded bg-elevated border border-default px-2 py-0.5 text-xs font-mono font-medium text-muted">{{ subtitle }}</span>
                    </h1>
                    <p class="text-[10px] sm:text-[11px] text-muted truncate">Terminal Input SPK & Service Advisor Bengkel</p>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                <!-- Clock -->
                <div class="hidden md:flex items-center gap-1.5 rounded-lg border border-default bg-default px-2.5 py-1 font-mono text-xs font-semibold text-highlighted">
                    <UIcon name="i-lucide-clock" class="size-3.5 text-primary" />
                    {{ currentTime }}
                </div>

                <!-- Return to Admin -->
                <button
                    class="inline-flex items-center gap-1.5 rounded-lg border border-default bg-default px-2.5 py-1.5 text-xs font-semibold text-highlighted transition-colors hover:bg-elevated"
                    type="button"
                    @click="router.visit('/services')"
                >
                    <UIcon name="i-lucide-arrow-left" class="size-4 text-muted" />
                    <span class="hidden sm:inline">Kembali ke Admin</span>
                    <span class="sm:hidden">Admin</span>
                </button>
            </div>
        </header>

        <!-- Fullscreen Content Slot -->
        <main class="flex-1 p-2 sm:p-3 bg-default overflow-y-auto lg:overflow-hidden">
            <slot />
        </main>
    </div>
</template>
