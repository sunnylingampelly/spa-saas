<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref, watch } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import WhatsAppMessagePreview from '../../Components/Ui/WhatsAppMessagePreview.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
    approvedTemplates: { type: Array, required: true },
});

const segmentOptions = [
    { value: 'all', label: 'All customers with a WhatsApp number' },
    { value: 'vip', label: 'VIP customers only' },
    { value: 'tag', label: 'Customers with a specific tag' },
    { value: 'inactive_days', label: "Haven't visited recently (win-back)" },
];

const variableSourceOptions = [
    { value: 'customer_name', label: "Customer's name" },
    { value: 'static', label: 'Fixed text for everyone' },
];

const form = useForm({
    name: '',
    whatsapp_template_id: props.approvedTemplates[0]?.id ?? null,
    variable_values: [],
    audience_filter: { type: 'all', tag: '', days: 60 },
});

const selectedTemplate = computed(() => props.approvedTemplates.find((t) => t.id === form.whatsapp_template_id) ?? null);

// Keep variable_values in step with whichever template is currently selected — switching
// templates resets the mapping rather than carrying over stale config for a different template.
watch(selectedTemplate, (template) => {
    const count = template?.variable_count ?? 0;
    form.variable_values = Array.from({ length: count }, () => ({ source: 'customer_name', value: '' }));
}, { immediate: true });

const previewBody = computed(() => {
    if (!selectedTemplate.value) return '';
    let text = selectedTemplate.value.body_text;
    form.variable_values.forEach((config, i) => {
        const sample = config.source === 'customer_name' ? 'Priya' : config.value || `{{${i + 1}}}`;
        text = text.replaceAll(`{{${i + 1}}}`, sample);
    });
    return text;
});

const recipientCount = ref(null);
const loadingCount = ref(false);

async function refreshAudienceCount() {
    loadingCount.value = true;
    try {
        const { data } = await axios.post(route('whatsapp-campaigns.audience-preview'), form.audience_filter);
        recipientCount.value = data.count;
    } catch {
        recipientCount.value = null;
    } finally {
        loadingCount.value = false;
    }
}

watch(() => [form.audience_filter.type, form.audience_filter.tag, form.audience_filter.days], refreshAudienceCount, { immediate: true });

function submit() {
    form.post(route('whatsapp-campaigns.store'));
}
</script>

<template>
    <Head title="New WhatsApp Campaign" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">New WhatsApp Campaign</h1>

    <div v-if="approvedTemplates.length === 0">
        <BaseCard>
            <p class="text-sm text-slate-600 dark:text-slate-300">
                You don't have any Meta-approved WhatsApp templates yet — a campaign can only send an approved template,
                never free-form text.
            </p>
            <Link :href="route('whatsapp-templates.create')" class="mt-3 inline-block">
                <BaseButton>Create a Template</BaseButton>
            </Link>
        </BaseCard>
    </div>

    <form v-else class="grid grid-cols-1 gap-6 lg:grid-cols-3" @submit.prevent="submit">
        <div class="space-y-6 lg:col-span-2">
            <BaseCard title="Details">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput v-model="form.name" label="Campaign name (internal)" required :error="form.errors.name" />
                    <BaseListbox
                        v-model="form.whatsapp_template_id"
                        label="Template"
                        :options="approvedTemplates.map((t) => ({ value: t.id, label: t.name }))"
                        :error="form.errors.whatsapp_template_id"
                    />
                </div>
            </BaseCard>

            <BaseCard v-if="selectedTemplate && form.variable_values.length > 0" title="Personalize the variables">
                <div v-for="(config, index) in form.variable_values" :key="index" class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <BaseListbox v-model="config.source" :label="`{{${index + 1}}}`" :options="variableSourceOptions" />
                    <BaseInput
                        v-if="config.source === 'static'"
                        v-model="config.value"
                        label="Text for everyone"
                        placeholder="20%"
                    />
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
        </div>

        <div class="lg:sticky lg:top-6 lg:self-start">
            <BaseCard title="Preview">
                <WhatsAppMessagePreview
                    v-if="selectedTemplate"
                    :header="selectedTemplate.header_text"
                    :body="previewBody"
                    :footer="selectedTemplate.footer_text"
                    :buttons="selectedTemplate.buttons ?? []"
                />
            </BaseCard>
        </div>
    </form>
</template>
