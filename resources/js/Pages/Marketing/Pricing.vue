<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { CheckIcon } from '@heroicons/vue/24/outline';
import MarketingLayout from '../../Layouts/MarketingLayout.vue';

defineOptions({ layout: MarketingLayout });

const props = defineProps({
    plans: { type: Object, required: true },
    trialDays: { type: Number, required: true },
});

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;

const included = [
    'Unlimited appointments & customers',
    'GST-compliant POS billing (CGST/SGST/IGST)',
    'Customer wallet & reward points',
    'Employee commission reports',
    'Expense tracking & profit dashboard',
    'One spa, unlimited staff logins are not needed — owner-only login by design',
];
</script>

<template>
    <Head title="Pricing" />

    <section class="mx-auto max-w-3xl px-6 pb-8 pt-20 text-center">
        <h1 class="font-serif text-4xl text-slate-900 dark:text-white sm:text-5xl">Simple, honest pricing</h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-slate-500 dark:text-slate-400">
            One product, one price. Every {{ trialDays }}-day trial starts free — no card required.
        </p>
    </section>

    <section class="mx-auto grid max-w-4xl grid-cols-1 gap-6 px-6 py-8">
        <!-- Monthly plan disabled for now — see config/subscriptions.php.
        <div class="card">
            <p class="text-sm font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ plans.monthly.label }}</p>
            <p class="mt-2 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ rupees(plans.monthly.price) }}<span class="text-base font-normal text-slate-400">/month</span>
            </p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Cancel any time. Pay monthly for as long as you need.</p>
            <Link :href="route('register')" class="btn-secondary mt-6 w-full">Start free trial</Link>
        </div>
        -->

        <div class="card border-brand-200 dark:border-brand-800/60 mx-auto w-full max-w-sm">
            <p class="text-sm font-medium uppercase tracking-wide text-brand-600 dark:text-brand-400">{{ plans.lifetime.label }} · Best value</p>
            <p class="mt-2 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">
                {{ rupees(plans.lifetime.price) }}<span class="text-base font-normal text-slate-400"> one-time</span>
            </p>
            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Pay once, use SpaOrbit for as long as your spa runs.</p>
            <Link :href="route('register')" class="btn-primary mt-6 w-full">Start free trial</Link>
        </div>
    </section>

    <section class="mx-auto max-w-2xl px-6 py-12">
        <h2 class="text-center text-lg font-semibold text-slate-900 dark:text-white">Every plan includes</h2>
        <ul class="mt-6 space-y-3">
            <li v-for="item in included" :key="item" class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-300">
                <CheckIcon class="mt-0.5 h-5 w-5 flex-none text-brand-600" />
                {{ item }}
            </li>
        </ul>
    </section>
</template>
