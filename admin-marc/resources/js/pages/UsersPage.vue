<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { Plus, Pencil, Eye, Ban, RotateCcw, Users as UsersIcon, Trash2 } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import Tabs from '../components/Tabs.vue';
import Pager from '../components/Pager.vue';
import StatusBadge from '../components/StatusBadge.vue';
import EmptyState from '../components/EmptyState.vue';
import TableSkeleton from '../components/TableSkeleton.vue';
import Modal from '../components/Modal.vue';
import UserFormModal from '../components/UserFormModal.vue';
import { useList } from '../lib/useList';
import { api, errorMessage } from '../lib/api';
import { toast } from '../lib/toast';
import { auth } from '../lib/auth';
import { date, ago, initials, roleLabels, number } from '../lib/format';

const route = useRoute();
const list = useList('users', { status: '', role: '', search: typeof route.query.search === 'string' ? route.query.search : '' });

const tabs = computed(() => [
    { value: '', label: 'Tous', count: list.extra.value.counts?.all ?? 0 },
    { value: 'active', label: 'Actifs', count: list.extra.value.counts?.active ?? 0 },
    { value: 'suspended', label: 'Suspendus', count: list.extra.value.counts?.suspended ?? 0 },
    { value: 'inactive', label: 'Inactifs', count: list.extra.value.counts?.inactive ?? 0 },
]);

// --- modales -------------------------------------------------------------
const form = ref(null);       // { user|null }
const detail = ref(null);     // user
const suspending = ref(null); // user
const removing = ref(null);   // user
const reason = ref('');
const busy = ref(false);
const modalError = ref('');

function saved(msg) {
    form.value = null;
    toast(msg);
    list.reload();
}

async function viewUser(u) {
    detail.value = { ...u };
    try { detail.value = (await api.get(`users/${u.id}`)).data.data; } catch { /* on garde la ligne */ }
}

async function suspend() {
    busy.value = true; modalError.value = '';
    try {
        await api.post(`users/${suspending.value.id}/suspend`, { reason: reason.value });
        suspending.value = null; detail.value = null; reason.value = '';
        toast('Compte suspendu');
        list.reload();
    } catch (e) { modalError.value = errorMessage(e); } finally { busy.value = false; }
}

async function activate(u) {
    try {
        await api.post(`users/${u.id}/activate`);
        detail.value = null;
        toast('Compte réactivé');
        list.reload();
    } catch (e) { toast(errorMessage(e), 'error'); }
}

async function remove() {
    busy.value = true; modalError.value = '';
    try {
        await api.delete(`users/${removing.value.id}`);
        removing.value = null; detail.value = null;
        toast('Utilisateur supprimé');
        list.reload();
    } catch (e) { modalError.value = errorMessage(e); } finally { busy.value = false; }
}

const isAdminRow = (u) => u.roles.includes('admin');
onMounted(() => { if (!auth.can('manage_users')) return; });
</script>

<template>
    <PageHead eyebrow="Communauté" sub="Clientes, prestataires et équipe de la plateforme.">
        <template #title>Gestion des <em>utilisateurs</em></template>
        <button class="btn btn--primary" type="button" @click="form = { user: null }"><Plus /> Ajouter un utilisateur</button>
    </PageHead>

    <section class="card">
        <div class="card__body" style="padding-bottom: 0">
            <div class="toolbar">
                <Tabs v-model="list.filters.status" :options="tabs" />
                <div class="filters">
                    <select v-model="list.filters.role" class="select" aria-label="Filtrer par rôle">
                        <option value="">Tous les rôles</option>
                        <option value="customer">Clientes</option>
                        <option value="provider">Prestataires</option>
                        <option value="admin">Administrateurs</option>
                    </select>
                    <input v-model="list.filters.search" class="input" style="width: 260px" type="search" placeholder="Nom, e-mail, téléphone…" aria-label="Rechercher" />
                </div>
            </div>
        </div>

        <div v-if="list.error.value" class="alert" style="margin: 0 24px 20px">{{ list.error.value }}</div>
        <TableSkeleton v-if="list.loading.value && !list.rows.value.length" />

        <div v-else class="table-wrap" :style="{ opacity: list.loading.value ? 0.55 : 1, transition: 'opacity .15s' }">
            <table v-if="list.rows.value.length" class="table">
                <thead><tr><th>Utilisateur</th><th>Contact</th><th>Rôle</th><th>Statut</th><th>Inscription</th><th>Dernière connexion</th><th /></tr></thead>
                <tbody>
                    <tr v-for="u in list.rows.value" :key="u.id">
                        <td>
                            <div class="cell-user">
                                <span class="avatar" :class="isAdminRow(u) ? 'avatar--plum' : u.roles.includes('provider') ? 'avatar--terra' : ''">{{ initials(u.first_name, u.last_name) }}</span>
                                <div><p class="cell-main">{{ u.full_name }}</p><p class="cell-sub">{{ u.city || '—' }}<template v-if="u.bookings_count"> · {{ number(u.bookings_count) }} réservation{{ u.bookings_count > 1 ? 's' : '' }}</template></p></div>
                            </div>
                        </td>
                        <td><p>{{ u.email }}</p><p class="cell-sub">{{ u.phone }}</p></td>
                        <td><span v-for="r in u.roles" :key="r" class="chip" :class="{ 'chip--plum': r === 'admin' }">{{ roleLabels[r] || r }}</span></td>
                        <td><StatusBadge kind="user" :value="u.status" /></td>
                        <td class="muted">{{ date(u.created_at) }}</td>
                        <td class="muted">{{ ago(u.last_login_at) }}</td>
                        <td>
                            <div class="row-actions">
                                <button class="btn btn--soft btn--sm" type="button" @click="viewUser(u)"><Eye /> Voir</button>
                                <button class="btn btn--ghost btn--sm" type="button" @click="form = { user: u }"><Pencil /> Éditer</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <EmptyState v-else title="Aucun utilisateur trouvé" text="Modifiez vos filtres ou votre recherche.">
                <template #icon><UsersIcon /></template>
            </EmptyState>
        </div>

        <Pager :meta="list.meta.value" noun="utilisateurs" @go="list.goTo" />
    </section>

    <!-- Création / édition -->
    <UserFormModal v-if="form" :user="form.user" @close="form = null" @saved="saved" />

    <!-- Détail -->
    <Modal v-if="detail" @close="detail = null">
        <template #title>{{ detail.full_name }}</template>
        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 20px">
            <span class="avatar avatar--lg">{{ initials(detail.first_name, detail.last_name) }}</span>
            <div><StatusBadge kind="user" :value="detail.status" /><p class="cell-sub" style="margin-top: 6px">Inscrit le {{ date(detail.created_at) }}</p></div>
        </div>
        <dl class="dl">
            <dt>E-mail</dt><dd>{{ detail.email }}</dd>
            <dt>Téléphone</dt><dd>{{ detail.phone }}</dd>
            <dt>Ville</dt><dd>{{ detail.city || '—' }}</dd>
            <dt>Rôle</dt><dd><span v-for="r in detail.roles" :key="r" class="chip">{{ roleLabels[r] || r }}</span></dd>
            <template v-if="detail.momo_provider"><dt>Mobile Money</dt><dd>{{ detail.momo_provider }} · {{ detail.momo_number }}</dd></template>
            <dt>Dernière connexion</dt><dd>{{ ago(detail.last_login_at) }}</dd>
            <template v-if="detail.suspension_reason"><dt>Motif de suspension</dt><dd>{{ detail.suspension_reason }}</dd></template>
        </dl>
        <template #footer>
            <button v-if="!isAdminRow(detail)" class="btn btn--danger" type="button" @click="removing = detail; modalError = ''"><Trash2 /> Supprimer</button>
            <button v-if="detail.status === 'suspended' || detail.status === 'inactive'" class="btn btn--ok" type="button" @click="activate(detail)"><RotateCcw /> Réactiver</button>
            <button v-else-if="!isAdminRow(detail)" class="btn btn--danger" type="button" @click="suspending = detail; reason = ''; modalError = ''"><Ban /> Suspendre</button>
            <button class="btn btn--ghost" type="button" @click="form = { user: detail }; detail = null"><Pencil /> Éditer</button>
        </template>
    </Modal>

    <!-- Suspension -->
    <Modal v-if="suspending" :busy="busy" @close="suspending = null">
        <template #title>Suspendre <em>{{ suspending.full_name }}</em> ?</template>
        <p class="muted" style="margin-bottom: 16px">Elle sera déconnectée immédiatement et ne pourra plus réserver ni recevoir de réservations.</p>
        <div v-if="modalError" class="alert" style="margin-bottom: 14px">{{ modalError }}</div>
        <div class="field">
            <label for="reason">Motif (conservé dans le journal d'audit)</label>
            <textarea id="reason" v-model="reason" class="textarea" placeholder="Ex. : annulations répétées le jour même" />
        </div>
        <template #footer>
            <button class="btn btn--ghost" type="button" :disabled="busy" @click="suspending = null">Annuler</button>
            <button class="btn btn--danger" type="button" :disabled="busy || !reason.trim()" @click="suspend"><Ban /> Suspendre le compte</button>
        </template>
    </Modal>

    <!-- Suppression -->
    <Modal v-if="removing" :busy="busy" @close="removing = null">
        <template #title>Supprimer <em>{{ removing.full_name }}</em> ?</template>
        <p class="muted">Le compte est archivé (suppression logique) : il disparaît des listes mais l'historique des réservations est conservé.</p>
        <div v-if="modalError" class="alert" style="margin-top: 14px">{{ modalError }}</div>
        <template #footer>
            <button class="btn btn--ghost" type="button" :disabled="busy" @click="removing = null">Annuler</button>
            <button class="btn btn--danger" type="button" :disabled="busy" @click="remove"><Trash2 /> Supprimer</button>
        </template>
    </Modal>
</template>
