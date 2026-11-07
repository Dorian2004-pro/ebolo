<script setup>
import { CircleCheck, CircleAlert } from '@lucide/vue';
import { toasts } from '../lib/toast';
</script>

<template>
    <div class="toasts" aria-live="polite">
        <div v-for="t in toasts" :key="t.id" class="toast" :class="`toast--${t.type}`" role="status">
            <CircleAlert v-if="t.type === 'error'" />
            <CircleCheck v-else />
            <span>{{ t.message }}</span>
        </div>
    </div>
</template>
