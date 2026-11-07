import { reactive } from 'vue';
import { api, tokenStore, setUnauthorizedHandler } from './api';

export const auth = reactive({
    admin: null,
    ready: false,

    get isLoggedIn() { return !!this.admin; },

    can(permission) {
        if (!this.admin) return false;
        return this.admin.adminRole === 'super_admin' || (this.admin.permissions || []).includes(permission);
    },

    async boot() {
        if (tokenStore.get()) {
            try {
                const { data } = await api.get('auth/me');
                this.admin = data.admin;
            } catch {
                tokenStore.clear();
                this.admin = null;
            }
        }
        this.ready = true;
    },

    async login(email, password) {
        const { data } = await api.post('auth/login', { email, password });
        tokenStore.set(data.accessToken);
        this.admin = data.admin;
    },

    async logout() {
        try { await api.post('auth/logout'); } catch { /* jeton déjà invalide */ }
        this.reset();
    },

    reset() {
        tokenStore.clear();
        this.admin = null;
    },
});

setUnauthorizedHandler(() => {
    if (auth.admin) {
        auth.reset();
        window.dispatchEvent(new CustomEvent('ebolo:session-expired'));
    }
});
