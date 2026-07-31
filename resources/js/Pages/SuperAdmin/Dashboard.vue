<script setup>
import { Head } from '@inertiajs/vue3';
import {
    BuildingStorefrontIcon,
    ChartBarIcon,
    ClockIcon,
    CurrencyRupeeIcon,
    NoSymbolIcon,
    SparklesIcon,
} from '@heroicons/vue/24/outline';
import { computed, defineAsyncComponent } from 'vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import StatTile from '../../Components/Ui/StatTile.vue';
import { useTheme } from '../../Composables/useTheme';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps({
    metrics: { type: Object, required: true },
});

const { isDark } = useTheme();

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;

const revenueTrendSeries = computed(() => [{
    name: 'Revenue',
    data: props.metrics.revenueTrend.map((d) => [d.date, d.value]),
}]);

const revenueTrendOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    grid: { borderColor: isDark.value ? '#2c2c2a' : '#e1e0d9', strokeDashArray: 3 },
    dataLabels: { enabled: false },
    colors: ['#db2777'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
    xaxis: {
        type: 'datetime',
        labels: { style: { colors: isDark.value ? '#c3c2b7' : '#898781' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: isDark.value ? '#c3c2b7' : '#898781' }, formatter: (v) => rupees(v) } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', x: { format: 'dd MMM' }, y: { formatter: (v) => rupees(v) } },
}));

const planLabels = { monthly: 'Monthly', lifetime: 'Lifetime' };
const planDistributionEntries = computed(() => Object.entries(props.metrics.planDistribution));

const planDistributionOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
    colors: ['#2a78d6'],
    dataLabels: {
        enabled: true,
        style: { colors: [isDark.value ? '#ffffff' : '#0b0b0b'] },
        offsetX: 8,
    },
    grid: { show: false },
    xaxis: {
        categories: planDistributionEntries.value.map(([code]) => planLabels[code] ?? code),
        labels: { show: false },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: isDark.value ? '#c3c2b7' : '#52514e' } } },
}));

const planDistributionSeries = computed(() => [{
    name: 'Active subscriptions',
    data: planDistributionEntries.value.map(([, count]) => count),
}]);
</script>

<template>
    <Head title="Platform Overview" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Platform Overview</h1>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <StatTile label="Total Spas" :value="metrics.totalSpas" :icon="BuildingStorefrontIcon" />
        <StatTile label="Trialing" :value="metrics.trialSpas" :icon="ClockIcon" accent="amber" />
        <StatTile label="Active" :value="metrics.activeSpas" :icon="SparklesIcon" accent="green" />
        <StatTile label="Suspended" :value="metrics.suspendedSpas" :icon="NoSymbolIcon" :accent="metrics.suspendedSpas > 0 ? 'rose' : null" />
        <StatTile label="Trial → Paid" :value="`${metrics.trialConversionRate}%`" :icon="ChartBarIcon" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatTile label="MRR" :value="rupees(metrics.mrr)" :icon="CurrencyRupeeIcon" accent="green" />
        <StatTile label="Revenue this month" :value="rupees(metrics.revenueThisMonth)" :icon="CurrencyRupeeIcon" />
        <StatTile label="Total revenue collected" :value="rupees(metrics.totalRevenueCollected)" :icon="CurrencyRupeeIcon" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <BaseCard title="Platform revenue — last 30 days">
            <VueApexCharts type="area" height="260" :options="revenueTrendOptions" :series="revenueTrendSeries" />
        </BaseCard>
        <BaseCard title="Active subscriptions by plan">
            <VueApexCharts
                v-if="planDistributionEntries.length"
                type="bar" height="260" :options="planDistributionOptions" :series="planDistributionSeries"
            />
            <p v-else class="text-sm text-slate-500">No active subscriptions yet.</p>
        </BaseCard>
    </div>
</template>
