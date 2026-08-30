<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTable from '../../Components/Ui/BaseTable.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    campaigns: { type: Array, required: true },
});

const statusColor = { draft: 'slate', sending: 'amber', sent: 'green' };

const columns = [
    { key: 'name', label: 'Campaign' },
    { key: 'template_name', label: 'Template' },
    { key: 'status', label: 'Status' },
    { key: 'recipients_count', label: 'Recipients' },
    { key: 'delivery_rate', label: 'Delivered' },
    { key: 'read_rate', label: 'Read' },
    { key: 'sent_at', label: 'Sent' },
];
</script>

<template>
    <Head title="WhatsApp Campaigns" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">WhatsApp Campaigns</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Broadcast an approved WhatsApp template to your customers — sent from your own connected number, billed to
                you directly by Meta.
            </p>
        </div>
        <Link :href="route('whatsapp-campaigns.create')">
            <BaseButton><PlusIcon class="h-4 w-4" /> New Campaign</BaseButton>
        </Link>
    </div>

    <BaseCard>
        <BaseTable :columns="columns" :rows="campaigns" empty-message="No campaigns yet — create your first one.">
            <template #cell-name="{ row }">
                <Link :href="route('whatsapp-campaigns.show', row.id)" class="font-medium text-brand-600 hover:text-brand-700">
                    {{ row.name }}
                </Link>
            </template>
            <template #cell-status="{ row }"><BaseBadge :color="statusColor[row.status]">{{ row.status }}</BaseBadge></template>
            <template #cell-delivery_rate="{ row }">{{ row.delivery_rate }}%</template>
            <template #cell-read_rate="{ row }">{{ row.read_rate }}%</template>
            <template #cell-sent_at="{ row }">{{ row.sent_at ? formatDate(row.sent_at) : '—' }}</template>
        </BaseTable>
    </BaseCard>
</template>
