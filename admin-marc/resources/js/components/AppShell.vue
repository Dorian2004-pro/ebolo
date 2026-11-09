<script setup>
import { ref, watch, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    LayoutDashboard, Users, Store, Scissors, CalendarCheck, Wallet, ShieldAlert, FileText,
    ChartColumn, Settings, LifeBuoy, LogOut, Search, Bell, Menu,
} from '@lucide/vue';
import BrandLogo from './BrandLogo.vue';
import { auth } from '../lib/auth';
import { initials } from '../lib/format';

const route = useRoute();
const router = useRouter();
const open = ref(false);

const groups = [
    { label: 'Pilotage', items: [
        { name: 'Tableau de bord', to: '/dashboard', icon: LayoutDashboard },
    ] },
    { label: 'Communauté', items: [
        { name: 'Utilisateurs', to: '/users', icon: Users, perm: 'manage_users' },
        { name: 'Prestataires', to: '/providers', icon: Store, perm: 'manage_providers' },
        { name: 'Services', to: '/services', icon: Scissors, perm: 'manage_services' },
    ] },
    { label: 'Activité', items: [
        { name: 'Réservations', to: '/bookings', icon: CalendarCheck, perm: 'manage_bookings' },
        { name: 'Paiements', to: '/payments', icon: Wallet, perm: 'manage_payments' },
        { name: 'Litiges', to: '/disputes', icon: ShieldAlert, perm: 'manage_disputes' },
    ] },
    { label: 'Plateforme', items: [
        { name: 'Contenu', to: '/content', icon: FileText },
        { name: 'Rapports', to: '/reports', icon: ChartColumn },
        { name: 'Paramètres', to: '/settings', icon: Settings },
        { name: 'Support', to: '/support', icon: LifeBuoy },
    ] },
];

const visible = computed(() => groups
    .map((g) => ({ ...g, items: g.items.filter((i) => !i.perm || auth.can(i.perm)) }))
    .filter((g) => g.items.length));

const isActive = (to) => route.path === to || route.path.startsWith(`${to}/`);

watch(() => route.path, () => { open.value = false; });

async function logout() {
    await auth.logout();
    router.replace({ name: 'login' });
}

const globalSearch = ref('');
function submitSearch() {
    const q = globalSearch.value.trim();
    if (q) router.push({ path: '/users', query: { search: q } });
}
</script>

<template>
    <div class="shell">
        <div v-if="open" class="scrim" @click="open = false" />

        <aside class="sidebar" :class="{ 'is-open': open }" aria-label="Navigation principale">
            <div class="sidebar__brand">
                <BrandLogo />
                <div>
                    <h1>EBOLO</h1>
                    <p>Admin Panel</p>
                </div>
            </div>

            <nav class="sidebar__nav">
                <template v-for="g in visible" :key="g.label">
                    <div class="nav-group">{{ g.label }}</div>
                    <router-link v-for="i in g.items" :key="i.to" :to="i.to" class="nav-item" :class="{ 'is-active': isActive(i.to) }">
                        <component :is="i.icon" aria-hidden="true" />
                        <span>{{ i.name }}</span>
                        <span v-if="i.soon" class="nav-item__soon">bientôt</span>
                    </router-link>
                </template>
            </nav>

            <div class="sidebar__foot">
                <button type="button" @click="logout"><LogOut aria-hidden="true" /> Déconnexion</button>
            </div>
        </aside>

        <div class="shell__main">
            <header class="topbar">
                <div class="topbar__left">
                    <button class="burger" type="button" aria-label="Ouvrir le menu" @click="open = true"><Menu :size="20" /></button>
                    <form class="search" role="search" @submit.prevent="submitSearch">
                        <Search aria-hidden="true" />
                        <input v-model="globalSearch" type="search" placeholder="Rechercher une cliente, un salon…" aria-label="Recherche" />
                    </form>
                </div>

                <div class="topbar__right">
                    <button class="icon-btn" type="button" aria-label="Notifications"><Bell /><span class="dot" /></button>
                    <div v-if="auth.admin" class="me">
                        <div class="me__text">
                            <p class="me__name">{{ auth.admin.firstName }} {{ auth.admin.lastName }}</p>
                            <p class="me__role">{{ auth.admin.adminRole.replace('_', ' ') }}</p>
                        </div>
                        <span class="avatar avatar--plum">{{ initials(auth.admin.firstName, auth.admin.lastName) }}</span>
                    </div>
                </div>
            </header>

            <main class="shell__content">
                <slot />
            </main>
        </div>
    </div>
</template>
