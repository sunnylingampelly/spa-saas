<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

defineProps({
    announcements: { type: Array, required: true },
});

const form = useForm({ message: '' });

function submit() {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function withdraw(announcement) {
    if (confirm('Withdraw this announcement? It will stop showing to spa owners immediately.')) {
        router.patch(route('admin.announcements.deactivate', announcement.id), {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Announcements" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Announcements</h1>

    <BaseCard title="Publish a new announcement" class="mb-6">
        <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">
            Shown as a banner to every spa owner across the platform. Publishing a new one retires whatever is currently live.
        </p>
        <form class="space-y-3" @submit.prevent="submit">
            <BaseTextarea v-model="form.message" placeholder="e.g. Scheduled maintenance tonight 11 PM–1 AM IST." :rows="2" :error="form.errors.message" />
            <BaseButton type="submit" :disabled="form.processing">Publish</BaseButton>
        </form>
    </BaseCard>

    <BaseCard title="History">
        <ul v-if="announcements.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <li v-for="announcement in announcements" :key="announcement.id" class="flex items-start justify-between gap-3 py-3">
                <div>
                    <p class="text-sm text-slate-900 dark:text-white">{{ announcement.message }}</p>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ announcement.creator?.name ?? 'System' }} · {{ formatDate(announcement.created_at, { withTime: true }) }}
                    </p>
                </div>
                <div class="flex flex-none items-center gap-2">
                    <BaseBadge :color="announcement.is_active ? 'green' : 'slate'">{{ announcement.is_active ? 'Live' : 'Retired' }}</BaseBadge>
                    <button
                        v-if="announcement.is_active"
                        class="text-sm font-medium text-rose-600 hover:text-rose-700"
                        @click="withdraw(announcement)"
                    >
                        Withdraw
                    </button>
                </div>
            </li>
        </ul>
        <p v-else class="text-sm text-slate-500 dark:text-slate-400">No announcements yet.</p>
    </BaseCard>
</template>
