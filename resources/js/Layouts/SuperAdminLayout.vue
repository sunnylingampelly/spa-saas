<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BuildingStorefrontIcon,
    ClockIcon,
    CurrencyRupeeIcon,
    ListBulletIcon,
    MegaphoneIcon,
    MoonIcon,
    ServerStackIcon,
    ShieldCheckIcon,
    Squares2X2Icon,
    SunIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { useTheme } from '../Composables/useTheme';
import { useAuthStore } from '../Stores/auth';

const page = usePage();
const authStore = useAuthStore();
const { isDark, toggleTheme } = useTheme();

authStore.syncFromPage(page.props);

const navigation = [
    { name: 'Dashboard', href: route('admin.dashboard'), icon: Squares2X2Icon, matches: 'admin.dashboard' },
    { name: 'Spas', href: route('admin.spas.index'), icon: BuildingStorefrontIcon, matches: 'admin.spas.*' },
    { name: 'Payments', href: route('admin.payments.index'), icon: CurrencyRupeeIcon, matches: 'admin.payments.*' },
    { name: 'Pending Payments', href: route('admin.pending-payments.index'), icon: ClockIcon, matches: 'admin.pending-payments.*' },
    { name: 'Admins', href: route('admin.admins.index'), icon: UsersIcon, matches: 'admin.admins.*' },
    { name: 'Activity Log', href: route('admin.activity.index'), icon: ListBulletIcon, matches: 'admin.activity.*' },
    { name: 'Announcements', href: route('admin.announcements.index'), icon: MegaphoneIcon, matches: 'admin.announcements.*' },
];
</script>

<template>
    <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
        <aside class="fixed inset-y-0 left-0 z-30 hidden w-64 border-r border-slate-200/60 bg-white/80 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/70 lg:block">
            <div class="flex h-16 items-center gap-2 px-6">
                <ShieldCheckIcon class="h-5 w-5 text-brand-600" />
                <span class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">Platform Admin</span>
            </div>

            <nav class="mt-4 space-y-1 px-3">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="item.href"
                    class="relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-brand-50 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                    :class="route().current(item.matches) && 'bg-gradient-to-r from-brand-50 to-transparent text-brand-700 dark:from-slate-800 dark:text-white'"
                >
                    <span
                        v-if="route().current(item.matches)"
                        class="absolute inset-y-1.5 left-0 w-1 rounded-full bg-gradient-to-b from-brand-600 to-brand-400"
                    />
                    <component :is="item.icon" class="h-5 w-5" />
                    {{ item.name }}
                </Link>

                <a
                    :href="route('horizon.index')"
                    target="_blank"
                    rel="noopener"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-brand-50 hover:text-brand-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
                >
                    <ServerStackIcon class="h-5 w-5" />
                    Queues (Horizon)
                </a>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200/60 bg-white/80 px-4 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/70 sm:px-6">
                <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Super Admin</span>

                <div class="flex items-center gap-3">
                    <button class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800" @click="toggleTheme">
                        <SunIcon v-if="isDark" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>
                    <button class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400" @click="router.post(route('logout'))">
                        Log out
                    </button>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
