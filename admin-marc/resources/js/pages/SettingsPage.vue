<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Settings } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import { api, errorMessage } from '../lib/api';
import { toast } from '../lib/toast';

const form = reactive({ commission_percent: 10, cancellation_hours: 24, support_email: 'support@ebolo.cm', brand_name: 'Ébolo' });
const loading = ref(true);
const saving = ref(false);
const error = ref('');

onMounted(async () => {
    try {
        Object.assign(form, (await api.get('settings')).data.data);
    } catch (exception) {
        error.value = errorMessage(exception);
    } finally {
        loading.value = false;
    }
});

async function save() {
    saving.value = true;
    error.value = '';
    try {
        Object.assign(form, (await api.patch('settings', form)).data.data);
        toast('Paramètres enregistrés.');
    } catch (exception) {
        error.value = errorMessage(exception);
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <PageHead eyebrow="Configuration" sub="Réglages persistés dans la base du backend partagé.">
        <template #title>Paramètres de la <em>plateforme</em></template>
    </PageHead>
    <p v-if="error" class="alert" role="alert">{{ error }}</p>
    <section class="card settings">
        <div class="card__head"><div><h2 class="card__title">Règles commerciales</h2><p class="card__sub">Ces valeurs sont des paramètres opérationnels de démonstration.</p></div><Settings /></div>
        <form class="card__body form" @submit.prevent="save">
            <label>Nom de la plateforme<input v-model.trim="form.brand_name" class="input" maxlength="120" required></label>
            <label>Commission (%)<input v-model.number="form.commission_percent" class="input" type="number" min="0" max="100" step="0.5" required></label>
            <label>Délai d’annulation (heures)<input v-model.number="form.cancellation_hours" class="input" type="number" min="0" max="720" required></label>
            <label>E-mail du support<input v-model.trim="form.support_email" class="input" type="email" required></label>
            <p class="notice">Les réglages affichés ne branchent pas encore de prestataire Mobile Money. Les paiements de démonstration ne débitent aucun compte.</p>
            <button class="btn btn--primary" type="submit" :disabled="saving || loading">{{ saving ? 'Enregistrement…' : 'Enregistrer les paramètres' }}</button>
        </form>
    </section>
</template>

<style scoped>
.form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.form label{display:grid;gap:7px;color:var(--muted);font-size:12px}.notice{grid-column:1/-1;padding:13px;border-radius:8px;background:#fff5df;color:#714c14;font-size:12px;line-height:1.5}.form button{justify-self:start}
@media(max-width:700px){.form{grid-template-columns:1fr}.notice{grid-column:auto}}
</style>
