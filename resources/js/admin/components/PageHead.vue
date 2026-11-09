<script setup>
defineProps({ eyebrow: { type: String, default: '' }, sub: { type: String, default: '' } });
</script>

<template>
    <div class="page-head">
        <div>
            <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
            <h1 class="page-title"><slot name="title" /></h1>
            <p v-if="sub" class="page-sub">{{ sub }}</p>
        </div>
        <div v-if="$slots.default" class="page-actions"><slot /></div>
    </div>
</template>
