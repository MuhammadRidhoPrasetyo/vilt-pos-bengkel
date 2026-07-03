<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <main class="min-h-screen bg-zinc-950 text-white">
        <section class="mx-auto flex min-h-screen max-w-5xl flex-col justify-center px-6 py-16">
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-300">VILT POS</p>
            <h1 class="mt-4 max-w-3xl text-4xl font-semibold leading-tight sm:text-6xl">
                Laravel, Inertia, Vue, Fortify, dan Spatie sudah siap.
            </h1>
            <p class="mt-6 max-w-2xl text-base leading-7 text-zinc-300">
                Ini halaman awal Inertia. Anda bisa mulai membangun modul POS di atas stack ini.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <Link href="/login" class="rounded-md bg-emerald-400 px-4 py-2 font-semibold text-zinc-950">
                    Login
                </Link>
                <Link href="/register" class="rounded-md border border-white/20 px-4 py-2 font-semibold text-white">
                    Register
                </Link>
            </div>
        </section>
    </main>
</template>
