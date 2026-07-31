<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    accent: { type: String, default: null }, // 'green' | 'rose' | 'amber' | null
    icon: { type: [Object, Function], default: null },
});

const accentClass = {
    green: 'text-emerald-600 dark:text-emerald-400',
    rose: 'text-rose-600 dark:text-rose-400',
    amber: 'text-amber-600 dark:text-amber-400',
};

const iconBadgeClass = {
    green: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-300',
    rose: 'bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300',
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-900/30 dark:text-brand-300',
};
</script>

<template>
    <div class="card !p-4">
        <div class="flex items-center gap-2">
            <div
                v-if="icon"
                class="flex h-7 w-7 flex-none items-center justify-center rounded-md"
                :class="iconBadgeClass[accent] ?? iconBadgeClass.brand"
            >
                <component :is="icon" class="h-4 w-4" />
            </div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ label }}</p>
        </div>
        <p
            class="mt-2 text-4xl font-bold tracking-tight tabular-nums text-slate-900 dark:text-white"
            :class="accent && accentClass[accent]"
        >
            {{ value }}
        </p>
    </div>
</template>
