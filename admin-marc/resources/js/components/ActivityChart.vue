<script setup>
import { computed, ref } from 'vue';
import { compact, fcfa, number } from '../lib/format';

// Barres = réservations / jour (échelle de gauche), courbe = volume d'affaires FCFA (échelle de droite).
const props = defineProps({
    labels: { type: Array, required: true },
    bookings: { type: Array, required: true },
    gmv: { type: Array, required: true },
});

const W = 760, H = 270, padL = 44, padR = 56, padT = 14, padB = 30;
const innerW = W - padL - padR;
const innerH = H - padT - padB;

const niceMax = (v) => {
    if (v <= 0) return 4;
    const pow = 10 ** Math.floor(Math.log10(v));
    const n = v / pow;
    return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pow;
};

const maxB = computed(() => Math.max(4, niceMax(Math.max(...props.bookings, 0))));
const maxG = computed(() => niceMax(Math.max(...props.gmv, 0)));
const n = computed(() => props.labels.length);
const step = computed(() => innerW / n.value);
const x = (i) => padL + step.value * i + step.value / 2;
const yB = (v) => padT + innerH - (v / maxB.value) * innerH;
const yG = (v) => padT + innerH - (v / maxG.value) * innerH;

const ticks = [0, 0.25, 0.5, 0.75, 1];

const barW = computed(() => Math.min(22, step.value * 0.55));
const linePath = computed(() => props.gmv.map((v, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)} ${yG(v).toFixed(1)}`).join(' '));

const hover = ref(-1);
const wrap = ref(null);
const tip = computed(() => {
    if (hover.value < 0) return null;
    const i = hover.value;
    return { left: (x(i) / W) * 100, top: (Math.min(yG(props.gmv[i]), yB(props.bookings[i])) / H) * 100 };
});

function onMove(e) {
    const rect = e.currentTarget.getBoundingClientRect();
    const px = ((e.clientX - rect.left) / rect.width) * W;
    const i = Math.floor((px - padL) / step.value);
    hover.value = i >= 0 && i < n.value ? i : -1;
}
</script>

<template>
    <div class="chart" ref="wrap">
        <svg :viewBox="`0 0 ${W} ${H}`" role="img" aria-label="Réservations et volume d'affaires sur 14 jours" @mousemove="onMove" @mouseleave="hover = -1">
            <g>
                <line v-for="t in ticks" :key="t" class="chart__grid" :x1="padL" :x2="W - padR" :y1="padT + innerH * (1 - t)" :y2="padT + innerH * (1 - t)" />
                <text v-for="t in ticks" :key="`l${t}`" class="chart__axis" :x="padL - 10" :y="padT + innerH * (1 - t) + 4" text-anchor="end">{{ Math.round(maxB * t) }}</text>
                <text v-for="t in ticks" :key="`r${t}`" class="chart__axis" :x="W - padR + 10" :y="padT + innerH * (1 - t) + 4">{{ compact(maxG * t) }}</text>
            </g>

            <rect
                v-for="(v, i) in bookings" :key="`b${i}`" class="chart__bar" :class="{ 'is-hot': hover === i }"
                :x="x(i) - barW / 2" :y="yB(v)" :width="barW" :height="Math.max(0, padT + innerH - yB(v))" rx="5"
            />

            <line v-if="hover >= 0" class="chart__guide" :x1="x(hover)" :x2="x(hover)" :y1="padT" :y2="padT + innerH" />
            <path class="chart__line" :d="linePath" />
            <circle v-if="hover >= 0" class="chart__dot" :cx="x(hover)" :cy="yG(gmv[hover])" r="5" />

            <text
                v-for="(l, i) in labels" :key="`x${i}`" class="chart__axis" :x="x(i)" :y="H - 8" text-anchor="middle"
                :style="{ display: i % 2 === 0 || n <= 8 ? '' : 'none' }"
            >{{ l }}</text>
        </svg>

        <div v-if="tip" class="chart__tip" :style="{ left: `${tip.left}%`, top: `${tip.top}%` }">
            <b>{{ labels[hover] }}</b>
            {{ number(bookings[hover]) }} réservation{{ bookings[hover] > 1 ? 's' : '' }}<br />
            {{ fcfa(gmv[hover]) }}
        </div>
    </div>

    <div class="legend" style="margin-top: 10px">
        <span><i style="background: var(--gold-400)" />Réservations / jour</span>
        <span><i style="background: var(--plum-700); border-radius: 99px; height: 4px; vertical-align: 2px" />Volume d'affaires (FCFA)</span>
    </div>
</template>
