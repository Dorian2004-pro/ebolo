<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { useProStore } from '@/stores/pro';
import { useToastStore } from '@/stores/toast';
import { errorMessage } from '@/services/http';

const pro = useProStore();
const toast = useToastStore();
const saving = ref(false);
const error = ref('');
const form = reactive({
    name: '',
    phone: '',
    city: '',
    specialty: '',
    bio: '',
    experience_years: 0,
    location: '',
});

onMounted(async () => {
    if (!pro.loaded) await pro.load();
    fillForm();
});

watch(() => pro.profile, fillForm, { deep: true });

function fillForm() {
    Object.assign(form, {
        name: pro.profile?.name ?? '',
        phone: pro.profile?.phone ?? '',
        city: pro.profile?.city ?? '',
        specialty: pro.profile?.specialty ?? '',
        bio: pro.profile?.bio ?? '',
        experience_years: pro.profile?.experience_years ?? 0,
        location: pro.profile?.location ?? '',
    });
}

async function save() {
    saving.value = true;
    error.value = '';
    try {
        await pro.updateProfile({ ...form, experience_years: Number(form.experience_years) });
        toast.show('Votre profil professionnel est enregistré.');
    } catch (exception) {
        error.value = errorMessage(exception, 'Impossible d’enregistrer le profil.');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="page">
        <header><span class="eyebrow">VOTRE IDENTITÉ PROFESSIONNELLE</span><h1>Mon profil</h1><p>Présentez votre activité aux clientes Ébolo.</p></header>
        <p v-if="pro.error && !pro.loaded" class="error" role="alert">{{ pro.error }}</p>
        <form v-else class="form" @submit.prevent="save">
            <label>Nom complet<input v-model.trim="form.name" required maxlength="255"></label>
            <div class="row">
                <label>Téléphone<input v-model.trim="form.phone" type="tel" maxlength="20"></label>
                <label>Ville<input v-model.trim="form.city" maxlength="255" placeholder="Douala"></label>
            </div>
            <label>Nom de l’atelier ou spécialité<input v-model.trim="form.specialty" required maxlength="255" placeholder="Coiffure afro & soins"></label>
            <label>Adresse / lieu d’activité<input v-model.trim="form.location" maxlength="255" placeholder="Bonapriso, Douala"></label>
            <label>Expérience (années)<input v-model.number="form.experience_years" type="number" min="0" max="60"></label>
            <label>Présentation<textarea v-model.trim="form.bio" rows="4" maxlength="2000" placeholder="Décrivez votre savoir-faire et votre accueil."></textarea></label>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
            <button class="primary" type="submit" :disabled="saving">{{ saving ? 'Enregistrement…' : 'Enregistrer mon profil' }}</button>
        </form>

        <section class="verification">
            <div><h2>Vérification d’identité</h2><p>Le badge certifié est nécessaire pour rendre vos prestations visibles dans le catalogue client.</p></div>
            <span class="status" :class="pro.profile?.status">{{ pro.profile?.status || 'À compléter' }}</span>
            <RouterLink class="secondary" :to="{ name: 'pro.verification' }">Gérer mon dossier →</RouterLink>
        </section>
    </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 16px; }
header { display: flex; flex-direction: column; gap: 4px; }
.eyebrow { color: var(--rust); font-size: 10px; letter-spacing: .14em; }
h1 { color: var(--plum); font: 700 25px var(--font-head); }
header p, .verification p { color: var(--text-2); font-size: 13px; line-height: 1.5; }
.form, .verification { padding: 16px; border: 1px solid var(--line); border-radius: 18px; background: var(--surface); display: flex; flex-direction: column; gap: 12px; }
label { display: flex; flex-direction: column; gap: 6px; color: var(--text-2); font-size: 12px; font-weight: 600; }
input, textarea { width: 100%; padding: 10px 11px; border: 1px solid var(--line); border-radius: 10px; background: var(--bg); color: var(--text); font: 400 14px var(--font-body); }
textarea { resize: vertical; }
.row { display: flex; gap: 10px; }
.row label { min-width: 0; flex: 1; }
.primary, .secondary { min-height: 42px; padding: 0 14px; border: 0; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; background: var(--plum); color: #fff; font-size: 12px; font-weight: 600; }
.primary:disabled { opacity: .6; }
.verification { gap: 8px; }
.verification h2 { color: var(--plum); font: 700 17px var(--font-head); }
.status { align-self: flex-start; padding: 5px 9px; border-radius: 99px; background: var(--amber-bg); color: var(--amber); font-size: 11px; text-transform: capitalize; }
.status.approved { background: var(--green-bg); color: var(--green); }
.secondary { align-self: flex-start; background: var(--blush-2); color: var(--plum); }
.error { color: var(--danger); font-size: 12px; }
</style>
