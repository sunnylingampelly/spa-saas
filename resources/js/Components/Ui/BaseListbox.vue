<script setup>
import { Listbox, ListboxButton, ListboxOption, ListboxOptions } from '@headlessui/vue';
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid';
import { computed, ref } from 'vue';

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

// See BaseCombobox.vue for why this is teleported: cards use backdrop-blur, which creates
// its own stacking context, so a dropdown's z-index can never escape above a later sibling
// card — teleporting straight to <body> and positioning manually sidesteps that entirely.
const anchorRef = ref(null);
const position = ref({ top: 0, left: 0, width: 0 });

function updatePosition() {
    if (!anchorRef.value) return;
    const rect = anchorRef.value.getBoundingClientRect();
    position.value = { top: rect.bottom, left: rect.left, width: rect.width };
}

// The options panel only exists in the DOM while open (v-if="open" below), so tying
// position tracking to its own mount/unmount avoids re-registering listeners on every render.
const vTrackPosition = {
    mounted() {
        updatePosition();
        window.addEventListener('scroll', updatePosition, true);
        window.addEventListener('resize', updatePosition);
    },
    unmounted() {
        window.removeEventListener('scroll', updatePosition, true);
        window.removeEventListener('resize', updatePosition);
    },
};
</script>

<template>
    <div>
        <label v-if="label" class="form-label">{{ label }}<span v-if="required" class="text-rose-500"> *</span></label>

        <Listbox :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" v-slot="{ open }">
            <div ref="anchorRef" class="relative">
                <ListboxButton
                    class="form-input flex w-full items-center justify-between text-left"
                    :class="error && 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/10'"
                >
                    <span :class="!selectedLabel && 'text-slate-400'">{{ selectedLabel ?? placeholder }}</span>
                    <ChevronUpDownIcon class="h-4 w-4 flex-none text-slate-400" />
                </ListboxButton>
            </div>

            <Teleport to="body">
                <transition leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <ListboxOptions
                        v-if="open"
                        v-track-position
                        class="card fixed z-50 max-h-60 overflow-auto !p-1.5 text-sm focus:outline-none"
                        :style="{ top: `${position.top + 6}px`, left: `${position.left}px`, width: `${position.width}px` }"
                    >
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
            </Teleport>
        </Listbox>

        <p v-if="error" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
    </div>
</template>
