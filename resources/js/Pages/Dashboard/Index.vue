<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import {
    ArrowPathIcon,
    ArrowTrendingUpIcon,
    BanknotesIcon,
    CalendarDaysIcon,
    ClockIcon,
    CurrencyRupeeIcon,
    DocumentTextIcon,
    ReceiptPercentIcon,
    UserGroupIcon,
    UserPlusIcon,
} from '@heroicons/vue/24/outline';
import { computed, defineAsyncComponent } from 'vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import StatTile from '../../Components/Ui/StatTile.vue';
import { useTheme } from '../../Composables/useTheme';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

// Charting is the heaviest dependency on this page — load it only once the
// component actually renders, instead of bundling it into every page load.
const VueApexCharts = defineAsyncComponent(() => import('vue3-apexcharts'));

const props = defineProps({
    metrics: { type: Object, required: true },
});

const page = usePage();
const { isDark } = useTheme();

const rupees = (value) => `₹${Number(value).toLocaleString('en-IN', { maximumFractionDigits: 0 })}`;

const chartBaseOptions = computed(() => ({
    chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    grid: { borderColor: isDark.value ? '#2c2c2a' : '#e1e0d9', strokeDashArray: 3 },
    dataLabels: { enabled: false },
    xaxis: {
        type: 'datetime',
        labels: { style: { colors: isDark.value ? '#c3c2b7' : '#898781' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: { labels: { style: { colors: isDark.value ? '#c3c2b7' : '#898781' } } },
    tooltip: { theme: isDark.value ? 'dark' : 'light', x: { format: 'dd MMM' } },
}));

const revenueTrendSeries = computed(() => [{
    name: 'Revenue',
    data: props.metrics.revenueTrend.map((d) => [d.date, d.value]),
}]);

const revenueTrendOptions = computed(() => ({
    ...chartBaseOptions.value,
    colors: ['#db2777'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
    yaxis: { ...chartBaseOptions.value.yaxis, labels: { ...chartBaseOptions.value.yaxis.labels, formatter: (v) => rupees(v) } },
    tooltip: { ...chartBaseOptions.value.tooltip, y: { formatter: (v) => rupees(v) } },
}));

const customerGrowthSeries = computed(() => [{
    name: 'New customers',
    data: props.metrics.customerGrowthTrend.map((d) => [d.date, d.value]),
}]);

const customerGrowthOptions = computed(() => ({
    ...chartBaseOptions.value,
    colors: ['#2a78d6'],
    stroke: { curve: 'smooth', width: 2 },
    fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0.02 } },
    yaxis: { ...chartBaseOptions.value.yaxis, labels: { ...chartBaseOptions.value.yaxis.labels, formatter: (v) => Math.round(v) } },
}));

function barOptions(categories, color) {
    return {
        chart: { toolbar: { show: false }, fontFamily: 'inherit', background: 'transparent' },
        theme: { mode: isDark.value ? 'dark' : 'light' },
        plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
        colors: [color],
        dataLabels: {
            enabled: true,
            formatter: (v) => rupees(v),
            style: { colors: [isDark.value ? '#ffffff' : '#0b0b0b'] },
            offsetX: 8,
        },
        grid: { show: false },
        xaxis: { categories, labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { style: { colors: isDark.value ? '#c3c2b7' : '#52514e' } } },
        tooltip: { theme: isDark.value ? 'dark' : 'light', y: { formatter: (v) => rupees(v) } },
    };
}

const popularServicesOptions = computed(() => barOptions(props.metrics.popularServices.map((s) => s.name), '#db2777'));
const popularServicesSeries = computed(() => [{ name: 'Revenue', data: props.metrics.popularServices.map((s) => s.revenue) }]);

const popularEmployeesOptions = computed(() => barOptions(props.metrics.popularEmployees.map((e) => e.name), '#2a78d6'));
const popularEmployeesSeries = computed(() => [{ name: 'Revenue', data: props.metrics.popularEmployees.map((e) => e.revenue) }]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="mb-6">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">
            Welcome, {{ page.props.auth.user?.name }}
        </h1>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            {{ page.props.currentSpa?.name }} · {{ formatDate(new Date()) }} · FY {{ metrics.financialYear }}
        </p>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <StatTile label="Today's Revenue" :value="rupees(metrics.today.revenue)" accent="green" :icon="CurrencyRupeeIcon" />
        <StatTile label="Today's Appointments" :value="metrics.today.appointments" :icon="CalendarDaysIcon" />
        <StatTile label="Today's Walk-ins" :value="metrics.today.walkIns" :icon="UserPlusIcon" />
        <StatTile label="Today's Customers" :value="metrics.today.newCustomers" :icon="UserGroupIcon" />
        <StatTile label="Today's Bills" :value="metrics.today.bills" :icon="ReceiptPercentIcon" />
        <StatTile label="Today's GST" :value="rupees(metrics.today.gst)" :icon="DocumentTextIcon" />
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <StatTile
            label="Pending Payments"
            :value="rupees(metrics.pendingPayments)"
            :accent="metrics.pendingPayments > 0 ? 'rose' : null"
            :icon="ClockIcon"
        />
        <StatTile label="Repeat Customers (mo.)" :value="metrics.customersThisMonth.repeat" :icon="ArrowPathIcon" />
        <StatTile label="New Customers (mo.)" :value="metrics.customersThisMonth.new" :icon="UserPlusIcon" />
        <StatTile label="Monthly Revenue" :value="rupees(metrics.monthlyRevenue)" :icon="CurrencyRupeeIcon" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <BaseCard title="Revenue — last 30 days">
            <VueApexCharts type="area" height="260" :options="revenueTrendOptions" :series="revenueTrendSeries" />
        </BaseCard>
        <BaseCard title="Customer growth — last 30 days">
            <VueApexCharts type="area" height="260" :options="customerGrowthOptions" :series="customerGrowthSeries" />
        </BaseCard>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <BaseCard title="Popular Services (30 days, paid bills)">
            <VueApexCharts
                v-if="metrics.popularServices.length"
                type="bar" height="240" :options="popularServicesOptions" :series="popularServicesSeries"
            />
            <p v-else class="text-sm text-slate-500">No paid bills yet in this window.</p>
        </BaseCard>
        <BaseCard title="Popular Therapists (30 days, paid bills)">
            <VueApexCharts
                v-if="metrics.popularEmployees.length"
                type="bar" height="240" :options="popularEmployeesOptions" :series="popularEmployeesSeries"
            />
            <p v-else class="text-sm text-slate-500">No paid bills with an assigned therapist yet.</p>
        </BaseCard>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <StatTile label="Yearly Revenue (FY)" :value="rupees(metrics.yearlyRevenue)" :icon="ArrowTrendingUpIcon" />
        <StatTile label="Expenses (this month)" :value="rupees(metrics.expensesThisMonth)" :icon="BanknotesIcon" />
        <StatTile
            label="Gross Profit (this month)"
            :value="rupees(metrics.profitThisMonth)"
            :accent="metrics.profitThisMonth >= 0 ? 'green' : 'rose'"
            :icon="ArrowTrendingUpIcon"
        />
    </div>
    <p class="mt-2 text-xs text-slate-400">
        Gross profit = revenue collected − expenses logged. Staff salaries/commission aren't netted out separately yet —
        if you log them as expenses too, avoid double-counting.
    </p>
</template>
