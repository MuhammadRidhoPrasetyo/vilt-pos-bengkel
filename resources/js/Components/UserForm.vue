<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    form: Object,
    roles: {
        type: Array,
        default: () => [],
    },
    submitLabel: {
        type: String,
        default: 'Simpan',
    },
    requirePassword: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['submit']);
</script>

<template>
    <form class="space-y-6" @submit.prevent="$emit('submit')">
        <div class="grid gap-4 md:grid-cols-2">
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Nama</span>
                <input v-model="form.name" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" required />
                <span v-if="form.errors.name" class="text-xs text-red-600">{{ form.errors.name }}</span>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="font-medium">Email</span>
                <input v-model="form.email" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="email" required />
                <span v-if="form.errors.email" class="text-xs text-red-600">{{ form.errors.email }}</span>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="font-medium">NIK</span>
                <input v-model="form.nik" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" />
                <span v-if="form.errors.nik" class="text-xs text-red-600">{{ form.errors.nik }}</span>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="font-medium">Telepon</span>
                <input v-model="form.phone" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="text" />
                <span v-if="form.errors.phone" class="text-xs text-red-600">{{ form.errors.phone }}</span>
            </label>

            <label class="grid gap-1 text-sm md:col-span-2">
                <span class="font-medium">Alamat</span>
                <textarea v-model="form.address" class="min-h-24 rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" />
                <span v-if="form.errors.address" class="text-xs text-red-600">{{ form.errors.address }}</span>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="font-medium">Password</span>
                <input v-model="form.password" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="password" :required="requirePassword" />
                <span v-if="!requirePassword" class="text-xs text-muted">Kosongkan jika tidak ingin mengganti password.</span>
                <span v-if="form.errors.password" class="text-xs text-red-600">{{ form.errors.password }}</span>
            </label>

            <label class="grid gap-1 text-sm">
                <span class="font-medium">Konfirmasi Password</span>
                <input v-model="form.password_confirmation" class="rounded-md border border-default bg-default px-3 py-2 outline-none focus:border-primary" type="password" :required="requirePassword" />
            </label>
        </div>

        <div class="grid gap-3 rounded-lg border border-default p-4">
            <p class="text-sm font-medium">Role</p>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <label v-for="role in roles" :key="role.id" class="flex items-center gap-2 text-sm">
                    <input v-model="form.roles" class="size-4" type="checkbox" :value="role.id" />
                    {{ role.name }}
                </label>
            </div>
            <span v-if="form.errors.roles" class="text-xs text-red-600">{{ form.errors.roles }}</span>
        </div>

        <div class="flex flex-wrap items-center gap-4 rounded-lg border border-default p-4">
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.active" class="size-4" type="checkbox" />
                Aktif
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input v-model="form.top_navigation" class="size-4" type="checkbox" />
                Top navigation
            </label>
        </div>

        <div class="flex justify-end gap-2">
            <Link href="/users" class="rounded-md border border-default px-4 py-2 text-sm hover:bg-elevated">Batal</Link>
            <button class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-inverted hover:bg-primary/90 disabled:opacity-60" type="submit" :disabled="form.processing">
                {{ submitLabel }}
            </button>
        </div>
    </form>
</template>
