<script setup>
import { onMounted, onBeforeUnmount } from 'vue';
import { X } from '@lucide/vue';

const props = defineProps({ wide: Boolean, busy: Boolean });
const emit = defineEmits(['close']);

const onKey = (e) => { if (e.key === 'Escape' && !props.busy) emit('close'); };
onMounted(() => { document.addEventListener('keydown', onKey); document.body.style.overflow = 'hidden'; });
onBeforeUnmount(() => { document.removeEventListener('keydown', onKey); document.body.style.overflow = ''; });
</script>

<template>
    <Teleport to="body">
        <div class="modal-back" @mousedown.self="!busy && emit('close')">
            <div class="modal" :class="{ 'modal--wide': wide }" role="dialog" aria-modal="true">
                <div class="modal__head">
                    <h2 class="modal__title"><slot name="title" /></h2>
                    <button class="icon-btn" type="button" aria-label="Fermer" :disabled="busy" @click="emit('close')"><X /></button>
                </div>
                <div class="modal__body"><slot /></div>
                <div v-if="$slots.footer" class="modal__foot"><slot name="footer" /></div>
            </div>
        </div>
    </Teleport>
</template>
