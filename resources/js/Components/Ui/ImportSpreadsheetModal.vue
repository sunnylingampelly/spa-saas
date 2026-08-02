<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, ArrowUpTrayIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';
import BaseButton from './BaseButton.vue';
import BaseModal from './BaseModal.vue';

const props = defineProps({
    label: { type: String, required: true }, // e.g. "customers" — used only in copy
    importRoute: { type: String, required: true },
    importTemplateRoute: { type: String, required: true },
});

const show = ref(false);
const fileInput = ref(null);
const form = useForm({ file: null });
const rowErrors = ref([]);

const page = usePage();

function open() {
    rowErrors.value = [];
    show.value = true;
}

function close() {
    show.value = false;
    form.reset();
    form.clearErrors();
    if (fileInput.value) fileInput.value.value = '';
}

function onFileChange(event) {
    form.file = event.target.files[0] ?? null;
}

function submit() {
    form.post(route(props.importRoute), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            rowErrors.value = page.props.flash?.import_errors ?? [];
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
            if (!rowErrors.value.length) show.value = false;
        },
    });
}
</script>

<template>
    <BaseButton variant="secondary" @click="open">
        <ArrowUpTrayIcon class="h-4 w-4" /> Import
    </BaseButton>

    <BaseModal :show="show" :title="`Import ${label} from a spreadsheet`" @close="close">
        <div class="space-y-4">
            <a
                :href="route(importTemplateRoute)"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700"
            >
                <ArrowDownTrayIcon class="h-4 w-4" /> Download template
            </a>

            <div>
                <input
                    ref="fileInput"
                    type="file"
                    accept=".xlsx,.xls,.csv"
                    class="form-input"
                    @change="onFileChange"
                />
                <p v-if="form.errors.file" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ form.errors.file }}</p>
            </div>

            <div v-if="rowErrors.length" class="max-h-48 space-y-1.5 overflow-y-auto rounded-lg bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-900/20 dark:text-rose-400">
                <p class="font-medium">{{ rowErrors.length }} row(s) could not be imported:</p>
                <p v-for="err in rowErrors" :key="err.row">Row {{ err.row }}: {{ err.errors.join(', ') }}</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <BaseButton variant="secondary" @click="close">Close</BaseButton>
                <BaseButton :disabled="!form.file || form.processing" @click="submit">
                    {{ form.processing ? 'Importing…' : 'Import' }}
                </BaseButton>
            </div>
        </div>
    </BaseModal>
</template>
