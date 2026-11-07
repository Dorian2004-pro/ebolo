<script setup>
import { Inbox } from '@lucide/vue';
defineProps({ title: { type: String, default: 'Aucun résultat' }, text: { type: String, default: '' } });
</script>

<template>
    <div class="empty">
        <span class="empty__icon"><slot name="icon"><Inbox /></slot></span>
        <h3>{{ title }}</h3>
        <p v-if="text">{{ text }}</p>
        <slot />
    </div>
</template>
