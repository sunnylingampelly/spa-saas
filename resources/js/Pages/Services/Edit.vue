<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '../../Components/Ui/BaseButton.vue';
import BaseCard from '../../Components/Ui/BaseCard.vue';
import BaseInput from '../../Components/Ui/BaseInput.vue';
import BaseListbox from '../../Components/Ui/BaseListbox.vue';
import BaseTextarea from '../../Components/Ui/BaseTextarea.vue';
import AuthenticatedLayout from '../../Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const commissionTypeOptions = [
    { value: 'percentage', label: 'Percentage' },
    { value: 'flat', label: 'Flat amount' },
];

const statusOptions = [
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const props = defineProps({
    service: { type: Object, required: true },
    categories: { type: Array, required: true },
});

const categoryOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: c.name })));

const form = useForm({
    service_category_id: props.service.service_category_id ?? '',
    name: props.service.name,
    description: props.service.description ?? '',
    duration_minutes: props.service.duration_minutes,
    price: props.service.price,
    offer_price: props.service.offer_price ?? '',
    gst_rate: props.service.gst_rate,
    hsn_sac_code: props.service.hsn_sac_code ?? '',
    commission_type: props.service.commission_type,
    commission_value: props.service.commission_value,
    color_hex: props.service.color_hex ?? '#6366f1',
    status: props.service.status,
});

function submit() {
    form.put(route('services.update', props.service.id));
}
</script>

<template>
    <Head title="Edit Service" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Edit {{ service.name }}</h1>

    <form class="space-y-6" @submit.prevent="submit">
        <BaseCard title="Details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.name" label="Service name" required :error="form.errors.name" />
                <BaseListbox v-model="form.service_category_id" label="Category" :options="categoryOptions" placeholder="None" />
            </div>
            <div class="mt-4">
                <BaseTextarea v-model="form.description" label="Description" :rows="3" />
            </div>
        </BaseCard>

        <BaseCard title="Pricing & duration">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <BaseInput v-model="form.duration_minutes" type="number" label="Duration (minutes)" required :error="form.errors.duration_minutes" />
                <BaseInput v-model="form.price" type="number" label="Price (₹)" required :error="form.errors.price" />
                <BaseInput v-model="form.offer_price" type="number" label="Offer price (₹, optional)" :error="form.errors.offer_price" />
                <BaseInput v-model="form.gst_rate" type="number" label="GST rate (%)" :error="form.errors.gst_rate" />
                <BaseInput v-model="form.hsn_sac_code" label="SAC code" placeholder="Ask your GST practitioner" :error="form.errors.hsn_sac_code" />
                <BaseListbox v-model="form.commission_type" label="Commission type" :options="commissionTypeOptions" />
                <BaseInput v-model="form.commission_value" type="number" label="Commission value" :error="form.errors.commission_value" />
            </div>
        </BaseCard>

        <BaseCard title="Display">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="form-label">Color</label>
                    <input v-model="form.color_hex" type="color" class="h-11 w-20 rounded-lg border border-slate-200 dark:border-slate-700" />
                </div>
                <BaseListbox v-model="form.status" label="Status" :options="statusOptions" />
            </div>
        </BaseCard>

        <BaseButton type="submit" :disabled="form.processing">Save Changes</BaseButton>
    </form>
</template>
