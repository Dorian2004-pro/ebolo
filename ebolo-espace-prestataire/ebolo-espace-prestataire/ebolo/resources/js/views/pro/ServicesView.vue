<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import * as api from '@/services/api';
import { errorMessage } from '@/services/http';
import { formatFCFA } from '@/composables/format';
import Icon from '@/components/ui/Icon.vue';
import { useToastStore } from '@/stores/toast';

const toast = useToastStore();
const services = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const editingId = ref(null);
const form = reactive({ name: '', description: '', category: '', duration_minutes: 60, price: '' });
const title = computed(() => editingId.value ? 'Modifier la prestation' : 'Ajouter une prestation');

async function load() {
    loading.value = true;
    error.value = '';
    try {
        services.value = await api.getOwnServices();
    } catch (e) {
        error.value = errorMessage(e, 'Impossible de charger vos prestations.');
    } finally {
        loading.value = false;
    }
}

function resetForm() {
    editingId.value = null;
    Object.assign(form, { name: '', description: '', category: '', duration_minutes: 60, price: '' });
}

function edit(service) {
    editingId.value = service.id;
    Object.assign(form, {
        name: service.name,
        description: service.description ?? '',
        category: service.category ?? '',
        duration_minutes: service.duration_minutes,
        price: service.price,
    });
    document.querySelector('#service-name')?.focus();
}

async function save() {
    saving.value = true;
    error.value = '';
    try {
        const payload = {
            ...form,
            duration_minutes: Number(form.duration_minutes),
            price: Number(form.price),
        };
        if (editingId.value) await api.updateService(editingId.value, payload);
        else await api.createService(payload);
        await load();
        resetForm();
        toast.show('Prestation enregistrée.');
    } catch (e) {
        error.value = errorMessage(e, 'Enregistrement impossible.');
    } finally {
        saving.value = false;
    }
}

async function remove(service) {
    if (!window.confirm(`Supprimer « ${service.name} » ?`)) return;
    try {
        await api.deleteService(service.id);
        services.value = services.value.filter((item) => item.id !== service.id);
        toast.show('Prestation supprimée.');
    } catch (e) {
        error.value = errorMessage(e, 'Suppression impossible.');
    }
}

onMounted(load);
</script>

<template>
    <div class="page">
        <header><span class="eyebrow">MON ACTIVITÉ</span><h1>Mes prestations</h1><p>Présentez les services proposés à vos clientes.</p></header>

        <form class="form" @submit.prevent="save">
            <div class="form-title"><h2>{{ title }}</h2><button v-if="editingId" type="button" class="link" @click="resetForm">Annuler</button></div>
            <label>Nom de la prestation<input id="service-name" v-model.trim="form.name" required maxlength="255" placeholder="Ex. Knotless Braids"></label>
            <label>Catégorie<input v-model.trim="form.category" maxlength="100" placeholder="Coiffure, soin…"></label>
            <label>Description<textarea v-model.trim="form.description" rows="3" maxlength="2000" placeholder="Décrivez votre prestation"></textarea></label>
            <div class="row">
                <label>Durée (minutes)<input v-model.number="form.duration_minutes" type="number" min="15" step="15" required></label>
                <label>Prix (FCFA)<input v-model.number="form.price" type="number" min="0" step="100" required></label>
            </div>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
            <button class="primary" type="submit" :disabled="saving"><Icon v-if="saving" name="progress_activity" class="spin" /><span v-else>{{ editingId ? 'Enregistrer' : 'Ajouter la prestation' }}</span></button>
        </form>

        <section class="list">
            <div class="list-head"><h2>Mon catalogue</h2><span class="count">{{ services.length }}</span></div>
            <p v-if="loading" class="notice">Chargement…</p>
            <p v-else-if="!services.length" class="notice">Aucune prestation pour le moment. Ajoutez votre premier service ci-dessus.</p>
            <article v-for="service in services" :key="service.id" class="service">
                <div class="service-top"><span class="service-icon"><Icon name="content_cut" /></span><span class="details"><b>{{ service.name }}</b><small>{{ service.category || 'Prestation' }} · {{ service.duration_minutes }} min</small></span><b class="price">{{ formatFCFA(service.price) }}</b></div>
                <p v-if="service.description" class="description">{{ service.description }}</p>
                <div class="actions"><button type="button" class="secondary" @click="edit(service)">Modifier</button><button type="button" class="danger" @click="remove(service)">Supprimer</button></div>
            </article>
        </section>
    </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 18px; }
header { display: flex; flex-direction: column; gap: 4px; }
.eyebrow { color: var(--rust); font-size: 10px; letter-spacing: .14em; }
h1 { color: var(--plum); font: 700 25px var(--font-head); }
header p, .description { color: var(--text-2); line-height: 1.5; font-size: 13px; }
.form, .service { padding: 16px; border: 1px solid var(--line); border-radius: 18px; background: var(--surface); display: flex; flex-direction: column; gap: 12px; }
.form-title, .list-head, .service-top, .actions, .row { display: flex; align-items: center; gap: 10px; }
.form-title, .list-head { justify-content: space-between; }
h2 { color: var(--plum); font: 700 17px var(--font-head); }
label { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 6px; color: var(--text-2); font-size: 12px; font-weight: 600; }
input, textarea { width: 100%; padding: 10px 11px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); color: var(--text); font: 400 14px var(--font-body); }
textarea { resize: vertical; }
.primary, .secondary, .danger { min-height: 42px; padding: 0 14px; border: 0; border-radius: 11px; font: 600 12px var(--font-body); }
.primary { display: flex; justify-content: center; align-items: center; gap: 8px; background: var(--plum); color: #fff; }
.primary:disabled { opacity: .65; }
.link { border: 0; background: none; color: var(--rust); }
.list { display: flex; flex-direction: column; gap: 10px; }
.count { min-width: 24px; padding: 3px 8px; border-radius: 99px; background: var(--blush-2); color: var(--rust); text-align: center; font-size: 11px; }
.notice { padding: 16px; border: 1px dashed var(--line); border-radius: 14px; color: var(--muted); font-size: 13px; text-align: center; }
.service { gap: 9px; }
.service-top { min-width: 0; }
.service-icon { width: 38px; height: 38px; flex: none; display: grid; place-items: center; border-radius: 11px; background: var(--blush-2); color: var(--plum); }
.details { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 3px; }
.details b { overflow-wrap: anywhere; color: var(--text); font: 700 14px var(--font-head); }
.details small { color: var(--muted); font-size: 11px; }
.price { color: var(--rust); font-size: 12px; white-space: nowrap; }
.description { font-size: 12px; }
.actions { justify-content: flex-end; }
.secondary { background: var(--blush-2); color: var(--plum); }
.danger { background: var(--danger-bg); color: var(--danger); }
.error { color: var(--danger); font-size: 12px; }
.spin { animation: eb-spin .9s linear infinite; }
</style>
