<script setup>
defineProps({
    header: { type: String, default: null },
    body: { type: String, default: '' },
    footer: { type: String, default: null },
    buttons: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="mx-auto max-w-xs rounded-2xl bg-[#e5ddd5] p-4 dark:bg-slate-800">
        <div class="rounded-lg rounded-tl-none bg-[#dcf8c6] p-3 text-sm text-slate-800 shadow dark:bg-emerald-900/70 dark:text-emerald-50">
            <p v-if="header" class="mb-1 font-semibold">{{ header }}</p>
            <p class="whitespace-pre-wrap">{{ body || 'Your message body will appear here.' }}</p>
            <p v-if="footer" class="mt-1 text-xs text-slate-500 dark:text-emerald-200/70">{{ footer }}</p>
        </div>
        <div v-if="buttons?.length" class="mt-1.5 space-y-1">
            <div
                v-for="(button, index) in buttons"
                :key="index"
                class="rounded-lg bg-white py-2 text-center text-sm font-medium text-sky-600 shadow dark:bg-slate-700 dark:text-sky-300"
            >
                {{ button.text || 'Button' }}
            </div>
        </div>
    </div>
</template>
