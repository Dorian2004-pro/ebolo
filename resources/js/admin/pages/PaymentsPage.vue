<script setup>
import { computed } from 'vue';
import { Wallet, CreditCard } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import KpiCard from '../components/KpiCard.vue';
import Pager from '../components/Pager.vue';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import TableSkeleton from '../components/TableSkeleton.vue';
import { useList } from '../lib/useList';
import { fcfa, number, date, ago, statusLabel, txTypeLabels, gatewayLabels } from '../lib/format';

const list = useList('payments', { status: '', type: '', search: '' });
const s = computed(() => list.extra.value.summary || {});
</script>

<template>
    <PageHead eyebrow="Finance" sub="Paiements enregistrés dans le backend principal. Le séquestre et les reversements ne sont pas encore disponibles.">
        <template #title>Les <em>paiements</em></template>
    </PageHead>

    <div class="summary">
        <KpiCard label="Encaissé" :value="fcfa(s.collected)" :icon="Wallet" foot="Paiements marqués comme réglés" />
        <KpiCard label="Transactions" :value="number(s.total)" :icon="CreditCard" tone="plum" foot="Tous statuts confondus" />
        <KpiCard label="En attente" :value="number(s.pending)" :icon="CreditCard" tone="gold" foot="Paiements à confirmer" />
        <KpiCard label="En échec" :value="number(s.failed)" :icon="CreditCard" tone="ink" foot="Transactions refusées ou échouées" />
    </div>

    <section class="card">
        <div class="card__body" style="padding-bottom: 0">
            <div class="toolbar">
                <div class="filters">
                    <select v-model="list.filters.type" class="select" aria-label="Type">
                        <option value="">Tous les types</option>
                        <option v-for="(label, k) in txTypeLabels" :key="k" :value="k">{{ label }}</option>
                    </select>
                    <select v-model="list.filters.status" class="select" aria-label="Statut">
                        <option value="">Tous les statuts</option>
                        <option v-for="k in ['pending', 'completed', 'failed']" :key="k" :value="k">{{ statusLabel('transaction', k) }}</option>
                    </select>
                </div>
                <input v-model="list.filters.search" class="input" style="width: 240px" type="search" placeholder="Référence…" aria-label="Rechercher" />
            </div>
        </div>

        <div v-if="list.error.value" class="alert" style="margin: 0 24px 20px">{{ list.error.value }}</div>
        <TableSkeleton v-if="list.loading.value && !list.rows.value.length" />

        <div v-else class="table-wrap" :style="{ opacity: list.loading.value ? 0.55 : 1, transition: 'opacity .15s' }">
            <table v-if="list.rows.value.length" class="table">
                <thead><tr><th>Référence</th><th>Type</th><th>Utilisateur</th><th>Réservation</th><th>Moyen</th><th>Statut</th><th>Date</th><th class="num">Montant</th></tr></thead>
                <tbody>
                    <tr v-for="t in list.rows.value" :key="t.id">
                        <td class="mono">{{ t.reference }}</td>
                        <td><span class="chip">{{ txTypeLabels[t.type] || t.type }}</span></td>
                        <td>{{ t.user }}</td>
                        <td class="mono">{{ t.booking || '—' }}</td>
                        <td>{{ gatewayLabels[t.gateway] || t.gateway || '—' }}</td>
                        <td>
                            <StatusBadge kind="transaction" :value="t.status" />
                            <p v-if="t.failure_reason" class="cell-sub">{{ t.failure_reason }}</p>
                        </td>
                        <td class="muted" :title="date(t.created_at)">{{ ago(t.created_at) }}</td>
                        <td class="num" :style="{ color: t.direction === 'out' ? 'var(--terra-700)' : undefined }">{{ t.direction === 'out' ? '−' : '' }}{{ fcfa(t.amount) }}</td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="Aucune transaction" text="Aucune transaction ne correspond à ces filtres.">
                <template #icon><CreditCard /></template>
            </EmptyState>
        </div>

        <Pager :meta="list.meta.value" noun="transactions" @go="list.goTo" />
    </section>
</template>
