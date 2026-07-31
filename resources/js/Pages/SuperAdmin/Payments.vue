<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    payments: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const statusColor = { pending: 'amber', paid: 'green', failed: 'rose', refunded: 'slate' };

const statusOptions = [
    { value: 'pending', label: 'Pending' },
    { value: 'paid', label: 'Paid' },
    { value: 'failed', label: 'Failed' },
    { value: 'refunded', label: 'Refunded' },
];

function filterByStatus(status) {
    router.get(route('admin.payments.index'), { status }, { preserveState: true });
}

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN')}`;
</script>

<template>
    <Head title="Payments" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Payments</h1>
        <BaseListbox
            :model-value="filters.status"
            class="w-44"
            :options="statusOptions"
            placeholder="All statuses"
            @update:model-value="filterByStatus"
        />
    </div>

    <BaseCard>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                    <th class="pb-2 font-medium">Spa</th>
                    <th class="pb-2 font-medium">Plan</th>
                    <th class="pb-2 font-medium">Method</th>
                    <th class="pb-2 font-medium">Amount</th>
                    <th class="pb-2 font-medium">Status</th>
                    <th class="pb-2 font-medium">Date</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="payment in payments.data" :key="payment.id" class="border-b border-slate-50 dark:border-slate-800/60">
                    <td class="py-2">
                        <Link
                            v-if="payment.subscription?.spa"
                            :href="route('admin.spas.show', payment.subscription.spa.id)"
                            class="font-medium text-brand-600 hover:text-brand-700"
                        >
                            {{ payment.subscription.spa.name }}
                        </Link>
                        <span v-else class="text-slate-400">—</span>
                    </td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ payment.plan_code }}</td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ payment.method }}</td>
                    <td class="py-2 text-slate-900 dark:text-white">{{ rupees(payment.amount) }}</td>
                    <td class="py-2"><BaseBadge :color="statusColor[payment.status]">{{ payment.status }}</BaseBadge></td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ formatDate(payment.created_at) }}</td>
                </tr>
                <tr v-if="payments.data.length === 0">
                    <td colspan="6" class="py-8 text-center text-slate-500 dark:text-slate-400">No payments match this filter.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="payments.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in payments.links"
                :key="index"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-1.5 text-sm"
                :class="link.active ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'"
                v-html="link.label"
            />
        </div>
    </BaseCard>
</template>
