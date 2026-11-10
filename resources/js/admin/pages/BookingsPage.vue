<script setup>
import { computed } from 'vue';
import { CalendarDays } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import Tabs from '../components/Tabs.vue';
import Pager from '../components/Pager.vue';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import TableSkeleton from '../components/TableSkeleton.vue';
import { useList } from '../lib/useList';
import { fcfa, date, statusLabel, locationLabels } from '../lib/format';

const list = useList('bookings', { status: '', from: '', to: '', search: '' });

const order = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];
const tabs = computed(() => [
    { value: '', label: 'Toutes', count: list.extra.value.counts?.all ?? 0 },
    ...order.map((s) => ({ value: s, label: statusLabel('booking', s), count: list.extra.value.counts?.[s] ?? 0 })),
]);
</script>

<template>
    <PageHead eyebrow="Activité" sub="Toutes les réservations de la plateforme, de la demande à l'encaissement.">
        <template #title>Les <em>réservations</em></template>
    </PageHead>

    <section class="card">
        <div class="card__body" style="padding-bottom: 0">
            <div class="toolbar">
                <Tabs v-model="list.filters.status" :options="tabs" />
                <div class="filters">
                    <input v-model="list.filters.from" class="input" type="date" aria-label="Du" />
                    <input v-model="list.filters.to" class="input" type="date" aria-label="Au" />
                    <input v-model="list.filters.search" class="input" style="width: 200px" type="search" placeholder="Référence…" aria-label="Rechercher" />
                </div>
            </div>
        </div>

        <div v-if="list.error.value" class="alert" style="margin: 0 24px 20px">{{ list.error.value }}</div>
        <TableSkeleton v-if="list.loading.value && !list.rows.value.length" />

        <div v-else class="table-wrap" :style="{ opacity: list.loading.value ? 0.55 : 1, transition: 'opacity .15s' }">
            <table v-if="list.rows.value.length" class="table">
                <thead><tr><th>Référence</th><th>Cliente</th><th>Prestataire</th><th>Service</th><th>Rendez-vous</th><th>Statut</th><th class="num">Montant</th><th class="num">Commission</th></tr></thead>
                <tbody>
                    <tr v-for="b in list.rows.value" :key="b.id">
                        <td class="mono">{{ b.reference }}</td>
                        <td>{{ b.customer }}</td>
                        <td>{{ b.provider }}</td>
                        <td><p>{{ b.service }}</p><p class="cell-sub">{{ locationLabels[b.location_type] || b.location_type }}</p></td>
                        <td><p>{{ date(b.booking_date) }}</p><p class="cell-sub">{{ b.start_time }}</p></td>
                        <td><StatusBadge kind="booking" :value="b.status" /></td>
                        <td class="num">{{ fcfa(b.total_price) }}</td>
                        <td class="num muted">{{ fcfa(b.commission_amount) }}</td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="Aucune réservation" text="Aucune réservation ne correspond à ces filtres.">
                <template #icon><CalendarDays /></template>
            </EmptyState>
        </div>

        <Pager :meta="list.meta.value" noun="réservations" @go="list.goTo" />
    </section>
</template>
