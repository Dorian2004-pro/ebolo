<script setup>
import Avatar from '@/components/ui/Avatar.vue';
import { formatDay, formatFCFA, formatRange } from '@/composables/format';

defineProps({
    booking: { type: Object, required: true },
    busy: { type: Boolean, default: false },
});
defineEmits(['accept', 'reject']);
</script>

<template>
    <article class="card">
        <div class="row">
            <Avatar :src="booking.client?.avatar_url" :name="booking.client?.name" :size="44" />
            <div class="who">
                <b>{{ booking.client?.name }}</b>
                <i>{{ booking.service?.name }}</i>
            </div>
            <span class="pill">À confirmer</span>
        </div>
        <div class="slot">
            <span>{{ formatDay(booking.starts_at) }} • {{ formatRange(booking.starts_at, booking.ends_at) }}</span>
            <b>{{ formatFCFA(booking.service?.price) }}</b>
        </div>
        <div class="actions">
            <button type="button" class="btn soft" :disabled="busy" @click="$emit('reject', booking)">Refuser</button>
            <button type="button" class="btn main" :disabled="busy" @click="$emit('accept', booking)">Accepter</button>
        </div>
    </article>
</template>

<style scoped>
.card { padding: 14px; border-radius: 18px; background: var(--surface); border: 1px solid var(--line); display: flex; flex-direction: column; gap: 10px; }
.row { display: flex; gap: 12px; align-items: center; }
.who { flex: 1; min-width: 0; display: flex; flex-direction: column; }
.who b { font: 700 15px var(--font-head); }
.who i { font-size: 12px; color: var(--rust); }
.pill { padding: 3px 8px; border-radius: 99px; background: var(--amber-bg); color: var(--amber); font: 600 11px var(--font-body); white-space: nowrap; }
.slot { padding: 10px; border-radius: 12px; background: var(--blush); display: flex; justify-content: space-between; gap: 8px; font: 500 12px var(--font-body); }
.actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.btn { height: 40px; border: none; border-radius: 10px; font: 600 13px var(--font-body); }
.btn.soft { background: var(--blush-2); color: var(--plum); }
.btn.main { background: var(--plum); color: #fff; }
.btn:disabled { opacity: .55; cursor: progress; }
</style>
