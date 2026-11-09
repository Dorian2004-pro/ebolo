<script setup>
import { useRoute, RouterLink } from 'vue-router';
import Icon from '@/components/ui/Icon.vue';

const route = useRoute();
</script>

<template>
    <section class="soon">
        <span class="badge"><Icon :name="route.meta.icon ?? 'construction'" :size="34" /></span>
        <h1>{{ route.meta.title }}</h1>
        <p>Cet écran fait partie d'une prochaine livraison de l'espace prestataire.</p>
        <RouterLink class="back" :to="{ name: 'pro.dashboard' }">Retour au tableau de bord</RouterLink>
    </section>
</template>

<style scoped>
.soon { padding: 56px 12px; display: flex; flex-direction: column; align-items: center; gap: 10px; text-align: center; }
.badge { width: 70px; height: 70px; border-radius: 99px; background: var(--blush-2); color: var(--plum-2); display: flex; align-items: center; justify-content: center; }
h1 { font: 700 22px var(--font-head); color: var(--plum); }
p { font-size: 14px; line-height: 21px; color: var(--text-2); max-width: 28ch; }
.back { margin-top: 8px; padding: 12px 20px; border-radius: 14px; background: var(--plum); color: #fff; font: 600 14px var(--font-body); }
</style>
