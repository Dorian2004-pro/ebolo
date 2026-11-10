<script setup>
import { onMounted, reactive, ref } from 'vue';
import { FileText, Plus, Trash2 } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import EmptyState from '../components/EmptyState.vue';
import { api, errorMessage } from '../lib/api';
import { toast } from '../lib/toast';

const items = ref([]);
const loading = ref(true);
const busy = ref(false);
const error = ref('');
const form = reactive({ content_type: 'announcement', title: '', body: '' });

async function load() {
    loading.value = true;
    error.value = '';
    try {
        items.value = (await api.get('content')).data.data;
    } catch (exception) {
        error.value = errorMessage(exception);
    } finally {
        loading.value = false;
    }
}

async function save() {
    busy.value = true;
    error.value = '';
    try {
        const { data } = await api.post('content', form);
        items.value.unshift(data.data);
        Object.assign(form, { content_type: 'announcement', title: '', body: '' });
        toast('Contenu publié dans le panneau.');
    } catch (exception) {
        error.value = errorMessage(exception);
    } finally {
        busy.value = false;
    }
}

async function toggle(item) {
    try {
        const { data } = await api.patch(`content/${item.id}`, { is_active: !item.is_active });
        Object.assign(item, data.data);
    } catch (exception) {
        error.value = errorMessage(exception);
    }
}

async function remove(item) {
    if (!window.confirm(`Supprimer « ${item.title} » ?`)) return;
    try {
        await api.delete(`content/${item.id}`);
        items.value = items.value.filter((entry) => entry.id !== item.id);
        toast('Contenu supprimé.');
    } catch (exception) {
        error.value = errorMessage(exception);
    }
}

onMounted(load);
</script>

<template>
    <PageHead eyebrow="Plateforme" sub="Publiez les annonces, questions fréquentes et bannières éditoriales d’Ébolo.">
        <template #title>Gestion du <em>contenu</em></template>
    </PageHead>
    <p v-if="error" class="alert" role="alert">{{ error }}</p>
    <section class="card">
        <div class="card__head"><div><h2 class="card__title">Ajouter un contenu</h2><p class="card__sub">Le texte est conservé dans la base partagée.</p></div></div>
        <form class="card__body form" @submit.prevent="save">
            <label>Type<select v-model="form.content_type" class="select"><option value="announcement">Annonce</option><option value="banner">Bannière</option><option value="faq">FAQ</option></select></label>
            <label>Titre<input v-model.trim="form.title" class="input" required maxlength="255"></label>
            <label>Texte<textarea v-model.trim="form.body" class="textarea" required maxlength="5000" rows="3"></textarea></label>
            <button class="btn btn--primary" :disabled="busy"><Plus />{{ busy ? 'Publication…' : 'Publier' }}</button>
        </form>
    </section>
    <section class="card list">
        <div class="card__head"><div><h2 class="card__title">Contenus publiés</h2></div></div>
        <p v-if="loading" class="card__body muted">Chargement…</p>
        <EmptyState v-else-if="!items.length" title="Aucun contenu" text="Ajoutez une annonce ou une question fréquente."><template #icon><FileText /></template></EmptyState>
        <article v-for="item in items" :key="item.id" class="item">
            <div><span class="chip">{{ item.content_type }}</span><h3>{{ item.title }}</h3><p>{{ item.body }}</p></div>
            <div class="actions">
                <button class="btn btn--ghost" type="button" @click="toggle(item)">{{ item.is_active ? 'Désactiver' : 'Activer' }}</button>
                <button class="btn btn--danger" type="button" aria-label="Supprimer" @click="remove(item)"><Trash2 /></button>
            </div>
        </article>
    </section>
</template>

<style scoped>
.form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.form label{display:grid;gap:6px;color:var(--muted);font-size:12px}.form label:nth-child(3){grid-column:1/-1}.form textarea{resize:vertical}.form button{justify-self:start}
.list{margin-top:20px}.item{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:18px 24px;border-top:1px solid var(--line)}.item h3{margin:8px 0;color:var(--ink);font-size:15px}.item p{max-width:70ch;color:var(--muted);font-size:12px;line-height:1.55}.actions{display:flex;gap:8px}
@media(max-width:700px){.form{grid-template-columns:1fr}.form label:nth-child(3){grid-column:auto}.item{padding:16px;flex-direction:column}}
</style>
