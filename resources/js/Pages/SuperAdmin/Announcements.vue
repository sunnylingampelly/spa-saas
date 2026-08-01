<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import { useConfirm } from '../../Composables/useConfirm';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const { confirmDialog } = useConfirm();

defineProps({
    announcements: { type: Array, required: true },
});

const colorOptions = [
    { value: 'indigo', label: 'Indigo (default)', swatch: 'bg-indigo-600' },
    { value: 'brand', label: 'Brand pink', swatch: 'bg-brand-600' },
    { value: 'emerald', label: 'Green (good news)', swatch: 'bg-emerald-600' },
    { value: 'amber', label: 'Amber (caution)', swatch: 'bg-amber-600' },
    { value: 'rose', label: 'Rose (urgent)', swatch: 'bg-rose-600' },
    { value: 'slate', label: 'Slate (neutral)', swatch: 'bg-slate-700' },
];

const form = useForm({ message: '', color: 'indigo' });

function submit() {
    form.post(route('admin.announcements.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

async function withdraw(announcement) {
    const confirmed = await confirmDialog({
        title: 'Withdraw this announcement?',
        message: 'It will stop showing to spa owners immediately.',
        confirmLabel: 'Withdraw',
        danger: true,
    });
    if (confirmed) {
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

            <div>
                <label class="form-label">Banner color</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="option in colorOptions"
                        :key="option.value"
                        type="button"
                        class="flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm transition"
                        :class="form.color === option.value
                            ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20'
                            : 'border-slate-200 hover:border-slate-300 dark:border-slate-700'"
                        @click="form.color = option.value"
                    >
                        <span class="h-3.5 w-3.5 rounded-full" :class="option.swatch" />
                        {{ option.label }}
                    </button>
                </div>
                <p v-if="form.errors.color" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ form.errors.color }}</p>
            </div>

            <BaseButton type="submit" :disabled="form.processing">Publish</BaseButton>
        </form>
    </BaseCard>

    <BaseCard title="History">
        <ul v-if="announcements.length" class="divide-y divide-slate-100 dark:divide-slate-800">
            <li v-for="announcement in announcements" :key="announcement.id" class="flex items-start justify-between gap-3 py-3">
                <div class="flex items-start gap-2.5">
                    <span
                        class="mt-1.5 h-2.5 w-2.5 flex-none rounded-full"
                        :class="colorOptions.find((c) => c.value === announcement.color)?.swatch ?? 'bg-indigo-600'"
                    />
                    <div>
                        <p class="text-sm text-slate-900 dark:text-white">{{ announcement.message }}</p>
                        <p class="mt-1 text-xs text-slate-400">
                            {{ announcement.creator?.name ?? 'System' }} · {{ formatDate(announcement.created_at, { withTime: true }) }}
                        </p>
                    </div>
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
