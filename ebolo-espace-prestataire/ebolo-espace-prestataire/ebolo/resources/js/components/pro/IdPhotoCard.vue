<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Icon from '@/components/ui/Icon.vue';

const props = defineProps({
    title: { type: String, required: true },
    prompt: { type: String, default: 'Scannez votre pièce' },
    modelValue: { type: File, default: null },
    error: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue', 'invalid']);

const camera = ref(null);
const gallery = ref(null);
const previewUrl = ref('');

watch(() => props.modelValue, (file) => {
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = file ? URL.createObjectURL(file) : '';
}, { immediate: true });
onBeforeUnmount(() => previewUrl.value && URL.revokeObjectURL(previewUrl.value));

const MAX_BYTES = 8 * 1024 * 1024;
const MIN_SIDE = 800; // « clichés haute résolution »

const measure = (file) => new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => { URL.revokeObjectURL(url); resolve(Math.max(img.naturalWidth, img.naturalHeight)); };
    img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('unreadable')); };
    img.src = url;
});

async function pick(e) {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return emit('invalid', 'Format non pris en charge (JPG, PNG ou WebP).');
    if (file.size > MAX_BYTES) return emit('invalid', 'Image trop lourde (8 Mo maximum).');
    try {
        if ((await measure(file)) < MIN_SIDE) return emit('invalid', 'Résolution insuffisante : rapprochez-vous de la pièce.');
    } catch {
        return emit('invalid', "Image illisible. Choisissez un autre fichier.");
    }
    emit('update:modelValue', file);
}

const has = computed(() => !!props.modelValue);
</script>

<template>
    <div class="card">
        <div class="head">
            <b>{{ title }}</b>
            <span class="tag" :class="has ? 'ok' : 'todo'">{{ has ? 'Ajouté' : 'À téléverser' }}</span>
        </div>

        <div v-if="has" class="preview">
            <img :src="previewUrl" :alt="`Aperçu : ${title}`">
            <div class="row">
                <button type="button" class="btn soft" @click="gallery.click()">Remplacer</button>
                <button type="button" class="btn ghost" @click="emit('update:modelValue', null)">Retirer</button>
            </div>
        </div>

        <div v-else class="empty">
            <Icon name="document_scanner" :size="30" />
            <b>{{ prompt }}</b>
            <span>4 coins visibles, sans reflet de flash.</span>
            <div class="row">
                <button type="button" class="btn main" @click="camera.click()">Prendre photo</button>
                <button type="button" class="btn soft" @click="gallery.click()">Depuis galerie</button>
            </div>
        </div>

        <p v-if="error" class="err" role="alert">{{ error }}</p>

        <input ref="camera" type="file" accept="image/*" capture="environment" class="sr-only" tabindex="-1" @change="pick">
        <input ref="gallery" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" @change="pick">
    </div>
</template>

<style scoped>
.card { padding: 14px; border-radius: 18px; background: var(--surface); border: 1px solid var(--line); display: flex; flex-direction: column; gap: 10px; }
.head { display: flex; justify-content: space-between; align-items: center; font: 600 13px var(--font-body); }
.tag { font-size: 11px; padding: 2px 8px; border-radius: 99px; }
.tag.ok { background: var(--green-bg); color: var(--green); }
.tag.todo { background: var(--amber-bg); color: var(--amber); }
.empty { padding: 18px; border-radius: 12px; background: var(--blush); display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; color: var(--plum); }
.empty b { font: 600 14px var(--font-body); color: var(--text); }
.empty span:not(.ms) { font-size: 12px; color: var(--muted); }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; width: 100%; }
.btn { height: 44px; border: none; border-radius: 12px; font: 600 13px var(--font-body); }
.btn.main { background: var(--plum); color: #fff; }
.btn.soft { background: var(--blush-2); color: var(--plum); }
.btn.ghost { background: transparent; color: var(--danger); border: 1px solid var(--line); }
.preview { display: flex; flex-direction: column; gap: 10px; }
.preview img { width: 100%; height: 150px; object-fit: cover; border-radius: 12px; background: var(--blush-2); }
.err { font-size: 12px; color: var(--danger); }
</style>
