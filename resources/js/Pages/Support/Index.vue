<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    tickets: { type: Array, required: true },
});

const statusBadge = {
    open: 'brand',
    in_progress: 'amber',
    resolved: 'green',
    closed: 'slate',
};
</script>

<template>
    <Head title="Support" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Support</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Questions or issues? Message the SpaSaaS team directly.</p>
        </div>
        <Link :href="route('support.tickets.create')"><BaseButton>New Ticket</BaseButton></Link>
    </div>

    <BaseCard>
        <ul v-if="tickets.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <li v-for="ticket in tickets" :key="ticket.id">
                <Link :href="route('support.tickets.show', ticket.id)" class="flex items-center justify-between gap-3 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40">
                    <div class="flex items-center gap-2.5">
                        <span v-if="ticket.unread" class="h-2 w-2 flex-none rounded-full bg-rose-500" />
                        <div>
                            <p class="text-sm font-medium text-slate-900 dark:text-white">{{ ticket.subject }}</p>
                            <p class="text-xs text-slate-400">{{ formatDate(ticket.last_message_at, { withTime: true }) }}</p>
                        </div>
                    </div>
                    <BaseBadge :color="statusBadge[ticket.status]">{{ ticket.status.replace('_', ' ') }}</BaseBadge>
                </Link>
            </li>
        </ul>
        <p v-else class="py-6 text-center text-sm text-slate-500 dark:text-slate-400">
            No support tickets yet — raised an issue? <Link :href="route('support.tickets.create')" class="font-medium text-brand-600 hover:text-brand-700">Start one here</Link>.
        </p>
    </BaseCard>
</template>
