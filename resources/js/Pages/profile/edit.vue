<script setup>
import DashboardLayout from '../../Layouts/DashboardLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({
    layout: [DashboardLayout, { title: 'Profil Saya', panelId: 'profile-edit' }],
});

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

// Password visibility toggles
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Profile Form
const profileForm = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    phone: props.user.phone || '',
    address: props.user.address || '',
});

const submitProfile = () => {
    profileForm.put('/profile', {
        preserveScroll: true,
    });
};

// Password Form
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submitPassword = () => {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showCurrentPassword.value = false;
            showNewPassword.value = false;
            showConfirmPassword.value = false;
        },
    });
};

const userInitials = computed(() => {
    const name = props.user.name || 'User';
    return name
        .split(' ')
        .map((part) => part[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});
</script>

<template>
    <Head title="Profil Saya" />

    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <h1 class="text-xl font-bold tracking-tight text-highlighted flex items-center gap-2">
                <UIcon name="i-lucide-user-circle" class="size-6 text-primary" />
                <span>Pengaturan Profil & Keamanan</span>
            </h1>
            <p class="text-xs text-muted mt-0.5">
                Kelola informasi biodata diri, nomor kontak, serta perbarui kata sandi akun Anda.
            </p>
        </div>

        <!-- Flash Messages -->
        <div
            v-if="flashSuccess"
            class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 text-sm font-medium flex items-center gap-3 shadow-xs"
        >
            <UIcon name="i-lucide-check-circle-2" class="size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
            <span>{{ flashSuccess }}</span>
        </div>

        <div
            v-if="flashError"
            class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-400 text-sm font-medium flex items-center gap-3 shadow-xs"
        >
            <UIcon name="i-lucide-alert-triangle" class="size-5 shrink-0 text-rose-600 dark:text-rose-400" />
            <span>{{ flashError }}</span>
        </div>

        <!-- Identity & Role Overview Card (Read Only) -->
        <UCard :ui="{ body: 'p-5 sm:p-6' }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
                <div class="flex items-center gap-4">
                    <div class="size-16 rounded-2xl bg-primary/10 border border-primary/20 text-primary font-bold text-xl flex items-center justify-center shrink-0 shadow-inner">
                        {{ userInitials }}
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-highlighted">{{ user.name }}</h2>
                            <span
                                v-if="user.active"
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20"
                            >
                                <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                Aktif
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-500/15 text-rose-700 dark:text-rose-400 border border-rose-500/20"
                            >
                                <span class="size-1.5 rounded-full bg-rose-500"></span>
                                Nonaktif
                            </span>
                        </div>
                        <p class="text-xs text-muted mt-0.5">{{ user.email }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <!-- Roles Badges -->
                    <div class="bg-elevated/70 border border-default rounded-xl px-3.5 py-2">
                        <span class="text-muted block text-[11px]">Hak Akses / Peran</span>
                        <div class="flex flex-wrap gap-1.5 mt-1">
                            <span
                                v-for="role in user.roles"
                                :key="role.id"
                                class="px-2 py-0.5 rounded-md font-medium text-xs bg-primary/10 text-primary border border-primary/20"
                            >
                                {{ role.name }}
                            </span>
                            <span v-if="!user.roles || user.roles.length === 0" class="text-muted italic">Tidak ada role</span>
                        </div>
                    </div>

                    <!-- Store / Branch Badge -->
                    <div class="bg-elevated/70 border border-default rounded-xl px-3.5 py-2">
                        <span class="text-muted block text-[11px]">Cabang Bengkel</span>
                        <span class="font-semibold text-highlighted block mt-1">
                            {{ user.store ? `${user.store.name} (${user.store.code})` : 'Pusat / Semua Cabang' }}
                        </span>
                    </div>

                    <!-- NIK Badge -->
                    <div class="bg-elevated/70 border border-default rounded-xl px-3.5 py-2">
                        <span class="text-muted block text-[11px]">Nomor Induk (NIK)</span>
                        <span class="font-mono font-semibold text-highlighted block mt-1">
                            {{ user.nik || '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-default flex items-center gap-2 text-xs text-muted">
                <UIcon name="i-lucide-info" class="size-4 shrink-0 text-muted" />
                <span>
                    Hak akses, penempatan cabang bengkel, dan NIK hanya dapat diubah oleh Administrator / Owner sistem.
                </span>
            </div>
        </UCard>

        <!-- Two Columns Form Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <!-- Form 1: Informasi Biodata & Kontak -->
            <UCard :ui="{ body: 'p-5 sm:p-6' }">
                <div class="border-b border-default pb-4 mb-5">
                    <h2 class="text-base font-bold text-highlighted flex items-center gap-2">
                        <UIcon name="i-lucide-user" class="size-5 text-primary" />
                        <span>Data Pribadi & Kontak</span>
                    </h2>
                    <p class="text-xs text-muted mt-0.5">
                        Perbarui informasi nama lengkap, email, dan kontak yang dapat dihubungi.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submitProfile">
                    <!-- Nama Lengkap -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-highlighted" for="profile-name">
                            Nama Lengkap <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="profile-name"
                            v-model="profileForm.name"
                            type="text"
                            required
                            class="w-full rounded-lg border border-default bg-default px-3 py-2 text-sm text-highlighted outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="Masukkan nama lengkap"
                        />
                        <p v-if="profileForm.errors.name" class="text-xs text-red-600 font-medium">
                            {{ profileForm.errors.name }}
                        </p>
                    </div>

                    <!-- Email -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-highlighted" for="profile-email">
                            Alamat Email <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="profile-email"
                            v-model="profileForm.email"
                            type="email"
                            required
                            class="w-full rounded-lg border border-default bg-default px-3 py-2 text-sm text-highlighted outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="nama@email.com"
                        />
                        <p v-if="profileForm.errors.email" class="text-xs text-red-600 font-medium">
                            {{ profileForm.errors.email }}
                        </p>
                    </div>

                    <!-- Telepon / WhatsApp -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-highlighted" for="profile-phone">
                            Nomor Telepon / WhatsApp
                        </label>
                        <input
                            id="profile-phone"
                            v-model="profileForm.phone"
                            type="tel"
                            class="w-full rounded-lg border border-default bg-default px-3 py-2 text-sm text-highlighted outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="Contoh: 08123456789"
                        />
                        <p v-if="profileForm.errors.phone" class="text-xs text-red-600 font-medium">
                            {{ profileForm.errors.phone }}
                        </p>
                    </div>

                    <!-- Alamat -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-highlighted" for="profile-address">
                            Alamat Domisili
                        </label>
                        <textarea
                            id="profile-address"
                            v-model="profileForm.address"
                            rows="3"
                            class="w-full rounded-lg border border-default bg-default px-3 py-2 text-sm text-highlighted outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="Alamat tempat tinggal atau domisili karyawan"
                        />
                        <p v-if="profileForm.errors.address" class="text-xs text-red-600 font-medium">
                            {{ profileForm.errors.address }}
                        </p>
                    </div>

                    <!-- Tombol Simpan -->
                    <div class="pt-3 border-t border-default flex justify-end">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-xs transition hover:bg-primary/90 disabled:opacity-60 cursor-pointer"
                        >
                            <UIcon
                                v-if="profileForm.processing"
                                name="i-lucide-loader-2"
                                class="size-4 animate-spin"
                            />
                            <UIcon v-else name="i-lucide-save" class="size-4" />
                            <span>{{ profileForm.processing ? 'Menyimpan...' : 'Simpan Profil' }}</span>
                        </button>
                    </div>
                </form>
            </UCard>

            <!-- Form 2: Ganti Password -->
            <UCard :ui="{ body: 'p-5 sm:p-6' }">
                <div class="border-b border-default pb-4 mb-5">
                    <h2 class="text-base font-bold text-highlighted flex items-center gap-2">
                        <UIcon name="i-lucide-key-round" class="size-5 text-primary" />
                        <span>Ganti Kata Sandi (Password)</span>
                    </h2>
                    <p class="text-xs text-muted mt-0.5">
                        Pastikan menggunakan password yang aman dengan kombinasi huruf dan angka minimal 8 karakter.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submitPassword">
                    <!-- Current Password -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-highlighted" for="current-password">
                            Password Saat Ini <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="current-password"
                                v-model="passwordForm.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                required
                                class="w-full rounded-lg border border-default bg-default px-3 py-2 pr-10 text-sm text-highlighted outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                                placeholder="Masukkan password saat ini"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-highlighted focus:outline-none"
                                @click="showCurrentPassword = !showCurrentPassword"
                            >
                                <UIcon :name="showCurrentPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.current_password" class="text-xs text-red-600 font-medium">
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <!-- New Password -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-highlighted" for="new-password">
                            Password Baru <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="new-password"
                                v-model="passwordForm.password"
                                :type="showNewPassword ? 'text' : 'password'"
                                required
                                minlength="8"
                                class="w-full rounded-lg border border-default bg-default px-3 py-2 pr-10 text-sm text-highlighted outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                                placeholder="Minimal 8 karakter"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-highlighted focus:outline-none"
                                @click="showNewPassword = !showNewPassword"
                            >
                                <UIcon :name="showNewPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.password" class="text-xs text-red-600 font-medium">
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="space-y-1">
                        <label class="block text-xs font-semibold text-highlighted" for="confirm-password">
                            Ulangi Password Baru <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                id="confirm-password"
                                v-model="passwordForm.password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                required
                                minlength="8"
                                class="w-full rounded-lg border border-default bg-default px-3 py-2 pr-10 text-sm text-highlighted outline-none transition focus:border-primary focus:ring-1 focus:ring-primary"
                                placeholder="Ketik ulang password baru"
                            />
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-highlighted focus:outline-none"
                                @click="showConfirmPassword = !showConfirmPassword"
                            >
                                <UIcon :name="showConfirmPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'" class="size-4" />
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.password_confirmation" class="text-xs text-red-600 font-medium">
                            {{ passwordForm.errors.password_confirmation }}
                        </p>
                    </div>

                    <!-- Tombol Perbarui Password -->
                    <div class="pt-3 border-t border-default flex justify-end">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-inverted shadow-xs transition hover:bg-primary/90 disabled:opacity-60 cursor-pointer"
                        >
                            <UIcon
                                v-if="passwordForm.processing"
                                name="i-lucide-loader-2"
                                class="size-4 animate-spin"
                            />
                            <UIcon v-else name="i-lucide-lock" class="size-4" />
                            <span>{{ passwordForm.processing ? 'Menyimpan...' : 'Perbarui Password' }}</span>
                        </button>
                    </div>
                </form>
            </UCard>
        </div>
    </div>
</template>
