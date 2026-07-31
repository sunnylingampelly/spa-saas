<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    activities: { type: Object, required: true },
    filters: { type: Object, required: true },
    spaName: { type: String, default: null },
});

const eventColor = { created: 'green', updated: 'amber', deleted: 'rose' };

function subjectLabel(activity) {
    const type = activity.subject_type?.split('\\').pop() ?? 'Record';
    const name = activity.subject?.name ?? activity.subject?.invoice_number ?? activity.subject?.employee_code ?? activity.subject?.customer_code;
    return name ? `${type} · ${name}` : `${type} #${activity.subject_id}`;
}

function changedFields(activity) {
    const attributes = activity.properties?.attributes ?? {};
    const old = activity.properties?.old ?? {};
    return Object.keys(attributes).filter((key) => key in old);
}
</script>

<template>
    <Head title="Activity Log" />

    <div class="mb-6">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Activity Log</h1>
        <p v-if="spaName" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            Filtered to <span class="font-medium">{{ spaName }}</span> —
            <Link :href="route('admin.activity.index')" class="text-brand-600 hover:text-brand-700">clear filter</Link>
        </p>
    </div>

    <BaseCard>
        <ul v-if="activities.data.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <li v-for="activity in activities.data" :key="activity.id" class="py-3">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-900 dark:text-white">
                            <span class="font-medium">{{ activity.causer?.name ?? 'System' }}</span>
                            {{ activity.description }}
                            <span class="text-slate-500 dark:text-slate-400">{{ subjectLabel(activity) }}</span>
                        </p>
                        <p v-if="changedFields(activity).length" class="mt-1 text-xs text-slate-400">
                            Changed: {{ changedFields(activity).join(', ') }}
                        </p>
                    </div>
                    <div class="flex flex-none items-center gap-2 text-right">
                        <BaseBadge :color="eventColor[activity.event] ?? 'slate'">{{ activity.event ?? activity.description }}</BaseBadge>
                        <span class="text-xs text-slate-400">{{ formatDate(activity.created_at, { withTime: true }) }}</span>
                    </div>
                </div>
            </li>
        </ul>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">No activity recorded yet.</p>

        <div v-if="activities.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in activities.links"
                :key="index"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-1.5 text-sm"
                :class="link.active ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'"
                v-html="link.label"
            />
        </div>
    </BaseCard>
</template>
