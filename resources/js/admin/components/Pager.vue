<script setup>
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { number } from '../lib/format';

defineProps({ meta: { type: Object, required: true }, noun: { type: String, default: 'résultats' } });
const emit = defineEmits(['go']);
</script>

<template>
    <div v-if="meta.total > 0" class="pager">
        <span>{{ number(meta.total) }} {{ noun }} · page {{ meta.page }} / {{ meta.last_page }}</span>
        <div class="pager__btns">
            <button class="icon-btn" type="button" :disabled="meta.page <= 1" aria-label="Page précédente" @click="emit('go', meta.page - 1)"><ChevronLeft /></button>
            <button class="icon-btn" type="button" :disabled="meta.page >= meta.last_page" aria-label="Page suivante" @click="emit('go', meta.page + 1)"><ChevronRight /></button>
        </div>
    </div>
</template>
