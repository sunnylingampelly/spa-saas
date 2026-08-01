<script setup>
import { Combobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/vue';
import { CheckIcon, ChevronUpDownIcon, PlusIcon } from '@heroicons/vue/20/solid';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: null },
    options: { type: Array, required: true }, // [{ value, label, subtitle? }]
    label: { type: String, default: null },
    error: { type: String, default: null },
    required: { type: Boolean, default: false },
    placeholder: { type: String, default: 'Search…' },
    // When set, typing something with no match shows a "+ {createLabel}" row.
    // `{query}` in the label is replaced with what was typed.
    createLabel: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue', 'create']);

const query = ref('');

const normalizedOptions = computed(() => props.options.map(
    (opt) => (typeof opt === 'object' && opt !== null ? opt : { value: opt, label: String(opt) }),
));

const filteredOptions = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return normalizedOptions.value;

    return normalizedOptions.value.filter(
        (opt) => opt.label.toLowerCase().includes(q) || (opt.subtitle ?? '').toLowerCase().includes(q),
    );
});

const showCreateOption = computed(() => Boolean(props.createLabel) && query.value.trim().length > 0 && filteredOptions.value.length === 0);

function displayValue(value) {
    return normalizedOptions.value.find((opt) => opt.value === value)?.label ?? '';
}

function onSelect(value) {
    if (value === '__create__') {
        emit('create', query.value.trim());
        return;
    }
    emit('update:modelValue', value);
}

// The dropdown is teleported straight to <body> so it can never end up rendered behind a
// later sibling card — cards use backdrop-blur, which creates its own CSS stacking context,
// and no z-index inside one stacking context can ever escape above a sibling context (this
// bit every card-stacked form in the app, not just one page). Position is computed from the
// anchor's own viewport rect and kept in sync with position:fixed while the dropdown is open.
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

        <Combobox :model-value="modelValue" @update:model-value="onSelect" v-slot="{ open }">
            <div ref="anchorRef" class="relative">
                <ComboboxInput
                    class="form-input pr-10"
                    :class="error && 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/10'"
                    :display-value="displayValue"
                    :placeholder="placeholder"
                    autocomplete="off"
                    @change="query = $event.target.value"
                />
                <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <ChevronUpDownIcon class="h-4 w-4 text-slate-400" />
                </ComboboxButton>
            </div>

            <Teleport to="body">
                <transition
                    leave-active-class="transition duration-100 ease-in"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                    @after-leave="query = ''"
                >
                    <ComboboxOptions
                        v-if="open"
                        v-track-position
                        class="card fixed z-50 max-h-60 overflow-auto !p-1.5 text-sm focus:outline-none"
                        :style="{ top: `${position.top + 6}px`, left: `${position.left}px`, width: `${position.width}px` }"
                    >
                        <div v-if="filteredOptions.length === 0 && !showCreateOption" class="px-3 py-2 text-slate-400">
                            No matches.
                        </div>

                        <ComboboxOption
                            v-for="opt in filteredOptions"
                            :key="opt.value"
                            v-slot="{ active, selected }"
                            :value="opt.value"
                            as="template"
                        >
                            <li
                                class="flex cursor-pointer items-center justify-between rounded-lg px-3 py-2"
                                :class="active ? 'bg-brand-50 text-brand-700 dark:bg-slate-800 dark:text-white' : 'text-slate-700 dark:text-slate-200'"
                            >
                                <span>
                                    {{ opt.label }}
                                    <span v-if="opt.subtitle" class="ml-1.5 text-xs text-slate-400">{{ opt.subtitle }}</span>
                                </span>
                                <CheckIcon v-if="selected" class="h-4 w-4 flex-none text-brand-600" />
                            </li>
                        </ComboboxOption>

                        <ComboboxOption v-if="showCreateOption" v-slot="{ active }" value="__create__" as="template">
                            <li
                                class="flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 font-medium text-brand-600"
                                :class="active && 'bg-brand-50 dark:bg-slate-800'"
                            >
                                <PlusIcon class="h-4 w-4 flex-none" />
                                {{ createLabel.replace('{query}', query) }}
                            </li>
                        </ComboboxOption>
                    </ComboboxOptions>
                </transition>
            </Teleport>
        </Combobox>

        <p v-if="error" class="mt-1.5 text-sm text-rose-600 dark:text-rose-400">{{ error }}</p>
    </div>
</template>
