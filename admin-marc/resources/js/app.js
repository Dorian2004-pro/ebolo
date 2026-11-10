import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { auth } from './lib/auth';

// Restaure la session (jeton en localStorage) avant le premier rendu pour éviter tout clignotement.
auth.boot().finally(() => {
    createApp(App).use(router).mount('#app');
});
