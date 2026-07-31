<script setup>
defineProps({
    columns: { type: Array, required: true }, // [{ key, label }]
    rows: { type: Array, required: true },
    emptyMessage: { type: String, default: 'No records found.' },
});
</script>

<template>
    <div class="overflow-x-auto rounded-xl">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead>
                <tr>
                    <th
                        v-for="column in columns"
                        :key="column.key"
                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400"
                    >
                        {{ column.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr v-if="rows.length === 0">
                    <td :colspan="columns.length" class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                        {{ emptyMessage }}
                    </td>
                </tr>
                <tr
                    v-for="(row, index) in rows"
                    :key="row.id ?? index"
                    class="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                >
                    <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                        <slot :name="`cell-${column.key}`" :row="row">{{ row[column.key] }}</slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
