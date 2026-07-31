<script setup>
import { Combobox, ComboboxInput, ComboboxOption, ComboboxOptions, Dialog, DialogPanel, TransitionChild, TransitionRoot } from '@headlessui/vue';
import { router } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useUiStore } from '../../Stores/ui';

const uiStore = useUiStore();
const { commandPaletteOpen, commands } = storeToRefs(uiStore);

const query = ref('');

const filteredCommands = computed(() => {
    if (!query.value) return commands.value;

    const search = query.value.toLowerCase();

    return commands.value.filter((command) =>
        [command.label, ...(command.keywords ?? [])].some((text) => text?.toLowerCase().includes(search)),
    );
});

function runCommand(command) {
    uiStore.closeCommandPalette();
    query.value = '';

    if (command.href) {
        router.visit(command.href);
    } else if (typeof command.action === 'function') {
        command.action();
    }
}

function handleKeydown(event) {
    if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        uiStore.toggleCommandPalette();
    }

    if (event.key === 'Escape') {
        uiStore.closeCommandPalette();
    }
}

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<template>
    <TransitionRoot appear :show="commandPaletteOpen" as="template" @after-leave="query = ''">
        <Dialog as="div" class="relative z-50" @close="uiStore.closeCommandPalette()">
            <TransitionChild
                as="template"
                enter="duration-150 ease-out" enter-from="opacity-0" enter-to="opacity-100"
                leave="duration-100 ease-in" leave-from="opacity-100" leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto p-4 pt-[15vh]">
                <TransitionChild
                    as="template"
                    enter="duration-150 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100"
                    leave="duration-100 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95"
                >
                    <DialogPanel class="glass-panel mx-auto max-w-xl rounded-2xl">
                        <Combobox @update:modelValue="runCommand">
                            <div class="flex items-center border-b border-slate-200 px-4 dark:border-slate-700">
                                <ComboboxInput
                                    class="w-full border-0 bg-transparent py-4 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-100"
                                    placeholder="Search actions, pages…"
                                    autocomplete="off"
                                    @change="query = $event.target.value"
                                />
                                <kbd class="rounded-md border border-slate-200 px-1.5 py-0.5 text-xs text-slate-400 dark:border-slate-600">Esc</kbd>
                            </div>

                            <ComboboxOptions static class="max-h-80 overflow-y-auto p-2">
                                <p v-if="filteredCommands.length === 0" class="px-3 py-6 text-center text-sm text-slate-500">
                                    No matching commands.
                                </p>
                                <ComboboxOption
                                    v-for="command in filteredCommands"
                                    :key="command.id"
                                    :value="command"
                                    v-slot="{ active }"
                                >
                                    <div
                                        class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm"
                                        :class="active ? 'bg-brand-500 text-white' : 'text-slate-700 dark:text-slate-200'"
                                    >
                                        <component :is="command.icon" v-if="command.icon" class="h-4 w-4 shrink-0" />
                                        <span>{{ command.label }}</span>
                                    </div>
                                </ComboboxOption>
                            </ComboboxOptions>
                        </Combobox>
                    </DialogPanel>
                </TransitionChild>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
