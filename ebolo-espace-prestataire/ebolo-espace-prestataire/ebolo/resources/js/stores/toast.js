import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useToastStore = defineStore('toast', () => {
    const message = ref('');
    const tone = ref('info'); // info | error
    let timer = null;

    const show = (text, t = 'info', ms = 3000) => {
        message.value = text;
        tone.value = t;
        clearTimeout(timer);
        timer = setTimeout(() => { message.value = ''; }, ms);
    };

    return { message, tone, show, error: (text) => show(text, 'error', 4000) };
});
