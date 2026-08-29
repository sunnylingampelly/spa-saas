<script setup>
import { Head, router } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTable from '../../Components/Ui/BaseTable.vue';
import { useConfirm } from '../../Composables/useConfirm';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    campaign: { type: Object, required: true },
    recipients: { type: Object, required: true },
});

const { confirmDialog } = useConfirm();

const statusColor = { draft: 'slate', sending: 'amber', sent: 'green' };
const recipientStatusColor = { pending: 'slate', sent: 'green', failed: 'rose', bounced: 'rose' };

const columns = [
    { key: 'email', label: 'Recipient' },
    { key: 'status', label: 'Status' },
    { key: 'opened_at', label: 'Opened' },
    { key: 'click_count', label: 'Clicks' },
];

async function send() {
    const confirmed = await confirmDialog({
        title: 'Send this campaign?',
        message: `This will email ${props.campaign.recipients_count || 'the matching'} customer(s) right now. This can't be undone.`,
        confirmLabel: 'Send Campaign',
        danger: true,
    });
    if (confirmed) {
        router.post(route('email-campaigns.send', props.campaign.id), {}, { preserveScroll: true });
    }
}

async function destroy() {
    const confirmed = await confirmDialog({
        title: 'Delete this draft?',
        message: `Delete "${props.campaign.name}"? This can't be undone.`,
        confirmLabel: 'Delete',
        danger: true,
    });
    if (confirmed) {
        router.delete(route('email-campaigns.destroy', props.campaign.id));
    }
}
</script>

<template>
    <Head :title="campaign.name" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">{{ campaign.name }}</h1>
                <BaseBadge :color="statusColor[campaign.status]">{{ campaign.status }}</BaseBadge>
            </div>
            <p class="text-sm text-slate-500 dark:text-slate-400">{{ campaign.subject }}</p>
        </div>
        <div class="flex gap-3">
            <BaseButton v-if="campaign.status === 'draft'" variant="danger" @click="destroy">Delete Draft</BaseButton>
            <BaseButton v-if="campaign.status === 'draft'" @click="send">Send Campaign</BaseButton>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Recipients</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ campaign.recipients_count }}</p>
        </BaseCard>
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Sent</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900 dark:text-white">{{ campaign.sent_count }}</p>
        </BaseCard>
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Opened</p>
            <p class="mt-1 text-2xl font-semibold text-brand-600">{{ campaign.opened_count }}</p>
        </BaseCard>
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Clicked</p>
            <p class="mt-1 text-2xl font-semibold text-brand-600">{{ campaign.clicked_count }}</p>
        </BaseCard>
        <BaseCard>
            <p class="text-sm text-slate-500 dark:text-slate-400">Unsubscribed</p>
            <p class="mt-1 text-2xl font-semibold text-rose-600">{{ campaign.unsubscribed_count }}</p>
        </BaseCard>
    </div>

    <BaseCard title="Recipients">
        <BaseTable :columns="columns" :rows="recipients.data" empty-message="Not sent yet — no recipients locked in.">
            <template #cell-email="{ row }">{{ row.customer?.name ?? row.email }}</template>
            <template #cell-status="{ row }"><BaseBadge :color="recipientStatusColor[row.status]">{{ row.status }}</BaseBadge></template>
            <template #cell-opened_at="{ row }">{{ row.opened_at ? formatDate(row.opened_at, { withTime: true }) : '—' }}</template>
        </BaseTable>
    </BaseCard>
</template>
