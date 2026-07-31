<script setup>
import { Head, router } from '@inertiajs/vue3';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTable from '../../Components/Ui/BaseTable.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    from: { type: String, required: true },
    to: { type: String, required: true },
    rows: { type: Array, required: true },
    totalCommission: { type: Number, required: true },
    totalRevenue: { type: Number, required: true },
});

function applyRange(from, to) {
    router.get(route('reports.commissions'), { from, to }, { preserveState: true });
}

const columns = [
    { key: 'employee_name', label: 'Therapist' },
    { key: 'items_count', label: 'Services billed' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'commission', label: 'Commission' },
];
</script>

<template>
    <Head title="Commission Report" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Commission Report</h1>
        <div class="flex items-center gap-2">
            <input type="date" :value="from" class="form-input" @change="applyRange($event.target.value, to)" />
            <span class="text-slate-400">to</span>
            <input type="date" :value="to" class="form-input" @change="applyRange(from, $event.target.value)" />
        </div>
    </div>

    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
        Commission is calculated only on <strong>paid</strong> invoices — a service's commission rate applies
        if configured, otherwise the therapist's personal rate is used as a fallback.
    </p>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Revenue (billed services)</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900 dark:text-white">₹{{ totalRevenue.toFixed(2) }}</p>
        </BaseCard>
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Commission Owed</p>
            <p class="mt-2 text-3xl font-semibold text-brand-600">₹{{ totalCommission.toFixed(2) }}</p>
        </BaseCard>
    </div>

    <BaseCard>
        <BaseTable :columns="columns" :rows="rows" empty-message="No paid invoices with an assigned therapist in this range.">
            <template #cell-revenue="{ row }">₹{{ row.revenue.toFixed(2) }}</template>
            <template #cell-commission="{ row }">
                <span class="font-medium text-brand-600">₹{{ row.commission.toFixed(2) }}</span>
            </template>
        </BaseTable>
    </BaseCard>
</template>
