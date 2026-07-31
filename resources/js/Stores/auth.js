import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        roles: [],
        permissions: [],
        currentSpa: null,
    }),

    getters: {
        isSuperAdmin: (state) => state.roles.includes('super_admin'),
        isSpaOwner: (state) => state.roles.includes('spa_owner'),
    },

    actions: {
        syncFromPage(props) {
            this.user = props.auth?.user ?? null;
            this.roles = props.auth?.roles ?? [];
            this.permissions = props.auth?.permissions ?? [];
            this.currentSpa = props.currentSpa ?? null;
        },

        hasPermission(name) {
            return this.permissions.includes(name);
        },
    },
});
