import axios from 'axios';

export const USE_MOCK = import.meta.env.VITE_USE_MOCK === 'true';

const http = axios.create({
    baseURL: import.meta.env.VITE_API_URL || '/api',
    headers: { Accept: 'application/json' },
    timeout: 20000,
});

// Jeton d'authentification (Sanctum / Bearer) — stocké par l'écran de connexion.
http.interceptors.request.use((config) => {
    try {
        const token = localStorage.getItem('ebolo_token');
        if (token) config.headers.Authorization = `Bearer ${token}`;
    } catch { /* stockage indisponible : on continue sans jeton */ }
    return config;
});

http.interceptors.response.use(
    (r) => r,
    (error) => {
        if (error.response?.status === 401) {
            try { localStorage.removeItem('ebolo_token'); } catch { /* noop */ }
        }
        return Promise.reject(error);
    },
);

// Mode démo : l'adaptateur en mémoire n'est chargé que si VITE_USE_MOCK=true.
if (USE_MOCK) {
    http.defaults.adapter = (config) => import('./mock.js').then((m) => m.mockAdapter(config));
}

/** Message lisible à partir d'une erreur axios / Laravel (422, 5xx, réseau). */
export const errorMessage = (error, fallback = 'Une erreur est survenue. Réessayez.') => {
    const data = error?.response?.data;
    if (data?.errors) return Object.values(data.errors).flat()[0] ?? fallback;
    if (data?.message) return data.message;
    if (error?.code === 'ERR_NETWORK') return 'Connexion impossible. Vérifiez votre réseau.';
    return fallback;
};

export default http;
