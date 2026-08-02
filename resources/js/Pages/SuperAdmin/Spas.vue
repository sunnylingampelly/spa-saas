<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import SuperAdminLayout from '../../Layouts/SuperAdminLayout.vue';

defineOptions({ layout: SuperAdminLayout });

const props = defineProps({
    spas: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');

function runSearch() {
    router.get(route('admin.spas.index'), { search: search.value }, { preserveState: true, replace: true });
}

const statusBadge = {
    trialing: 'brand',
    active: 'green',
    past_due: 'amber',
    cancelled: 'rose',
};
</script>

<template>
    <Head title="Spas" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Spas</h1>
        <div class="flex items-center gap-3">
            <div class="relative">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search spa or owner…"
                    class="form-input w-64 pl-9"
                    @keyup.enter="runSearch"
                />
            </div>
            <a :href="route('admin.spas.export', { search })">
                <BaseButton variant="secondary"><ArrowDownTrayIcon class="h-4 w-4" /> Export</BaseButton>
            </a>
        </div>
    </div>

    <BaseCard>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 text-left text-xs uppercase tracking-wide text-slate-400 dark:border-slate-800">
                    <th class="pb-2 font-medium">Spa</th>
                    <th class="pb-2 font-medium">Owner</th>
                    <th class="pb-2 font-medium">Plan</th>
                    <th class="pb-2 font-medium">Subscription status</th>
                    <th class="pb-2 font-medium">Platform status</th>
                    <th class="pb-2 font-medium">Trial / renewal</th>
                    <th class="pb-2 font-medium">Created</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="spa in spas.data" :key="spa.id" class="border-b border-slate-50 hover:bg-slate-50 dark:border-slate-800/60 dark:hover:bg-slate-800/40">
                    <td class="py-2">
                        <Link :href="route('admin.spas.show', spa.id)" class="font-medium text-brand-600 hover:text-brand-700">
                            {{ spa.name }}
                        </Link>
                    </td>
                    <td class="py-2 text-slate-600 dark:text-slate-300">
                        {{ spa.owner?.name }}
                        <span class="block text-xs text-slate-400">{{ spa.owner?.email }}</span>
                    </td>
                    <td class="py-2 capitalize text-slate-600 dark:text-slate-300">{{ spa.subscription?.plan_code ?? '—' }}</td>
                    <td class="py-2">
                        <BaseBadge v-if="spa.subscription" :color="statusBadge[spa.subscription.status]">
                            {{ spa.subscription.status }}
                        </BaseBadge>
                        <span v-else class="text-slate-400">—</span>
                    </td>
                    <td class="py-2">
                        <BaseBadge v-if="spa.status === 'suspended'" color="rose">Suspended</BaseBadge>
                        <span v-else class="text-slate-400">—</span>
                    </td>
                    <td class="py-2 text-slate-600 dark:text-slate-300">
                        {{ spa.subscription?.current_period_ends_at ? formatDate(spa.subscription.current_period_ends_at) : '—' }}
                    </td>
                    <td class="py-2 text-slate-500 dark:text-slate-400">{{ formatDate(spa.created_at) }}</td>
                </tr>
                <tr v-if="spas.data.length === 0">
                    <td colspan="7" class="py-8 text-center text-slate-500 dark:text-slate-400">No spas match this search.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="spas.links.length > 3" class="mt-4 flex flex-wrap gap-1">
            <Link
                v-for="(link, index) in spas.links"
                :key="index"
                :href="link.url ?? '#'"
                class="rounded-lg px-3 py-1.5 text-sm"
                :class="link.active ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800'"
                v-html="link.label"
            />
        </div>
    </BaseCard>
</template>
