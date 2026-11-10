<script setup>
import { computed } from 'vue';
import { initials } from '@/composables/format';

const props = defineProps({
    src: { type: String, default: null },
    name: { type: String, default: '' },
    size: { type: Number, default: 44 },
    ring: { type: Boolean, default: false },
});
const px = computed(() => ({ width: props.size + 'px', height: props.size + 'px', fontSize: Math.round(props.size * 0.36) + 'px' }));
</script>

<template>
    <img v-if="src" :src="src" :alt="name" class="avatar" :class="{ ring }" :style="px">
    <span v-else class="avatar fallback" :class="{ ring }" :style="px" role="img" :aria-label="name">{{ initials(name) }}</span>
</template>

<style scoped>
.avatar { border-radius: 99px; object-fit: cover; flex: none; display: block; }
.avatar.ring { border: 2px solid var(--gold-2); }
.fallback {
    display: flex; align-items: center; justify-content: center;
    background: var(--blush-2); color: var(--plum-2); font: 700 14px var(--font-head);
}
</style>
