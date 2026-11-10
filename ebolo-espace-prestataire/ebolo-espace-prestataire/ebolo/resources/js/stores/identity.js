import { defineStore } from 'pinia';
import { ref } from 'vue';
import * as api from '@/services/api';
import { errorMessage } from '@/services/http';

export const useIdentityStore = defineStore('identity', () => {
    // status : none | pending | approved | rejected
    const status = ref('none');
    const idType = ref(null);
    const submittedAt = ref(null);
    const rejectionReason = ref('');
    const error = ref('');
    const loading = ref(false);
    const submitting = ref(false);

    const apply = (d = {}) => {
        status.value = d.status ?? 'none';
        idType.value = d.id_type ?? null;
        submittedAt.value = d.submitted_at ?? null;
        rejectionReason.value = d.rejection_reason ?? '';
    };

    async function load() {
        loading.value = true;
        error.value = '';
        try {
            apply(await api.getIdentityVerification());
        } catch (e) {
            error.value = errorMessage(e, 'Impossible de charger votre dossier de vérification.');
        } finally {
            loading.value = false;
        }
    }

    async function submit(payload) {
        submitting.value = true;
        try {
            apply(await api.submitIdentityVerification(payload));
        } catch (e) {
            throw new Error(errorMessage(e, "L'envoi du dossier a échoué. Réessayez."));
        } finally {
            submitting.value = false;
        }
    }

    return { status, idType, submittedAt, rejectionReason, loading, submitting, error, load, submit };
});
