import { ref, reactive, watch, onMounted, onBeforeUnmount } from 'vue';
import { api, errorMessage } from './api';

/**
 * Liste paginée + filtres + recherche (anti-rebond) branchée sur un endpoint de l'API.
 *   const list = useList('users', { status: '', role: '', search: '' });
 *   list.rows / list.meta / list.extra (counts, summary…) / list.loading / list.error / list.reload()
 */
export function useList(endpoint, initialFilters = {}, { perPage = 15 } = {}) {
    const filters = reactive({ ...initialFilters });
    const rows = ref([]);
    const meta = ref({ page: 1, last_page: 1, total: 0, per_page: perPage });
    const extra = ref({});
    const loading = ref(true);
    const error = ref('');
    const page = ref(1);

    let timer = null;
    let seq = 0;

    async function load() {
        const mine = ++seq;
        loading.value = true;
        error.value = '';
        try {
            const params = { page: page.value, per_page: perPage };
            Object.entries(filters).forEach(([k, v]) => { if (v !== '' && v != null) params[k] = v; });
            const { data } = await api.get(endpoint, { params });
            if (mine !== seq) return; // réponse obsolète
            rows.value = data.data;
            meta.value = data.meta;
            const { data: _d, meta: _m, ...rest } = data;
            extra.value = rest;
        } catch (e) {
            if (mine !== seq) return;
            error.value = errorMessage(e);
        } finally {
            if (mine === seq) loading.value = false;
        }
    }

    watch(filters, () => {
        page.value = 1;
        clearTimeout(timer);
        timer = setTimeout(load, 280);
    });

    onMounted(load);
    onBeforeUnmount(() => clearTimeout(timer));

    return {
        filters, rows, meta, extra, loading, error, page,
        reload: load,
        goTo(p) { page.value = Math.min(Math.max(1, p), meta.value.last_page); load(); },
        /** Remplace une ligne en place (après une action) sans recharger la page. */
        patch(row) {
            const i = rows.value.findIndex((r) => r.id === row.id);
            if (i > -1) rows.value[i] = { ...rows.value[i], ...row };
        },
    };
}
