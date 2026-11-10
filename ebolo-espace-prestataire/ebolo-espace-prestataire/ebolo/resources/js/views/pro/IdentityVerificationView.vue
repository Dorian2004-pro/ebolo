<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useIdentityStore } from '@/stores/identity';
import { useToastStore } from '@/stores/toast';
import Icon from '@/components/ui/Icon.vue';
import IdPhotoCard from '@/components/pro/IdPhotoCard.vue';

const route = useRoute();
const router = useRouter();
const identity = useIdentityStore();
const toast = useToastStore();

const ID_TYPES = [
    { id: 'cni', title: "Carte Nationale d'Identité", desc: 'CNI biométrique ou récépissé officiel', icon: 'badge', sides: 2 },
    { id: 'passport', title: 'Passeport Camerounais', desc: 'Page biométrique centrale lisible', icon: 'menu_book', sides: 1 },
    { id: 'residence_permit', title: 'Carte de Séjour / Récépissé', desc: 'Ressortissants CEMAC ou résidents', icon: 'contact_page', sides: 2 },
];

const idType = ref('cni');
const files = reactive({ recto: null, verso: null });
const errors = reactive({ recto: '', verso: '' });

const current = computed(() => ID_TYPES.find((t) => t.id === idType.value));
const twoSides = computed(() => current.value.sides === 2);
const complete = computed(() => !!files.recto && (!twoSides.value || !!files.verso));

// Parcours d'inscription : « Étape 2 sur 3 » + barre de progression (?onboarding=1)
const inOnboarding = computed(() => route.query.onboarding === '1');

const sent = computed(() => ['pending', 'approved'].includes(identity.status));
const typeLabel = computed(() => ID_TYPES.find((t) => t.id === identity.idType)?.title);

onMounted(() => identity.load());

function setError(side, msg) {
    errors[side] = msg;
}
function setFile(side, file) {
    files[side] = file;
    errors[side] = '';
}
function pickType(id) {
    idType.value = id;
    if (current.value.sides === 1) { files.verso = null; errors.verso = ''; }
}

async function submit() {
    if (!files.recto) return toast.show(twoSides.value ? 'Ajoutez le recto de votre pièce' : 'Ajoutez la page biométrique de votre passeport');
    if (twoSides.value && !files.verso) return toast.show('Ajoutez le verso de votre pièce');
    try {
        await identity.submit({ idType: idType.value, recto: files.recto, verso: twoSides.value ? files.verso : null });
        window.scrollTo({ top: 0 });
    } catch (e) {
        toast.error(e.message);
    }
}

const back = () => (window.history.length > 1 ? router.back() : router.push({ name: 'pro.dashboard' }));
</script>

<template>
    <div class="verify">
        <header class="bar">
            <button type="button" class="icon-btn" aria-label="Retour" @click="back"><Icon name="arrow_back" /></button>
            <span class="title">
                <b>Vérification Prestataire</b>
                <span v-if="inOnboarding">Étape 2 sur 3</span>
            </span>
            <span class="icon-btn static"><Icon name="lock" /></span>
        </header>
        <span v-if="inOnboarding" class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="66"><span /></span>

        <div v-if="identity.loading" class="skeleton" aria-busy="true"><span class="sk" style="height:60px" /><span class="sk" style="height:220px" /></div>
        <section v-else-if="identity.error" class="load-error" role="alert">
            <p>{{ identity.error }}</p>
            <button type="button" @click="identity.load()">Réessayer</button>
        </section>

        <!-- Dossier transmis / validé -->
        <section v-else-if="sent" class="done" :class="identity.status">
            <span class="done-icon"><Icon :name="identity.status === 'approved' ? 'verified' : 'hourglass_top'" :size="36" /></span>
            <template v-if="identity.status === 'approved'">
                <h1>Identité vérifiée</h1>
                <p>Votre badge <b>Artisane Certifiée</b> est actif et vos encaissements MTN MoMo et Orange Money sont garantis.</p>
            </template>
            <template v-else>
                <h1>Dossier transmis</h1>
                <p>Notre équipe conformité à Douala valide votre identité sous 2h ouvrées. Vous recevrez le badge « Artisane Certifiée » par SMS.</p>
                <p v-if="typeLabel" class="meta">Pièce transmise : {{ typeLabel }}</p>
            </template>
            <RouterLink class="cta" :to="{ name: 'pro.dashboard' }">Accéder à mon espace Pro →</RouterLink>
        </section>

        <!-- Formulaire -->
        <form v-else class="form" novalidate @submit.prevent="submit">
            <span class="chip">AGRÉMENT EBOLO 2024</span>
            <h1>Vérifiez votre identité professionnelle</h1>
            <p class="lead">Activez vos encaissements garantis MTN MoMo et Orange Money, puis apposez le sceau <b>Artisane Certifiée</b>.</p>

            <div v-if="identity.status === 'rejected'" class="rejected" role="alert">
                <Icon name="error" :size="20" />
                <span><b>Dossier refusé.</b> {{ identity.rejectionReason || 'Les clichés ne sont pas lisibles. Merci de les renvoyer.' }}</span>
            </div>

            <h2 class="step">1. NATURE DU DOCUMENT</h2>
            <div class="types" role="radiogroup" aria-label="Nature du document">
                <button
                    v-for="t in ID_TYPES" :key="t.id" type="button" role="radio" class="type"
                    :class="{ selected: idType === t.id }" :aria-checked="idType === t.id" @click="pickType(t.id)"
                >
                    <span class="type-ico"><Icon :name="t.icon" /></span>
                    <span class="type-txt"><b>{{ t.title }}</b><span>{{ t.desc }}</span></span>
                    <span class="check"><Icon v-if="idType === t.id" name="check" :size="16" /></span>
                </button>
            </div>

            <h2 class="step">2. CLICHÉS HAUTE RÉSOLUTION</h2>
            <IdPhotoCard
                :model-value="files.recto" :error="errors.recto"
                :title="twoSides ? 'Recto (Face avant)' : 'Page biométrique'"
                :prompt="twoSides ? 'Scannez la face avant de votre pièce' : 'Scannez la page biométrique'"
                @update:model-value="setFile('recto', $event)" @invalid="setError('recto', $event)"
            />
            <IdPhotoCard
                v-if="twoSides"
                :model-value="files.verso" :error="errors.verso"
                title="Verso (Face arrière)" prompt="Scannez le dos de votre pièce"
                @update:model-value="setFile('verso', $event)" @invalid="setError('verso', $event)"
            />

            <button type="submit" class="submit" :class="{ incomplete: !complete }" :aria-disabled="!complete || identity.submitting" :disabled="identity.submitting">
                <Icon v-if="identity.submitting" name="progress_activity" :size="20" class="spin" />
                <template v-else>Soumettre pour validation →</template>
            </button>
            <p class="foot">Équipe de modération active à Douala &amp; Yaoundé • Réponse sous 2h</p>
        </form>
    </div>
</template>

<style scoped>
.verify { display: flex; flex-direction: column; gap: 14px; }
.load-error { padding: 18px; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); display: flex; flex-direction: column; align-items: center; gap: 12px; color: var(--danger); text-align: center; }
.load-error button { min-height: 40px; padding: 0 16px; border: 0; border-radius: 10px; background: var(--plum); color: #fff; }
.bar { display: flex; align-items: center; justify-content: space-between; }
.icon-btn { width: 40px; height: 40px; border: none; background: none; color: var(--plum); display: flex; align-items: center; justify-content: center; }
.icon-btn.static { pointer-events: none; }
.title { display: flex; flex-direction: column; align-items: center; }
.title b { font: 700 16px var(--font-head); }
.title span { font-size: 11px; color: var(--muted); }
.progress { height: 4px; border-radius: 9px; background: var(--line); overflow: hidden; display: block; }
.progress span { display: block; width: 66%; height: 100%; background: var(--plum); }

.form { display: flex; flex-direction: column; gap: 14px; }
.chip { align-self: flex-start; padding: 4px 10px; border-radius: 99px; background: var(--blush-2); font: 600 11px var(--font-body); letter-spacing: .1em; color: var(--text-2); }
h1 { font: 700 25px/31px var(--font-head); color: var(--plum); }
.lead { font-size: 14px; line-height: 21px; color: var(--text-2); }
.step { font: 600 12px var(--font-body); letter-spacing: .1em; color: var(--plum); margin-top: 4px; }

.rejected { display: flex; gap: 10px; padding: 12px 14px; border-radius: 14px; background: var(--danger-bg); color: var(--danger); font-size: 13px; line-height: 19px; }

.types { display: flex; flex-direction: column; gap: 14px; }
.type {
    display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 16px; text-align: left;
    background: var(--surface); border: 1px solid var(--line);
}
.type.selected { border: 1.5px solid var(--plum); padding: 13.5px; }
.type-ico { width: 40px; height: 40px; border-radius: 10px; background: #f6ebef; color: var(--plum-2); display: flex; align-items: center; justify-content: center; flex: none; }
.type-txt { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.type-txt b { font: 600 14px var(--font-body); color: var(--text); }
.type-txt span { font-size: 12px; color: var(--muted); }
.check { width: 24px; height: 24px; border-radius: 99px; background: var(--blush-2); color: #fff; display: flex; align-items: center; justify-content: center; flex: none; }
.type.selected .check { background: var(--plum); }

.submit {
    height: 56px; border: none; border-radius: 16px; background: var(--plum); color: #fff;
    font: 700 16px var(--font-head); display: flex; align-items: center; justify-content: center;
}
.submit.incomplete { opacity: .5; }
.submit:disabled { cursor: progress; }
.foot { text-align: center; font-size: 11px; color: var(--muted); }
.spin { animation: eb-spin .9s linear infinite; }

.done { padding: 28px 20px; border-radius: 22px; background: var(--surface); border: 1px solid var(--line); display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; }
.done-icon { width: 70px; height: 70px; border-radius: 99px; background: var(--amber-bg); color: var(--amber); display: flex; align-items: center; justify-content: center; }
.done.approved .done-icon { background: var(--green-bg); color: var(--green); }
.done h1 { font: 700 21px var(--font-head); }
.done p { font-size: 14px; line-height: 21px; color: var(--text-2); }
.done .meta { font-size: 12px; color: var(--muted); }
.cta { margin-top: 8px; height: 52px; width: 100%; border-radius: 14px; background: var(--plum); color: #fff; font: 700 15px var(--font-head); display: flex; align-items: center; justify-content: center; }

.skeleton { display: flex; flex-direction: column; gap: 14px; }
.sk { display: block; border-radius: 18px; background: var(--blush-2); animation: eb-pulse 1.4s ease-in-out infinite; }
</style>
