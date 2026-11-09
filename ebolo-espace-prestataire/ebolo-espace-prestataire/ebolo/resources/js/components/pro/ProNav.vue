<script setup>
import Icon from '@/components/ui/Icon.vue';

const tabs = [
    { to: { name: 'pro.dashboard' }, label: 'Accueil', icon: 'space_dashboard' },
    { to: { name: 'pro.services' }, label: 'Prestations', icon: 'content_cut' },
    { to: { name: 'pro.planning' }, label: 'Planning', icon: 'calendar_month' },
    { to: { name: 'pro.messages' }, label: 'Messages', icon: 'chat_bubble' },
    { to: { name: 'pro.profile' }, label: 'Mon profil', icon: 'badge' },
];
</script>

<template>
    <nav class="pro-nav" aria-label="Navigation espace prestataire">
        <RouterLink v-for="t in tabs" :key="t.label" :to="t.to" custom v-slot="{ href, navigate, isExactActive }">
            <a :href="href" class="tab" :class="{ active: isExactActive }" :aria-current="isExactActive ? 'page' : undefined" @click="navigate">
                <Icon :name="t.icon" :size="23" :filled="isExactActive" />
                <span>{{ t.label }}</span>
            </a>
        </RouterLink>
    </nav>
</template>

<style scoped>
.pro-nav {
    position: fixed; z-index: 40; bottom: 0; left: 50%; transform: translateX(-50%);
    width: min(100%, var(--app-width)); height: 78px;
    padding: 6px 8px 18px;
    display: flex; justify-content: space-around; align-items: center;
    background: rgba(255, 247, 249, .96); backdrop-filter: blur(8px);
    border-top: 1px solid var(--line);
}
.tab {
    flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px;
    color: var(--faint); font: 600 10px var(--font-body); padding: 4px 0;
}
.tab.active { color: var(--plum); }
</style>
