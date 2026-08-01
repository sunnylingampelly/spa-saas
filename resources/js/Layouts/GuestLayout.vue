<script setup>
import { Link } from '@inertiajs/vue3';
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { useTheme } from '../Composables/useTheme';

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-white px-4 dark:bg-slate-950">
        <!-- Decorative premium background accents -->
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-900/20" />
            <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-brand-100/60 blur-3xl dark:bg-brand-800/10" />
            <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-rose-100/50 blur-3xl dark:bg-slate-800/20" />
        </div>

        <button
            class="fixed right-6 top-6 z-10 rounded-full p-2 text-slate-500 transition hover:bg-white/60 dark:text-slate-400 dark:hover:bg-slate-800"
            @click="toggleTheme"
        >
            <SunIcon v-if="isDark" class="h-5 w-5" />
            <MoonIcon v-else class="h-5 w-5" />
        </button>

        <div class="relative z-10 w-full max-w-md">
            <div class="mb-8 text-center">
                <Link href="/" class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    Spa<span class="bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">Orbit</span>
                </Link>
            </div>

            <div class="glass-panel rounded-2xl p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
