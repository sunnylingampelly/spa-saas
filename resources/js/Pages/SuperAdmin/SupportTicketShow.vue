<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    ticket: { type: Object, required: true },
});

const statusBadge = {
    open: 'brand',
    in_progress: 'amber',
    resolved: 'green',
    closed: 'slate',
};

const statusOptions = [
    { value: 'open', label: 'Open' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'resolved', label: 'Resolved' },
    { value: 'closed', label: 'Closed' },
];

function updateStatus(status) {
    router.patch(route('admin.support-tickets.update-status', props.ticket.id), { status }, { preserveScroll: true });
}

const form = useForm({ body: '' });

function submit() {
    form.post(route('admin.support-tickets.reply', props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head :title="ticket.subject" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <Link :href="route('admin.support-tickets.index')" class="text-sm font-medium text-slate-500 hover:text-slate-700">← Back to Support Tickets</Link>
            <h1 class="mt-1 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ ticket.subject }}</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ ticket.spa?.name }} · {{ ticket.creator?.name }}</p>
        </div>
        <div class="flex items-center gap-3">
            <BaseBadge :color="statusBadge[ticket.status]">{{ ticket.status.replace('_', ' ') }}</BaseBadge>
            <BaseListbox :model-value="ticket.status" :options="statusOptions" class="w-40" @update:model-value="updateStatus" />
        </div>
    </div>

    <BaseCard>
        <div class="space-y-4">
            <div
                v-for="message in ticket.messages"
                :key="message.id"
                class="flex"
                :class="message.is_from_admin ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-lg rounded-2xl px-4 py-2.5 text-sm"
                    :class="message.is_from_admin
                        ? 'bg-brand-600 text-white'
                        : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'"
                >
                    <p class="whitespace-pre-wrap">{{ message.body }}</p>
                    <p class="mt-1 text-xs opacity-70">
                        {{ message.is_from_admin ? 'SpaSaaS Support' : (message.author?.name ?? ticket.creator?.name) }} ·
                        {{ formatDate(message.created_at, { withTime: true }) }}
                    </p>
                </div>
            </div>
        </div>

        <form class="mt-6 space-y-3 border-t border-slate-100 pt-4 dark:border-slate-800" @submit.prevent="submit">
            <BaseTextarea v-model="form.body" placeholder="Write a reply…" :rows="3" :error="form.errors.body" />
            <BaseButton type="submit" :disabled="form.processing">Send Reply</BaseButton>
        </form>
    </BaseCard>
</template>
