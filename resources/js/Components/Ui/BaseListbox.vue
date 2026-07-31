<script setup>
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue';
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, Boolean], default: null },
    options: { type: Array, required: true }, // [{ value, label }] or plain strings/numbers
    label: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    placeholder: { type: String, default: 'Select…' },
});

defineEmits(['update:modelValue']);

const normalizedOptions = computed(() => props.options.map(
    (opt) => (typeof opt === 'object' && opt !== null ? opt : { value: opt, label: String(opt) }),
));

const selectedLabel = computed(() => normalizedOptions.value.find((opt) => opt.value === props.modelValue)?.label ?? null);
</script>

<template>
    <div>
        <label v-if="label" class="form-label">{{ label }}<span v-if="required" class="text-rose-500"> *</span></label>

        <Listbox :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)">
            <div class="relative">
                <ListboxButton
                    class="form-input flex w-full items-center justify-between text-left"
                    :class="error && 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/10'"
                >
                    <span :class="!selectedLabel && 'text-slate-400'">{{ selectedLabel ?? placeholder }}</span>
                    <ChevronUpDownIcon class="h-4 w-4 flex-none text-slate-400" />
                </ListboxButton>

                <transition leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <ListboxOptions class="card absolute z-20 mt-1.5 max-h-60 w-full overflow-auto !p-1.5 text-sm focus:outline-none">
                        <ListboxOption
                            v-for="opt in normalizedOptions"
                            :key="opt.value"
                            v-slot="{ active, selected }"
                            :value="opt.value"
                            as="template"
                        >
                            <li
                                class="flex cursor-pointer items-center justify-between rounded-lg px-3 py-2"
                                :class="active ? 'bg-brand-50 text-brand-700 dark:bg-slate-800 dark:text-white' : 'text-slate-700 dark:text-slate-200'"
                            >
                                {{ opt.label }}
                                <CheckIcon v-if="selected" class="h-4 w-4 flex-none text-brand-600" />
                            </li>
                        </ListboxOption>
                    </ListboxOptions>
                </transition>
            </div>
        </Listbox>

        <p v-if="error" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
    </div>
</template>
