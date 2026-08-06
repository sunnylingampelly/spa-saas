<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue';
import {
    BanknotesIcon,
    CalendarDaysIcon,
    ChartBarIcon,
    ChatBubbleLeftRightIcon,
    CheckIcon,
    CreditCardIcon,
    DevicePhoneMobileIcon,
    GiftIcon,
    PlusIcon,
    QrCodeIcon,
    ReceiptPercentIcon,
    ShieldCheckIcon,
    SparklesIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import MarketingLayout from '../../Layouts/MarketingLayout.vue';

defineOptions({ layout: MarketingLayout });

const props = defineProps({
    plans: { type: Object, required: true },
    trialDays: { type: Number, required: true },
});

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;

const unsplash = (id, w = 800) => `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&w=${w}&q=75`;

const heroStats = [
    { value: `${props.trialDays} days`, label: 'Free trial, no card needed' },
    { value: '0%', label: 'Commission on what you collect' },
    { value: '6', label: 'Ways your customers can pay' },
    { value: '100%', label: 'GST-compliant billing' },
];

const trustBadges = [
    { icon: ReceiptPercentIcon, label: 'GST Compliant' },
    { icon: CreditCardIcon, label: 'Razorpay Secured' },
    { icon: ShieldCheckIcon, label: 'Two-Factor Auth' },
    { icon: SparklesIcon, label: 'Encrypted Credentials' },
];

const pillars = [
    {
        title: 'Manage',
        description: 'Appointments, staff, services and customers — everything on one calendar, with conflict checks built in.',
    },
    {
        title: 'Grow',
        description: 'A live dashboard, loyalty & wallets, and commission reports that turn repeat customers into your biggest asset.',
    },
    {
        title: 'Get paid',
        description: 'Cash, UPI or card on the spot, or send a secure online pay link — straight into your own account.',
    },
];

const manageChecklist = [
    'Powerful calendar with unlimited bookings, walk-ins and staff',
    'GST-accurate billing — CGST/SGST/IGST calculated automatically, with financial-year invoice numbering',
    'A live dashboard: revenue, GST, walk-ins and 30-day trends, updated in real time',
];

const paymentsChecklist = [
    'Record cash, UPI or card the moment a customer walks in',
    'Send a secure online pay link — customers pay from their phone, no card machine needed',
    "Money settles straight into your own Razorpay account — SpaOrbit is never in the middle",
];

const paymentMethods = [
    { icon: BanknotesIcon, label: 'Cash' },
    { icon: DevicePhoneMobileIcon, label: 'UPI' },
    { icon: CreditCardIcon, label: 'Card' },
    { icon: GiftIcon, label: 'Wallet / voucher' },
    { icon: QrCodeIcon, label: 'Online pay link' },
    { icon: BanknotesIcon, label: 'Bank transfer' },
];

const features = [
    {
        icon: CalendarDaysIcon,
        title: 'Appointments that never double-book',
        description: 'Walk-ins and advance bookings on one calendar, with conflict checks per therapist and instant rescheduling.',
    },
    {
        icon: ReceiptPercentIcon,
        title: 'GST-compliant billing',
        description: 'CGST/SGST or IGST calculated automatically, with financial-year invoice numbering and one-click PDF/WhatsApp/email delivery.',
    },
    {
        icon: CreditCardIcon,
        title: 'Get paid on the spot or online',
        description: 'Manual recording for cash/UPI/card, or a shareable pay-online link — powered by your own Razorpay account.',
    },
    {
        icon: UserGroupIcon,
        title: 'Customers, wallet & loyalty',
        description: 'Prepaid wallets, reward points, VIP tagging, and full visit history at a glance.',
    },
    {
        icon: UsersIcon,
        title: 'Staff attendance & commissions',
        description: 'Track attendance and leave, then let automatic per-therapist payout calculations do the maths.',
    },
    {
        icon: BanknotesIcon,
        title: 'Expenses & real profit',
        description: 'Track rent, salaries and supplies, and see gross profit next to revenue — not just a sales number.',
    },
    {
        icon: ChartBarIcon,
        title: 'A live dashboard, not a static one',
        description: "Today's revenue, GST, walk-ins and 30-day trends, with your most popular services front and centre.",
    },
    {
        icon: ShieldCheckIcon,
        title: 'Secure by design',
        description: 'Two-factor authentication, a full audit log of every change, and encrypted payment credentials.',
    },
    {
        icon: ChatBubbleLeftRightIcon,
        title: 'A real human on support',
        description: 'Raise a support ticket right from your dashboard and hear back from the SpaOrbit team directly.',
    },
];

const categories = [
    { label: 'Massage & Bodywork', image: unsplash('1519823551278-64ac92734fb1') },
    { label: 'Facials & Skincare', image: unsplash('1552693673-1bf958298935') },
    { label: 'Hair & Salon', image: unsplash('1595476108010-b4d1f102b1b1') },
    { label: 'Nail Care', image: unsplash('1522337660859-02fbefca4702') },
    { label: 'Sauna & Wellness', image: unsplash('1583416750470-965b2707b355') },
    { label: 'Spa Retreat', image: unsplash('1560750588-73207b1ef5b8') },
];

const faqs = [
    {
        q: `What happens after my ${props.trialDays}-day trial ends?`,
        a: `Nothing is deleted and nothing happens automatically — your account simply pauses. You'll be asked to make the one-time Lifetime purchase from your Subscription page to keep going, and the moment you do, you're right back where you left off with all your data intact.`,
    },
    {
        q: 'Do I need a credit card to start the trial?',
        a: 'No. You can register and use every feature for free for 14 days without entering any payment details at all.',
    },
    {
        q: 'Who actually receives payments from my customers?',
        a: 'You do, directly. Online payments are collected through your own Razorpay account, which you connect from your Spa Profile — SpaOrbit is never in the middle of your money.',
    },
    {
        q: 'Is my data secure?',
        a: 'Yes. Payment credentials are encrypted at rest, every change to your data is logged in an audit trail, and the platform side is protected by two-factor authentication.',
    },
    {
        q: 'Can multiple staff members log in?',
        a: 'SpaOrbit is intentionally owner-only login — one simple, secure account per spa, with staff managed as records inside the app rather than separate logins to juggle.',
    },
    // Monthly plan disabled for now — see config/subscriptions.php.
    // {
    //     q: 'Can I switch between the monthly and lifetime plan?',
    //     a: 'Yes, any time from your Subscription page — or just message support if you need a hand.',
    // },
];
</script>

<template>
    <Head title="Spa & Salon Management Software for India" />

    <!-- Hero -->
    <section class="relative mx-auto max-w-5xl px-6 pb-16 pt-20 text-center sm:pt-28">
        <p class="mb-4 inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
            Built for Indian wellness spas
        </p>
        <h1 class="font-serif text-4xl leading-tight text-slate-900 dark:text-white sm:text-6xl">
            Run your spa like a
            <span class="bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">premium brand</span>,
            not a diary.
        </h1>
        <p class="mx-auto mt-6 max-w-2xl text-lg text-slate-500 dark:text-slate-400">
            Appointments, GST billing, real payments, customer wallets, staff commissions, and a live dashboard —
            one platform, built for how Indian spas actually run.
        </p>
        <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
            <Link :href="route('register')" class="btn-primary px-6 py-3 text-base">Start your free {{ trialDays }}-day trial</Link>
            <Link :href="route('pricing')" class="btn-secondary px-6 py-3 text-base">See pricing</Link>
        </div>
        <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">No credit card required to start.</p>

        <!-- Product hero image -->
        <div class="relative mx-auto mt-16 max-w-5xl">
            <img
                src="/images/spa-orbit-hero.webp"
                alt="SpaOrbit dashboard showing today's revenue, appointments, and 30-day trends"
                class="h-auto w-full rounded-2xl shadow-2xl"
            />
        </div>
    </section>

    <!-- Gradient stat band -->
    <section class="relative overflow-hidden bg-gradient-to-br from-indigo-600 via-fuchsia-600 to-brand-500 py-16 text-white sm:py-20">
        <div class="mx-auto grid max-w-5xl grid-cols-2 gap-8 px-6 text-center sm:grid-cols-4">
            <div v-for="stat in heroStats" :key="stat.label">
                <p class="font-serif text-3xl sm:text-5xl">{{ stat.value }}</p>
                <p class="mt-2 text-xs text-white/80 sm:text-sm">{{ stat.label }}</p>
            </div>
        </div>
    </section>

    <!-- Trust badges -->
    <section class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-center gap-3 sm:gap-4">
            <div
                v-for="badge in trustBadges"
                :key="badge.label"
                class="flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 dark:border-slate-800 dark:text-slate-300"
            >
                <component :is="badge.icon" class="h-4 w-4 text-brand-600 dark:text-brand-400" />
                {{ badge.label }}
            </div>
        </div>
    </section>

    <!-- Manage / Grow / Get paid -->
    <section class="bg-gradient-to-b from-brand-50/60 to-indigo-50/40 py-20 dark:from-slate-900 dark:to-slate-900/50">
        <div class="mx-auto max-w-5xl px-6">
            <div class="max-w-2xl">
                <h2 class="font-serif text-3xl text-slate-900 dark:text-white sm:text-4xl">
                    Everything you need to run your spa
                </h2>
                <p class="mt-4 text-slate-600 dark:text-slate-300">
                    SpaOrbit brings convenience and control to both how you run your business and how your customers experience it.
                </p>
            </div>
            <div class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-3">
                <div v-for="pillar in pillars" :key="pillar.title">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white">{{ pillar.title }}</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">{{ pillar.description }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Feature spotlight: manage/billing -->
    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            <div>
                <h2 class="font-serif text-3xl leading-tight text-slate-900 dark:text-white sm:text-4xl">
                    All-in-one
                    <span class="bg-gradient-to-r from-brand-600 to-brand-400 bg-clip-text text-transparent">software</span>
                    to run your spa
                </h2>
                <p class="mt-4 text-slate-500 dark:text-slate-400">
                    The most complete booking and billing software built specifically for Indian spas and salons.
                </p>
                <ul class="mt-6 space-y-4">
                    <li v-for="item in manageChecklist" :key="item" class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <CheckIcon class="mt-0.5 h-5 w-5 flex-none text-brand-600" />
                        {{ item }}
                    </li>
                </ul>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Today's schedule</p>
                    <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">6 bookings</span>
                </div>
                <ul class="mt-3 space-y-2.5">
                    <li class="flex items-center justify-between rounded-lg bg-brand-50/70 px-3 py-2.5 text-sm dark:bg-brand-900/20">
                        <span class="font-medium text-slate-800 dark:text-slate-100">10:00 · Deep Tissue Massage</span>
                        <span class="text-slate-400">Priya S.</span>
                    </li>
                    <li class="flex items-center justify-between rounded-lg bg-indigo-50/70 px-3 py-2.5 text-sm dark:bg-indigo-900/20">
                        <span class="font-medium text-slate-800 dark:text-slate-100">11:15 · Classic Facial</span>
                        <span class="text-slate-400">Ananya R.</span>
                    </li>
                    <li class="flex items-center justify-between rounded-lg bg-emerald-50/70 px-3 py-2.5 text-sm dark:bg-emerald-900/20">
                        <span class="font-medium text-slate-800 dark:text-slate-100">12:30 · Manicure & Pedicure</span>
                        <span class="text-slate-400">Kavya M.</span>
                    </li>
                    <li class="flex items-center justify-between rounded-lg bg-amber-50/70 px-3 py-2.5 text-sm dark:bg-amber-900/20">
                        <span class="font-medium text-slate-800 dark:text-slate-100">14:00 · Aromatherapy</span>
                        <span class="text-slate-400">Rohan K.</span>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Feature spotlight: payments (reversed) -->
    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2">
            <div class="order-2 grid grid-cols-2 gap-4 lg:order-1">
                <div v-for="method in paymentMethods" :key="method.label" class="card flex items-center gap-3 !p-4">
                    <div class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300">
                        <component :is="method.icon" class="h-5 w-5" />
                    </div>
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ method.label }}</span>
                </div>
            </div>
            <div class="order-1 lg:order-2">
                <p class="mb-3 inline-flex items-center rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-300">
                    Your money, your account
                </p>
                <h2 class="font-serif text-3xl leading-tight text-slate-900 dark:text-white sm:text-4xl">Get paid your way</h2>
                <p class="mt-4 text-slate-500 dark:text-slate-400">
                    Take payment however suits the moment — nothing ever routes through a platform-owned account.
                </p>
                <ul class="mt-6 space-y-4">
                    <li v-for="item in paymentsChecklist" :key="item" class="flex items-start gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <CheckIcon class="mt-0.5 h-5 w-5 flex-none text-brand-600" />
                        {{ item }}
                    </li>
                </ul>
                <Link :href="route('register')" class="btn-primary mt-8 inline-flex px-6 py-3 text-base">Start free trial</Link>
            </div>
        </div>
    </section>

    <!-- Atmosphere banner -->
    <section class="mx-auto max-w-6xl px-6 py-4">
        <div class="relative overflow-hidden rounded-3xl">
            <img
                :src="unsplash('1596178065887-1198b6148b2b', 1600)"
                alt="A calm, premium spa retreat"
                class="h-72 w-full object-cover sm:h-96"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent" />
            <div class="absolute bottom-6 left-6 right-6 sm:bottom-10 sm:left-10 sm:right-10">
                <p class="font-serif text-2xl text-white sm:text-3xl">A calmer way to run your day.</p>
                <p class="mt-2 max-w-md text-sm text-white/85">
                    Less time on spreadsheets and phone calls, more time on the experience your customers came for.
                </p>
            </div>
        </div>
    </section>

    <!-- Full feature grid -->
    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="font-serif text-3xl text-slate-900 dark:text-white sm:text-4xl">Everything, included</h2>
            <p class="mt-3 text-slate-500 dark:text-slate-400">No add-ons to buy, no modules to unlock — it's all there from day one.</p>
        </div>
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

    <!-- Category tiles -->
    <section class="mx-auto max-w-6xl px-6 py-20">
        <div class="mx-auto mb-12 max-w-2xl text-center">
            <h2 class="font-serif text-3xl text-slate-900 dark:text-white sm:text-4xl">One system, every treatment</h2>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            <div
                v-for="category in categories"
                :key="category.label"
                class="group relative aspect-square overflow-hidden rounded-2xl"
            >
                <img
                    :src="category.image"
                    :alt="category.label"
                    class="h-full w-full bg-slate-100 object-cover transition-transform duration-500 group-hover:scale-105 dark:bg-slate-800"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/5 to-transparent" />
                <span class="absolute bottom-4 left-4 text-base font-semibold text-white sm:text-lg">{{ category.label }}</span>
            </div>
        </div>
    </section>

    <!-- Pricing teaser -->
    <section class="mx-auto max-w-4xl px-6 py-20">
        <div class="card text-center">
            <h2 class="font-serif text-3xl text-slate-900 dark:text-white sm:text-4xl">Simple, honest pricing</h2>
            <p class="mx-auto mt-3 max-w-lg text-sm text-slate-500 dark:text-slate-400">
                One product, one price. Every {{ trialDays }}-day trial starts free — no card required.
            </p>
            <div class="mx-auto mt-8 grid max-w-2xl grid-cols-1 gap-6">
                <!-- Monthly plan disabled for now — see config/subscriptions.php.
                <div class="rounded-2xl border border-slate-100 p-6 text-left dark:border-slate-800">
                    <p class="text-sm font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ plans.monthly.label }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
                        {{ rupees(plans.monthly.price) }}<span class="text-base font-normal text-slate-400">/month</span>
                    </p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Cancel any time.</p>
                </div>
                -->
                <div class="mx-auto w-full max-w-sm rounded-2xl border border-brand-200 p-6 text-left dark:border-brand-800/60">
                    <p class="text-sm font-medium uppercase tracking-wide text-brand-600 dark:text-brand-400">{{ plans.lifetime.label }} · Best value</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
                        {{ rupees(plans.lifetime.price) }}<span class="text-base font-normal text-slate-400"> one-time</span>
                    </p>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Pay once, use it for as long as your spa runs.</p>
                </div>
            </div>
            <Link :href="route('pricing')" class="mt-8 inline-block text-sm font-medium text-brand-600 hover:text-brand-700">
                See everything included →
            </Link>
        </div>
    </section>

    <!-- FAQ accordion -->
    <section class="mx-auto max-w-3xl px-6 py-20">
        <h2 class="mb-10 text-center font-serif text-3xl text-slate-900 dark:text-white sm:text-4xl">Frequently asked questions</h2>
        <div class="space-y-3">
            <Disclosure v-for="faq in faqs" :key="faq.q" v-slot="{ open }" as="div" class="rounded-2xl bg-slate-50 dark:bg-slate-900">
                <DisclosureButton class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left">
                    <span class="font-semibold text-slate-900 dark:text-white">{{ faq.q }}</span>
                    <PlusIcon class="h-5 w-5 flex-none text-slate-400 transition-transform duration-200" :class="open && 'rotate-45'" />
                </DisclosureButton>
                <DisclosurePanel class="px-6 pb-5 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ faq.a }}
                </DisclosurePanel>
            </Disclosure>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="mx-auto max-w-4xl px-6 py-16 text-center">
        <div class="card !bg-gradient-to-br !from-brand-600 !to-brand-500 !border-0 px-8 py-12 text-white">
            <h2 class="font-serif text-3xl sm:text-4xl">Ready to see it running your spa?</h2>
            <p class="mx-auto mt-3 max-w-lg text-brand-50">
                Set up your spa in minutes. Free for {{ trialDays }} days, then a one-time Lifetime purchase.
            </p>
            <Link :href="route('register')" class="btn mt-6 bg-white px-6 py-3 text-base text-brand-700 hover:bg-brand-50">
                Start free trial
            </Link>
        </div>
    </section>
</template>
