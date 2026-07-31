<script setup>
import { Link } from '@inertiajs/vue3';
import { MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { useTheme } from '../Composables/useTheme';

const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-white dark:bg-slate-950">
        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-brand-200/50 blur-3xl dark:bg-brand-900/20" />
            <div class="absolute -right-24 top-1/4 h-80 w-80 rounded-full bg-brand-100/60 blur-3xl dark:bg-brand-800/10" />
            <div class="absolute bottom-0 left-1/3 h-72 w-72 rounded-full bg-rose-100/50 blur-3xl dark:bg-slate-800/20" />
        </div>

        <header class="relative z-10">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <Link href="/" class="text-xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    Spa<span class="bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">SaaS</span>
                </Link>

                <nav class="flex items-center gap-2 sm:gap-4">
                    <Link :href="route('pricing')" class="hidden px-2 text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white sm:inline-block">
                        Pricing
                    </Link>
                    <button class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800" @click="toggleTheme">
                        <SunIcon v-if="isDark" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>
                    <Link :href="route('login')" class="btn-secondary !px-3 !py-2 text-sm">Log in</Link>
                    <Link :href="route('register')" class="btn-primary !px-3 !py-2 text-sm">Start free trial</Link>
                </nav>
            </div>
        </header>

        <main class="relative z-10">
            <slot />
        </main>

        <footer class="relative z-10 mx-auto max-w-6xl px-6 py-10 text-sm text-slate-400 dark:text-slate-500">
            <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 pt-6 dark:border-slate-800 sm:flex-row">
                <span>© {{ new Date().getFullYear() }} SpaSaaS. Built for Indian wellness spas.</span>
                <div class="flex gap-4">
                    <Link :href="route('pricing')" class="hover:text-slate-600 dark:hover:text-slate-300">Pricing</Link>
                    <Link :href="route('login')" class="hover:text-slate-600 dark:hover:text-slate-300">Log in</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
