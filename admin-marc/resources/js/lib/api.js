import axios from 'axios';

export const TOKEN_KEY = 'admin_token';

export const api = axios.create({
    baseURL: '/api/admin',
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    timeout: 20000,
});

export const tokenStore = {
    get: () => { try { return localStorage.getItem(TOKEN_KEY); } catch { return null; } },
    set: (t) => { try { localStorage.setItem(TOKEN_KEY, t); } catch { /* stockage indisponible */ } },
    clear: () => { try { localStorage.removeItem(TOKEN_KEY); } catch { /* stockage indisponible */ } },
};

api.interceptors.request.use((config) => {
    const token = tokenStore.get();
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

let onUnauthorized = () => {};
export const setUnauthorizedHandler = (fn) => { onUnauthorized = fn; };

api.interceptors.response.use(
    (r) => r,
    (error) => {
        const url = error.config?.url || '';
        if (error.response?.status === 401 && !url.includes('auth/login')) onUnauthorized();
        return Promise.reject(error);
    },
);

/** Message lisible depuis une erreur Laravel (422 → premier message de champ). */
export function errorMessage(error, fallback = 'Une erreur est survenue. Veuillez réessayer.') {
    const data = error?.response?.data;
    if (data?.errors) return Object.values(data.errors).flat()[0] || data.message || fallback;
    if (data?.message) return data.message;
    if (error?.code === 'ECONNABORTED') return 'Le serveur met trop de temps à répondre.';
    if (!error?.response) return 'Impossible de joindre le serveur.';
    return fallback;
}

/** { champ: 'message' } pour l'affichage sous les champs de formulaire. */
export function fieldErrors(error) {
    const errors = error?.response?.data?.errors || {};
    return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]));
}
