<script setup>
import { computed, onMounted } from 'vue';
import { useProStore } from '@/stores/pro';
import { formatDay, formatRange, formatFCFA } from '@/composables/format';
import BookingCard from '@/components/pro/BookingCard.vue';
import RequestCard from '@/components/pro/RequestCard.vue';
import { useToastStore } from '@/stores/toast';
import { errorMessage } from '@/services/http';
import { RouterLink } from 'vue-router';

const pro = useProStore();
const toast = useToastStore();

onMounted(() => { if (!pro.loaded) pro.load(); });

const schedule = computed(() => pro.bookings
    .filter((booking) => !['rejected', 'cancelled'].includes(booking.status))
    .sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at)));

const groups = computed(() => {
    const days = new Map();
    for (const booking of schedule.value) {
        const key = new Date(booking.starts_at).toLocaleDateString('fr-CA');
        if (!days.has(key)) days.set(key, { key, label: formatDay(booking.starts_at), bookings: [] });
        days.get(key).bookings.push(booking);
    }
    return [...days.values()];
});

async function answer(booking, status) {
    try {
        await pro.respond(booking.id, status);
        toast.show(status === 'confirmed' ? 'Rendez-vous confirmé.' : 'Demande refusée.');
    } catch (e) {
        toast.error(errorMessage(e, 'Action impossible.'));
    }
}
</script>

<template>
    <div class="page">
        <header><span class="eyebrow">MON ACTIVITÉ</span><h1>Planning</h1><p>Retrouvez les demandes et rendez-vous reçus.</p><RouterLink class="schedule-link" :to="{ name: 'pro.availability' }">Gérer mes horaires →</RouterLink></header>
        <p v-if="pro.loading && !pro.loaded" class="notice">Chargement du planning…</p>
        <p v-else-if="pro.error && !pro.loaded" class="error" role="alert">{{ pro.error }}</p>
        <template v-else>
            <section v-if="pro.pendingRequests.length" class="section">
                <div class="section-head"><h2>À confirmer</h2><span>{{ pro.pendingRequests.length }}</span></div>
                <RequestCard v-for="booking in pro.pendingRequests" :key="booking.id" :booking="booking" :busy="pro.busy.has(booking.id)" @accept="answer($event, 'confirmed')" @reject="answer($event, 'rejected')" />
            </section>
            <section v-for="group in groups" :key="group.key" class="section">
                <div class="section-head"><h2>{{ group.label }}</h2><span>{{ group.bookings.length }} RDV</span></div>
                <article v-for="booking in group.bookings" :key="booking.id" class="booking">
                    <div class="line"><b>{{ booking.client?.name || 'Cliente' }}</b><span class="status" :class="booking.status">{{ booking.status === 'confirmed' ? 'Confirmé' : 'En attente' }}</span></div>
                    <p>{{ booking.service?.name }} · {{ formatRange(booking.starts_at, booking.ends_at) }}</p>
                    <div class="line muted"><span>{{ booking.payment?.status === 'escrow' ? 'Paiement consigné' : 'Paiement en attente' }}</span><b>{{ formatFCFA(booking.service?.price) }}</b></div>
                </article>
            </section>
            <p v-if="!groups.length && !pro.pendingRequests.length" class="notice">Aucun rendez-vous dans votre planning.</p>
        </template>
    </div>
</template>

<style scoped>
.page, .section { display: flex; flex-direction: column; gap: 12px; }
header { display: flex; flex-direction: column; gap: 4px; }
.eyebrow { color: var(--rust); font-size: 10px; letter-spacing: .14em; }
h1 { color: var(--plum); font: 700 25px var(--font-head); }
h2 { color: var(--plum); font: 700 17px var(--font-head); }
header p { color: var(--text-2); font-size: 13px; }
.section-head, .line { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
.section-head span { color: var(--rust); font-size: 11px; }
.booking { padding: 14px; border: 1px solid var(--line); border-radius: 16px; background: var(--surface); display: flex; flex-direction: column; gap: 8px; }
.booking p, .muted { color: var(--muted); font-size: 12px; }
.line b { font-size: 13px; }
.status { padding: 3px 8px; border-radius: 99px; background: var(--amber-bg); color: var(--amber); font-size: 10px; }
.status.confirmed { background: var(--green-bg); color: var(--green); }
.notice, .error { padding: 16px; color: var(--muted); text-align: center; font-size: 13px; }
.error { color: var(--danger); }
.schedule-link { align-self: flex-start; margin-top: 6px; color: var(--plum); font-size: 12px; font-weight: 700; }
</style>
