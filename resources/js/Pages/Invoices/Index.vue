<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseTable from '../../Components/Ui/BaseTable.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const statusFilterOptions = [
    { value: 'unpaid', label: 'Unpaid' },
    { value: 'partially_paid', label: 'Partially paid' },
    { value: 'paid', label: 'Paid' },
    { value: 'refunded', label: 'Refunded' },
    { value: 'cancelled', label: 'Cancelled' },
];

const props = defineProps({
    invoices: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const statusColor = (status) => ({
    unpaid: 'rose',
    partially_paid: 'amber',
    paid: 'green',
    refunded: 'slate',
    cancelled: 'slate',
}[status] ?? 'slate');

function filterByStatus(status) {
    router.get(route('invoices.index'), { status }, { preserveState: true });
}

const columns = [
    { key: 'invoice_number', label: 'Invoice' },
    { key: 'customer', label: 'Billed to' },
    { key: 'total_amount', label: 'Total' },
    { key: 'balance_amount', label: 'Due' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Date' },
    { key: 'actions', label: '' },
];
</script>

<template>
    <Head title="Invoices" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Invoices</h1>
        <div class="flex items-center gap-3">
            <BaseListbox
                :model-value="filters.status"
                class="w-44"
                :options="statusFilterOptions"
                placeholder="All statuses"
                @update:model-value="filterByStatus"
            />
            <Link :href="route('invoices.create')">
                <BaseButton><PlusIcon class="h-4 w-4" /> New Bill</BaseButton>
            </Link>
        </div>
    </div>

    <BaseCard>
        <BaseTable :columns="columns" :rows="invoices.data" empty-message="No invoices yet.">
            <template #cell-customer="{ row }">{{ row.customer?.name ?? row.guest_name ?? 'Guest' }}</template>
            <template #cell-total_amount="{ row }">₹{{ row.total_amount }}</template>
            <template #cell-balance_amount="{ row }">₹{{ row.balance_amount }}</template>
            <template #cell-status="{ row }">
                <BaseBadge :color="statusColor(row.status)">{{ row.status.replace('_', ' ') }}</BaseBadge>
            </template>
            <template #cell-created_at="{ row }">{{ formatDate(row.created_at) }}</template>
            <template #cell-actions="{ row }">
                <Link :href="route('invoices.show', row.id)" class="text-brand-600 hover:text-brand-700">View</Link>
            </template>
        </BaseTable>
    </BaseCard>
</template>
