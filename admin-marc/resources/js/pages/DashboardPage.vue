<script setup>
import { ref, computed, onMounted } from 'vue';
import {
    Users, Store, CalendarCheck, Scissors, Lock, Hourglass, ShieldAlert, UserPlus, CircleCheck,
    RotateCcw, Plus, ArrowUpRight, Wallet,
} from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import KpiCard from '../components/KpiCard.vue';
import ActivityChart from '../components/ActivityChart.vue';
import DonutChart from '../components/DonutChart.vue';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import { api, errorMessage } from '../lib/api';
import { auth } from '../lib/auth';
import { fcfa, number, greeting, longDate, shortDate, statusLabel, statusColors } from '../lib/format';

const data = ref(null);
const loading = ref(true);
const error = ref('');

async function load() {
    loading.value = true;
    error.value = '';
    try {
        data.value = (await api.get('dashboard')).data;
    } catch (e) {
        error.value = errorMessage(e);
    } finally {
        loading.value = false;
    }
}
onMounted(load);

const segments = computed(() => (data.value?.booking_status || []).map((s) => ({
    label: statusLabel('booking', s.status), value: s.count, color: statusColors[s.status],
})));
const maxCat = computed(() => Math.max(1, ...(data.value?.top_categories || []).map((c) => c.count)));
</script>

<template>
    <PageHead :eyebrow="`${longDate()} · Douala • Yaoundé`">
        <template #title>{{ greeting() }} {{ auth.admin?.firstName }}, <em>pilotez</em> Ébolo.</template>
        <router-link v-if="auth.can('manage_users')" class="btn btn--ghost" to="/users"><Plus /> Ajouter un utilisateur</router-link>
        <button class="btn btn--primary" type="button" :disabled="loading" @click="load"><RotateCcw :class="{ spin: loading }" /> Actualiser</button>
    </PageHead>

    <div v-if="error" class="alert" role="alert" style="margin-bottom: 20px">{{ error }}</div>

    <!-- Squelette de chargement initial -->
    <template v-if="loading && !data">
        <div class="grid dash-top">
            <div class="skeleton" style="height: 300px; border-radius: 22px" />
            <div class="kpis"><div v-for="n in 4" :key="n" class="skeleton" style="height: 140px; border-radius: 22px" /></div>
        </div>
    </template>

    <template v-else-if="data">
        <!-- Séquestre + KPI -->
        <section class="grid dash-top">
            <article class="vault">
                <div class="vault__top">
                    <span class="vault__label">Séquestre Mobile Money</span>
                    <span class="vault__pill"><Lock :size="12" style="vertical-align: -1px" /> {{ number(data.finance.escrow_count) }} paiement{{ data.finance.escrow_count > 1 ? 's' : '' }} consigné{{ data.finance.escrow_count > 1 ? 's' : '' }}</span>
                </div>
                <div>
                    <p class="vault__amount">{{ number(data.finance.escrow_held) }}<small>FCFA</small></p>
                    <p class="vault__text">Fonds protégés jusqu'à la validation du soin par la cliente. Ils sont libérés à la prestataire dès la prestation confirmée.</p>
                </div>
                <dl class="vault__row">
                    <div><dt>Commissions du mois</dt><dd>{{ fcfa(data.finance.commission_month) }}</dd></div>
                    <div><dt>Volume du mois</dt><dd>{{ fcfa(data.finance.gmv_month) }}</dd></div>
                    <div><dt>Reversements à faire</dt><dd>{{ fcfa(data.finance.payouts_pending_amount) }}</dd></div>
                </dl>
            </article>

            <div class="kpis">
                <KpiCard label="Utilisateurs" :value="number(data.stats.users.total)" :icon="Users" :trend="data.stats.users.trend" :foot="`+${data.stats.users.last_30d} en 30 j`" />
                <KpiCard label="Prestataires" :value="number(data.stats.providers.total)" :icon="Store" tone="plum" :trend="data.stats.providers.trend" :foot="`${data.stats.providers.pending} à valider`" />
                <KpiCard label="Réservations" :value="number(data.stats.bookings.total)" :icon="CalendarCheck" tone="gold" :trend="data.stats.bookings.trend" :foot="`${data.stats.bookings.today} aujourd'hui`" />
                <KpiCard label="Services" :value="number(data.stats.services.total)" :icon="Scissors" tone="ink" :foot="`${data.stats.categories.total} catégories`" />
            </div>
        </section>

        <!-- Activité -->
        <section class="grid dash-mid">
            <article class="card">
                <div class="card__head">
                    <div>
                        <h2 class="card__title">Activité des 14 derniers jours</h2>
                        <p class="card__sub">Réservations créées et volume d'affaires généré</p>
                    </div>
                </div>
                <div class="card__body"><ActivityChart :labels="data.series.labels" :bookings="data.series.bookings" :gmv="data.series.gmv" /></div>
            </article>

            <article class="card">
                <div class="card__head">
                    <div>
                        <h2 class="card__title">Statut des réservations</h2>
                        <p class="card__sub">Toutes périodes</p>
                    </div>
                </div>
                <div class="card__body"><DonutChart :segments="segments" center-label="Réservations" /></div>
            </article>
        </section>

        <!-- À traiter / Univers -->
        <section class="grid dash-low">
            <article class="card">
                <div class="card__head"><div><h2 class="card__title">À traiter aujourd'hui</h2><p class="card__sub">Ce qui attend l'équipe</p></div></div>
                <div class="card__body todo">
                    <router-link class="todo__item" to="/providers">
                        <span class="todo__ico todo__ico--gold"><Hourglass /></span>
                        <div><p class="todo__title">Prestataires en attente</p><p class="todo__sub">{{ data.todo.pending_providers }} dossier{{ data.todo.pending_providers > 1 ? 's' : '' }} à valider</p></div>
                        <span class="todo__n">{{ data.todo.pending_providers }}</span>
                    </router-link>
                    <router-link class="todo__item" to="/disputes">
                        <span class="todo__ico todo__ico--terra"><ShieldAlert /></span>
                        <div><p class="todo__title">Litiges ouverts</p><p class="todo__sub">Fonds potentiellement bloqués en séquestre</p></div>
                        <span class="todo__n">{{ data.todo.open_disputes }}</span>
                    </router-link>
                    <router-link class="todo__item" to="/payments">
                        <span class="todo__ico todo__ico--plum"><Wallet /></span>
                        <div><p class="todo__title">Paiements en attente</p><p class="todo__sub">{{ data.finance.payouts_pending_count }} reversement{{ data.finance.payouts_pending_count > 1 ? 's' : '' }} prestataire à émettre</p></div>
                        <span class="todo__n">{{ data.finance.payments_pending }}</span>
                    </router-link>
                    <div class="todo__item">
                        <span class="todo__ico todo__ico--ok"><UserPlus /></span>
                        <div><p class="todo__title">Nouvelles inscriptions</p><p class="todo__sub">Aujourd'hui</p></div>
                        <span class="todo__n">{{ data.todo.new_users_today }}</span>
                    </div>
                    <div class="todo__item">
                        <span class="todo__ico todo__ico--ok"><CircleCheck /></span>
                        <div><p class="todo__title">Prestations terminées</p><p class="todo__sub">{{ fcfa(data.finance.revenue_today) }} · commission {{ fcfa(data.finance.commission_today) }}</p></div>
                        <span class="todo__n">{{ data.todo.completed_today }}</span>
                    </div>
                </div>
            </article>

            <article class="card">
                <div class="card__head"><div><h2 class="card__title">Les univers les plus réservés</h2><p class="card__sub">Savoir-faire artisanaux d'Afrique</p></div></div>
                <div class="card__body">
                    <div v-if="data.top_categories.length" class="bars">
                        <div v-for="c in data.top_categories" :key="c.name">
                            <div class="bar__row"><span>{{ c.name }}</span><span>{{ number(c.count) }} réservations</span></div>
                            <div class="bar__track"><div class="bar__fill" :style="{ width: `${(c.count / maxCat) * 100}%` }" /></div>
                        </div>
                    </div>
                    <EmptyState v-else title="Pas encore de réservations" />
                </div>
            </article>
        </section>

        <!-- Réservations récentes -->
        <section class="card" style="margin-top: 20px">
            <div class="card__head">
                <div><h2 class="card__title">Réservations récentes</h2><p class="card__sub">Les 6 dernières créées</p></div>
                <router-link v-if="auth.can('manage_bookings')" class="card__link" to="/bookings">Tout voir <ArrowUpRight :size="12" style="vertical-align: -2px" /></router-link>
            </div>
            <div class="table-wrap" style="margin-top: 16px">
                <table v-if="data.recent_bookings.length" class="table">
                    <thead><tr><th>Référence</th><th>Cliente</th><th>Prestation</th><th>Prestataire</th><th>Date</th><th>Statut</th><th class="num">Montant</th></tr></thead>
                    <tbody>
                        <tr v-for="b in data.recent_bookings" :key="b.id">
                            <td><span class="mono">{{ b.reference }}</span></td>
                            <td class="cell-main">{{ b.customer }}</td>
                            <td>{{ b.service }}</td>
                            <td class="muted">{{ b.provider }}</td>
                            <td class="muted">{{ shortDate(b.booking_date) }} · {{ b.start_time }}</td>
                            <td><StatusBadge kind="booking" :value="b.status" /></td>
                            <td class="num">{{ fcfa(b.total_price) }}</td>
                        </tr>
                    </tbody>
                </table>
                <EmptyState v-else title="Aucune réservation" text="Les réservations apparaîtront ici dès la première commande." />
            </div>
        </section>
    </template>
</template>
