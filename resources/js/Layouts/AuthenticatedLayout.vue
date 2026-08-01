<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRightOnRectangleIcon,
    BanknotesIcon,
    Bars3Icon,
    BuildingStorefrontIcon,
    CalendarDaysIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    CreditCardIcon,
    MagnifyingGlassIcon,
    MoonIcon,
    ReceiptPercentIcon,
    SparklesIcon,
    Squares2X2Icon,
    SunIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { storeToRefs } from 'pinia';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import CommandPalette from '../Components/CommandPalette/CommandPalette.vue';
import { useTheme } from '../Composables/useTheme';
import { useAuthStore } from '../Stores/auth';
import { useUiStore } from '../Stores/ui';

const page = usePage();
const authStore = useAuthStore();
const uiStore = useUiStore();
const { sidebarOpen } = storeToRefs(uiStore);
const { isDark, toggleTheme } = useTheme();

authStore.syncFromPage(page.props);

const userMenuOpen = ref(false);
const userMenuRef = ref(null);

function onDocumentClick(event) {
    if (userMenuOpen.value && userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        userMenuOpen.value = false;
    }
}

function onDocumentKeydown(event) {
    if (event.key === 'Escape') {
        userMenuOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onDocumentKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onDocumentKeydown);
});

function logout() {
    router.post(route('logout'));
}

const navigation = [
    { name: 'Dashboard', href: route('dashboard'), icon: Squares2X2Icon, matches: 'dashboard' },
    { name: 'Appointments', href: route('appointments.index'), icon: CalendarDaysIcon, matches: 'appointments.*' },
    { name: 'Billing', href: route('invoices.index'), icon: ReceiptPercentIcon, matches: 'invoices.*' },
    { name: 'Customers', href: route('customers.index'), icon: UserGroupIcon, matches: 'customers.*' },
    { name: 'Employees', href: route('employees.index'), icon: UsersIcon, matches: 'employees.*' },
    { name: 'Services', href: route('services.index'), icon: SparklesIcon, matches: 'services.*' },
    { name: 'Expenses', href: route('expenses.index'), icon: BanknotesIcon, matches: 'expenses.*' },
    { name: 'Commissions', href: route('reports.commissions'), icon: ChartBarIcon, matches: 'reports.*' },
    { name: 'Spa Profile', href: route('spa.profile.show'), icon: BuildingStorefrontIcon, matches: 'spa.profile.*' },
    { name: 'Subscription', href: route('subscription.show'), icon: CreditCardIcon, matches: 'subscription.*' },
    { name: 'Support', href: route('support.tickets.index'), icon: ChatBubbleLeftRightIcon, matches: 'support.tickets.*' },
];

const currentSpaName = computed(() => page.props.currentSpa?.name);
const impersonating = computed(() => page.props.impersonating);
const announcement = computed(() => page.props.announcement);
const unreadSupportCount = computed(() => page.props.unreadSupportCount ?? 0);

const announcementColorClasses = {
    indigo: 'bg-indigo-600 text-white',
    brand: 'bg-brand-600 text-white',
    emerald: 'bg-emerald-600 text-white',
    amber: 'bg-amber-600 text-white',
    rose: 'bg-rose-600 text-white',
    slate: 'bg-slate-700 text-white',
};
const announcementClass = computed(() => announcementColorClasses[announcement.value?.color] ?? announcementColorClasses.indigo);

function stopImpersonating() {
    router.post(route('stop-impersonating'));
}

onMounted(() => {
    uiStore.registerCommand({
        id: 'nav-dashboard',
        label: 'Go to Dashboard',
        icon: Squares2X2Icon,
        href: route('dashboard'),
        keywords: ['home'],
    });
    uiStore.registerCommand({
        id: 'nav-appointments',
        label: 'Go to Appointments',
        icon: CalendarDaysIcon,
        href: route('appointments.index'),
        keywords: ['bookings', 'calendar', 'schedule'],
    });
    uiStore.registerCommand({
        id: 'nav-book-appointment',
        label: 'Book Appointment',
        icon: CalendarDaysIcon,
        href: route('appointments.create'),
        keywords: ['new booking', 'walk-in'],
    });
    uiStore.registerCommand({
        id: 'nav-billing',
        label: 'Go to Billing',
        icon: ReceiptPercentIcon,
        href: route('invoices.index'),
        keywords: ['invoices', 'pos', 'gst'],
    });
    uiStore.registerCommand({
        id: 'nav-expenses',
        label: 'Go to Expenses',
        icon: BanknotesIcon,
        href: route('expenses.index'),
        keywords: ['costs', 'rent', 'salary'],
    });
    uiStore.registerCommand({
        id: 'nav-add-expense',
        label: 'Add Expense',
        icon: BanknotesIcon,
        href: route('expenses.create'),
        keywords: ['new expense'],
    });
    uiStore.registerCommand({
        id: 'nav-commissions',
        label: 'Go to Commission Report',
        icon: ChartBarIcon,
        href: route('reports.commissions'),
        keywords: ['employee', 'therapist', 'payout'],
    });
    uiStore.registerCommand({
        id: 'nav-new-bill',
        label: 'New Bill',
        icon: ReceiptPercentIcon,
        href: route('invoices.create'),
        keywords: ['pos', 'checkout', 'billing'],
    });
    uiStore.registerCommand({
        id: 'nav-customers',
        label: 'Go to Customers',
        icon: UserGroupIcon,
        href: route('customers.index'),
        keywords: ['clients'],
    });
    uiStore.registerCommand({
        id: 'nav-add-customer',
        label: 'Add Customer',
        icon: UserGroupIcon,
        href: route('customers.create'),
        keywords: ['new customer', 'client'],
    });
    uiStore.registerCommand({
        id: 'nav-employees',
        label: 'Go to Employees',
        icon: UsersIcon,
        href: route('employees.index'),
        keywords: ['staff', 'team'],
    });
    uiStore.registerCommand({
        id: 'nav-add-employee',
        label: 'Add Employee',
        icon: UsersIcon,
        href: route('employees.create'),
        keywords: ['new employee', 'staff'],
    });
    uiStore.registerCommand({
        id: 'nav-services',
        label: 'Go to Services',
        icon: SparklesIcon,
        href: route('services.index'),
        keywords: ['massage', 'catalog'],
    });
    uiStore.registerCommand({
        id: 'nav-add-service',
        label: 'Add Service',
        icon: SparklesIcon,
        href: route('services.create'),
        keywords: ['new service', 'massage'],
    });
    uiStore.registerCommand({
        id: 'nav-spa-profile',
        label: 'Go to Spa Profile',
        icon: BuildingStorefrontIcon,
        href: route('spa.profile.show'),
    });
    uiStore.registerCommand({
        id: 'nav-subscription',
        label: 'Go to Subscription',
        icon: CreditCardIcon,
        href: route('subscription.show'),
        keywords: ['billing', 'plan', 'payment', 'trial'],
    });
    uiStore.registerCommand({
        id: 'toggle-theme',
        label: 'Toggle dark / light mode',
        icon: SunIcon,
        action: toggleTheme,
        keywords: ['dark mode', 'light mode', 'theme'],
    });
    uiStore.registerCommand({
        id: 'logout',
        label: 'Log out',
        action: () => router.post(route('logout')),
        keywords: ['sign out'],
    });
});
</script>

<template>
    <div class="flex min-h-screen bg-slate-50 dark:bg-slate-950">
        <CommandPalette />

        <aside
            class="fixed inset-y-0 left-0 z-30 w-64 -translate-x-full border-r border-slate-200/60 bg-white/80 backdrop-blur-xl transition-transform dark:border-slate-800 dark:bg-slate-900/70 lg:translate-x-0"
            :class="sidebarOpen && 'translate-x-0'"
        >
            <div class="flex h-16 items-center gap-2 px-6">
                <span class="text-lg font-semibold tracking-tight text-slate-900 dark:text-white">
                    Spa<span class="bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">Orbit</span>
                </span>
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
                    <span
                        v-if="item.name === 'Support' && unreadSupportCount > 0"
                        class="ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 text-xs font-semibold text-white"
                    >
                        {{ unreadSupportCount }}
                    </span>
                </Link>
            </nav>
        </aside>

        <div class="flex flex-1 flex-col lg:pl-64">
            <div
                v-if="announcement"
                class="sticky top-0 z-30 px-4 py-2 text-center text-sm font-medium sm:px-6"
                :class="announcementClass"
            >
                {{ announcement.message }}
            </div>

            <div
                v-if="impersonating?.active"
                class="sticky top-0 z-30 flex items-center justify-between gap-3 bg-amber-500 px-4 py-2 text-sm font-medium text-white sm:px-6"
            >
                <span>Viewing as {{ impersonating.ownerName }} ({{ currentSpaName }}) — actions here are real.</span>
                <button class="rounded-lg bg-white/20 px-3 py-1 font-semibold hover:bg-white/30" @click="stopImpersonating">
                    Return to Admin
                </button>
            </div>

            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200/60 bg-white/80 px-4 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/70 sm:px-6">
                <div class="flex items-center gap-3">
                    <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 lg:hidden" @click="uiStore.toggleSidebar()">
                        <Bars3Icon class="h-5 w-5" />
                    </button>

                    <button
                        class="hidden items-center gap-2 rounded-xl border border-slate-200 px-3 py-1.5 text-sm text-slate-500 hover:border-slate-300 dark:border-slate-700 dark:text-slate-400 sm:flex"
                        @click="uiStore.openCommandPalette()"
                    >
                        <MagnifyingGlassIcon class="h-4 w-4" />
                        Search…
                        <kbd class="ml-4 rounded-md border border-slate-200 px-1.5 py-0.5 text-xs dark:border-slate-600">⌘K</kbd>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="currentSpaName" class="hidden text-sm font-medium text-slate-500 dark:text-slate-400 sm:inline">
                        {{ currentSpaName }}
                    </span>

                    <button class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800" @click="toggleTheme">
                        <SunIcon v-if="isDark" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>

                    <div ref="userMenuRef" class="relative">
                        <button
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-semibold text-white shadow-[0_2px_8px_-2px_rgb(219,39,119,0.5)] transition hover:brightness-105"
                            @click="userMenuOpen = !userMenuOpen"
                        >
                            {{ authStore.user?.name?.charAt(0) }}
                        </button>

                        <div
                            v-if="userMenuOpen"
                            class="card absolute right-0 top-12 z-30 w-56 !p-2"
                        >
                            <div class="border-b border-slate-100 px-3 py-2 dark:border-slate-800">
                                <p class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ authStore.user?.name }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ authStore.user?.email }}</p>
                            </div>
                            <Link
                                :href="route('spa.profile.show')"
                                class="mt-1 flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800"
                                @click="userMenuOpen = false"
                            >
                                <BuildingStorefrontIcon class="h-4 w-4" />
                                Spa Profile
                            </Link>
                            <Link
                                :href="route('subscription.show')"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800"
                                @click="userMenuOpen = false"
                            >
                                <CreditCardIcon class="h-4 w-4" />
                                Subscription
                            </Link>
                            <button
                                class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20"
                                @click="logout"
                            >
                                <ArrowRightOnRectangleIcon class="h-4 w-4" />
                                Log out
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div v-if="page.props.flash?.success" class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                    {{ page.props.flash.success }}
                </div>
                <div v-if="page.props.flash?.error" class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                    {{ page.props.flash.error }}
                </div>

                <slot />
            </main>
        </div>
    </div>
</template>
