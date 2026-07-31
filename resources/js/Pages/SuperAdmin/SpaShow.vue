<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import StatTile from '../../Components/Ui/StatTile.vue';
import { UserGroupIcon, UsersIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    spa: { type: Object, required: true },
    stats: { type: Object, required: true },
    impersonations: { type: Array, required: true },
});

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;

const subscriptionStatusColor = { trialing: 'brand', active: 'green', past_due: 'amber', cancelled: 'rose' };
const paymentStatusColor = { pending: 'amber', paid: 'green', failed: 'rose', refunded: 'slate' };

function toggleSpaStatus() {
    const next = props.spa.status === 'suspended' ? 'active' : 'suspended';
    const verb = next === 'suspended' ? 'suspend' : 'reactivate';

    if (confirm(`Are you sure you want to ${verb} "${props.spa.name}"?`)) {
        router.patch(route('admin.spas.update-status', props.spa.id), { status: next }, { preserveScroll: true });
    }
}

function impersonate() {
    if (confirm(`Log in as ${props.spa.owner?.name} (${props.spa.name})? This will be recorded in the audit log.`)) {
        router.post(route('admin.spas.impersonate', props.spa.id));
    }
}

function durationBetween(start, end) {
    if (!end) return 'In progress';
    const minutes = Math.round((new Date(end) - new Date(start)) / 60000);
    return minutes < 60 ? `${minutes}m` : `${Math.round(minutes / 60)}h`;
}
</script>

<template>
    <Head :title="spa.name" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ spa.name }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ spa.owner?.name }} · {{ spa.owner?.email }} · Since {{ formatDate(spa.created_at) }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <BaseBadge v-if="spa.status === 'suspended'" color="rose">Suspended by platform</BaseBadge>
            <BaseButton variant="secondary" @click="impersonate">Log in as owner</BaseButton>
            <BaseButton :variant="spa.status === 'suspended' ? 'primary' : 'danger'" @click="toggleSpaStatus">
                {{ spa.status === 'suspended' ? 'Reactivate' : 'Suspend' }}
            </BaseButton>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-3 gap-4">
        <StatTile label="Employees" :value="stats.employees" :icon="UsersIcon" />
        <StatTile label="Customers" :value="stats.customers" :icon="UserGroupIcon" />
        <StatTile label="Appointments" :value="stats.appointments" :icon="CalendarDaysIcon" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <BaseCard title="Subscription">
            <dl v-if="spa.subscription" class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Plan</dt>
                    <dd class="capitalize font-medium text-slate-900 dark:text-white">{{ spa.subscription.plan_code }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Status</dt>
                    <dd><BaseBadge :color="subscriptionStatusColor[spa.subscription.status]">{{ spa.subscription.status }}</BaseBadge></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ spa.subscription.status === 'trialing' ? 'Trial ends' : 'Renews / expires' }}</dt>
                    <dd>{{ spa.subscription.current_period_ends_at ? formatDate(spa.subscription.current_period_ends_at) : 'Never (lifetime)' }}</dd>
                </div>
            </dl>
            <p v-else class="text-sm text-slate-500">No subscription record.</p>
        </BaseCard>

        <BaseCard title="Impersonation history">
            <ul v-if="impersonations.length" class="space-y-1 text-sm">
                <li v-for="i in impersonations" :key="i.id" class="flex justify-between border-b border-slate-100 py-1 dark:border-slate-800">
                    <span>{{ i.super_admin?.name }} · {{ formatDate(i.started_at, { withTime: true }) }}</span>
                    <span class="text-slate-400">{{ durationBetween(i.started_at, i.ended_at) }}</span>
                </li>
            </ul>
            <p v-else class="text-sm text-slate-500">No one has logged in as this owner yet.</p>
        </BaseCard>
    </div>

    <BaseCard title="Payment history" class="mt-6">
        <p v-if="!spa.subscription?.payments?.length" class="text-sm text-slate-500">No payments yet.</p>
        <table v-else class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                    <th class="pb-2 font-medium">Date</th>
                    <th class="pb-2 font-medium">Plan</th>
                    <th class="pb-2 font-medium">Method</th>
                    <th class="pb-2 font-medium">Amount</th>
                    <th class="pb-2 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="payment in spa.subscription.payments" :key="payment.id" class="border-b border-slate-50 dark:border-slate-800/60">
                    <td class="py-2 text-slate-600 dark:text-slate-300">{{ formatDate(payment.created_at) }}</td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ payment.plan_code }}</td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ payment.method }}</td>
                    <td class="py-2 text-slate-900 dark:text-white">{{ rupees(payment.amount) }}</td>
                    <td class="py-2"><BaseBadge :color="paymentStatusColor[payment.status]">{{ payment.status }}</BaseBadge></td>
                </tr>
            </tbody>
        </table>
    </BaseCard>

    <div class="mt-6 flex items-center gap-4">
        <Link :href="route('admin.spas.index')" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back to Spas</Link>
        <Link :href="route('admin.activity.index', { spa_id: spa.id })" class="text-sm font-medium text-brand-600 hover:text-brand-700">
            View activity log →
        </Link>
    </div>
</template>
