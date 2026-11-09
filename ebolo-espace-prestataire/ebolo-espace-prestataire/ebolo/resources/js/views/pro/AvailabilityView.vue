<script setup>
import { onMounted, ref } from 'vue';
import { useProStore } from '@/stores/pro';
import { useToastStore } from '@/stores/toast';
import { errorMessage } from '@/services/http';

const pro = useProStore();
const toast = useToastStore();
const saving = ref(false);
const error = ref('');
const names = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
const defaults = () => names.map((_, index) => ({
    day: index + 1,
    open: index < 6,
    from: '08:00',
    to: '18:00',
}));
const days = ref(defaults());

onMounted(async () => {
    if (!pro.loaded) await pro.load();
    if (pro.availability?.days?.length === 7) days.value = pro.availability.days.map((day) => ({ ...day }));
});

async function save() {
    saving.value = true;
    error.value = '';
    try {
        await pro.updateAvailability({ days: days.value });
        toast.show('Vos horaires sont enregistrés.');
    } catch (exception) {
        error.value = errorMessage(exception, 'Impossible d’enregistrer les horaires.');
    } finally {
        saving.value = false;
    }
}
</script>

<template>
    <div class="page">
        <header><span class="eyebrow">ORGANISATION DE VOTRE ACTIVITÉ</span><h1>Mes disponibilités</h1><p>Les clientes pourront demander un rendez-vous sur ces plages horaires.</p></header>
        <form class="schedule" @submit.prevent="save">
            <div v-for="(day, index) in days" :key="day.day" class="day">
                <label class="day-name"><input v-model="day.open" type="checkbox">{{ names[index] }}</label>
                <template v-if="day.open">
                    <label class="sr-only" :for="`from-${day.day}`">Ouverture {{ names[index] }}</label>
                    <input :id="`from-${day.day}`" v-model="day.from" type="time" required>
                    <span>à</span>
                    <label class="sr-only" :for="`to-${day.day}`">Fermeture {{ names[index] }}</label>
                    <input :id="`to-${day.day}`" v-model="day.to" type="time" required>
                </template>
                <span v-else class="closed">Fermé</span>
            </div>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
            <button class="primary" type="submit" :disabled="saving">{{ saving ? 'Enregistrement…' : 'Enregistrer mes horaires' }}</button>
        </form>
    </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 16px; }
header { display: flex; flex-direction: column; gap: 4px; }
.eyebrow { color: var(--rust); font-size: 10px; letter-spacing: .14em; }
h1 { color: var(--plum); font: 700 25px var(--font-head); }
header p { color: var(--text-2); font-size: 13px; line-height: 1.5; }
.schedule { padding: 14px; border: 1px solid var(--line); border-radius: 18px; background: var(--surface); display: flex; flex-direction: column; gap: 10px; }
.day { min-height: 48px; display: flex; align-items: center; gap: 8px; padding: 7px 8px; border-bottom: 1px solid var(--line); color: var(--muted); font-size: 12px; }
.day-name { flex: 1; display: flex; align-items: center; gap: 8px; color: var(--text); font-weight: 600; }
.day input[type="checkbox"] { accent-color: var(--plum); }
.day input[type="time"] { padding: 7px; border: 1px solid var(--line); border-radius: 8px; color: var(--text); }
.closed { margin-right: 8px; }
.primary { min-height: 44px; margin-top: 6px; border: 0; border-radius: 11px; background: var(--plum); color: #fff; font-size: 12px; font-weight: 600; }
.primary:disabled { opacity: .6; }
.error { color: var(--danger); font-size: 12px; }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; }
</style>
