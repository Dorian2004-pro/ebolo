<script setup>
import { ref, computed } from 'vue';
import { Store, Star, MapPin, Phone, Check, X } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import Tabs from '../components/Tabs.vue';
import Pager from '../components/Pager.vue';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import TableSkeleton from '../components/TableSkeleton.vue';
import Modal from '../components/Modal.vue';
import { useList } from '../lib/useList';
import { api, errorMessage } from '../lib/api';
import { toast } from '../lib/toast';
import { initials, date } from '../lib/format';

const list = useList('providers', { status: '', search: '' }, { perPage: 12 });

const tabs = computed(() => [
    { value: '', label: 'Tous', count: list.extra.value.counts?.all ?? 0 },
    { value: 'pending', label: 'À valider', count: list.extra.value.counts?.pending ?? 0 },
    { value: 'approved', label: 'Validés', count: list.extra.value.counts?.approved ?? 0 },
    { value: 'rejected', label: 'Refusés', count: list.extra.value.counts?.rejected ?? 0 },
]);

const rejecting = ref(null);
const reason = ref('');
const busy = ref(false);
const modalError = ref('');

async function approve(p) {
    busy.value = true;
    try {
        await api.post(`providers/${p.id}/approve`);
        toast(`${p.business_name} est validé·e`);
        list.reload();
    } catch (e) { toast(errorMessage(e), 'error'); } finally { busy.value = false; }
}

async function reject() {
    busy.value = true; modalError.value = '';
    try {
        await api.post(`providers/${rejecting.value.id}/reject`, { reason: reason.value });
        rejecting.value = null; reason.value = '';
        toast('Dossier refusé, la prestataire a été notifiée');
        list.reload();
    } catch (e) { modalError.value = errorMessage(e); } finally { busy.value = false; }
}

</script>

<template>
    <PageHead eyebrow="Communauté" sub="Validez ou refusez les profils artisans. La collecte de pièces justificatives n'est pas encore gérée par le backend.">
        <template #title>Les <em>prestataires</em></template>
    </PageHead>

    <div class="toolbar" style="margin-bottom: 20px">
        <Tabs v-model="list.filters.status" :options="tabs" />
        <div class="filters">
            <input v-model="list.filters.search" class="input" style="width: 260px" type="search" placeholder="Atelier, nom, ville…" aria-label="Rechercher" />
        </div>
    </div>

    <div v-if="list.error.value" class="alert" style="margin-bottom: 16px">{{ list.error.value }}</div>
    <section v-if="list.loading.value && !list.rows.value.length" class="card"><TableSkeleton /></section>

    <div v-else-if="list.rows.value.length" class="cards" :style="{ opacity: list.loading.value ? 0.55 : 1, transition: 'opacity .15s' }">
        <article v-for="p in list.rows.value" :key="p.id" class="card pcard">
            <div class="pcard__head">
                <span class="avatar avatar--lg avatar--terra">{{ initials(p.owner.split(' ')[0], p.owner.split(' ')[1] || '') }}</span>
                <div style="min-width: 0; flex: 1">
                    <h3 class="pcard__name">{{ p.business_name }}</h3>
                    <p class="cell-sub">{{ p.owner }}</p>
                </div>
                <StatusBadge kind="provider" :value="p.status" />
            </div>

            <div class="pcard__meta">
                <span><MapPin />{{ p.city || '—' }}</span>
                <span><Star />{{ p.rating ? p.rating.toFixed(1).replace('.', ',') : '—' }} ({{ p.total_reviews }})</span>
                <span v-if="p.phone"><Phone />{{ p.phone }}</span>
            </div>

            <div>
                <span v-for="c in p.categories" :key="c" class="chip">{{ c }}</span>
                <span v-if="!p.categories.length" class="cell-sub">Aucune catégorie</span>
            </div>

            <p v-if="p.status === 'rejected' && p.rejection_reason" class="note">Motif : {{ p.rejection_reason }}</p>

            <div class="pcard__actions">
                <template v-if="p.status === 'pending'">
                    <button class="btn btn--ok" type="button" :disabled="busy" @click="approve(p)"><Check /> Valider</button>
                    <button class="btn btn--danger" type="button" :disabled="busy" @click="rejecting = p; reason = ''; modalError = ''"><X /> Refuser</button>
                </template>
                <template v-else-if="p.status === 'approved'">
                    <p class="cell-sub" style="align-self: center">Validé le {{ date(p.verified_at) }}</p>
                    <button class="btn btn--ghost" type="button" :disabled="busy" @click="rejecting = p; reason = ''; modalError = ''">Retirer</button>
                </template>
                <template v-else>
                    <button class="btn btn--ok" type="button" :disabled="busy" @click="approve(p)"><Check /> Valider finalement</button>
                </template>
            </div>
        </article>
    </div>

    <section v-else class="card">
        <EmptyState title="Aucune prestataire" text="Aucun dossier ne correspond à ces filtres.">
            <template #icon><Store /></template>
        </EmptyState>
    </section>

    <div v-if="list.rows.value.length" class="card" style="margin-top: 20px"><Pager :meta="list.meta.value" noun="prestataires" @go="list.goTo" /></div>

    <Modal v-if="rejecting" :busy="busy" @close="rejecting = null">
        <template #title>Refuser <em>{{ rejecting.business_name }}</em> ?</template>
        <p class="muted" style="margin-bottom: 16px">Le motif est envoyé à la prestataire par notification et conservé dans le journal d'audit.</p>
        <div v-if="modalError" class="alert" style="margin-bottom: 14px">{{ modalError }}</div>
        <div class="field">
            <label for="rej">Motif du refus</label>
            <textarea id="rej" v-model="reason" class="textarea" placeholder="Ex. : pièce d'identité illisible, merci de la renvoyer" />
        </div>
        <template #footer>
            <button class="btn btn--ghost" type="button" :disabled="busy" @click="rejecting = null">Annuler</button>
            <button class="btn btn--danger" type="button" :disabled="busy || !reason.trim()" @click="reject"><X /> Confirmer le refus</button>
        </template>
    </Modal>
</template>
