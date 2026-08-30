<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowPathIcon, PlusIcon } from '@heroicons/vue/24/outline';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTable from '../../Components/Ui/BaseTable.vue';
import { useConfirm } from '../../Composables/useConfirm';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
    templates: { type: Array, required: true },
});

const { confirmDialog } = useConfirm();

const statusColor = { pending: 'amber', approved: 'green', rejected: 'rose', paused: 'slate' };

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'category', label: 'Category' },
    { key: 'language', label: 'Language' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: '' },
];

function syncFromMeta() {
    router.post(route('whatsapp-templates.sync'), {}, { preserveScroll: true });
}

async function destroy(template) {
    const confirmed = await confirmDialog({
        title: 'Delete this template?',
        message: `Delete "${template.name}"? This can't be undone.`,
        confirmLabel: 'Delete',
        danger: true,
    });
    if (confirmed) {
        router.delete(route('whatsapp-templates.destroy', template.id));
    }
}
</script>

<template>
    <Head title="WhatsApp Templates" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">WhatsApp Templates</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Submitted to Meta for approval before they can be used in a campaign — free-form text can't be sent as a
                broadcast, only approved templates can.
            </p>
        </div>
        <div class="flex gap-3">
            <BaseButton variant="secondary" @click="syncFromMeta"><ArrowPathIcon class="h-4 w-4" /> Sync from Meta</BaseButton>
            <Link :href="route('whatsapp-templates.create')">
                <BaseButton><PlusIcon class="h-4 w-4" /> New Template</BaseButton>
            </Link>
        </div>
    </div>

    <BaseCard>
        <BaseTable :columns="columns" :rows="templates" empty-message="No templates yet — create your first one.">
            <template #cell-name="{ row }">
                <span class="font-medium text-slate-900 dark:text-white">{{ row.name }}</span>
                <p v-if="row.status === 'rejected' && row.rejected_reason" class="mt-0.5 text-xs text-rose-500">{{ row.rejected_reason }}</p>
            </template>
            <template #cell-category="{ row }">{{ row.category }}</template>
            <template #cell-status="{ row }"><BaseBadge :color="statusColor[row.status]">{{ row.status }}</BaseBadge></template>
            <template #cell-actions="{ row }">
                <BaseButton
                    v-if="row.status !== 'approved'"
                    variant="danger"
                    class="!px-2.5 !py-1 text-xs"
                    @click="destroy(row)"
                >
                    Delete
                </BaseButton>
            </template>
        </BaseTable>
    </BaseCard>
</template>
