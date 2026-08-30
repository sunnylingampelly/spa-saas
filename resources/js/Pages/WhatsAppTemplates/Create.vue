<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { computed, watch } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import WhatsAppMessagePreview from '../../Components/Ui/WhatsAppMessagePreview.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const categoryOptions = [
    { value: 'marketing', label: 'Marketing (offers, announcements)' },
    { value: 'utility', label: 'Utility (reminders, confirmations)' },
];

const buttonTypeOptions = [
    { value: 'QUICK_REPLY', label: 'Quick reply' },
    { value: 'URL', label: 'Website link' },
];

// Extracted to plain identifiers — a literal '{{1}}' written directly inside a template mustache
// breaks Vue's compiler ("Unterminated string constant"), since the {{ / }} inside the string
// confuses its own tokenizer.
const placeholderOne = '{{1}}';
const placeholderTwo = '{{2}}';

const form = useForm({
    name: '',
    category: 'marketing',
    language: 'en',
    header_text: '',
    footer_text: '',
    body_text: '',
    buttons: [],
    variable_samples: [],
});

// Meta's own placeholder convention: {{1}}, {{2}}... in the body text. Keeping the sample-value
// array in sync as the body is edited means the "example values for Meta's review" section never
// drifts out of step with what the body actually references.
const variableCount = computed(() => {
    const matches = [...form.body_text.matchAll(/\{\{(\d+)\}\}/g)].map((m) => Number(m[1]));
    return matches.length ? Math.max(...matches) : 0;
});

watch(variableCount, (count) => {
    const samples = form.variable_samples.slice(0, count);
    while (samples.length < count) samples.push('');
    form.variable_samples = samples;
});

function addButton() {
    if (form.buttons.length >= 3) return;
    form.buttons.push({ type: 'QUICK_REPLY', text: '', url: '' });
}

function removeButton(index) {
    form.buttons.splice(index, 1);
}

const previewBody = computed(() => {
    let text = form.body_text;
    form.variable_samples.forEach((sample, i) => {
        text = text.replaceAll(`{{${i + 1}}}`, sample || `{{${i + 1}}}`);
    });
    return text;
});

function submit() {
    form.post(route('whatsapp-templates.store'));
}
</script>

<template>
    <Head title="New WhatsApp Template" />

    <h1 class="mb-1 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">New WhatsApp Template</h1>
    <p class="mb-6 text-sm text-slate-500 dark:text-slate-400">
        This gets submitted to Meta for approval before it can be used in a campaign — usually a few minutes, sometimes up
        to a day.
    </p>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <form class="space-y-6 lg:col-span-2" @submit.prevent="submit">
            <BaseCard title="Template details">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        v-model="form.name"
                        label="Template name"
                        placeholder="festive_offer"
                        required
                        :error="form.errors.name"
                    />
                    <BaseListbox v-model="form.category" label="Category" :options="categoryOptions" :error="form.errors.category" />
                    <BaseInput v-model="form.language" label="Language code" placeholder="en" required :error="form.errors.language" />
                </div>
                <p class="mt-1.5 text-xs text-slate-400">
                    Lowercase letters, numbers, and underscores only — Meta's own naming rule (e.g. festive_offer).
                </p>
            </BaseCard>

            <BaseCard title="Content">
                <div class="space-y-4">
                    <BaseInput v-model="form.header_text" label="Header (optional)" placeholder="A little something for you" :error="form.errors.header_text" />
                    <BaseTextarea
                        v-model="form.body_text"
                        label="Body"
                        placeholder="Hi {{1}}, enjoy {{2}} off your next visit!"
                        :rows="5"
                        required
                        :error="form.errors.body_text"
                    />
                    <p class="text-xs text-slate-400">
                        Use <code>{{ placeholderOne }}</code>, <code>{{ placeholderTwo }}</code>... for personalization — you'll map
                        each one to a customer field or fixed text when you create a campaign.
                    </p>
                    <BaseInput v-model="form.footer_text" label="Footer (optional)" placeholder="Reply STOP to opt out" :error="form.errors.footer_text" />
                </div>
            </BaseCard>

            <BaseCard v-if="variableCount > 0" title="Example values (for Meta's review)">
                <p class="mb-3 text-xs text-slate-400">
                    Meta needs to see realistic example values for each placeholder to review this template.
                </p>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        v-for="n in variableCount"
                        :key="n"
                        v-model="form.variable_samples[n - 1]"
                        :label="`Example for {{${n}}}`"
                        :error="form.errors[`variable_samples.${n - 1}`]"
                    />
                </div>
            </BaseCard>

            <BaseCard title="Buttons (optional)">
                <div v-for="(button, index) in form.buttons" :key="index" class="mb-4 grid grid-cols-1 gap-3 border-b border-slate-100 pb-4 last:border-0 last:pb-0 sm:grid-cols-[1fr_1fr_auto] dark:border-slate-800">
                    <BaseListbox v-model="button.type" label="Type" :options="buttonTypeOptions" />
                    <BaseInput v-model="button.text" label="Button text" placeholder="Book Now" />
                    <BaseInput v-if="button.type === 'URL'" v-model="button.url" label="URL" placeholder="https://..." class="sm:col-span-2" />
                    <BaseButton type="button" variant="danger" class="self-end" @click="removeButton(index)"><TrashIcon class="h-4 w-4" /></BaseButton>
                </div>
                <BaseButton v-if="form.buttons.length < 3" type="button" variant="secondary" @click="addButton">
                    <PlusIcon class="h-4 w-4" /> Add button
                </BaseButton>
            </BaseCard>

            <BaseButton type="submit" :disabled="form.processing">Submit for Approval</BaseButton>
        </form>

        <div class="lg:sticky lg:top-6 lg:self-start">
            <BaseCard title="Preview">
                <WhatsAppMessagePreview
                    :header="form.header_text"
                    :body="previewBody"
                    :footer="form.footer_text"
                    :buttons="form.buttons"
                />
            </BaseCard>
        </div>
    </div>
</template>
