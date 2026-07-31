import { defineStore } from 'pinia';

export const useUiStore = defineStore('ui', {
    state: () => ({
        isDark: false,
        sidebarOpen: true,
        commandPaletteOpen: false,
        commands: [],
    }),

    actions: {
        init() {
            const stored = localStorage.getItem('theme');
            this.isDark = stored
                ? stored === 'dark'
                : window.matchMedia('(prefers-color-scheme: dark)').matches;
            this.applyTheme();
        },

        toggleTheme() {
            this.isDark = !this.isDark;
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            this.applyTheme();
        },

        applyTheme() {
            document.documentElement.classList.toggle('dark', this.isDark);
            document.documentElement.setAttribute('data-theme', this.isDark ? 'dark' : 'light');
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        openCommandPalette() {
            this.commandPaletteOpen = true;
        },

        closeCommandPalette() {
            this.commandPaletteOpen = false;
        },

        toggleCommandPalette() {
            this.commandPaletteOpen = !this.commandPaletteOpen;
        },

        registerCommand(command) {
            if (this.commands.some((c) => c.id === command.id)) return;
            this.commands.push(command);
        },

        unregisterCommand(id) {
            this.commands = this.commands.filter((c) => c.id !== id);
        },
    },
});
