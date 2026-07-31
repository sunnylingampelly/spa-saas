<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import BaseBadge from '../../Components/Ui/BaseBadge.vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import { formatDate } from '../../Composables/useDateFormat.js';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    customers: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const search = ref(props.filters.search ?? '');

function runSearch() {
    router.get(route('customers.index'), { search: search.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Customers" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Customers</h1>
        <div class="flex items-center gap-3">
            <div class="relative">
                <MagnifyingGlassIcon class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search name or phone…"
                    class="form-input w-64 pl-9"
                    @keyup.enter="runSearch"
                />
            </div>
            <Link :href="route('customers.create')">
                <BaseButton><PlusIcon class="h-4 w-4" /> Add Customer</BaseButton>
            </Link>
        </div>
    </div>

    <div v-if="customers.data.length === 0" class="card text-center text-sm text-slate-500 dark:text-slate-400">
        No customers yet.
    </div>

    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="customer in customers.data" :key="customer.id" class="card !p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-semibold text-white">
                    {{ customer.name.charAt(0) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <p class="truncate font-medium text-slate-900 dark:text-white">{{ customer.name }}</p>
                        <BaseBadge v-if="customer.is_vip" color="brand">VIP</BaseBadge>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ customer.phone || 'No phone on file' }}</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-2">
                <div class="rounded-lg bg-slate-50 py-2 text-center dark:bg-slate-800/60">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">₹{{ customer.wallet_balance }}</p>
                    <p class="text-xs text-slate-400">Wallet</p>
                </div>
                <div class="rounded-lg bg-slate-50 py-2 text-center dark:bg-slate-800/60">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ customer.reward_points }}</p>
                    <p class="text-xs text-slate-400">Points</p>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
                <span class="text-slate-400">Since {{ formatDate(customer.customer_since) }}</span>
                <div class="flex items-center gap-3">
                    <Link :href="route('customers.show', customer.id)" class="font-medium text-brand-600 hover:text-brand-700">View</Link>
                    <Link :href="route('customers.edit', customer.id)" class="font-medium text-slate-500 hover:text-slate-700">Edit</Link>
                </div>
            </div>
        </div>
    </div>
</template>
