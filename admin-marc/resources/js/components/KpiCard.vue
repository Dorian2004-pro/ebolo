<script setup>
import { computed } from 'vue';
import { TrendingUp, TrendingDown, Minus } from '@lucide/vue';

const props = defineProps({
    label: String,
    value: [String, Number],
    icon: { type: Object, required: true },
    tone: { type: String, default: '' },   // '' | gold | plum | ink
    trend: { type: Number, default: null }, // variation en %
    foot: { type: String, default: '' },
});

const t = computed(() => {
    if (props.trend === null || props.trend === undefined) return null;
    if (props.trend > 0) return { cls: 'trend--up', icon: TrendingUp, text: `+${String(props.trend).replace('.', ',')}%` };
    if (props.trend < 0) return { cls: 'trend--down', icon: TrendingDown, text: `${String(props.trend).replace('.', ',')}%` };
    return { cls: 'trend--flat', icon: Minus, text: '0%' };
});
</script>

<template>
    <div class="kpi">
        <div class="kpi__top">
            <span class="kpi__label">{{ label }}</span>
            <span class="kpi__icon" :class="tone && `kpi__icon--${tone}`"><component :is="icon" /></span>
        </div>
        <p class="kpi__value">{{ value }}</p>
        <p class="kpi__foot">
            <span v-if="t" class="trend" :class="t.cls"><component :is="t.icon" />{{ t.text }}</span>
            <span>{{ foot }}</span>
        </p>
    </div>
</template>
