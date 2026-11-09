<script setup>
import { computed, onMounted, ref } from 'vue';
import { ChartColumn, Download, RotateCcw } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import KpiCard from '../components/KpiCard.vue';
import EmptyState from '../components/EmptyState.vue';
import { api, errorMessage } from '../lib/api';
import { fcfa, number } from '../lib/format';

const report = ref(null);
const loading = ref(false);
const error = ref('');
const categories = computed(() => report.value?.top_categories ?? []);

async function load() {
    loading.value = true;
    error.value = '';
    try {
        report.value = (await api.get('dashboard')).data;
    } catch (exception) {
        error.value = errorMessage(exception);
    } finally {
        loading.value = false;
    }
}

function exportCsv() {
    if (!report.value) return;
    const rows = [
        ['Indicateur', 'Valeur'],
        ['Utilisateurs', report.value.stats.users.total],
        ['Prestataires', report.value.stats.providers.total],
        ['Réservations', report.value.stats.bookings.total],
        ['Prestations', report.value.stats.services.total],
        ['Montant encaissé', report.value.finance.collected],
        ['Montant en séquestre', report.value.finance.escrow_held],
        ...categories.value.map((category) => [`Catégorie - ${category.name}`, category.count]),
    ];
    const csv = rows.map((row) => row.map((cell) => `"${String(cell ?? '').replaceAll('"', '""')}"`).join(';')).join('\r\n');
    const url = URL.createObjectURL(new Blob(['\ufeff', csv], { type: 'text/csv;charset=utf-8' }));
    const link = document.createElement('a');
    link.href = url;
    link.download = `ebolo-rapport-${new Date().toISOString().slice(0, 10)}.csv`;
    link.click();
    URL.revokeObjectURL(url);
}

onMounted(load);
</script>

<template>
    <PageHead eyebrow="Analyse de l’activité" sub="Indicateurs calculés depuis les réservations et paiements du backend.">
        <template #title>Rapports &amp; <em>statistiques</em></template>
        <button class="btn btn--ghost" type="button" :disabled="loading" @click="load"><RotateCcw /> Actualiser</button>
        <button class="btn btn--primary" type="button" :disabled="!report" @click="exportCsv"><Download /> Exporter CSV</button>
    </PageHead>
    <p v-if="error" class="alert" role="alert">{{ error }}</p>
    <div v-if="report" class="summary">
        <KpiCard label="Utilisateurs" :value="number(report.stats.users.total)" :icon="ChartColumn" />
        <KpiCard label="Prestataires" :value="number(report.stats.providers.total)" :icon="ChartColumn" tone="plum" />
        <KpiCard label="Réservations" :value="number(report.stats.bookings.total)" :icon="ChartColumn" tone="gold" />
        <KpiCard label="Encaissé" :value="fcfa(report.finance.collected)" :icon="ChartColumn" tone="ink" />
    </div>
    <section class="card report">
        <div class="card__head"><div><h2 class="card__title">Activité des 14 derniers jours</h2><p class="card__sub">Réservations créées par jour.</p></div></div>
        <div v-if="report?.series?.labels?.length" class="bars card__body">
            <div v-for="(label, index) in report.series.labels" :key="label" class="bar-row">
                <span>{{ label }}</span><progress max="100" :value="Math.min(report.series.bookings[index] * 10, 100)"></progress><b>{{ report.series.bookings[index] }}</b>
            </div>
        </div>
        <EmptyState v-else-if="!loading" title="Aucune activité" text="Les données apparaîtront ici après les premières réservations." />
    </section>
</template>

<style scoped>
.report{margin-top:20px}.bars{display:grid;gap:10px}.bar-row{display:grid;grid-template-columns:54px 1fr 28px;align-items:center;gap:10px;color:var(--muted);font-size:11px}.bar-row progress{width:100%;height:8px;accent-color:var(--plum)}.bar-row b{text-align:right;color:var(--ink)}
</style>
