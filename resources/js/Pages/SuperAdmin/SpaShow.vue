<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import StatTile from '../../Components/Ui/StatTile.vue';
import { UserGroupIcon, UsersIcon, CalendarDaysIcon } from '@heroicons/vue/24/outline';
import { useConfirm } from '../../Composables/useConfirm';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const { confirmDialog } = useConfirm();

const props = defineProps({
    spa: { type: Object, required: true },
    stats: { type: Object, required: true },
    impersonations: { type: Array, required: true },
});

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;

const subscriptionStatusColor = { trialing: 'brand', active: 'green', past_due: 'amber', cancelled: 'rose' };
const paymentStatusColor = { pending: 'amber', paid: 'green', failed: 'rose', refunded: 'slate' };

const planOptions = [
    { value: 'trial', label: 'Trial' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'lifetime', label: 'Lifetime' },
];
const statusOptions = [
    { value: 'trialing', label: 'Trialing' },
    { value: 'active', label: 'Active' },
    { value: 'past_due', label: 'Past due' },
    { value: 'cancelled', label: 'Cancelled' },
];

const subscriptionForm = useForm({
    plan_code: props.spa.subscription?.plan_code ?? 'monthly',
    status: props.spa.subscription?.status ?? 'trialing',
    current_period_ends_at: props.spa.subscription?.current_period_ends_at?.slice(0, 10) ?? '',
});

function submitSubscription() {
    subscriptionForm.patch(route('admin.spas.subscription.update', props.spa.id), { preserveScroll: true });
}

const ownerForm = useForm({
    name: props.spa.owner?.name ?? '',
    email: props.spa.owner?.email ?? '',
    password: '',
});

function submitOwner() {
    ownerForm.patch(route('admin.spas.owner.update', props.spa.id), {
        preserveScroll: true,
        onSuccess: () => { ownerForm.password = ''; },
    });
}

async function toggleOwnerStatus() {
    const verb = props.spa.owner?.is_active ? 'deactivate' : 'reactivate';

    const confirmed = await confirmDialog({
        title: `${verb.charAt(0).toUpperCase()}${verb.slice(1)} owner account?`,
        message: `Are you sure you want to ${verb} ${props.spa.owner?.name}'s account?`,
        confirmLabel: verb.charAt(0).toUpperCase() + verb.slice(1),
        danger: verb === 'deactivate',
    });
    if (confirmed) {
        router.patch(route('admin.spas.owner.toggle-status', props.spa.id), {}, { preserveScroll: true });
    }
}

async function deleteOwner() {
    const confirmed = await confirmDialog({
        title: 'Delete owner account?',
        message: `Delete ${props.spa.owner?.name}'s account? This disables their login immediately.`,
        confirmLabel: 'Delete',
        danger: true,
    });
    if (confirmed) {
        router.delete(route('admin.spas.owner.delete', props.spa.id), { preserveScroll: true });
    }
}

async function toggleSpaStatus() {
    const next = props.spa.status === 'suspended' ? 'active' : 'suspended';
    const verb = next === 'suspended' ? 'suspend' : 'reactivate';

    const confirmed = await confirmDialog({
        title: `${verb.charAt(0).toUpperCase()}${verb.slice(1)} this spa?`,
        message: `Are you sure you want to ${verb} "${props.spa.name}"?`,
        confirmLabel: verb.charAt(0).toUpperCase() + verb.slice(1),
        danger: next === 'suspended',
    });
    if (confirmed) {
        router.patch(route('admin.spas.update-status', props.spa.id), { status: next }, { preserveScroll: true });
    }
}

async function impersonate() {
    const confirmed = await confirmDialog({
        title: 'Log in as owner?',
        message: `Log in as ${props.spa.owner?.name} (${props.spa.name})? This will be recorded in the audit log.`,
        confirmLabel: 'Log in',
    });
    if (confirmed) {
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
            <form v-if="spa.subscription" class="space-y-3" @submit.prevent="submitSubscription">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Current status</span>
                    <BaseBadge :color="subscriptionStatusColor[spa.subscription.status]">{{ spa.subscription.status }}</BaseBadge>
                </div>
                <BaseListbox v-model="subscriptionForm.plan_code" label="Plan" :options="planOptions" :error="subscriptionForm.errors.plan_code" />
                <BaseListbox v-model="subscriptionForm.status" label="Status" :options="statusOptions" :error="subscriptionForm.errors.status" />
                <BaseInput
                    v-model="subscriptionForm.current_period_ends_at"
                    type="date"
                    label="Renews / expires on"
                    :error="subscriptionForm.errors.current_period_ends_at"
                />
                <BaseButton type="submit" :disabled="subscriptionForm.processing">Save Subscription</BaseButton>
            </form>
            <p v-else class="text-sm text-slate-500">No subscription record.</p>
        </BaseCard>

        <BaseCard title="Owner account">
            <form class="space-y-3" @submit.prevent="submitOwner">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-slate-500">Login status</span>
                    <BaseBadge :color="spa.owner?.is_active ? 'green' : 'rose'">{{ spa.owner?.is_active ? 'Active' : 'Deactivated' }}</BaseBadge>
                </div>
                <BaseInput v-model="ownerForm.name" label="Name" :error="ownerForm.errors.name" />
                <BaseInput v-model="ownerForm.email" type="email" label="Email" :error="ownerForm.errors.email" />
                <BaseInput
                    v-model="ownerForm.password"
                    type="password"
                    label="New password"
                    placeholder="Leave blank to keep current password"
                    :error="ownerForm.errors.password"
                />
                <div class="flex flex-wrap gap-2">
                    <BaseButton type="submit" :disabled="ownerForm.processing">Save Owner</BaseButton>
                    <BaseButton type="button" variant="secondary" @click="toggleOwnerStatus">
                        {{ spa.owner?.is_active ? 'Deactivate' : 'Reactivate' }}
                    </BaseButton>
                    <BaseButton type="button" variant="danger" @click="deleteOwner">Delete Owner Account</BaseButton>
                </div>
            </form>
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
