<script setup>
import { ref } from 'vue';
import { LifeBuoy, Send } from '@lucide/vue';
import PageHead from '../components/PageHead.vue';
import EmptyState from '../components/EmptyState.vue';
import TableSkeleton from '../components/TableSkeleton.vue';
import Pager from '../components/Pager.vue';
import { useList } from '../lib/useList';
import { api, errorMessage } from '../lib/api';

const list = useList('support');
const drafts = ref({});
const busy = ref(null);
const error = ref('');

async function reply(message) {
    const content = drafts.value[message.user_id]?.trim();
    if (!content || busy.value) return;
    busy.value = message.user_id;
    error.value = '';
    try {
        await api.post(`support/${message.user_id}/reply`, { content });
        drafts.value[message.user_id] = '';
        await list.reload();
    } catch (exception) {
        error.value = errorMessage(exception);
    } finally {
        busy.value = null;
    }
}
</script>

<template>
    <PageHead eyebrow="Relation client" sub="Messages récents liés aux réservations client-prestataire.">
        <template #title>Centre de <em>support</em></template>
    </PageHead>
    <p v-if="error || list.error.value" class="alert" role="alert">{{ error || list.error.value }}</p>
    <section class="card">
        <TableSkeleton v-if="list.loading.value && !list.rows.value.length" />
        <div v-else-if="list.rows.value.length" class="inbox">
            <article v-for="message in list.rows.value" :key="message.id" class="message">
                <header><div><strong>{{ message.user }}</strong><span class="chip">{{ message.role === 'artisan' ? 'Prestataire' : 'Cliente' }}</span></div><time>{{ message.created_at ? new Date(message.created_at).toLocaleString('fr-FR') : '' }}</time></header>
                <p>{{ message.content }}</p>
                <small v-if="message.service">Réservation liée : {{ message.service }}</small>
                <form @submit.prevent="reply(message)">
                    <input v-model="drafts[message.user_id]" class="input" maxlength="2000" placeholder="Répondre…" :disabled="busy === message.user_id">
                    <button class="btn btn--primary" type="submit" :disabled="busy === message.user_id || !drafts[message.user_id]?.trim()"><Send /> Répondre</button>
                </form>
            </article>
        </div>
        <EmptyState v-else-if="!list.loading.value" title="Aucun message de support" text="Les échanges associés à une réservation apparaîtront ici."><template #icon><LifeBuoy /></template></EmptyState>
        <Pager :meta="list.meta.value" noun="messages" @go="list.goTo" />
    </section>
</template>

<style scoped>
.inbox{display:grid}.message{padding:20px 24px;border-bottom:1px solid var(--line)}.message header,.message header>div,.message form{display:flex;align-items:center;gap:10px}.message header{justify-content:space-between}.message header>div{flex-wrap:wrap}.message time,.message small{color:var(--muted);font-size:10px}.message p{margin:14px 0;color:var(--ink);font-size:13px;line-height:1.5}.message form{margin-top:12px}.message form .input{flex:1}
@media(max-width:650px){.message{padding:16px}.message header{align-items:flex-start;flex-direction:column}.message form{align-items:stretch;flex-direction:column}}
</style>
