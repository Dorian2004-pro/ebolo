<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useProStore } from '@/stores/pro';
import { useToastStore } from '@/stores/toast';
import { errorMessage } from '@/services/http';
import { formatAmount, formatFCFA } from '@/composables/format';
import logo from '@img/logo.png';
import Avatar from '@/components/ui/Avatar.vue';
import EscrowCard from '@/components/pro/EscrowCard.vue';
import BookingCard from '@/components/pro/BookingCard.vue';
import RequestCard from '@/components/pro/RequestCard.vue';

const pro = useProStore();
const toast = useToastStore();

onMounted(() => { if (!pro.loaded) pro.load(); });

const monthLabel = computed(() =>
    pro.now.toLocaleDateString('fr-FR', { month: 'long' }).toUpperCase());

const delta = computed(() => {
    const d = pro.revenueDelta;
    if (d === null) return null;
    return { text: `${d >= 0 ? '↗ +' : '↘ '}${d}%`, up: d >= 0 };
});

const nextToday = computed(() => pro.todayBookings[0] ?? null);
const restToday = computed(() => pro.todayBookings.slice(1));

async function release(id, code) {
    try {
        await pro.release(id, code);
    } catch (e) {
        throw new Error(errorMessage(e, 'Code incorrect. Vérifiez auprès de votre cliente.'));
    }
}

async function answer(booking, status) {
    try {
        await pro.respond(booking.id, status);
        toast.show(status === 'confirmed' ? `Rendez-vous confirmé avec ${booking.client?.name}` : 'Demande refusée');
    } catch (e) {
        toast.error(errorMessage(e, 'Action impossible pour le moment.'));
    }
}
</script>

<template>
    <div class="dash">
        <header class="top">
            <span class="brand">
                <img :src="logo" alt="" width="34" height="34">
                <b>Ebolo</b>
                <span class="pro-tag">PRO</span>
            </span>
            <RouterLink :to="{ name: 'pro.verification' }" aria-label="Mon profil professionnel">
                <Avatar :src="pro.profile?.avatar_url" :name="pro.profile?.name ?? ''" :size="36" />
            </RouterLink>
        </header>

        <!-- Chargement initial -->
        <div v-if="pro.loading && !pro.loaded" class="skeleton" aria-busy="true" aria-label="Chargement">
            <span class="sk" style="height:52px" /><span class="sk" style="height:84px" /><span class="sk" style="height:200px" /><span class="sk" style="height:120px" />
        </div>

        <!-- Erreur -->
        <div v-else-if="pro.error && !pro.loaded" class="state" role="alert">
            <p>{{ pro.error }}</p>
            <button type="button" class="retry" @click="pro.load()">Réessayer</button>
        </div>

        <template v-else-if="pro.loaded">
            <section class="hello">
                <div>
                    <h1>Bonjour {{ pro.firstName }}</h1>
                    <p>{{ pro.subtitle }}</p>
                </div>
                <span v-if="pro.isOpen !== null" class="open" :class="{ closed: !pro.isOpen }">
                    <span class="dot">●</span> {{ pro.isOpen ? 'Ouvert' : 'Fermé' }}
                </span>
            </section>

            <section class="stats" aria-label="Indicateurs du mois">
                <div class="stat">
                    <span class="k">REVENUS DE {{ monthLabel }}</span>
                    <b>{{ formatAmount(pro.revenueMonth) }} <small>FCFA</small></b>
                    <span v-if="delta" class="d" :class="{ down: !delta.up }">{{ delta.text }}</span>
                </div>
                <div class="stat">
                    <span class="k">RENDEZ-VOUS</span>
                    <b>{{ pro.confirmedThisMonth }} <small>confirmés</small></b>
                    <span class="d muted">{{ pro.pendingRequests.length ? `${pro.pendingRequests.length} en attente` : 'Aucune demande en attente' }}</span>
                </div>
            </section>

            <EscrowCard v-if="pro.escrowBooking" :booking="pro.escrowBooking" :busy="pro.busy.has(pro.escrowBooking.id)" :release="release" />

            <section v-if="pro.pendingRequests.length" class="block" aria-labelledby="req-title">
                <div class="block-head">
                    <h2 id="req-title">Demandes à confirmer</h2>
                    <span class="count">{{ pro.pendingRequests.length }}</span>
                </div>
                <RequestCard
                    v-for="b in pro.pendingRequests" :key="b.id" :booking="b" :busy="pro.busy.has(b.id)"
                    @accept="answer($event, 'confirmed')" @reject="answer($event, 'rejected')"
                />
            </section>

            <section class="block" aria-labelledby="plan-title">
                <div class="block-head">
                    <h2 id="plan-title">Planning d'aujourd'hui</h2>
                    <span class="count">{{ pro.todayBookings.length }} RDV</span>
                </div>
                <BookingCard v-if="nextToday" :booking="nextToday" :now="pro.now" detailed />
                <BookingCard v-for="b in restToday" :key="b.id" :booking="b" :now="pro.now" />
                <p v-if="!pro.todayBookings.length" class="empty">Aucun rendez-vous confirmé aujourd'hui.</p>
            </section>
        </template>
    </div>
</template>

<style scoped>
.dash { display: flex; flex-direction: column; gap: 16px; }
.top { display: flex; align-items: center; justify-content: space-between; }
.brand { display: flex; align-items: center; gap: 8px; }
.brand img { border-radius: 9px; object-fit: cover; }
.brand b { font: 800 18px var(--font-head); color: var(--plum); }
.pro-tag { padding: 2px 8px; border-radius: 99px; background: var(--plum); color: var(--gold); font: 700 10px var(--font-body); }

.hello { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
h1 { font: 700 26px var(--font-head); color: var(--plum); }
.hello p { font-size: 13px; color: var(--text-2); margin-top: 2px; }
.open { padding: 6px 12px; border-radius: 99px; background: var(--surface); border: 1px solid var(--line); font: 600 11px var(--font-body); white-space: nowrap; }
.open .dot { color: var(--green); }
.open.closed .dot { color: var(--faint); }

.stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.stat { padding: 14px; border-radius: 18px; background: var(--surface); border: 1px solid var(--line); display: flex; flex-direction: column; gap: 4px; min-width: 0; }
.stat .k { font-size: 10px; letter-spacing: .12em; color: var(--rust); }
.stat b { font: 700 22px var(--font-head); }
.stat small { font-size: 12px; font-weight: 500; }
.stat b small:first-of-type { font-weight: 700; }
.d { font-size: 11px; color: var(--green); }
.d.down { color: var(--danger); }
.d.muted { color: var(--muted); }

.block { display: flex; flex-direction: column; gap: 12px; }
.block-head { display: flex; justify-content: space-between; align-items: baseline; }
h2 { font: 700 20px var(--font-head); color: var(--plum); }
.count { font: 600 11px var(--font-body); color: var(--rust); }
.empty { padding: 18px; border-radius: 18px; background: var(--surface); border: 1px dashed var(--line); text-align: center; color: var(--muted); font-size: 13px; }

.skeleton { display: flex; flex-direction: column; gap: 14px; }
.sk { display: block; border-radius: 18px; background: var(--blush-2); animation: eb-pulse 1.4s ease-in-out infinite; }

.state { padding: 24px 20px; border-radius: 20px; background: var(--surface); border: 1px solid var(--line); text-align: center; display: flex; flex-direction: column; gap: 14px; align-items: center; color: var(--text-2); }
.retry { height: 44px; padding: 0 22px; border: none; border-radius: 12px; background: var(--plum); color: #fff; font: 600 14px var(--font-body); }
</style>
