<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import * as api from '@/services/api';
import { errorMessage } from '@/services/http';
import { useProStore } from '@/stores/pro';
import Avatar from '@/components/ui/Avatar.vue';

const pro = useProStore();
const selected = ref(null);
const messages = ref([]);
const draft = ref('');
const loadingMessages = ref(false);
const sending = ref(false);
const error = ref('');

const clients = computed(() => {
    const known = new Map();
    for (const booking of pro.bookings) {
        const client = booking.client;
        if (client?.id && !known.has(client.id)) known.set(client.id, client);
    }
    return [...known.values()];
});

onMounted(async () => {
    if (!pro.loaded) await pro.load();
    if (clients.value.length) selected.value = clients.value[0];
});

watch(selected, async (client) => {
    messages.value = [];
    error.value = '';
    if (!client) return;
    loadingMessages.value = true;
    try {
        messages.value = await api.getConversation(client.id);
    } catch (e) {
        error.value = errorMessage(e, 'Impossible de charger cette conversation.');
    } finally {
        loadingMessages.value = false;
    }
});

async function send() {
    const content = draft.value.trim();
    if (!content || !selected.value || sending.value) return;
    sending.value = true;
    error.value = '';
    try {
        const message = await api.sendMessage(selected.value.id, content);
        messages.value.push(message);
        draft.value = '';
    } catch (e) {
        error.value = errorMessage(e, 'Message non envoyé.');
    } finally {
        sending.value = false;
    }
}
</script>

<template>
    <div class="page">
        <header><span class="eyebrow">ÉCHANGE CLIENTES</span><h1>Messages</h1><p>Discutez avec les clientes ayant réservé une prestation.</p></header>
        <p v-if="pro.loading && !pro.loaded" class="notice">Chargement des conversations…</p>
        <p v-else-if="pro.error && !pro.loaded" class="error" role="alert">{{ pro.error }}</p>
        <p v-else-if="!clients.length" class="notice">Aucune conversation pour le moment. Les clientes apparaîtront après une réservation.</p>
        <template v-else>
            <nav class="contacts" aria-label="Conversations">
                <button v-for="client in clients" :key="client.id" type="button" class="contact" :class="{ active: selected?.id === client.id }" @click="selected = client">
                    <Avatar :src="client.avatar_url" :name="client.name" :size="38" /><span>{{ client.name }}</span>
                </button>
            </nav>
            <section class="conversation" aria-live="polite">
                <div class="messages">
                    <p v-if="loadingMessages" class="notice">Chargement des messages…</p>
                    <p v-else-if="!messages.length" class="empty">Démarrez la conversation avec {{ selected?.name }}.</p>
                    <article v-for="message in messages" :key="message.id" class="bubble" :class="{ mine: message.sender_id !== selected?.id }">
                        <p>{{ message.content }}</p><time>{{ new Date(message.created_at).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' }) }}</time>
                    </article>
                </div>
                <form class="composer" @submit.prevent="send">
                    <label class="sr-only" for="message">Votre message</label>
                    <input id="message" v-model="draft" maxlength="2000" placeholder="Écrire un message…" :disabled="sending">
                    <button type="submit" aria-label="Envoyer" :disabled="sending || !draft.trim()">Envoyer</button>
                </form>
            </section>
            <p v-if="error" class="error" role="alert">{{ error }}</p>
        </template>
    </div>
</template>

<style scoped>
.page { display: flex; flex-direction: column; gap: 14px; }
header { display: flex; flex-direction: column; gap: 4px; }
.eyebrow { color: var(--rust); font-size: 10px; letter-spacing: .14em; }
h1 { color: var(--plum); font: 700 25px var(--font-head); }
header p { color: var(--text-2); font-size: 13px; }
.contacts { display: flex; gap: 8px; overflow-x: auto; padding: 2px 0; }
.contact { min-width: 115px; padding: 8px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); display: flex; align-items: center; gap: 7px; color: var(--text); font-size: 11px; }
.contact.active { border-color: var(--plum); background: var(--blush); }
.contact span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.conversation { min-height: 48vh; display: flex; flex-direction: column; border: 1px solid var(--line); border-radius: 18px; overflow: hidden; background: var(--surface); }
.messages { flex: 1; max-height: 55vh; overflow-y: auto; padding: 14px; display: flex; flex-direction: column; align-items: flex-start; gap: 10px; }
.bubble { max-width: 85%; padding: 10px 12px; border-radius: 14px 14px 14px 4px; background: var(--blush-2); overflow-wrap: anywhere; }
.bubble.mine { align-self: flex-end; border-radius: 14px 14px 4px 14px; background: var(--plum); color: #fff; }
.bubble p { font-size: 13px; line-height: 1.45; }
.bubble time { display: block; margin-top: 5px; opacity: .7; font-size: 9px; }
.empty, .notice { margin: auto; color: var(--muted); text-align: center; font-size: 12px; }
.composer { display: flex; gap: 8px; padding: 10px; border-top: 1px solid var(--line); }
.composer input { flex: 1; min-width: 0; padding: 0 11px; border: 1px solid var(--line); border-radius: 11px; }
.composer button { padding: 0 14px; border: 0; border-radius: 11px; background: var(--plum); color: #fff; font-size: 12px; }
.composer button:disabled { opacity: .5; }
.error { color: var(--danger); font-size: 12px; }
</style>
