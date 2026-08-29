<script setup>
import { Head } from '@inertiajs/vue3';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';
</script>

<template>
    <Head title="Unsubscribed" />

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-white px-4 py-12 dark:bg-slate-950">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-900/20" />
            <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-brand-100/60 blur-3xl dark:bg-brand-800/10" />
        </div>

        <div class="relative z-10 w-full max-w-md text-center">
            <CheckCircleIcon class="mx-auto h-14 w-14 text-emerald-500" />
            <h1 class="mt-4 text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">You've been unsubscribed</h1>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                You won't receive marketing emails from this spa going forward. If this was a mistake, just get in touch with them directly.
            </p>
        </div>
    </div>
</template>
