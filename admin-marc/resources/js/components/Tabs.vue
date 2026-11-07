<script setup>
// Onglets de filtre avec compteurs. options: [{ value, label, count? }]
defineProps({ modelValue: { type: String, default: '' }, options: { type: Array, required: true } });
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="tabs" role="tablist">
        <button
            v-for="o in options" :key="o.value" type="button" role="tab" class="tab"
            :class="{ 'is-active': modelValue === o.value }" :aria-selected="modelValue === o.value"
            @click="emit('update:modelValue', o.value)"
        >
            {{ o.label }}<small v-if="o.count !== undefined">{{ o.count }}</small>
        </button>
    </div>
</template>
