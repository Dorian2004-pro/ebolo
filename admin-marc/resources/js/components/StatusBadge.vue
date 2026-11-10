<script setup>
import { computed } from 'vue';
import { badge } from '../lib/format';

const props = defineProps({ kind: { type: String, required: true }, value: { type: String, default: '' } });
const b = computed(() => badge(props.kind, props.value));
</script>

<template>
    <span class="badge" :class="`badge--${b.tone}`">{{ b.label }}</span>
</template>
