<script setup>
import { ref, computed } from 'vue';
import { ShieldAlert, Search, CheckCheck } from '@lucide/vue';
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
import { fcfa, ago, date, reasonLabels } from '../lib/format';

const list = useList('disputes', { status: '', search: '' });

const tabs = computed(() => [
    { value: '', label: 'Tous', count: list.extra.value.counts?.all ?? 0 },
    { value: 'open', label: 'Ouverts', count: list.extra.value.counts?.open ?? 0 },
    { value: 'investigating', label: 'En analyse', count: list.extra.value.counts?.investigating ?? 0 },
    { value: 'resolved', label: 'Résolus', count: list.extra.value.counts?.resolved ?? 0 },
]);

const current = ref(null);
const resolution = ref('');
const busy = ref(false);
const modalError = ref('');

function open(d) { current.value = d; resolution.value = d.resolution || ''; modalError.value = ''; }

async function setStatus(status) {
    busy.value = true; modalError.value = '';
    try {
        await api.patch(`disputes/${current.value.id}`, { status, resolution: resolution.value || null });
        toast(status === 'resolved' ? 'Litige résolu' : 'Litige mis à jour');
        current.value = null;
        list.reload();
    } catch (e) { modalError.value = errorMessage(e); } finally { busy.value = false; }
}
</script>

<template>
    <PageHead eyebrow="Confiance & sécurité" sub="Consultez et traitez les réclamations enregistrées par les clientes ou les prestataires.">
        <template #title>Les <em>litiges</em></template>
    </PageHead>

    <section class="card">
        <div class="card__body" style="padding-bottom: 0">
            <div class="toolbar">
                <Tabs v-model="list.filters.status" :options="tabs" />
                <input v-model="list.filters.search" class="input" style="width: 260px" type="search" placeholder="Référence, motif…" aria-label="Rechercher" />
            </div>
        </div>

        <div v-if="list.error.value" class="alert" style="margin: 0 24px 20px">{{ list.error.value }}</div>
        <TableSkeleton v-if="list.loading.value && !list.rows.value.length" />

        <div v-else class="table-wrap" :style="{ opacity: list.loading.value ? 0.55 : 1, transition: 'opacity .15s' }">
            <table v-if="list.rows.value.length" class="table">
                <thead><tr><th>Litige</th><th>Opposant</th><th>Motif</th><th class="num">Montant</th><th>Priorité</th><th>Statut</th><th>Ouvert</th><th /></tr></thead>
                <tbody>
                    <tr v-for="d in list.rows.value" :key="d.id">
                        <td><p class="mono">{{ d.reference }}</p><p class="cell-sub">Réservation {{ d.booking || '—' }}</p></td>
                        <td><p class="cell-main">{{ d.raised_by }}</p><p class="cell-sub">contre {{ d.raised_against }}</p></td>
                        <td><span class="chip">{{ reasonLabels[d.reason_type] || d.reason_type || '—' }}</span><p class="cell-sub">{{ d.reason }}</p></td>
                        <td class="num">{{ d.amount_disputed !== null ? fcfa(d.amount_disputed) : '—' }}</td>
                        <td><StatusBadge kind="priority" :value="d.priority" /></td>
                        <td><StatusBadge kind="dispute" :value="d.status" /></td>
                        <td class="muted">{{ ago(d.created_at) }}</td>
                        <td><button class="btn btn--soft btn--sm" type="button" @click="open(d)">Traiter</button></td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="Aucun litige" text="Bonne nouvelle : rien à arbitrer avec ces filtres.">
                <template #icon><ShieldAlert /></template>
            </EmptyState>
        </div>

        <Pager :meta="list.meta.value" noun="litiges" @go="list.goTo" />
    </section>

    <Modal v-if="current" :busy="busy" @close="current = null">
        <template #title>Litige <em>{{ current.reference }}</em></template>
        <dl class="dl" style="margin-bottom: 20px">
            <dt>Réservation</dt><dd class="mono">{{ current.booking || '—' }}</dd>
            <dt>Ouvert par</dt><dd>{{ current.raised_by }} <span class="muted">contre {{ current.raised_against }}</span></dd>
            <dt>Motif</dt><dd>{{ current.reason }}</dd>
            <dt>Montant contesté</dt><dd>{{ current.amount_disputed !== null ? fcfa(current.amount_disputed) : '—' }}</dd>
            <dt>Date</dt><dd>{{ date(current.created_at) }} · <StatusBadge kind="dispute" :value="current.status" /></dd>
        </dl>
        <div v-if="modalError" class="alert" style="margin-bottom: 14px">{{ modalError }}</div>
        <div class="field">
            <label for="res">Décision / résolution</label>
            <textarea id="res" v-model="resolution" class="textarea" placeholder="Obligatoire pour résoudre : ex. remboursement partiel de 50 % accordé à la cliente" />
        </div>
        <template #footer>
            <button class="btn btn--ghost" type="button" :disabled="busy" @click="current = null">Fermer</button>
            <button v-if="current.status === 'open'" class="btn btn--soft" type="button" :disabled="busy" @click="setStatus('investigating')"><Search /> Passer en analyse</button>
            <button class="btn btn--ok" type="button" :disabled="busy || !resolution.trim()" @click="setStatus('resolved')"><CheckCheck /> Marquer résolu</button>
        </template>
    </Modal>
</template>
