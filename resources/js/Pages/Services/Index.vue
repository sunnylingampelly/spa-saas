<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import { useConfirm } from '../../Composables/useConfirm';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const { confirmDialog } = useConfirm();

const props = defineProps({
    services: { type: Object, required: true },
    categories: { type: Array, required: true },
});

function toggleStatus(service) {
    router.patch(route('services.toggle-status', service.id), {}, { preserveScroll: true });
}

async function destroy(service) {
    const confirmed = await confirmDialog({
        title: 'Delete this service?',
        message: `Delete "${service.name}"?`,
        confirmLabel: 'Delete',
        danger: true,
    });
    if (confirmed) {
        router.delete(route('services.destroy', service.id), { preserveScroll: true });
    }
}

function loadSampleCatalog() {
    router.post(route('services.seed-sample-catalog'), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Services" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Massage Services</h1>
        <div class="flex gap-3">
            <BaseButton v-if="services.data.length === 0" variant="secondary" @click="loadSampleCatalog">
                Load Sample Catalog
            </BaseButton>
            <Link :href="route('services.create')">
                <BaseButton><PlusIcon class="h-4 w-4" /> Add Service</BaseButton>
            </Link>
        </div>
    </div>

    <div v-if="services.data.length === 0" class="card text-center text-sm text-slate-500 dark:text-slate-400">
        No services yet — add one or load the sample catalog.
    </div>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="service in services.data" :key="service.id" class="card overflow-hidden !p-0">
            <div class="h-1.5 w-full" :style="{ backgroundColor: service.color_hex || '#94a3b8' }" />

            <div class="p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-900 dark:text-white">{{ service.name }}</p>
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ service.category?.name ?? 'Uncategorized' }}</p>
                    </div>
                    <BaseBadge :color="service.status === 'active' ? 'green' : 'slate'">{{ service.status }}</BaseBadge>
                </div>

                <div class="mt-4 flex items-center justify-between">
                    <div>
                        <span v-if="service.offer_price" class="mr-1.5 text-sm text-slate-400 line-through">₹{{ service.price }}</span>
                        <span class="text-lg font-semibold text-slate-900 dark:text-white">₹{{ service.offer_price ?? service.price }}</span>
                    </div>
                    <div class="text-right text-xs text-slate-400">
                        <p>{{ service.duration_minutes }} min</p>
                        <p>GST {{ service.gst_rate }}%</p>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
                    <Link :href="route('services.edit', service.id)" class="font-medium text-slate-500 hover:text-slate-700">Edit</Link>
                    <button class="font-medium text-brand-600 hover:text-brand-700" @click="toggleStatus(service)">
                        {{ service.status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <button class="font-medium text-rose-600 hover:text-rose-700" @click="destroy(service)">Delete</button>
                </div>
            </div>
        </div>
    </div>
</template>
