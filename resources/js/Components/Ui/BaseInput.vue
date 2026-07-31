<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: { type: String, default: null },
    type: { type: String, default: 'text' },
    error: { type: String, default: null },
    placeholder: { type: String, default: '' },
    autofocus: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div>
        <label v-if="label" class="form-label">{{ label }}</label>
        <input
            :type="type"
            :value="modelValue"
            :placeholder="placeholder"
            :autofocus="autofocus"
            :required="required"
            class="form-input"
            :class="error && 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20'"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
    </div>
</template>
