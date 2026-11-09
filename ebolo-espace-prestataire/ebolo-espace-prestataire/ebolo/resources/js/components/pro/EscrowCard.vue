<script setup>
import { computed, ref, watch, onBeforeUnmount } from 'vue';
import Icon from '@/components/ui/Icon.vue';
import { formatFCFA, PAYMENT_LABELS } from '@/composables/format';

const props = defineProps({
    booking: { type: Object, required: true },
    busy: { type: Boolean, default: false },
    /** (bookingId, code) => Promise — rejette avec une Error(message) si le code est refusé. */
    release: { type: Function, required: true },
});

const pin = ref('');
const error = ref('');
const done = ref(null); // instantané affiché après un déblocage réussi
const focused = ref(false);
let timer = null;

const view = computed(() => done.value ?? props.booking);
const amount = computed(() => view.value.service?.price ?? 0);
const method = computed(() => PAYMENT_LABELS[view.value.payment?.method] ?? 'Mobile Money');
const digits = computed(() => [0, 1, 2, 3].map((i) => pin.value[i] ?? ''));

const onInput = (e) => {
    pin.value = e.target.value.replace(/\D/g, '').slice(0, 4);
    e.target.value = pin.value;
    error.value = '';
};

async function submit() {
    if (pin.value.length < 4) {
        error.value = 'Saisissez les 4 chiffres du code.';
        return;
    }
    const snapshot = { ...props.booking };
    try {
        await props.release(props.booking.id, pin.value);
        done.value = snapshot;
        pin.value = '';
        timer = setTimeout(() => { done.value = null; }, 4500);
    } catch (e) {
        error.value = e.message;
    }
}

watch(() => props.booking.id, () => { pin.value = ''; error.value = ''; });
onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <section class="escrow" aria-labelledby="escrow-title">
        <header>
            <h2 id="escrow-title">Déblocage Séquestre</h2>
            <span class="pill">Consigné</span>
        </header>
        <p class="hint">
            Prestation achevée ? Entrez le code à 4 chiffres de {{ view.client?.name ? `votre cliente ${view.client.name.split(' ')[0]}` : 'votre cliente' }}.
        </p>

        <form @submit.prevent="submit" novalidate>
            <label class="digits" :class="{ focused }">
                <span class="sr-only">Code à 4 chiffres de la cliente</span>
                <span v-for="(d, i) in digits" :key="i" class="digit" :class="{ current: focused && pin.length === i }">{{ d }}</span>
                <input
                    :value="pin" inputmode="numeric" pattern="[0-9]*" maxlength="4" autocomplete="one-time-code"
                    :disabled="!!done || busy" @input="onInput" @focus="focused = true" @blur="focused = false"
                >
            </label>

            <p v-if="error" class="err" role="alert">{{ error }}</p>

            <p v-if="done" class="ok" role="status">
                <Icon name="task_alt" />{{ formatFCFA(amount) }} crédités sur {{ method }}
            </p>
            <button v-else class="cta" type="submit" :disabled="busy">
                <Icon v-if="busy" name="progress_activity" :size="20" class="spin" />
                <template v-else>Valider et encaisser ({{ formatFCFA(amount) }})</template>
            </button>
        </form>
    </section>
</template>

<style scoped>
.escrow {
    padding: 18px; border-radius: 22px; background: var(--plum-deep); color: #fff;
    display: flex; flex-direction: column; gap: 12px;
    box-shadow: 0 16px 36px -8px rgba(35, 9, 30, .4);
}
header { display: flex; justify-content: space-between; align-items: center; }
h2 { font: 700 18px var(--font-head); }
.pill { padding: 3px 8px; border-radius: 99px; background: rgba(46, 158, 107, .2); color: var(--green-soft); font: 600 11px var(--font-body); }
.hint { font-size: 13px; line-height: 19px; color: rgba(255, 255, 255, .8); }
form { display: flex; flex-direction: column; gap: 12px; }
.digits { position: relative; display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; cursor: text; }
.digit {
    height: 56px; border-radius: 12px; background: rgba(255, 255, 255, .06);
    border: 1px solid rgba(255, 255, 255, .18);
    font: 700 26px var(--font-head); display: flex; align-items: center; justify-content: center;
}
.digit.current { border: 1.5px solid var(--gold); }
.digits input { position: absolute; inset: 0; width: 100%; opacity: 0; font-size: 16px; }
.err { font-size: 12px; color: var(--danger-soft); }
.cta {
    height: 50px; border: none; border-radius: 14px; background: var(--terracotta); color: #fff;
    font: 700 15px var(--font-head); display: flex; align-items: center; justify-content: center;
}
.cta:disabled { opacity: .7; cursor: progress; }
.ok {
    height: 50px; border-radius: 14px; background: rgba(46, 158, 107, .25); color: var(--green-soft);
    font: 600 14px var(--font-body); display: flex; align-items: center; justify-content: center; gap: 8px;
}
.spin { animation: eb-spin .9s linear infinite; }
</style>
