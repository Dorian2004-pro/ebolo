import { createRouter, createWebHistory } from 'vue-router';
import ProLayout from '@/layouts/ProLayout.vue';
import AuthView from '@/views/AuthView.vue';

const routes = [
    { path: '/', redirect: '/pro' },
    { path: '/connexion', name: 'pro.login', component: AuthView, meta: { title: 'Connexion', guestOnly: true } },
    { path: '/inscription', name: 'pro.register', component: AuthView, meta: { title: 'Inscription', guestOnly: true } },
    {
        path: '/pro',
        component: ProLayout,
        children: [
            { path: '', name: 'pro.dashboard', component: () => import('@/views/pro/DashboardView.vue'), meta: { title: 'Tableau de bord' } },
            { path: 'profil', name: 'pro.profile', component: () => import('@/views/pro/ProfileView.vue'), meta: { title: 'Mon profil', icon: 'badge' } },
            { path: 'disponibilites', name: 'pro.availability', component: () => import('@/views/pro/AvailabilityView.vue'), meta: { title: 'Mes disponibilités', icon: 'schedule' } },
            { path: 'verification', name: 'pro.verification', component: () => import('@/views/pro/IdentityVerificationView.vue'), meta: { title: 'Vérification prestataire', hideNav: true } },
            { path: 'prestations', name: 'pro.services', component: () => import('@/views/pro/ServicesView.vue'), meta: { title: 'Prestations', icon: 'content_cut' } },
            { path: 'planning', name: 'pro.planning', component: () => import('@/views/pro/PlanningView.vue'), meta: { title: 'Planning', icon: 'calendar_month' } },
            { path: 'messages', name: 'pro.messages', component: () => import('@/views/pro/MessagesView.vue'), meta: { title: 'Messages', icon: 'chat_bubble' } },
        ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/pro' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.afterEach((to) => {
    document.title = `${to.meta.title ?? 'Espace Pro'} • Ébolo`;
});

router.beforeEach((to) => {
    const hasToken = Boolean(localStorage.getItem('ebolo_token'));

    if (to.meta.guestOnly && hasToken) return { name: 'pro.dashboard' };
    if (!to.meta.guestOnly && !hasToken) return { name: 'pro.login' };
});

export default router;
