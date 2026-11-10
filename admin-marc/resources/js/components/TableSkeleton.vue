<script setup>
defineProps({ rows: { type: Number, default: 6 } });
</script>

<template>
    <div style="padding: 20px 24px; display: grid; gap: 14px" aria-busy="true" aria-label="Chargement">
        <div v-for="n in rows" :key="n" class="skeleton" style="height: 44px" />
    </div>
</template>
