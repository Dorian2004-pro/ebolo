<script setup>
import { useRoute } from 'vue-router';
import ProNav from '@/components/pro/ProNav.vue';
import ToastHost from '@/components/ui/ToastHost.vue';

const route = useRoute();
</script>

<template>
    <div class="shell">
        <main class="screen" :class="{ 'with-nav': !route.meta.hideNav }">
            <RouterView v-slot="{ Component }">
                <component :is="Component" />
            </RouterView>
        </main>
        <ProNav v-if="!route.meta.hideNav" />
        <ToastHost />
    </div>
</template>

<style scoped>
.shell {
    width: min(100%, var(--app-width)); min-height: 100dvh; margin: 0 auto;
    background: var(--bg); position: relative;
}
@media (min-width: 520px) {
    .shell { box-shadow: 0 0 80px -20px rgba(49, 7, 41, .35); }
}
.screen { padding: max(16px, env(safe-area-inset-top)) 20px 28px; }
.screen.with-nav { padding-bottom: 104px; }
</style>
