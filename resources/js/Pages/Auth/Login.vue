<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-default px-4 py-12 text-highlighted sm:px-6 lg:px-8">
        <UCard class="w-full max-w-sm" :ui="{ body: 'p-6 sm:p-8' }">
            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2 text-center">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-lg bg-primary text-inverted">
                        <UIcon name="i-lucide-badge-dollar-sign" class="size-6" />
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight">Login</h1>
                    <p class="text-sm text-muted">Masuk ke admin POS Bengkel</p>
                </div>

                <UFormField label="Email" name="email" :error="form.errors.email">
                    <UInput v-model="form.email" type="email" autocomplete="username" icon="i-lucide-mail" class="w-full" required autofocus />
                </UFormField>

                <UFormField label="Password" name="password" :error="form.errors.password">
                    <UInput v-model="form.password" type="password" autocomplete="current-password" icon="i-lucide-lock" class="w-full" required />
                </UFormField>

                <div class="flex items-center justify-between gap-3">
                    <UCheckbox v-model="form.remember" name="remember" label="Ingat saya" />
                </div>

                <UButton type="submit" block size="lg" :loading="form.processing" :disabled="form.processing">
                    Masuk
                </UButton>

                <p class="text-center text-sm text-muted">
                    Belum punya akun?
                    <Link href="/register" class="font-medium text-primary hover:underline">
                        Register
                    </Link>
                </p>
            </form>
        </UCard>
    </main>
</template>
