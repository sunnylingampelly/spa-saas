<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: null },
    error: { type: String, default: null },
    placeholder: { type: String, default: '' },
    rows: { type: [String, Number], default: 3 },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label v-if="label" class="form-label">{{ label }}</label>
        <textarea
            :value="modelValue"
            :rows="rows"
            :placeholder="placeholder"
            class="form-textarea"
            :class="error && 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/10'"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
    </div>
</template>
