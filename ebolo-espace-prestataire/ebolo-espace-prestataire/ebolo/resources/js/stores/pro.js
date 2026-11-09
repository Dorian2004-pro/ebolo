import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import * as api from '@/services/api';
import { errorMessage } from '@/services/http';
import { isSameDay, monthKey } from '@/composables/format';

const ACTIVE = ['confirmed', 'completed'];

export const useProStore = defineStore('pro', () => {
    const profile = ref(null);
    const availability = ref(null);
    const bookings = ref([]);
    const loading = ref(false);
    const loaded = ref(false);
    const error = ref('');
    const busy = ref(new Set()); // ids de réservations en cours de mise à jour
    const now = ref(new Date());

    setInterval(() => { now.value = new Date(); }, 30000);

    // ── Chargement ───────────────────────────────────────────────────────
    async function load() {
        loading.value = true;
        error.value = '';
        try {
            const [p, b, a] = await Promise.allSettled([api.getProfile(), api.getBookings(), api.getAvailability()]);
            if (p.status === 'rejected') throw p.reason;
            if (b.status === 'rejected') throw b.reason;
            profile.value = p.value;
            bookings.value = b.value;
            // Les horaires sont « à convenir » : leur absence ne bloque pas le dashboard.
            availability.value = a.status === 'fulfilled' ? a.value : null;
            loaded.value = true;
        } catch (e) {
            error.value = errorMessage(e, 'Impossible de charger votre espace.');
        } finally {
            loading.value = false;
        }
    }

    const replace = (updated) => {
        const i = bookings.value.findIndex((x) => x.id === updated.id);
        if (i >= 0) bookings.value[i] = { ...bookings.value[i], ...updated };
    };

    const withBusy = async (id, fn) => {
        busy.value = new Set(busy.value).add(id);
        try {
            return await fn();
        } finally {
            const s = new Set(busy.value);
            s.delete(id);
            busy.value = s;
        }
    };

    /** Accepter / refuser une demande de réservation. */
    const respond = (id, status) =>
        withBusy(id, async () => replace(await api.updateBookingStatus(id, status)));

    async function updateProfile(payload) {
        profile.value = await api.updateProfile(payload);
    }

    async function updateAvailability(payload) {
        availability.value = await api.updateAvailability(payload);
    }

    /** Débloquer le séquestre avec le code à 4 chiffres de la cliente. */
    const release = (id, code) =>
        withBusy(id, async () => replace(await api.releaseEscrow(id, code)));

    // ── Dérivés ──────────────────────────────────────────────────────────
    const firstName = computed(() => (profile.value?.name ?? '').split(' ')[0]);

    const subtitle = computed(() => {
        const p = profile.value;
        if (!p) return '';
        return p.workplace ?? [p.city, p.location?.label].filter(Boolean).join(' • ');
    });

    /** Ouvert / fermé selon les horaires (null si les horaires ne sont pas disponibles). */
    const isOpen = computed(() => {
        const days = availability.value?.days;
        if (!days?.length) return null;
        const d = now.value;
        const dow = d.getDay() === 0 ? 7 : d.getDay();
        const today = days.find((x) => x.day === dow);
        if (!today?.open) return false;
        const hm = `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
        return hm >= today.from && hm < today.to;
    });

    const revenueFor = (offset) => {
        const key = monthKey(now.value) + offset;
        return bookings.value
            .filter((b) => b.payment?.status === 'released' && monthKey(b.starts_at) === key)
            .reduce((sum, b) => sum + (b.service?.price ?? 0), 0);
    };
    const revenueMonth = computed(() => revenueFor(0));
    const revenueDelta = computed(() => {
        const prev = revenueFor(-1);
        return prev > 0 ? Math.round(((revenueMonth.value - prev) / prev) * 100) : null;
    });

    const confirmedThisMonth = computed(() =>
        bookings.value.filter((b) => ACTIVE.includes(b.status) && monthKey(b.starts_at) === monthKey(now.value)).length);

    const byStart = (a, b) => new Date(a.starts_at) - new Date(b.starts_at);

    const pendingRequests = computed(() =>
        bookings.value.filter((b) => b.status === 'pending').sort(byStart));

    const todayBookings = computed(() =>
        bookings.value
            .filter((b) => b.status === 'confirmed' && isSameDay(b.starts_at, now.value))
            .sort(byStart));

    /** Réservation dont le paiement est consigné en séquestre et dont la prestation a commencé aujourd'hui ou avant. */
    const escrowBooking = computed(() =>
        bookings.value
            .filter((b) => b.status === 'confirmed' && b.payment?.status === 'escrow' && new Date(b.starts_at) <= endOfToday(now.value))
            .sort(byStart)[0] ?? null);

    return {
        profile, availability, bookings, loading, loaded, error, busy, now,
        load, respond, release, updateProfile, updateAvailability,
        firstName, subtitle, isOpen, revenueMonth, revenueDelta, confirmedThisMonth,
        pendingRequests, todayBookings, escrowBooking,
    };
});

function endOfToday(d) {
    const e = new Date(d);
    e.setHours(23, 59, 59, 999);
    return e;
}
