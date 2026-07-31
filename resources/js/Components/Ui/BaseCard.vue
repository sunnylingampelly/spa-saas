<script setup>
defineProps({
    title: { type: String, default: null },
});
</script>

<template>
    <div class="card">
        <div v-if="title || $slots.actions" class="mb-4 flex items-center justify-between">
            <h3 v-if="title" class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h3>
            <div v-if="$slots.actions"><slot name="actions" /></div>
        </div>
        <slot />
    </div>
</template>
