<script setup>
import { Scissors, Star } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import Pager from '../components/Pager.vue';
import EmptyState from '../components/EmptyState.vue';
import TableSkeleton from '../components/TableSkeleton.vue';
import { useList } from '../lib/useList';
import { api, errorMessage } from '../lib/api';
import { toast } from '../lib/toast';
import { fcfa } from '../lib/format';

const list = useList('services', { category: '', active: '', search: '' });

// Bascule optimiste : on change l'interrupteur tout de suite, on annule si l'API refuse.
async function toggle(s, field) {
    const prev = s[field];
    s[field] = !prev;
    try {
        const { data } = await api.patch(`services/${s.id}`, { [field]: s[field] });
        list.patch(data.data);
        toast(field === 'is_active' ? (s.is_active ? 'Service activé' : 'Service désactivé') : (s.is_featured ? 'Service mis en avant' : 'Service retiré de la une'));
    } catch (e) {
        s[field] = prev;
        toast(errorMessage(e), 'error');
    }
}
</script>

<template>
    <PageHead eyebrow="Catalogue" sub="Prestations proposées par les artisanes : activez, désactivez ou mettez en avant.">
        <template #title>Les <em>services</em></template>
    </PageHead>

    <section class="card">
        <div class="card__body" style="padding-bottom: 0">
            <div class="toolbar">
                <div class="filters">
                    <select v-model="list.filters.category" class="select" aria-label="Catégorie">
                        <option value="">Toutes les catégories</option>
                        <option v-for="c in list.extra.value.categories || []" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                    <select v-model="list.filters.active" class="select" aria-label="Statut">
                        <option value="">Tous les statuts</option>
                        <option value="1">Actifs</option>
                        <option value="0">Désactivés</option>
                    </select>
                </div>
                <input v-model="list.filters.search" class="input" style="width: 260px" type="search" placeholder="Rechercher un service…" aria-label="Rechercher" />
            </div>
        </div>

        <div v-if="list.error.value" class="alert" style="margin: 0 24px 20px">{{ list.error.value }}</div>
        <TableSkeleton v-if="list.loading.value && !list.rows.value.length" />

        <div v-else class="table-wrap" :style="{ opacity: list.loading.value ? 0.55 : 1, transition: 'opacity .15s' }">
            <table v-if="list.rows.value.length" class="table">
                <thead><tr><th>Service</th><th>Catégorie</th><th>Prestataire</th><th class="num">Prix</th><th class="num">Durée</th><th>À la une</th><th>Actif</th></tr></thead>
                <tbody>
                    <tr v-for="s in list.rows.value" :key="s.id">
                        <td><p class="cell-main">{{ s.name }}</p><p class="cell-sub">{{ s.short_description || '—' }}</p></td>
                        <td><span class="chip">{{ s.category || '—' }}</span></td>
                        <td>{{ s.provider }}</td>
                        <td class="num">{{ fcfa(s.base_price) }}</td>
                        <td class="num">{{ s.duration_minutes ? `${s.duration_minutes} min` : '—' }}</td>
                        <td><button class="switch is-gold" :class="{ 'is-on': s.is_featured }" type="button" role="switch" :aria-checked="s.is_featured" aria-label="Mettre à la une" @click="toggle(s, 'is_featured')" /></td>
                        <td><button class="switch" :class="{ 'is-on': s.is_active }" type="button" role="switch" :aria-checked="s.is_active" aria-label="Activer le service" @click="toggle(s, 'is_active')" /></td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="Aucun service" text="Aucun service ne correspond à ces filtres.">
                <template #icon><Scissors /></template>
            </EmptyState>
        </div>

        <Pager :meta="list.meta.value" noun="services" @go="list.goTo" />
    </section>
</template>
