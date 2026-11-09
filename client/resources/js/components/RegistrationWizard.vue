<script setup>
import { computed, reactive, ref } from 'vue';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const step = ref('welcome');
const selectedRole = ref('client');
const selectedInterests = ref([]);
const busy = ref(false);
const error = ref('');
const fieldErrors = ref({});
const form = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    accepted_terms: false,
});

const roles = [
    {
        id: 'client',
        title: 'Cliente & Membre Privilège',
        description: 'Découvrez des créatrices, coiffeuses et soins d’exception.',
        detail: 'Rendez-vous réservés et paiement protégé',
        icon: '✦',
    },
    {
        id: 'artisan',
        title: 'Artisane indépendante',
        description: 'Présentez vos prestations et gérez votre activité.',
        detail: 'Espace professionnel Ébolo',
        icon: '✂',
    },
    {
        id: 'salon',
        title: 'Salon, atelier ou institut',
        description: 'Rejoignez Ébolo avec votre établissement et votre équipe.',
        detail: 'Compte professionnel Salon',
        icon: '⌂',
    },
];

const interests = [
    { id: 'tresses', title: 'Tresses & Braids', description: 'Knotless, Fulani et nattes d’art' },
    { id: 'perruques', title: 'Perruques & Lace', description: 'Wigs et pose HD' },
    { id: 'locks', title: 'Locks & Twists', description: 'Locks, départs et soins' },
    { id: 'maquillage', title: 'Maquillage & Glow', description: 'Maquillage et mise en beauté' },
    { id: 'manucure', title: 'Manucure Prestige', description: 'Nail art et soins des mains' },
    { id: 'soins', title: 'Soins & Spa Afro', description: 'Hydratation et rituels bien-être' },
    { id: 'couture', title: 'Couture & Pagne', description: 'Créations sur mesure et wax' },
    { id: 'tailoring', title: 'Tailoring & Vestiaire', description: 'Coupes modernes et vestes' },
    { id: 'retouches', title: 'Retouches d’atelier', description: 'Ourlets et finitions' },
];

const stepNumber = computed(() => ({ role: 1, details: 2, interests: 3 }[step.value] ?? 0));
const stepTitle = computed(() => ({
    role: 'Choisissez votre expérience Ébolo',
    details: 'Vos coordonnées d’exception',
    interests: 'Vos centres d’intérêt',
}[step.value] ?? 'Bienvenue chez Ébolo'));

function chooseRole(role) {
    selectedRole.value = role;
    error.value = '';
}

function toggleInterest(interest) {
    selectedInterests.value = selectedInterests.value.includes(interest)
        ? selectedInterests.value.filter((item) => item !== interest)
        : [...selectedInterests.value, interest];
}

function back() {
    error.value = '';
    step.value = step.value === 'interests' ? 'details' : step.value === 'details' ? 'role' : 'welcome';
}

function continueToDetails() {
    error.value = '';
    step.value = 'details';
}

function continueToInterests() {
    error.value = '';
    fieldErrors.value = {};
    if (form.password !== form.password_confirmation) {
        fieldErrors.value.password_confirmation = ['Les mots de passe ne correspondent pas.'];
        return;
    }
    if (!form.accepted_terms) {
        error.value = 'Veuillez accepter les conditions et la politique de confidentialité.';
        return;
    }
    step.value = 'interests';
}

async function submitRegistration(skipInterests = false) {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    fieldErrors.value = {};

    try {
        const response = await fetch('/client-api/inscription', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                account_type: selectedRole.value,
                name: form.name,
                email: form.email,
                phone: normalizePhone(form.phone),
                password: form.password,
                password_confirmation: form.password_confirmation,
                accepted_terms: form.accepted_terms,
                interests: skipInterests ? [] : selectedInterests.value,
            }),
        });
        const body = await response.json();
        if (!response.ok) {
            fieldErrors.value = body.errors ?? {};
            error.value = body.message || 'La création du compte a échoué.';
            return;
        }
        window.location.assign(body.redirect);
    } catch {
        error.value = 'Impossible de joindre le serveur. Réessayez dans quelques instants.';
    } finally {
        busy.value = false;
    }
}

function normalizePhone(phone) {
    const digits = phone.replace(/\D/g, '');
    if (!digits) return '';
    const nationalNumber = digits.startsWith('237') ? digits.slice(3) : digits.replace(/^0/, '');
    return `+237${nationalNumber}`;
}
</script>

<template>
    <main class="registration-page">
        <header class="registration-header">
            <a class="brand" href="/"><span class="brand-mark">É</span> ébolo<span class="brand-dot">.</span></a>
            <a class="quiet-link" href="/connexion">Déjà membre ? <strong>Se connecter</strong></a>
        </header>

        <section v-if="step === 'welcome'" class="registration-welcome">
            <div class="registration-visual" aria-hidden="true">
                <span class="visual-orbit orbit-one"></span>
                <span class="visual-orbit orbit-two"></span>
                <span class="visual-sun"></span>
                <span class="visual-caption">LA BEAUTÉ D’ICI<br><em>tout simplement.</em></span>
                <span class="visual-stamp">DOUALA<br>·<br>YAOUNDÉ</span>
            </div>
            <div class="registration-welcome-copy">
                <p class="registration-eyebrow">ÉBOLO PRESTIGE · BEAUTÉ & BIEN-ÊTRE</p>
                <h1>L’art de la beauté africaine, <em>sublimé.</em></h1>
                <p>Découvrez les artisanes et maisons d’exception de Douala et Yaoundé. Choisissez votre expérience pour commencer.</p>
                <button class="registration-primary" type="button" @click="step = 'role'">
                    Commencer l’expérience <span aria-hidden="true">→</span>
                </button>
                <a class="registration-login" href="/connexion">J’ai déjà un compte</a>
                <div class="registration-trust">
                    <span>✦ Prestataires vérifiés</span>
                    <span>✦ Rendez-vous sécurisés</span>
                    <span>✦ Mobile Money</span>
                </div>
            </div>
        </section>

        <section v-else class="registration-flow" :aria-labelledby="'registration-title'">
            <div class="registration-topline">
                <button class="registration-back" type="button" aria-label="Retour" @click="back">←</button>
                <div class="registration-progress" aria-label="Progression de l’inscription">
                    <span v-for="number in 3" :key="number" :class="{ complete: number <= stepNumber }"></span>
                    <small>{{ stepNumber }}/3</small>
                </div>
                <span class="registration-prestige">PRESTIGE</span>
            </div>

            <div class="registration-intro">
                <p class="registration-eyebrow">{{ step === 'role' ? 'VOTRE ACCÈS PRIVILÉGIÉ' : step === 'details' ? 'CRÉATION DE COMPTE' : 'PERSONNALISATION SUR-MESURE' }}</p>
                <h1 id="registration-title">{{ stepTitle }}</h1>
                <p v-if="step === 'role'">Sélectionnez votre profil pour un accompagnement adapté à votre activité.</p>
                <p v-else-if="step === 'details'">Quelques informations pour créer votre compte Ébolo.</p>
                <p v-else>Sélectionnez au moins deux univers pour personnaliser votre expérience. Vous pourrez passer cette étape.</p>
            </div>

            <div v-if="step === 'role'" class="registration-roles">
                <button
                    v-for="role in roles"
                    :key="role.id"
                    class="registration-role"
                    :class="{ selected: selectedRole === role.id }"
                    type="button"
                    :aria-pressed="selectedRole === role.id"
                    @click="chooseRole(role.id)"
                >
                    <span class="registration-role-icon">{{ role.icon }}</span>
                    <span class="registration-role-copy">
                        <strong>{{ role.title }}</strong>
                        <span>{{ role.description }}</span>
                        <small>{{ role.detail }}</small>
                    </span>
                    <span class="registration-check" aria-hidden="true">{{ selectedRole === role.id ? '✓' : '' }}</span>
                </button>
                <button class="registration-primary" type="button" @click="continueToDetails">
                    Continuer <span aria-hidden="true">→</span>
                </button>
            </div>

            <form v-else-if="step === 'details'" class="registration-form" @submit.prevent="continueToInterests">
                <div class="registration-selected-role">
                    <span>{{ roles.find((role) => role.id === selectedRole)?.icon }}</span>
                    <div><small>Type de profil sélectionné</small><strong>{{ roles.find((role) => role.id === selectedRole)?.title }}</strong></div>
                    <button type="button" @click="step = 'role'">Modifier</button>
                </div>

                <label>Nom complet
                    <input v-model.trim="form.name" autocomplete="name" maxlength="255" placeholder="Ex. Vanessa Ebanda" required>
                    <small v-if="fieldErrors.name" class="registration-error">{{ fieldErrors.name[0] }}</small>
                </label>
                <label>Adresse e-mail
                    <input v-model.trim="form.email" type="email" autocomplete="email" maxlength="255" placeholder="nom@exemple.cm" required>
                    <small v-if="fieldErrors.email" class="registration-error">{{ fieldErrors.email[0] }}</small>
                </label>
                <label>Téléphone
                    <span class="registration-phone"><b>+237</b><input v-model.trim="form.phone" type="tel" autocomplete="tel" maxlength="20" placeholder="6XX XXX XXX"></span>
                    <small v-if="fieldErrors.phone" class="registration-error">{{ fieldErrors.phone[0] }}</small>
                </label>
                <label>Mot de passe
                    <input v-model="form.password" type="password" autocomplete="new-password" minlength="8" placeholder="8 caractères minimum" required>
                    <small v-if="fieldErrors.password" class="registration-error">{{ fieldErrors.password[0] }}</small>
                </label>
                <label>Confirmer le mot de passe
                    <input v-model="form.password_confirmation" type="password" autocomplete="new-password" minlength="8" placeholder="Saisissez-le à nouveau" required>
                    <small v-if="fieldErrors.password_confirmation" class="registration-error">{{ fieldErrors.password_confirmation[0] }}</small>
                </label>

                <label class="registration-consent">
                    <input v-model="form.accepted_terms" type="checkbox" required>
                    <span>J’accepte les conditions d’utilisation et la politique de confidentialité d’Ébolo.</span>
                </label>
                <p v-if="error" class="registration-error" role="alert">{{ error }}</p>
                <button class="registration-primary" type="submit">Continuer vers mes centres d’intérêt <span aria-hidden="true">→</span></button>
            </form>

            <div v-else class="registration-interest-step">
                <div class="registration-interests">
                    <button
                        v-for="interest in interests"
                        :key="interest.id"
                        class="registration-interest"
                        :class="[`interest-${interest.id}`, { selected: selectedInterests.includes(interest.id) }]"
                        type="button"
                        :aria-pressed="selectedInterests.includes(interest.id)"
                        @click="toggleInterest(interest.id)"
                    >
                        <span class="interest-check">{{ selectedInterests.includes(interest.id) ? '✓' : '+' }}</span>
                        <strong>{{ interest.title }}</strong>
                        <small>{{ interest.description }}</small>
                    </button>
                </div>
                <p class="registration-count">{{ selectedInterests.length }} univers sélectionné(s) <span>· minimum 2</span></p>
                <p v-if="error" class="registration-error" role="alert">{{ error }}</p>
                <button class="registration-primary" type="button" :disabled="selectedInterests.length < 2 || busy" @click="submitRegistration()">
                    {{ busy ? 'Création du compte…' : 'Entrer dans l’expérience Ébolo' }} <span aria-hidden="true">→</span>
                </button>
                <button class="registration-skip" type="button" :disabled="busy" @click="submitRegistration(true)">Passer cette étape pour le moment</button>
            </div>

            <p class="registration-privacy">✦ Vos informations restent confidentielles.</p>
        </section>
    </main>
</template>

<style scoped>
.registration-page{min-height:100vh;max-width:1440px;margin:auto;padding:0 6.5%;background:#fbfaf8;color:#302823}
.registration-header{height:82px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee8e2}
.brand{display:inline-flex;align-items:center;gap:8px;color:#4d2934;font:800 21px Manrope,sans-serif;letter-spacing:-.8px;text-decoration:none}
.brand-mark{display:grid;place-items:center;width:37px;height:37px;border-radius:11px;background:#e5b967;color:#542936;font:700 26px Georgia,serif}
.brand-dot{margin-left:-8px;color:#c59351}
.quiet-link{color:#8f847d;font-size:12px;text-decoration:none}.quiet-link strong{padding-left:4px;color:#592c39}
.registration-welcome{min-height:calc(100vh - 83px);display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:8%;padding:50px 0}
.registration-visual{position:relative;min-height:520px;overflow:hidden;border-radius:28px;background:linear-gradient(145deg,#43232f,#8c544e 54%,#e0b794)}
.visual-orbit{position:absolute;border:1px solid #ffffff50;border-radius:50%}.orbit-one{width:460px;height:460px;right:-60px;top:-95px}.orbit-two{width:330px;height:330px;right:5px;top:-30px}
.visual-sun{position:absolute;top:82px;right:25%;width:145px;height:145px;border-radius:50%;background:#e8c092;box-shadow:0 0 75px #f0c69d80}
.visual-caption{position:absolute;left:9%;bottom:11%;color:white;font:700 clamp(26px,4vw,48px)/1.1 Manrope,sans-serif;letter-spacing:-1px}.visual-caption em{color:#ffcf9f;font-family:Georgia,serif;font-weight:400}
.visual-stamp{position:absolute;right:10%;top:43%;display:grid;place-items:center;width:94px;height:94px;border:1px solid #fff9;border-radius:50%;color:#fff;font-size:10px;line-height:1.2;letter-spacing:1px;text-align:center;transform:rotate(10deg)}
.registration-welcome-copy{max-width:530px}.registration-eyebrow{margin:0 0 12px;color:#a27e57;font-size:10px;font-weight:700;letter-spacing:1.5px}
.registration-welcome-copy h1,.registration-intro h1{margin:0;color:#3a292c;font:700 clamp(34px,4.2vw,56px)/1.12 Manrope,sans-serif;letter-spacing:-1.6px}
.registration-welcome-copy h1 em{color:#a87961;font-family:Georgia,serif;font-weight:400}
.registration-welcome-copy>p:not(.registration-eyebrow){max-width:460px;margin:18px 0 26px;color:#82766e;font-size:15px;line-height:1.8}
.registration-primary{width:100%;min-height:52px;padding:0 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;border:0;border-radius:12px;background:#4a1d3f;color:#fff;font:700 15px Manrope,sans-serif;transition:opacity .2s,transform .2s}
.registration-primary:hover{transform:translateY(-1px)}.registration-primary:disabled{opacity:.5;cursor:not-allowed}
.registration-login{display:block;width:max-content;margin:19px auto;color:#592c39;font-size:13px;font-weight:700;text-decoration:underline;text-underline-offset:4px}
.registration-trust{display:flex;flex-wrap:wrap;gap:12px;margin-top:35px;color:#887b72;font-size:11px}
.registration-flow{width:min(100%,720px);margin:0 auto;padding:30px 0 48px}
.registration-topline{display:grid;grid-template-columns:44px 1fr 70px;align-items:center;gap:14px}
.registration-back{width:42px;height:42px;border:0;border-radius:50%;background:#f0e5e9;color:#310729;font-size:24px}
.registration-progress{display:flex;align-items:center;justify-content:center;gap:6px}.registration-progress span{width:20px;height:4px;border-radius:9px;background:#d3c2ca}.registration-progress span.complete{background:#310729}.registration-progress small{margin-left:5px;color:#4f444a;font-size:11px}
.registration-prestige{justify-self:end;padding:6px 9px;border-radius:6px;background:#ffddb5;color:#8a5a00;font-size:9px;font-weight:700;letter-spacing:1px}
.registration-intro{margin:30px 0 22px}.registration-intro h1{font-size:clamp(27px,4vw,36px)}.registration-intro>p:last-child{margin:10px 0 0;color:#71645e;font-size:14px;line-height:1.6}
.registration-roles{display:grid;gap:12px}.registration-role{display:flex;align-items:flex-start;gap:14px;width:100%;padding:18px;border:1px solid #eae0e4;border-radius:18px;background:#fff;text-align:left;transition:border-color .2s,box-shadow .2s}.registration-role.selected{border:2px solid #592c39;padding:17px;box-shadow:0 6px 18px #592c3910}
.registration-role-icon{display:grid;place-items:center;flex:none;width:46px;height:46px;border-radius:14px;background:#f8e9dc;color:#a13f1f;font-size:23px}
.registration-role-copy{display:grid;gap:5px;flex:1}.registration-role-copy strong{color:#302823;font:700 15px Manrope,sans-serif}.registration-role-copy>span{color:#71645e;font-size:12px;line-height:1.5}.registration-role-copy small{color:#a13f1f;font-size:11px}
.registration-check{display:grid;place-items:center;flex:none;width:24px;height:24px;border:1px solid #d3c2ca;border-radius:50%;color:white;font-size:13px}.selected .registration-check{border-color:#592c39;background:#592c39}
.registration-roles>.registration-primary{margin-top:5px}
.registration-form{display:grid;gap:14px}.registration-selected-role{display:flex;align-items:center;gap:12px;padding:13px;border:1px solid #eae0e4;border-radius:14px;background:#fff}.registration-selected-role>span{display:grid;place-items:center;width:38px;height:38px;border-radius:10px;background:#ffddb5;color:#643f00;font-size:20px}.registration-selected-role>div{display:grid;gap:3px;flex:1}.registration-selected-role small{color:#81737b;font-size:10px}.registration-selected-role strong{color:#302823;font-size:12px}.registration-selected-role button{border:0;border-bottom:1px solid #a13f1f;background:none;color:#a13f1f;font-size:12px}
.registration-form>label{display:grid;gap:6px;color:#554b46;font-size:12px;font-weight:700}.registration-form input:not([type=checkbox]){width:100%;height:46px;padding:0 13px;border:1px solid #e8e1dc;border-radius:9px;outline:0;background:#fff;color:#302823;font-size:13px}.registration-form input:focus{border-color:#9b6772;box-shadow:0 0 0 3px #7f475212}
.registration-phone{display:flex;align-items:center;gap:10px;padding-left:10px;border:1px solid #e8e1dc;border-radius:9px;background:#fff}.registration-phone b{padding:7px 9px;border-radius:7px;background:#f0e5e9;color:#592c39;font-size:12px}.registration-phone input:not([type=checkbox]){border:0;box-shadow:none}
.registration-consent{display:flex!important;grid-template-columns:20px 1fr;align-items:flex-start;gap:9px!important;color:#71645e!important;font-size:11px!important;font-weight:400!important;line-height:1.55}.registration-consent input{margin:1px 0 0;accent-color:#592c39}
.registration-error{color:#ae584e;font-size:11px;font-weight:500;line-height:1.5}
.registration-interests{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
.registration-interest{position:relative;min-height:128px;padding:14px;display:flex;flex-direction:column;justify-content:flex-end;align-items:flex-start;gap:5px;overflow:hidden;border:2px solid transparent;border-radius:15px;background:linear-gradient(145deg,#563644,#a86a5c);color:white;text-align:left}
.registration-interest:before{position:absolute;inset:0;content:"";background:linear-gradient(0deg,#26091fdd,transparent 90%)}
.registration-interest>*{position:relative}.registration-interest.selected{border-color:#e5b967;box-shadow:0 0 0 2px #e5b96755}
.interest-check{position:absolute;top:9px;right:9px;display:grid;place-items:center;width:23px;height:23px;border:1px solid #ffffffaa;border-radius:50%;background:#ffffff33;font-size:13px}
.registration-interest strong{font:700 13px Manrope,sans-serif}.registration-interest small{color:#f5e6e1;font-size:10px;line-height:1.4}
.interest-perruques,.interest-couture,.interest-retouches{background:linear-gradient(145deg,#69503d,#c18b60)}.interest-locks,.interest-soins,.interest-tailoring{background:linear-gradient(145deg,#324944,#749381)}.interest-maquillage,.interest-manucure{background:linear-gradient(145deg,#59334d,#bc7183)}
.registration-count{margin:15px 0;color:#302823;text-align:center;font-size:12px}.registration-count span{color:#a13f1f}
.registration-skip{width:100%;padding:13px;border:0;background:transparent;color:#71645e;font-size:12px}
.registration-privacy{margin:25px 0 0;color:#a19892;text-align:center;font-size:10px}
@media(max-width:760px){.registration-page{padding:0 20px}.registration-header{height:70px}.registration-welcome{min-height:calc(100vh - 70px);grid-template-columns:1fr;gap:24px;padding:25px 0 35px}.registration-visual{min-height:250px;order:2}.visual-caption{font-size:30px}.visual-sun{top:30px;width:100px;height:100px}.registration-welcome-copy{order:1}.registration-welcome-copy h1{font-size:38px}.registration-trust{margin-top:22px;font-size:10px}.registration-flow{padding-top:20px}.registration-intro{margin:23px 0 18px}.registration-interests{grid-template-columns:repeat(2,minmax(0,1fr))}.registration-interest{min-height:120px}}
@media(max-width:380px){.registration-page{padding:0 15px}.quiet-link{font-size:10px}.registration-role{gap:9px;padding:13px}.registration-role.selected{padding:12px}.registration-role-icon{width:38px;height:38px}.registration-role-copy strong{font-size:13px}.registration-interest{min-height:112px;padding:10px}.registration-interest strong{font-size:12px}}
</style>
