<script setup>
defineProps({
    name: { type: String, required: true },
    size: { type: Number, default: 24 },
    filled: { type: Boolean, default: false },
});
</script>

<template>
    <span class="ms" :class="{ 'is-filled': filled }" :style="{ fontSize: size + 'px' }" aria-hidden="true">{{ name }}</span>
</template>
