<script setup>
import { useToastStore } from '@/stores/toast';
import Icon from './Icon.vue';

const toast = useToastStore();
</script>

<template>
    <Transition name="toast">
        <div v-if="toast.message" class="toast" :class="{ error: toast.tone === 'error' }" role="status" aria-live="polite">
            <Icon :name="toast.tone === 'error' ? 'error' : 'check_circle'" :size="18" />
            <span>{{ toast.message }}</span>
        </div>
    </Transition>
</template>

<style scoped>
.toast {
    position: fixed; z-index: 50; left: 50%; bottom: 96px;
    width: min(calc(100% - 40px), calc(var(--app-width) - 40px));
    transform: translateX(-50%);
    display: flex; align-items: center; gap: 10px;
    padding: 12px 16px; border-radius: 14px;
    background: var(--plum); color: #fff; font: 500 13px var(--font-body);
    box-shadow: 0 16px 36px -4px rgba(74, 29, 63, .4);
}
.toast .ms { color: var(--gold); }
.toast.error { background: #5a1a14; }
.toast.error .ms { color: var(--danger-soft); }
.toast-enter-active, .toast-leave-active { transition: opacity .2s, transform .2s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translate(-50%, 8px); }
</style>
