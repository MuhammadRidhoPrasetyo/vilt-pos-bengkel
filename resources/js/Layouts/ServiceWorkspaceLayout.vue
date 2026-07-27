<script setup>
import { router } from '@inertiajs/vue3';
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
    <div class="flex h-screen w-screen flex-col overflow-hidden bg-slate-950 text-slate-100 antialiased font-sans">
        <!-- Top Workspace Bar -->
        <header class="flex h-14 shrink-0 items-center justify-between border-b border-slate-800 bg-slate-900/90 px-4 backdrop-blur-md">
            <!-- Left Info -->
            <div class="flex items-center gap-3">
                <div class="flex size-9 items-center justify-center rounded-lg bg-primary-600 text-white shadow-lg shadow-primary-600/30">
                    <UIcon name="i-lucide-wrench" class="size-5" />
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-white flex items-center gap-2">
                        {{ title }}
                        <span v-if="subtitle" class="rounded bg-slate-800 px-2 py-0.5 text-xs font-mono font-medium text-slate-300">{{ subtitle }}</span>
                    </h1>
                    <p class="text-[11px] text-slate-400">Terminal Input SPK & Service Advisor Bengkel</p>
                </div>
            </div>

            <!-- Right Controls -->
            <div class="flex items-center gap-4">
                <!-- Clock -->
                <div class="hidden sm:flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950/80 px-3 py-1 font-mono text-xs font-semibold text-slate-300">
                    <UIcon name="i-lucide-clock" class="size-3.5 text-primary-400" />
                    {{ currentTime }}
                </div>

                <!-- Return to Admin -->
                <button
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-200 transition-colors hover:bg-slate-700 hover:text-white"
                    type="button"
                    @click="router.visit('/services')"
                >
                    <UIcon name="i-lucide-arrow-left" class="size-4 text-slate-400" />
                    <span>Kembali ke Admin</span>
                </button>
            </div>
        </header>

        <!-- Fullscreen Content Slot -->
        <main class="flex-1 overflow-hidden p-3 bg-slate-950">
            <slot />
        </main>
    </div>
</template>
