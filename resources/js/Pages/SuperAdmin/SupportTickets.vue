<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    tickets: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const statusBadge = {
    open: 'brand',
    in_progress: 'amber',
    resolved: 'green',
    closed: 'slate',
};

const statusFilterOptions = [
    { value: '', label: 'All statuses' },
    { value: 'open', label: 'Open' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'closed', label: 'Closed' },
];

function filterByStatus(status) {
    router.get(route('admin.support-tickets.index'), { status }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Support Tickets" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Support Tickets</h1>
        <BaseListbox
            :model-value="filters.status ?? ''"
            :options="statusFilterOptions"
            class="w-48"
            @update:model-value="filterByStatus"
        />
    </div>

    <BaseCard>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                    <th class="pb-2 font-medium">Subject</th>
                    <th class="pb-2 font-medium">Spa</th>
                    <th class="pb-2 font-medium">Status</th>
                    <th class="pb-2 font-medium">Last activity</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="ticket in tickets.data"
                    :key="ticket.id"
                    class="cursor-pointer border-b border-slate-50 hover:bg-slate-50 dark:border-slate-800/60 dark:hover:bg-slate-800/40"
                    @click="router.visit(route('admin.support-tickets.show', ticket.id))"
                >
                    <td class="py-2">
                        <span v-if="ticket.unread" class="mr-1.5 inline-block h-2 w-2 rounded-full bg-rose-500" />
                        <Link :href="route('admin.support-tickets.show', ticket.id)" class="font-medium text-brand-600 hover:text-brand-700">
                            {{ ticket.subject }}
                        </Link>
                    </td>
                    <td class="py-2 text-slate-600 dark:text-slate-300">
                        {{ ticket.spa?.name }}
                        <span class="block text-xs text-slate-400">{{ ticket.creator?.name }}</span>
                    </td>
                    <td class="py-2"><BaseBadge :color="statusBadge[ticket.status]">{{ ticket.status.replace('_', ' ') }}</BaseBadge></td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ formatDate(ticket.last_message_at, { withTime: true }) }}</td>
                </tr>
                <tr v-if="tickets.data.length === 0">
                    <td colspan="4" class="py-8 text-center text-slate-500 dark:text-slate-400">No support tickets.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="tickets.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in tickets.links"
                :key="index"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-1.5 text-sm"
                :class="link.active ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'"
                v-html="link.label"
            />
        </div>
    </BaseCard>
</template>
