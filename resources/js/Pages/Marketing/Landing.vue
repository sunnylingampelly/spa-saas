<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    BanknotesIcon,
    CalendarDaysIcon,
    ChartBarIcon,
    GiftIcon,
    ReceiptPercentIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';
import MarketingLayout from '../../Layouts/MarketingLayout.vue';

defineOptions({ layout: MarketingLayout });

const features = [
    {
        icon: CalendarDaysIcon,
        title: 'Appointments that never double-book',
        description: 'Walk-ins and advance bookings on one calendar, with conflict checks per therapist.',
    },
    {
        icon: ReceiptPercentIcon,
        title: 'GST-compliant billing',
        description: 'CGST/SGST or IGST calculated automatically, with Indian-financial-year invoice numbering.',
    },
    {
        icon: UserGroupIcon,
        title: 'Customers, wallet & loyalty',
        description: 'Prepaid wallets, reward points, and full visit history — lifetime spend, average bill, and more.',
    },
    {
        icon: BanknotesIcon,
        title: 'Expenses & real profit',
        description: 'Track rent, salaries and supplies, and see gross profit next to revenue — not just a sales number.',
    },
    {
        icon: ChartBarIcon,
        title: 'Therapist commission reports',
        description: 'Automatic payout calculations per therapist, per service, on money actually collected.',
    },
    {
        icon: GiftIcon,
        title: 'A live dashboard, not a static one',
        description: "Today's revenue, GST, popular services and 30-day trends — updated in real time.",
    },
];
</script>

<template>
    <Head title="Spa & Salon Management Software for India" />

    <section class="mx-auto max-w-4xl px-6 pb-16 pt-20 text-center sm:pt-28">
        <p class="mb-4 inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
            Built for Indian wellness spas
        </p>
        <h1 class="font-serif text-4xl leading-tight text-slate-900 dark:text-white sm:text-6xl">
            Run your spa like a
            <span class="bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">premium brand</span>,
            not a diary.
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-500 dark:text-slate-400">
            Appointments, GST billing, customer wallets, therapist commissions, and a real-time dashboard — one platform,
            built for how Indian spas actually run.
        </p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link :href="route('register')" class="btn-primary px-6 py-3 text-base">Start your free 14-day trial</Link>
            <Link :href="route('pricing')" class="btn-secondary px-6 py-3 text-base">See pricing</Link>
        </div>
        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">No credit card required to start.</p>
    </section>

    <section class="mx-auto max-w-6xl px-6 py-12">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="feature in features" :key="feature.title" class="card">
                <div class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                    <component :is="feature.icon" class="h-5 w-5" />
                </div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ feature.title }}</h3>
                <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">{{ feature.description }}</p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-4xl px-6 py-16 text-center">
        <div class="card !bg-gradient-to-br !from-brand-600 !to-brand-500 !border-0 px-8 py-12 text-white">
            <h2 class="font-serif text-3xl sm:text-4xl">Ready to see it running your spa?</h2>
            <p class="mx-auto mt-3 max-w-lg text-brand-50">
                Set up your spa in minutes. Free for 14 days, then choose monthly or a one-time lifetime plan.
            </p>
            <Link :href="route('register')" class="btn mt-6 bg-white px-6 py-3 text-base text-brand-700 hover:bg-brand-50">
                Start free trial
            </Link>
        </div>
    </section>
</template>
