<script setup>
// Logo « E » de l'admin généré — conservé à l'identique (dégradé indigo → violet, E blanc).
defineProps({ size: { type: String, default: 'md' } });
</script>

<template>
    <span class="brand-logo" :class="`brand-logo--${size}`" aria-hidden="true">E</span>
</template>
