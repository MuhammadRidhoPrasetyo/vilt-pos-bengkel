<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const user = computed(() => page.props.auth?.user || {});
const userRoles = computed(() => user.value.roles || []);
const userPermissions = computed(() => user.value.permissions || []);
const userStore = computed(() => user.value.store || null);

const isOwner = computed(() => 
    userRoles.value.includes('owner') || 
    userRoles.value.includes('admin') || 
    userRoles.value.includes('super-admin')
);
const isKasir = computed(() => userRoles.value.includes('kasir'));
const isMekanik = computed(() => userRoles.value.includes('mekanik'));

// Menu Access Rights
const canAccessAdmin = computed(() => {
    if (isOwner.value) return true;
    return userPermissions.value.some(p => 
        p.startsWith('stores.') || p.startsWith('users.') || p.startsWith('roles.') || p.startsWith('permissions.')
    );
});

const canAccessKasir = computed(() => {
    if (isOwner.value || isKasir.value) return true;
    return userPermissions.value.some(p => p.startsWith('pos.'));
});

const canAccessService = computed(() => {
    if (isOwner.value || isKasir.value || isMekanik.value) return true;
    return userPermissions.value.some(p => p.startsWith('services.') || p.startsWith('work-orders.'));
});

const handleLogout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Portal Seleksi Menu" />

    <div class="min-h-screen bg-zinc-950 text-zinc-100 flex flex-col justify-between selection:bg-emerald-500 selection:text-zinc-950 font-sans">
        <!-- Top Navigation / Header -->
        <header class="border-b border-zinc-800/80 bg-zinc-900/50 backdrop-blur-md sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <!-- Brand / Logo -->
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-zinc-950 font-bold shadow-lg shadow-emerald-500/20">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-extrabold tracking-wider text-lg text-white">POS BENGKEL</span>
                            <span class="px-2 py-0.5 text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full">v1.0</span>
                        </div>
                        <p class="text-xs text-zinc-400 hidden sm:block">Sistem Manajemen & Kasir Bengkel</p>
                    </div>
                </div>

                <!-- User Profile & Logout -->
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-semibold text-zinc-200">{{ user.name }}</p>
                        <div class="flex items-center justify-end gap-1.5 text-xs text-zinc-400">
                            <span v-if="userStore" class="text-emerald-400 font-medium">{{ userStore.name }}</span>
                            <span v-if="userStore && userRoles.length">•</span>
                            <span class="capitalize text-zinc-300">{{ userRoles.join(', ') || 'User' }}</span>
                        </div>
                    </div>

                    <button 
                        @click="handleLogout"
                        class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-zinc-300 hover:text-white bg-zinc-800/80 hover:bg-zinc-700/80 border border-zinc-700/60 rounded-xl transition duration-200 shadow-sm"
                        title="Keluar dari Aplikasi"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Keluar</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 flex flex-col justify-center">
            <!-- Hero / Greeting -->
            <div class="text-center max-w-2xl mx-auto mb-12">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-xs font-medium mb-4">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    Selamat Datang Kembali
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Pilih Menu Layanan Utama
                </h1>
                <p class="mt-3 text-zinc-400 text-sm sm:text-base leading-relaxed">
                    Silakan pilih modul kerja yang ingin Anda buka. Pilihan menu disesuaikan dengan hak akses & role akun Anda.
                </p>
            </div>

            <!-- 3 Cards Selection Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-6xl mx-auto w-full">

                <!-- 1. MENU ADMIN -->
                <div 
                    :class="[
                        'group relative rounded-2xl border p-6 flex flex-col justify-between transition-all duration-300 shadow-xl',
                        canAccessAdmin 
                            ? 'bg-zinc-900/70 hover:bg-zinc-900 border-zinc-800 hover:border-emerald-500/50 hover:shadow-emerald-500/10 hover:-translate-y-1 cursor-pointer' 
                            : 'bg-zinc-900/30 border-zinc-800/40 opacity-50 cursor-not-allowed'
                    ]"
                >
                    <div>
                        <!-- Top Icon & Badge -->
                        <div class="flex items-center justify-between mb-6">
                            <div :class="[
                                'h-14 w-14 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-105',
                                canAccessAdmin ? 'bg-gradient-to-br from-indigo-500/20 to-emerald-500/20 border border-indigo-500/30 text-indigo-400' : 'bg-zinc-800 border border-zinc-700 text-zinc-500'
                            ]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                </svg>
                            </div>

                            <span v-if="canAccessAdmin" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 border border-indigo-500/20 text-indigo-400">
                                Owner & Admin
                            </span>
                            <span v-else class="px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-500 border border-zinc-700/50">
                                Terkunci
                            </span>
                        </div>

                        <!-- Card Content -->
                        <h2 class="text-xl font-bold text-white mb-2 group-hover:text-emerald-400 transition-colors">
                            Menu Admin
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed mb-6">
                            Akses Dashboard Admin untuk memantau grafik statistik penjualan, mengelola stok barang, katalog produk, data user, dan pengaturan sistem.
                        </p>
                    </div>

                    <!-- Footer Action Link -->
                    <div class="pt-4 border-t border-zinc-800/60">
                        <Link 
                            v-if="canAccessAdmin"
                            href="/dashboard"
                            class="inline-flex items-center justify-between w-full px-4 py-2.5 rounded-xl bg-indigo-600/20 hover:bg-indigo-600 text-indigo-200 hover:text-white font-semibold text-xs sm:text-sm transition duration-200 group/btn"
                        >
                            <span>Buka Dashboard Admin</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <div v-else class="text-xs text-zinc-500 font-medium text-center py-2 bg-zinc-950/40 rounded-xl border border-zinc-800/40">
                            Akses Khusus Admin / Owner
                        </div>
                    </div>
                </div>

                <!-- 2. MENU KASIR / POS -->
                <div 
                    :class="[
                        'group relative rounded-2xl border p-6 flex flex-col justify-between transition-all duration-300 shadow-xl',
                        canAccessKasir 
                            ? 'bg-zinc-900/70 hover:bg-zinc-900 border-zinc-800 hover:border-emerald-500/50 hover:shadow-emerald-500/10 hover:-translate-y-1 cursor-pointer' 
                            : 'bg-zinc-900/30 border-zinc-800/40 opacity-50 cursor-not-allowed'
                    ]"
                >
                    <div>
                        <!-- Top Icon & Badge -->
                        <div class="flex items-center justify-between mb-6">
                            <div :class="[
                                'h-14 w-14 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-105',
                                canAccessKasir ? 'bg-gradient-to-br from-emerald-500/20 to-teal-500/20 border border-emerald-500/30 text-emerald-400' : 'bg-zinc-800 border border-zinc-700 text-zinc-500'
                            ]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                                </svg>
                            </div>

                            <span v-if="canAccessKasir" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                                Kasir & Penjualan
                            </span>
                            <span v-else class="px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-500 border border-zinc-700/50">
                                Terkunci
                            </span>
                        </div>

                        <!-- Card Content -->
                        <h2 class="text-xl font-bold text-white mb-2 group-hover:text-emerald-400 transition-colors">
                            Menu Kasir / POS
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed mb-6">
                            Buka modul Kasir Point of Sale (POS) untuk melayani transaksi pembelian sparepart, barang toko, dan pembayaran langsung secara cepat.
                        </p>
                    </div>

                    <!-- Footer Action Link -->
                    <div class="pt-4 border-t border-zinc-800/60">
                        <Link 
                            v-if="canAccessKasir"
                            href="/transactions/create"
                            class="inline-flex items-center justify-between w-full px-4 py-2.5 rounded-xl bg-emerald-600/20 hover:bg-emerald-600 text-emerald-200 hover:text-white font-semibold text-xs sm:text-sm transition duration-200 group/btn"
                        >
                            <span>Buka Kasir POS</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <div v-else class="text-xs text-zinc-500 font-medium text-center py-2 bg-zinc-950/40 rounded-xl border border-zinc-800/40">
                            Akses Khusus Kasir
                        </div>
                    </div>
                </div>

                <!-- 3. MENU CREATE SERVICE -->
                <div 
                    :class="[
                        'group relative rounded-2xl border p-6 flex flex-col justify-between transition-all duration-300 shadow-xl',
                        canAccessService 
                            ? 'bg-zinc-900/70 hover:bg-zinc-900 border-zinc-800 hover:border-emerald-500/50 hover:shadow-emerald-500/10 hover:-translate-y-1 cursor-pointer' 
                            : 'bg-zinc-900/30 border-zinc-800/40 opacity-50 cursor-not-allowed'
                    ]"
                >
                    <div>
                        <!-- Top Icon & Badge -->
                        <div class="flex items-center justify-between mb-6">
                            <div :class="[
                                'h-14 w-14 rounded-2xl flex items-center justify-center transition-transform duration-300 group-hover:scale-105',
                                canAccessService ? 'bg-gradient-to-br from-amber-500/20 to-orange-500/20 border border-amber-500/30 text-amber-400' : 'bg-zinc-800 border border-zinc-700 text-zinc-500'
                            ]">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>

                            <span v-if="canAccessService" class="px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/10 border border-amber-500/20 text-amber-400">
                                SPK & Servis
                            </span>
                            <span v-else class="px-2.5 py-1 rounded-full text-xs font-semibold bg-zinc-800 text-zinc-500 border border-zinc-700/50">
                                Terkunci
                            </span>
                        </div>

                        <!-- Card Content -->
                        <h2 class="text-xl font-bold text-white mb-2 group-hover:text-emerald-400 transition-colors">
                            Menu Create Service
                        </h2>
                        <p class="text-xs sm:text-sm text-zinc-400 leading-relaxed mb-6">
                            Buat Surat Perintah Kerja (SPK) servis kendaraan baru, daftarkan data kendaraan & keluhan pelanggan, serta jadwalkan mekanik.
                        </p>
                    </div>

                    <!-- Footer Action Link -->
                    <div class="pt-4 border-t border-zinc-800/60">
                        <Link 
                            v-if="canAccessService"
                            href="/services/create"
                            class="inline-flex items-center justify-between w-full px-4 py-2.5 rounded-xl bg-amber-600/20 hover:bg-amber-600 text-amber-200 hover:text-white font-semibold text-xs sm:text-sm transition duration-200 group/btn"
                        >
                            <span>Buat SPK Servis Baru</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transform group-hover/btn:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </Link>
                        <div v-else class="text-xs text-zinc-500 font-medium text-center py-2 bg-zinc-950/40 rounded-xl border border-zinc-800/40">
                            Akses Khusus Servis
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- Simple Footer -->
        <footer class="border-t border-zinc-900 bg-zinc-950 py-4">
            <div class="max-w-7xl mx-auto px-4 text-center text-xs text-zinc-500">
                &copy; {{ new Date().getFullYear() }} POS Bengkel. Hak Cipta Dilindungi.
            </div>
        </footer>
    </div>
</template>
