<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-default px-4 py-12 text-highlighted sm:px-6 lg:px-8">
        <UCard class="w-full max-w-sm" :ui="{ body: 'p-6 sm:p-8' }">
            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2 text-center">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-lg bg-primary text-inverted">
                        <UIcon name="i-lucide-user-plus" class="size-6" />
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight">Register</h1>
                    <p class="text-sm text-muted">Buat akun admin POS Bengkel</p>
                </div>

                <UFormField label="Nama" name="name" :error="form.errors.name">
                    <UInput v-model="form.name" type="text" autocomplete="name" icon="i-lucide-user" class="w-full" required autofocus />
                </UFormField>

                <UFormField label="Email" name="email" :error="form.errors.email">
                    <UInput v-model="form.email" type="email" autocomplete="username" icon="i-lucide-mail" class="w-full" required />
                </UFormField>

                <UFormField label="Password" name="password" :error="form.errors.password">
                    <UInput v-model="form.password" type="password" autocomplete="new-password" icon="i-lucide-lock" class="w-full" required />
                </UFormField>

                <UFormField label="Konfirmasi Password" name="password_confirmation" :error="form.errors.password_confirmation">
                    <UInput v-model="form.password_confirmation" type="password" autocomplete="new-password" icon="i-lucide-lock-keyhole" class="w-full" required />
                </UFormField>

                <UButton type="submit" block size="lg" :loading="form.processing" :disabled="form.processing">
                    Daftar
                </UButton>

                <p class="text-center text-sm text-muted">
                    Sudah punya akun?
                    <Link href="/login" class="font-medium text-primary hover:underline">
                        Login
                    </Link>
                </p>
            </form>
        </UCard>
    </main>
</template>
