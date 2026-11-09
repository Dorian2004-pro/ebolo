<script setup>
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Eye, EyeOff, LoaderCircle, Lock, ShieldCheck, Wallet, BadgeCheck } from '@lucide/vue';
import BrandLogo from '../components/BrandLogo.vue';
import { auth } from '../lib/auth';
import { errorMessage } from '../lib/api';

const router = useRouter();
const route = useRoute();

const email = ref('');
const password = ref('');
const show = ref(false);
const loading = ref(false);
const error = ref('');

async function submit() {
    error.value = '';
    loading.value = true;
    try {
        await auth.login(email.value, password.value);
        const target = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') ? route.query.redirect : '/dashboard';
        router.replace(target);
    } catch (e) {
        error.value = e?.response?.status === 429
            ? 'Trop de tentatives. Réessayez dans une minute.'
            : errorMessage(e, 'Identifiants invalides');
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="login">
        <section class="login__art" aria-hidden="true">
            <div style="position: relative; z-index: 1">
                <p class="login__tag">Douala <span>•</span> Yaoundé</p>
                <h2 class="login__title">Le centre de contrôle des <em>maisons d'art</em> Ébolo.</h2>
                <p class="login__lead">Validez les artisanes, suivez les réservations et gardez un œil sur les fonds consignés en séquestre Mobile Money.</p>
            </div>
            <div class="login__feats">
                <div class="login__feat"><span><BadgeCheck /></span>Vérification d'identité des prestataires</div>
                <div class="login__feat"><span><Wallet /></span>Séquestre MTN MoMo &amp; Orange Money</div>
                <div class="login__feat"><span><ShieldCheck /></span>Litiges et journal d'audit</div>
            </div>
        </section>

        <section class="login__panel">
            <div class="login__card">
                <div class="login__brand">
                    <BrandLogo size="lg" />
                    <h1>EBOLO Admin</h1>
                    <p>Connectez-vous à votre compte administrateur</p>
                </div>

                <form class="card card__body" style="display: grid; gap: 18px; box-shadow: var(--shadow)" novalidate @submit.prevent="submit">
                    <div v-if="error" class="alert" role="alert">{{ error }}</div>

                    <div class="field">
                        <label for="email">Adresse e-mail</label>
                        <input id="email" v-model="email" class="input" type="email" autocomplete="username" placeholder="admin@ebolo.com" required autofocus />
                    </div>

                    <div class="field">
                        <label for="password">Mot de passe</label>
                        <div class="pw">
                            <input id="password" v-model="password" class="input" :type="show ? 'text' : 'password'" autocomplete="current-password" placeholder="Entrez votre mot de passe" required />
                            <button type="button" :aria-label="show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'" @click="show = !show">
                                <EyeOff v-if="show" /><Eye v-else />
                            </button>
                        </div>
                    </div>

                    <button class="btn btn--primary btn--block" type="submit" :disabled="loading || !email || !password">
                        <LoaderCircle v-if="loading" class="spin" />
                        <Lock v-else />
                        {{ loading ? 'Connexion en cours…' : 'Se connecter' }}
                    </button>
                </form>

                <p class="login__foot">© {{ new Date().getFullYear() }} EBOLO. Tous droits réservés.</p>
            </div>
        </section>
    </div>
</template>
