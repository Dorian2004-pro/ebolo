import { createApp } from 'vue';
import ClientApp from './components/ClientApp.vue';
import '../css/app.css';

const root = document.querySelector('#client-app');

if (root) {
    createApp(ClientApp, {
        screen: root.dataset.screen,
        user: JSON.parse(root.dataset.user || 'null'),
        bookingId: root.dataset.bookingId ? Number(root.dataset.bookingId) : null,
    }).mount(root);
}
