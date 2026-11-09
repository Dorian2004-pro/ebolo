<script setup>
import { computed, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { errorMessage } from '@/services/http';
import * as api from '@/services/api';
import Icon from '@/components/ui/Icon.vue';
import logo from '@img/logo.png';

const route = useRoute();
const router = useRouter();
const isRegister = computed(() => route.name === 'pro.register');
const loading = ref(false);
const error = ref('');
const form = ref({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

async function submit() {
    loading.value = true;
    error.value = '';
    try {
        const response = isRegister.value
            ? await api.register({
                ...form.value,
                role_id: Number(import.meta.env.VITE_ARTISAN_ROLE_ID || 2),
            })
            : await api.login({ email: form.value.email, password: form.value.password });

        localStorage.setItem('ebolo_token', response.token);
        const user = await api.getCurrentUser();
        if (user.role?.slug !== 'artisan') {
            await api.logout();
            localStorage.removeItem('ebolo_token');
            throw new Error('Cet espace est réservé aux comptes prestataires.');
        }

        await router.replace({ name: 'pro.dashboard' });
    } catch (e) {
        error.value = errorMessage(e, e.message || 'Connexion impossible. Réessayez.');
        if (e.message === 'Cet espace est réservé aux comptes prestataires.') error.value = e.message;
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <main class="auth">
        <div class="brand">
            <img :src="logo" alt="" width="44" height="44">
            <span><b>Ébolo</b><small>ESPACE PRESTATAIRE</small></span>
        </div>
        <section class="panel">
            <span class="chip">VOTRE ESPACE PROFESSIONNEL</span>
            <h1>{{ isRegister ? 'Rejoignez Ébolo' : 'Ravie de vous revoir' }}</h1>
            <p class="lead">{{ isRegister ? 'Créez votre compte prestataire pour présenter vos services et gérer vos rendez-vous.' : 'Connectez-vous pour retrouver votre activité et vos rendez-vous.' }}</p>

            <form @submit.prevent="submit">
                <label v-if="isRegister">Nom complet<input v-model.trim="form.name" autocomplete="name" required maxlength="255"></label>
                <label>Adresse e-mail<input v-model.trim="form.email" type="email" autocomplete="email" required></label>
                <label v-if="isRegister">Téléphone<input v-model.trim="form.phone" type="tel" autocomplete="tel"></label>
                <label>Mot de passe<input v-model="form.password" type="password" :autocomplete="isRegister ? 'new-password' : 'current-password'" minlength="8" required></label>
                <label v-if="isRegister">Confirmer le mot de passe<input v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="8" required></label>
                <p v-if="error" class="error" role="alert">{{ error }}</p>
                <button class="submit" type="submit" :disabled="loading">
                    <Icon v-if="loading" name="progress_activity" class="spin" />
                    <span v-else>{{ isRegister ? 'Créer mon compte' : 'Se connecter' }}</span>
                </button>
            </form>

            <p class="switch">
                {{ isRegister ? 'Vous avez déjà un compte ?' : 'Vous n’avez pas encore de compte ?' }}
                <RouterLink :to="{ name: isRegister ? 'pro.login' : 'pro.register' }">
                    {{ isRegister ? 'Se connecter' : 'Créer un compte' }}
                </RouterLink>
            </p>
        </section>
        <p class="secure"><Icon name="lock" :size="16" /> Vos informations restent protégées.</p>
    </main>
</template>

<style scoped>
.auth { width: min(100%, var(--app-width)); min-height: 100dvh; margin: auto; padding: 28px 20px; display: flex; flex-direction: column; justify-content: center; gap: 24px; background: var(--bg); }
.brand { display: flex; align-items: center; gap: 10px; }
.brand img { border-radius: 12px; }
.brand span { display: flex; flex-direction: column; }
.brand b { font: 800 20px var(--font-head); color: var(--plum); }
.brand small { font-size: 9px; letter-spacing: .14em; color: var(--rust); }
.panel { padding: 22px; border: 1px solid var(--line); border-radius: 22px; background: var(--surface); display: flex; flex-direction: column; gap: 14px; }
.chip { align-self: flex-start; padding: 5px 9px; border-radius: 99px; background: var(--blush-2); color: var(--rust); font-size: 10px; letter-spacing: .1em; }
h1 { color: var(--plum); font: 700 25px/1.2 var(--font-head); }
.lead { color: var(--text-2); line-height: 1.5; }
form { display: flex; flex-direction: column; gap: 13px; margin-top: 6px; }
label { display: flex; flex-direction: column; gap: 6px; color: var(--text-2); font-size: 12px; font-weight: 600; }
input { width: 100%; height: 46px; padding: 0 12px; border: 1px solid var(--line); border-radius: 12px; background: var(--bg); color: var(--text); font-size: 14px; }
.submit { height: 48px; display: flex; align-items: center; justify-content: center; gap: 8px; border: 0; border-radius: 13px; background: var(--plum); color: #fff; font: 700 14px var(--font-head); }
.submit:disabled { opacity: .65; }
.error { color: var(--danger); font-size: 12px; }
.switch, .secure { text-align: center; color: var(--muted); font-size: 12px; }
.switch a { margin-left: 4px; font-weight: 700; }
.secure { display: flex; align-items: center; justify-content: center; gap: 6px; }
.spin { animation: eb-spin .9s linear infinite; }
</style>
