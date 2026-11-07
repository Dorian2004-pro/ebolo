<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import AppShell from './components/AppShell.vue';
import ToastHost from './components/ToastHost.vue';

const route = useRoute();
const bare = computed(() => route.meta.public);
</script>

<template>
    <router-view v-if="bare" />
    <AppShell v-else>
        <router-view />
    </AppShell>
    <ToastHost />
</template>
