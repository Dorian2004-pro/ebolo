<script setup>
import { computed } from 'vue';
import Avatar from '@/components/ui/Avatar.vue';
import { formatFCFA, formatRange, relativeStart, PAYMENT_LABELS } from '@/composables/format';

const props = defineProps({
    booking: { type: Object, required: true },
    now: { type: Date, required: true },
    detailed: { type: Boolean, default: false },
});

const method = computed(() => PAYMENT_LABELS[props.booking.payment?.method] ?? 'Mobile Money');
const escrow = computed(() => props.booking.payment?.status === 'escrow');
const digits = computed(() => (props.booking.client?.phone ?? '').replace(/\D/g, ''));
const countdown = computed(() => relativeStart(props.booking.starts_at, props.booking.ends_at, props.now));
</script>

<template>
    <article class="card" :class="{ detailed }">
        <div class="row">
            <Avatar :src="booking.client?.avatar_url" :name="booking.client?.name" :size="44" />
            <div class="who">
                <b>{{ booking.client?.name }}</b>
                <i v-if="detailed">{{ booking.service?.name }}</i>
                <span v-else>{{ formatRange(booking.starts_at, booking.ends_at) }} • {{ formatFCFA(booking.service?.price) }} • {{ method }}</span>
            </div>
            <span v-if="detailed" class="pill amber">{{ countdown }}</span>
            <span v-else class="pill green">Confirmé</span>
        </div>

        <template v-if="detailed">
            <div class="slot">
                <span>{{ formatRange(booking.starts_at, booking.ends_at) }}</span>
                <b>{{ formatFCFA(booking.service?.price) }}</b>
                <span class="green-text">{{ method }} {{ escrow ? 'consigné' : 'encaissé' }}</span>
            </div>
            <div v-if="digits" class="actions">
                <a class="btn soft" :href="`tel:+${digits}`">Appeler</a>
                <a class="btn wa" :href="`https://wa.me/${digits}`" target="_blank" rel="noopener">WhatsApp</a>
            </div>
        </template>
    </article>
</template>

<style scoped>
.card { padding: 14px; border-radius: 18px; background: var(--surface); border: 1px solid var(--line); display: flex; flex-direction: column; gap: 10px; }
.row { display: flex; gap: 12px; align-items: center; }
.who { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.who b { font: 700 15px var(--font-head); }
.who i { font-size: 12px; color: var(--rust); }
.who span { font-size: 12px; color: var(--muted); }
.pill { padding: 3px 8px; border-radius: 99px; font: 600 11px var(--font-body); white-space: nowrap; }
.pill.amber { background: var(--amber-bg); color: var(--amber); }
.pill.green { background: var(--green-bg); color: var(--green); }
.slot { padding: 10px; border-radius: 12px; background: var(--blush); display: flex; justify-content: space-between; gap: 8px; font: 500 12px var(--font-body); }
.green-text { color: var(--green); }
.actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.btn { height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font: 600 12px var(--font-body); }
.btn.soft { background: var(--blush-2); color: var(--plum); }
.btn.wa { background: var(--wa); color: #fff; }
</style>
