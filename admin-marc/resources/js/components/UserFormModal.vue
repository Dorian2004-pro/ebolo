<script setup>
import { reactive, ref, computed } from 'vue';
import { LoaderCircle } from '@lucide/vue';
import Modal from './Modal.vue';
import { api, errorMessage, fieldErrors } from '../lib/api';

const props = defineProps({ user: { type: Object, default: null } });
const emit = defineEmits(['close', 'saved']);

const editing = computed(() => !!props.user);
const f = reactive({
    first_name: props.user?.first_name ?? '',
    last_name: props.user?.last_name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    city: props.user?.city ?? '',
    password: '',
    role: 'customer',
});
const errors = ref({});
const general = ref('');
const busy = ref(false);

async function submit() {
    busy.value = true; errors.value = {}; general.value = '';
    try {
        const body = { ...f };
        if (editing.value) { delete body.role; }
        if (!body.password) delete body.password;
        if (editing.value) await api.put(`users/${props.user.id}`, body);
        else await api.post('users', body);
        emit('saved', editing.value ? 'Utilisateur mis à jour' : 'Utilisateur créé');
    } catch (e) {
        errors.value = fieldErrors(e);
        if (!Object.keys(errors.value).length) general.value = errorMessage(e);
    } finally { busy.value = false; }
}
</script>

<template>
    <Modal wide :busy="busy" @close="emit('close')">
        <template #title>{{ editing ? 'Modifier' : 'Ajouter' }} <em>{{ editing ? user.full_name : 'un utilisateur' }}</em></template>

        <form id="user-form" class="form-grid" novalidate @submit.prevent="submit">
            <div v-if="general" class="alert span-2">{{ general }}</div>

            <div class="field"><label for="fn">Prénom</label><input id="fn" v-model="f.first_name" class="input" :class="{ 'has-error': errors.first_name }" required /><span v-if="errors.first_name" class="err">{{ errors.first_name }}</span></div>
            <div class="field"><label for="ln">Nom</label><input id="ln" v-model="f.last_name" class="input" :class="{ 'has-error': errors.last_name }" required /><span v-if="errors.last_name" class="err">{{ errors.last_name }}</span></div>
            <div class="field span-2"><label for="em">Adresse e-mail</label><input id="em" v-model="f.email" type="email" class="input" :class="{ 'has-error': errors.email }" required /><span v-if="errors.email" class="err">{{ errors.email }}</span></div>
            <div class="field"><label for="ph">Téléphone</label><input id="ph" v-model="f.phone" class="input" placeholder="+237 6XX XX XX XX" :class="{ 'has-error': errors.phone }" required /><span v-if="errors.phone" class="err">{{ errors.phone }}</span></div>
            <div class="field"><label for="ci">Ville</label><input id="ci" v-model="f.city" class="input" placeholder="Douala" /></div>

            <div v-if="!editing" class="field">
                <label for="ro">Rôle</label>
                <select id="ro" v-model="f.role" class="select" style="width: 100%"><option value="customer">Cliente</option><option value="provider">Prestataire</option></select>
            </div>
            <div class="field" :class="{ 'span-2': editing }">
                <label for="pw">Mot de passe <span class="muted" style="font-weight: 400">(optionnel)</span></label>
                <input id="pw" v-model="f.password" type="password" class="input" autocomplete="new-password" :class="{ 'has-error': errors.password }" />
                <span v-if="errors.password" class="err">{{ errors.password }}</span>
                <span v-else class="hint">{{ editing ? 'Laisser vide pour conserver le mot de passe actuel.' : 'Les clientes se connectent par code SMS : laisser vide est possible.' }}</span>
            </div>
        </form>

        <template #footer>
            <button class="btn btn--ghost" type="button" :disabled="busy" @click="emit('close')">Annuler</button>
            <button class="btn btn--primary" type="submit" form="user-form" :disabled="busy"><LoaderCircle v-if="busy" class="spin" />{{ editing ? 'Enregistrer' : 'Créer le compte' }}</button>
        </template>
    </Modal>
</template>
