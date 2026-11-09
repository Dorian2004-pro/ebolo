<script setup>
import { computed } from 'vue';
import { number } from '../lib/format';

// segments: [{ label, value, color }]
const props = defineProps({ segments: { type: Array, required: true }, centerLabel: { type: String, default: 'Total' } });

const R = 42;
const C = 2 * Math.PI * R;
const total = computed(() => props.segments.reduce((a, s) => a + s.value, 0));

const arcs = computed(() => {
    let acc = 0;
    return props.segments.filter((s) => s.value > 0).map((s) => {
        const len = (s.value / total.value) * C;
        const arc = { color: s.color, dash: `${Math.max(0, len - 2)} ${C - Math.max(0, len - 2)}`, offset: -acc };
        acc += len;
        return arc;
    });
});
</script>

<template>
    <div class="donut">
        <div class="donut__svg">
            <svg viewBox="0 0 100 100" role="img" aria-label="Répartition des réservations par statut">
                <circle cx="50" cy="50" :r="R" fill="none" stroke="var(--surface-3)" stroke-width="13" />
                <circle
                    v-for="(a, i) in arcs" :key="i" cx="50" cy="50" :r="R" fill="none" :stroke="a.color" stroke-width="13"
                    :stroke-dasharray="a.dash" :stroke-dashoffset="a.offset" stroke-linecap="butt"
                />
            </svg>
            <div class="donut__center"><b>{{ number(total) }}</b><span>{{ centerLabel }}</span></div>
        </div>
        <ul class="plain donut__list">
            <li v-for="s in segments" :key="s.label">
                <i :style="{ background: s.color }" /><span>{{ s.label }}</span><span>{{ number(s.value) }}</span>
            </li>
        </ul>
    </div>
</template>
