<script setup>
import { Head, router } from '@inertiajs/vue3';
import GuestLayout from '../../Layouts/GuestLayout.vue';

defineOptions({ layout: GuestLayout });

defineProps({
    spas: { type: Array, required: true },
});

function selectSpa(spaId) {
    router.post('/spa/switch', { spa_id: spaId });
}
</script>

<template>
    <Head title="Choose a spa" />

    <h1 class="mb-6 text-xl font-semibold text-slate-900 dark:text-white">Choose which spa to manage</h1>

    <div class="space-y-3">
        <button
            v-for="spa in spas"
            :key="spa.id"
            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-left text-sm font-medium text-slate-700 transition hover:border-brand-400 hover:bg-brand-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
            @click="selectSpa(spa.id)"
        >
            {{ spa.name }}
        </button>
    </div>
</template>
