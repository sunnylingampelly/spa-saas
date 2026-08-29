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
    totalRevenue: { type: Number, required: true },
});

function applyRange(from, to) {
    router.get(route('reports.lead-sources'), { from, to }, { preserveState: true });
}

const leadSourceLabels = {
    walk_in: 'Walk-in',
    google_ads: 'Google Ads',
    meta_ads: 'Meta Ads',
    referral: 'Referral',
    website: 'Website / Organic',
    phone_enquiry: 'Phone enquiry',
    other: 'Other',
};

function labelFor(row) {
    return row.lead_source ? (leadSourceLabels[row.lead_source] ?? row.lead_source) : 'Direct (no appointment)';
}

const columns = [
    { key: 'lead_source', label: 'Source' },
    { key: 'invoice_count', label: 'Bills' },
    { key: 'revenue', label: 'Revenue' },
    { key: 'share', label: 'Share' },
];
</script>

<template>
    <Head title="Revenue by Source" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Revenue by Source</h1>
        <div class="flex items-center gap-2">
            <input type="date" :value="from" class="form-input" @change="applyRange($event.target.value, to)" />
            <span class="text-slate-400">to</span>
            <input type="date" :value="to" class="form-input" @change="applyRange(from, $event.target.value)" />
        </div>
    </div>

    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
        Revenue is grouped by the booking source recorded on the appointment behind each <strong>paid</strong> bill —
        useful for weighing ad spend (Google/Meta) against what it's actually bringing in.
    </p>

    <div class="mb-6">
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Total Revenue (all sources)</p>
            <p class="mt-2 text-3xl font-semibold text-slate-900 dark:text-white">₹{{ totalRevenue.toFixed(2) }}</p>
        </BaseCard>
    </div>

    <BaseCard>
        <BaseTable :columns="columns" :rows="rows" empty-message="No paid bills in this range yet.">
            <template #cell-lead_source="{ row }">{{ labelFor(row) }}</template>
            <template #cell-revenue="{ row }">₹{{ row.revenue.toFixed(2) }}</template>
            <template #cell-share="{ row }">
                {{ totalRevenue > 0 ? ((row.revenue / totalRevenue) * 100).toFixed(1) : '0.0' }}%
            </template>
        </BaseTable>
    </BaseCard>
</template>
