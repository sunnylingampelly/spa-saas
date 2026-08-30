<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import grapesjs from 'grapesjs';
import 'grapesjs-preset-newsletter';
import 'grapesjs/dist/css/grapes.min.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    starterTemplates: { type: Array, required: true },
});

const placeholderExample = '{{customer_name}}';

const segmentOptions = [
    { value: 'all', label: 'All customers with an email' },
    { value: 'vip', label: 'VIP customers only' },
    { value: 'tag', label: 'Customers with a specific tag' },
    { value: 'inactive_days', label: "Haven't visited recently (win-back)" },
];

const form = useForm({
    name: '',
    subject: '',
    preheader: '',
    body_html: '',
    audience_filter: { type: 'all', tag: '', days: 60 },
});

// --- Content mode: a visual drag-and-drop builder (default), or raw HTML for anyone who'd
// rather hand-write/paste a template. Both edit the exact same form.body_html string — the
// builder is purely a friendlier way to author it, nothing downstream needs to know which
// mode produced it.
const mode = ref('visual');
const editorContainer = ref(null);
let editor = null;

function syncFromEditor() {
    if (!editor) return;
    const css = editor.getCss();
    form.body_html = css ? `<style>${css}</style>${editor.getHtml()}` : editor.getHtml();
}

function loadIntoEditor(html) {
    if (!editor) return;
    editor.setComponents(html || '');
}

onMounted(() => {
    editor = grapesjs.init({
        container: editorContainer.value,
        fromElement: false,
        height: '600px',
        storageManager: false,
        plugins: ['gjs-preset-newsletter'],
        pluginsOpts: { 'gjs-preset-newsletter': {} },
    });

    editor.on('update', syncFromEditor);

    if (form.body_html) loadIntoEditor(form.body_html);
});

onBeforeUnmount(() => {
    editor?.destroy();
});

watch(mode, async (newMode) => {
    if (newMode === 'visual') {
        await nextTick();
        loadIntoEditor(form.body_html);
        editor?.refresh();
    }
});

function useTemplate(template) {
    form.name = template.name;
    form.subject = template.subject;
    form.body_html = template.body_html;

    if (mode.value === 'visual') loadIntoEditor(template.body_html);
}

const previewHtml = computed(() => form.body_html.replaceAll('{{customer_name}}', 'Priya'));

const recipientCount = ref(null);
const loadingCount = ref(false);

async function refreshAudienceCount() {
    loadingCount.value = true;
    try {
        const { data } = await axios.post(route('email-campaigns.audience-preview'), form.audience_filter);
        recipientCount.value = data.count;
    } catch {
        recipientCount.value = null;
    } finally {
        loadingCount.value = false;
    }
}

watch(() => [form.audience_filter.type, form.audience_filter.tag, form.audience_filter.days], refreshAudienceCount, { immediate: true });

function submit() {
    if (mode.value === 'visual') syncFromEditor();
    form.post(route('email-campaigns.store'));
}
</script>

<template>
    <Head title="New Campaign" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">New Campaign</h1>

    <form class="space-y-6" @submit.prevent="submit">
        <BaseCard title="Start from a template">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <button
                    v-for="template in starterTemplates"
                    :key="template.name"
                    type="button"
                    class="rounded-xl border border-slate-200 p-3 text-left text-sm hover:border-brand-400 dark:border-slate-700"
                    @click="useTemplate(template)"
                >
                    <p class="font-medium text-slate-900 dark:text-white">{{ template.name }}</p>
                    <p class="mt-1 truncate text-xs text-slate-400">{{ template.subject }}</p>
                </button>
            </div>
        </BaseCard>

        <BaseCard title="Details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.name" label="Campaign name (internal)" required :error="form.errors.name" />
                <BaseInput v-model="form.subject" label="Email subject" required :error="form.errors.subject" />
                <div class="sm:col-span-2">
                    <BaseInput v-model="form.preheader" label="Preheader (optional preview text)" :error="form.errors.preheader" />
                </div>
            </div>
        </BaseCard>

        <BaseCard title="Email content">
            <div class="mb-4 flex gap-1 rounded-xl bg-slate-100 p-1 dark:bg-slate-800/60">
                <button
                    type="button"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="mode === 'visual'
                        ? 'bg-white text-brand-700 shadow-sm dark:bg-slate-900 dark:text-white'
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                    @click="mode = 'visual'"
                >
                    Design visually
                </button>
                <button
                    type="button"
                    class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="mode === 'html'
                        ? 'bg-white text-brand-700 shadow-sm dark:bg-slate-900 dark:text-white'
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                    @click="mode = 'html'"
                >
                    Edit HTML directly
                </button>
            </div>

            <p class="mb-3 text-xs text-slate-400">
                Use <code>{{ placeholderExample }}</code> anywhere you want it personalized. An unsubscribe link and
                tracking pixel are added automatically — no need to include them.
            </p>

            <div v-show="mode === 'visual'" ref="editorContainer" />

            <div v-show="mode === 'html'" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <BaseTextarea v-model="form.body_html" label="HTML body" :rows="16" :error="form.errors.body_html" />
                <div>
                    <label class="form-label">Live preview</label>
                    <iframe :srcdoc="previewHtml" class="h-96 w-full rounded-xl border border-slate-200 bg-white dark:border-slate-700" />
                </div>
            </div>
        </BaseCard>

        <BaseCard title="Audience">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseListbox v-model="form.audience_filter.type" label="Send to" :options="segmentOptions" />

                <BaseInput
                    v-if="form.audience_filter.type === 'tag'"
                    v-model="form.audience_filter.tag"
                    label="Tag"
                    placeholder="e.g. vip-guest"
                />
                <BaseInput
                    v-if="form.audience_filter.type === 'inactive_days'"
                    v-model.number="form.audience_filter.days"
                    type="number"
                    min="1"
                    label="No visit in the last (days)"
                />
            </div>

            <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                <span v-if="loadingCount">Calculating…</span>
                <span v-else-if="recipientCount === null">Couldn't calculate the audience size.</span>
                <span v-else-if="recipientCount === 0">No customers match this — nothing would be sent.</span>
                <span v-else>~<strong>{{ recipientCount }}</strong> customer(s) will receive this campaign.</span>
            </p>
        </BaseCard>

        <BaseButton type="submit" :disabled="form.processing">Save as Draft</BaseButton>
    </form>
</template>
