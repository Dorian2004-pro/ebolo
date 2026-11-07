import { createRouter, createWebHistory } from 'vue-router';
import { auth } from './lib/auth';

const Soon = () => import('./pages/ComingSoonPage.vue');

const routes = [
    { path: '/', redirect: '/dashboard' },
    { path: '/login', name: 'login', component: () => import('./pages/LoginPage.vue'), meta: { public: true, title: 'Connexion' } },
    { path: '/dashboard', name: 'dashboard', component: () => import('./pages/DashboardPage.vue'), meta: { title: 'Tableau de bord' } },
    { path: '/users', name: 'users', component: () => import('./pages/UsersPage.vue'), meta: { title: 'Utilisateurs', permission: 'manage_users' } },
    { path: '/providers', name: 'providers', component: () => import('./pages/ProvidersPage.vue'), meta: { title: 'Prestataires', permission: 'manage_providers' } },
    { path: '/services', name: 'services', component: () => import('./pages/ServicesPage.vue'), meta: { title: 'Services', permission: 'manage_services' } },
    { path: '/bookings', name: 'bookings', component: () => import('./pages/BookingsPage.vue'), meta: { title: 'Réservations', permission: 'manage_bookings' } },
    { path: '/payments', name: 'payments', component: () => import('./pages/PaymentsPage.vue'), meta: { title: 'Paiements', permission: 'manage_payments' } },
    { path: '/disputes', name: 'disputes', component: () => import('./pages/DisputesPage.vue'), meta: { title: 'Litiges', permission: 'manage_disputes' } },
    { path: '/content', name: 'content', component: Soon, props: { icon: 'FileText', title: 'Contenu', text: 'Pages légales, FAQ, annonces et bannières.' }, meta: { title: 'Contenu' } },
    { path: '/reports', name: 'reports', component: Soon, props: { icon: 'ChartColumn', title: 'Rapports', text: 'Exports financiers, statistiques par catégorie et journal d\'audit.' }, meta: { title: 'Rapports' } },
    { path: '/settings', name: 'settings', component: Soon, props: { icon: 'Settings', title: 'Paramètres', text: 'Commissions, devise, règles de séquestre et équipe d\'administration.' }, meta: { title: 'Paramètres' } },
    { path: '/support', name: 'support', component: Soon, props: { icon: 'LifeBuoy', title: 'Support', text: 'Tickets et messages des clientes et des prestataires.' }, meta: { title: 'Support' } },
    { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
];

const router = createRouter({
    history: createWebHistory('/admin'),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach((to) => {
    if (!to.meta.public && !auth.isLoggedIn) {
        return { name: 'login', query: to.fullPath !== '/dashboard' ? { redirect: to.fullPath } : {} };
    }
    if (to.name === 'login' && auth.isLoggedIn) return { name: 'dashboard' };
    if (to.meta.permission && !auth.can(to.meta.permission)) return { name: 'dashboard' };
});

router.afterEach((to) => {
    document.title = `${to.meta.title ? `${to.meta.title} · ` : ''}EBOLO Admin`;
});

// Jeton expiré/révoqué pendant la navigation → retour à la connexion.
window.addEventListener('ebolo:session-expired', () => {
    router.replace({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } });
});

export default router;
