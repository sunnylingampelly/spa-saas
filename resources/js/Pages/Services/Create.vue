<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
    categories: { type: Array, required: true },
});

const categoryOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: c.name })));

const form = useForm({
    service_category_id: '',
    name: '',
    description: '',
    duration_minutes: 60,
    price: '',
    offer_price: '',
    gst_rate: 18.0,
    hsn_sac_code: '',
    commission_type: 'percentage',
    commission_value: 0,
    color_hex: '#6366f1',
    status: 'active',
});

const newCategoryName = ref('');
const categoryForm = useForm({ name: '' });

function addCategory() {
    categoryForm.name = newCategoryName.value;
    categoryForm.post(route('service-categories.store'), {
        preserveScroll: true,
        onSuccess: () => { newCategoryName.value = ''; },
    });
}

function submit() {
    form.post(route('services.store'));
}
</script>

<template>
    <Head title="Add Service" />

    <h1 class="mb-6 text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Add Service</h1>

    <form class="space-y-6" @submit.prevent="submit">
        <BaseCard title="Details">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <BaseInput v-model="form.name" label="Service name" required autofocus :error="form.errors.name" />

                <div>
                    <BaseListbox v-model="form.service_category_id" label="Category" :options="categoryOptions" placeholder="None" />
                    <div class="mt-2 flex gap-2">
                        <BaseInput v-model="newCategoryName" type="text" placeholder="New category name" />
                        <BaseButton type="button" variant="secondary" @click="addCategory">Add</BaseButton>
                    </div>
                </div>
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

        <BaseButton type="submit" :disabled="form.processing">Save Service</BaseButton>
    </form>
</template>
