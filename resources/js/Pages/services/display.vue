<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    activeOrders: Object,
    store: Object,
});

const currentTime = ref('');
const currentDate = ref('');
const autoRefreshSeconds = ref(10);
const countdown = ref(10);
let clockTimer = null;
let countdownTimer = null;
const isAudioEnabled = ref(true);

const ordersList = computed(() => props.activeOrders?.data || props.activeOrders || []);

const checkinOrders = computed(() => ordersList.value.filter(o => o.status === 'checkin'));
const waitingPartsOrders = computed(() => ordersList.value.filter(o => o.status === 'waiting_parts'));
const inProgressOrders = computed(() => ordersList.value.filter(o => o.status === 'in_progress'));
const readyOrders = computed(() => ordersList.value.filter(o => o.status === 'ready'));

const updateClock = () => {
    const now = new Date();
    currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
};

const playChime = () => {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
        osc.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.3); // A5
        gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.5);
    } catch (e) {
        // AudioContext disabled by browser policy until user gesture
    }
};

const toggleAudio = () => {
    isAudioEnabled.value = !isAudioEnabled.value;
    if (isAudioEnabled.value) {
        playChime();
    }
};

const toggleFullScreen = () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen().catch(() => {});
        }
    }
};

const getMechanicName = (order) => {
    if (!order.items || order.items.length === 0) return 'Belum ditentukan';
    const laborItem = order.items.find(i => i.mechanic?.name);
    return laborItem ? laborItem.mechanic.name : 'Mekanik Standby';
};

const formatTimeAgo = (dateTimeStr) => {
    if (!dateTimeStr) return '';
    const checkin = new Date(dateTimeStr);
    const now = new Date();
    const diffMins = Math.floor((now - checkin) / 60000);
    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins} menit lalu`;
    const hours = Math.floor(diffMins / 60);
    const mins = diffMins % 60;
    return `${hours}j ${mins}m lalu`;
};

onMounted(() => {
    updateClock();
    clockTimer = setInterval(updateClock, 1000);

    countdownTimer = setInterval(() => {
        if (countdown.value > 1) {
            countdown.value--;
        } else {
            countdown.value = autoRefreshSeconds.value;
            router.reload({
                preserveScroll: true,
                preserveState: true,
            });
        }
    }, 1000);
});

onUnmounted(() => {
    if (clockTimer) clearInterval(clockTimer);
    if (countdownTimer) clearInterval(countdownTimer);
});
</script>

<template>
    <Head title="Monitor Status Antrean Servis Bengkel" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none overflow-hidden">
        <!-- Progress Bar Indicator -->
        <div class="h-1 bg-slate-800 w-full overflow-hidden">
            <div
                class="h-full bg-gradient-to-r from-blue-500 via-orange-500 via-amber-500 to-emerald-500 transition-all duration-1000 ease-linear"
                :style="{ width: `${((autoRefreshSeconds - countdown) / autoRefreshSeconds) * 100}%` }"
            ></div>
        </div>

        <!-- TV Header -->
        <header class="px-6 py-4 bg-slate-900/90 border-b border-slate-800 flex items-center justify-between shadow-2xl backdrop-blur-md">
            <div class="flex items-center gap-4">
                <div class="size-12 rounded-xl bg-blue-600/20 border border-blue-500/40 flex items-center justify-center text-blue-400 shadow-inner">
                    <UIcon name="i-lucide-wrench" class="size-7 animate-pulse" />
                </div>
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-white flex items-center gap-3">
                        {{ store?.name || 'POS BENGKEL' }}
                        <span class="text-xs uppercase tracking-widest px-2.5 py-1 rounded-full bg-blue-500/20 text-blue-400 border border-blue-500/30">
                            Monitor Status Pitstop
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400 font-medium">Status pengerjaan servis & antrean kendaraan secara real-time</p>
                </div>
            </div>

            <!-- Clock & Action Tools -->
            <div class="flex items-center gap-6">
                <!-- Status Badges Count -->
                <div class="hidden lg:flex items-center gap-3 bg-slate-950/80 px-4 py-2 rounded-xl border border-slate-800 text-xs">
                    <span class="text-slate-400">Total Aktif: <strong class="text-white font-bold text-sm">{{ ordersList.length }}</strong></span>
                    <span class="text-slate-700">|</span>
                    <span class="text-orange-400">Tunggu Part: <strong class="font-bold text-sm">{{ waitingPartsOrders.length }}</strong></span>
                    <span class="text-slate-700">|</span>
                    <span class="text-amber-400">Proses: <strong class="font-bold text-sm">{{ inProgressOrders.length }}</strong></span>
                    <span class="text-slate-700">|</span>
                    <span class="text-emerald-400">Siap: <strong class="font-bold text-sm">{{ readyOrders.length }}</strong></span>
                </div>

                <!-- Clock -->
                <div class="text-right">
                    <div class="text-3xl font-black font-mono tracking-wider text-emerald-400 drop-shadow">
                        {{ currentTime }}
                    </div>
                    <div class="text-[11px] font-medium text-slate-400 uppercase tracking-wider">
                        {{ currentDate }}
                    </div>
                </div>

                <!-- Controls -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-slate-300 transition-colors"
                        :title="isAudioEnabled ? 'Matikan Suara Alert' : 'Aktifkan Suara Alert'"
                        @click="toggleAudio"
                    >
                        <UIcon :name="isAudioEnabled ? 'i-lucide-volume-2' : 'i-lucide-volume-x'" class="size-5" />
                    </button>
                    <button
                        type="button"
                        class="p-2.5 rounded-xl border border-slate-800 bg-slate-900 hover:bg-slate-800 text-slate-300 transition-colors"
                        title="Tampilan Layar Penuh"
                        @click="toggleFullScreen"
                    >
                        <UIcon name="i-lucide-maximize" class="size-5" />
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Display Content Grid -->
        <main class="flex-1 p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 overflow-hidden">
            <!-- Left & Middle Column (8 Cols) -->
            <div class="lg:col-span-8 flex flex-col gap-5 overflow-hidden">
                <!-- Section 1: Ready / Siap Diambil (High Priority Top Display) -->
                <div v-if="readyOrders.length > 0" class="bg-emerald-950/40 border border-emerald-500/40 rounded-2xl p-4 shadow-xl shadow-emerald-950/20 backdrop-blur-sm">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h2 class="text-lg font-extrabold text-emerald-400 flex items-center gap-2">
                            <UIcon name="i-lucide-check-circle-2" class="size-6 text-emerald-400 animate-bounce" />
                            SIAP DIAMBIL / SILAKAN MENUJU KASIR
                        </h2>
                        <span class="px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-500/40">
                            {{ readyOrders.length }} Kendaraan
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="order in readyOrders"
                            :key="order.id"
                            class="bg-slate-900/90 border-2 border-emerald-500/60 rounded-xl p-3.5 flex items-center justify-between shadow-lg relative overflow-hidden group hover:border-emerald-400 transition-all"
                        >
                            <div class="space-y-1 relative z-10">
                                <div class="inline-block bg-black text-amber-300 font-mono font-black text-lg px-3 py-1 rounded-md border border-amber-400/50 shadow-md">
                                    {{ order.plate_number }}
                                </div>
                                <p class="text-sm font-bold text-white uppercase">{{ order.vehicle_brand }} {{ order.vehicle_model }}</p>
                                <p class="text-xs text-slate-400 font-medium">Pemilik: <span class="text-slate-200">{{ order.customer_name }}</span></p>
                            </div>

                            <div class="text-right relative z-10">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-500/20 text-emerald-300 text-xs font-black uppercase tracking-wider border border-emerald-500/40">
                                    <UIcon name="i-lucide-check" class="size-4" /> SELESAI
                                </span>
                                <p class="text-[11px] text-slate-400 mt-2 font-mono">{{ order.number }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Menunggu Sparepart (Waiting Parts - Top Priority Before In Progress) -->
                <div v-if="waitingPartsOrders.length > 0" class="bg-orange-950/30 border border-orange-500/40 rounded-2xl p-4 shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-base font-extrabold text-orange-400 flex items-center gap-2">
                            <UIcon name="i-lucide-package" class="size-5 text-orange-400" />
                            MENUNGGU SPAREPART
                        </h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-orange-500/20 text-orange-300 text-xs font-bold border border-orange-500/30">
                            {{ waitingPartsOrders.length }} Unit
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="order in waitingPartsOrders"
                            :key="order.id"
                            class="bg-slate-900 border border-slate-800 p-3 rounded-xl flex items-center justify-between shadow"
                        >
                            <div>
                                <span class="bg-black text-amber-300 font-mono font-bold text-sm px-2.5 py-0.5 rounded border border-amber-400/40 inline-block">
                                    {{ order.plate_number }}
                                </span>
                                <p class="text-xs font-bold text-slate-200 mt-1 uppercase">{{ order.vehicle_brand }} {{ order.vehicle_model }}</p>
                                <p class="text-[11px] text-slate-400">Pemilik: {{ order.customer_name }}</p>
                            </div>
                            <span class="text-[10px] text-orange-400 font-bold uppercase bg-orange-500/10 px-2.5 py-1 rounded border border-orange-500/20">
                                Tunggu Part
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Sedang Dikerjakan (In Progress) -->
                <div class="flex-1 bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 flex flex-col overflow-hidden shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-extrabold text-amber-400 flex items-center gap-2 tracking-wide">
                            <UIcon name="i-lucide-cog" class="size-6 animate-spin text-amber-400" />
                            SEDANG DIKERJAKAN DI PITSTOP
                        </h2>
                        <span class="px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/40">
                            {{ inProgressOrders.length }} Unit
                        </span>
                    </div>

                    <div v-if="inProgressOrders.length === 0" class="flex-1 flex flex-col items-center justify-center text-slate-500 py-10">
                        <UIcon name="i-lucide-wrench" class="size-12 text-slate-700 mb-2" />
                        <p class="text-xs font-medium">Belum ada kendaraan yang sedang dikerjakan di pitstop.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4 overflow-y-auto pr-1">
                        <div
                            v-for="order in inProgressOrders"
                            :key="order.id"
                            class="bg-slate-900 border border-slate-800 hover:border-amber-500/50 rounded-xl p-4 flex flex-col justify-between shadow-md transition-all hover:bg-slate-850"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="bg-black text-amber-300 font-mono font-black text-base px-3 py-1 rounded border border-amber-400/40 shadow">
                                        {{ order.plate_number }}
                                    </div>
                                    <span class="text-xs text-slate-400 font-mono flex items-center gap-1">
                                        <UIcon name="i-lucide-clock" class="size-3.5 text-amber-400" />
                                        {{ formatTimeAgo(order.checkin_at) }}
                                    </span>
                                </div>

                                <h3 class="text-base font-bold text-white tracking-wide">
                                    {{ order.vehicle_brand || '' }} {{ order.vehicle_model || 'Motor/Mobil' }}
                                </h3>

                                <p class="text-xs text-slate-400 mt-1 line-clamp-2">
                                    <strong class="text-slate-300">Keluhan:</strong> {{ order.general_complaint || 'Servis Rutin' }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 text-slate-300">
                                    <div class="size-6 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-[10px]">
                                        <UIcon name="i-lucide-user" class="size-3.5" />
                                    </div>
                                    <span class="font-medium truncate max-w-[130px]">{{ getMechanicName(order) }}</span>
                                </div>
                                <span class="px-2.5 py-1 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20 font-bold text-[11px] uppercase">
                                    Proses Servis
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Queue Check-In (4 Cols) -->
            <div class="lg:col-span-4 flex flex-col overflow-hidden">
                <!-- Section: Antrean Menunggu Pitstop (Check-in) -->
                <div class="flex-1 bg-slate-900/60 border border-slate-800/80 rounded-2xl p-5 flex flex-col overflow-hidden shadow-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-base font-extrabold text-blue-400 flex items-center gap-2 tracking-wide">
                            <UIcon name="i-lucide-list-ordered" class="size-5 text-blue-400" />
                            ANTEAN MENUNGGU
                        </h2>
                        <span class="px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-bold border border-blue-500/40">
                            {{ checkinOrders.length }} Antrean
                        </span>
                    </div>

                    <div v-if="checkinOrders.length === 0" class="flex-1 flex flex-col items-center justify-center text-slate-500 py-8">
                        <UIcon name="i-lucide-check-circle" class="size-10 text-slate-700 mb-2" />
                        <p class="text-xs">Tidak ada antrean kendaraan menunggu.</p>
                    </div>

                    <div v-else class="space-y-3 overflow-y-auto pr-1 flex-1">
                        <div
                            v-for="(order, idx) in checkinOrders"
                            :key="order.id"
                            class="bg-slate-900 border border-slate-800/90 rounded-xl p-3 flex items-center justify-between hover:border-blue-500/40 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="size-8 rounded-lg bg-blue-600/20 border border-blue-500/30 text-blue-400 font-black font-mono text-sm flex items-center justify-center">
                                    #{{ idx + 1 }}
                                </div>

                                <div>
                                    <div class="bg-black text-amber-300 font-mono font-bold text-xs px-2 py-0.5 rounded border border-amber-400/30 inline-block">
                                        {{ order.plate_number }}
                                    </div>
                                    <p class="text-xs font-bold text-white mt-1">{{ order.vehicle_brand || '' }} {{ order.vehicle_model || 'Motor/Mobil' }}</p>
                                    <p class="text-[11px] text-slate-400">Pemilik: {{ order.customer_name }}</p>
                                </div>
                            </div>

                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded bg-blue-500/10 text-blue-400 border border-blue-500/20 text-[10px] font-bold uppercase">
                                    Terdaftar
                                </span>
                                <p class="text-[10px] text-slate-400 font-mono mt-1">{{ formatTimeAgo(order.checkin_at) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
