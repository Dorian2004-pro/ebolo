<script setup>
import { computed } from 'vue';
import { FileText, ChartColumn, Settings, LifeBuoy } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';

const props = defineProps({ icon: { type: String, default: 'Settings' }, title: String, text: String });
const icons = { FileText, ChartColumn, Settings, LifeBuoy };
const Icon = computed(() => icons[props.icon] || Settings);
</script>

<template>
    <PageHead eyebrow="Plateforme">
        <template #title>{{ title }}</template>
    </PageHead>
    <div class="card soon">
        <span class="empty__icon"><component :is="Icon" /></span>
        <h3 class="card__title" style="font-size: 22px">Module <em style="color: var(--terra-700)">bientôt</em> disponible</h3>
        <p class="muted" style="margin-top: 8px; max-width: 52ch; margin-inline: auto">{{ text }} Ce module n'existait pas dans l'admin d'origine (le lien menait à une page vide) ; il sera construit sur la même API Laravel.</p>
    </div>
</template>
