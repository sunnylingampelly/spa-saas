<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, ChevronLeftIcon, ChevronRightIcon, PlusIcon } from '@heroicons/vue/24/outline';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import { formatDate, formatTime } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const statusOptions = [
    { value: 'booked', label: 'Booked' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'no_show', label: 'No show' },
];

const leadSourceLabels = {
    walk_in: 'Walk-in',
    google_ads: 'Google Ads',
    meta_ads: 'Meta Ads',
    referral: 'Referral',
    website: 'Website',
    phone_enquiry: 'Phone enquiry',
    other: 'Other',
};

const leadSourceFilterOptions = Object.entries(leadSourceLabels).map(([value, label]) => ({ value, label }));

const leadSourceColor = (source) => (['google_ads', 'meta_ads'].includes(source) ? 'brand' : 'slate');

const props = defineProps({
    date: { type: String, required: true },
    appointments: { type: Array, required: true },
    filters: { type: Object, required: true },
});

const statusColor = (status) => ({
    booked: 'slate',
    confirmed: 'brand',
    in_progress: 'amber',
    completed: 'green',
    cancelled: 'rose',
    no_show: 'rose',
}[status] ?? 'slate');

function goToDate(newDate) {
    router.get(route('appointments.index'), { date: newDate, lead_source: props.filters.lead_source }, { preserveState: true });
}

function filterBySource(source) {
    router.get(route('appointments.index'), { date: props.date, lead_source: source }, { preserveState: true });
}

function shiftDay(offset) {
    // Pure date-part arithmetic (via Date.UTC) — avoids the browser's local
    // timezone shifting the calendar day when adding/subtracting whole days.
    const [year, month, day] = props.date.split('-').map(Number);
    const shifted = new Date(Date.UTC(year, month - 1, day + offset));
    goToDate(shifted.toISOString().slice(0, 10));
}

function setStatus(appointment, status) {
    router.patch(route('appointments.update-status', appointment.id), { status }, { preserveScroll: true });
}
</script>

<template>
    <Head title="Appointments" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Appointments</h1>
        <div class="flex items-center gap-2">
            <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="shiftDay(-1)">
                <ChevronLeftIcon class="h-5 w-5" />
            </button>
            <input
                type="date"
                :value="date"
                class="form-input w-44"
                @change="goToDate($event.target.value)"
            />
            <button class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" @click="shiftDay(1)">
                <ChevronRightIcon class="h-5 w-5" />
            </button>
            <BaseListbox
                class="ml-2 w-44"
                :model-value="filters.lead_source"
                :options="leadSourceFilterOptions"
                placeholder="All sources"
                @update:model-value="filterBySource"
            />
            <a :href="route('appointments.export', { date })">
                <BaseButton variant="secondary" class="ml-2"><ArrowDownTrayIcon class="h-4 w-4" /> Export</BaseButton>
            </a>
            <Link :href="route('appointments.create', { date })">
                <BaseButton class="ml-2"><PlusIcon class="h-4 w-4" /> Book Appointment</BaseButton>
            </Link>
        </div>
    </div>

    <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">{{ formatDate(date) }}</p>

    <div v-if="appointments.length === 0" class="card text-center text-sm text-slate-500 dark:text-slate-400">
        No appointments booked for this day.
    </div>

    <div class="space-y-3">
        <BaseCard v-for="appointment in appointments" :key="appointment.id" class="!p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-4">
                    <div class="text-center">
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ formatTime(appointment.starts_at) }}</p>
                        <p class="text-xs text-slate-400">{{ appointment.service.duration_minutes }}m</p>
                    </div>
                    <span
                        class="h-10 w-1 rounded-full"
                        :style="{ backgroundColor: appointment.service.color_hex || '#db2777' }"
                    />
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white">
                            {{ appointment.customer.name }}
                            <span class="text-sm font-normal text-slate-500">· {{ appointment.service.name }}</span>
                        </p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            {{ appointment.employee?.name ?? 'Unassigned' }}
                            <BaseBadge v-if="appointment.booking_type === 'walk_in'" color="amber" class="ml-2">Walk-in</BaseBadge>
                            <BaseBadge :color="leadSourceColor(appointment.lead_source)" class="ml-2">
                                {{ leadSourceLabels[appointment.lead_source] ?? appointment.lead_source }}
                            </BaseBadge>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <BaseBadge :color="statusColor(appointment.status)">{{ appointment.status.replace('_', ' ') }}</BaseBadge>

                    <BaseListbox
                        class="w-40"
                        :model-value="appointment.status"
                        :options="statusOptions"
                        @update:model-value="setStatus(appointment, $event)"
                    />

                    <Link
                        v-if="appointment.invoice"
                        :href="route('invoices.show', appointment.invoice.id)"
                        class="text-sm font-medium text-emerald-600 hover:text-emerald-700"
                    >
                        {{ appointment.invoice.invoice_number }}
                    </Link>
                    <Link
                        v-else
                        :href="route('invoices.create', { appointment_id: appointment.id })"
                        class="text-sm font-medium text-brand-600 hover:text-brand-700"
                    >
                        Bill
                    </Link>

                    <Link :href="route('appointments.edit', appointment.id)" class="text-sm text-slate-500 hover:text-slate-700">Edit</Link>
                </div>
            </div>
        </BaseCard>
    </div>
</template>
