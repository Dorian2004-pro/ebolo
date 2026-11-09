<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import RegistrationWizard from './RegistrationWizard.vue';

const props = defineProps({
    screen: { type: String, required: true },
    user: { type: Object, default: null },
    bookingId: { type: Number, default: null },
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const user = ref(props.user);
const services = ref([]);
const bookings = ref([]);
const account = ref({ name: '', phone: '', city: '', address: '' });
const messages = ref([]);
const selectedConversation = ref(null);
const draftMessage = ref('');
const availability = ref({ days: [], booked: [] });
const paymentProvider = ref('momo');
const checkoutBooking = ref(null);
const confirmation = ref(null);
const favorites = ref(readFavorites());
const reviewDrafts = ref({});
const loading = ref(false);
const busy = ref(false);
const error = ref('');
const search = ref('');
const selectedCategory = ref('Tout');
const selectedService = ref(null);
const fieldErrors = ref({});
const form = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});
const bookingForm = reactive({
    booking_date: '',
    booking_time: '',
    location_type: 'studio',
    location_details: '',
    notes: '',
});
const timeSlots = computed(() => {
    const weekday = new Date(`${bookingForm.booking_date}T12:00:00`).getDay() || 7;
    const day = availability.value.days.find((item) => item.day === weekday);
    if (!day?.open || !day.from || !day.to) return [];
    const slots = [];
    const [fromHour, fromMinute] = day.from.split(':').map(Number);
    const [toHour, toMinute] = day.to.split(':').map(Number);
    let minutes = fromHour * 60 + fromMinute;
    const end = toHour * 60 + toMinute;
    while (minutes < end) {
        const time = `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
        if (!availability.value.booked.some((booking) => booking.date === bookingForm.booking_date && booking.time === time)) {
            slots.push(time);
        }
        minutes += 30;
    }
    return slots;
});

const categories = computed(() => [
    'Tout',
    ...new Set(services.value.map((service) => service.category).filter(Boolean)),
]);

const filteredServices = computed(() => services.value.filter((service) => {
    if (props.screen === 'favorites' && !favorites.value.includes(service.id)) return false;
    const matchesCategory = selectedCategory.value === 'Tout'
        || service.category === selectedCategory.value;
    const searchable = `${service.name} ${service.category ?? ''} ${providerName(service)}`
        .toLocaleLowerCase();

    return matchesCategory && searchable.includes(search.value.toLocaleLowerCase());
}));

const now = new Date();
const minimumDate = [
    now.getFullYear(),
    String(now.getMonth() + 1).padStart(2, '0'),
    String(now.getDate()).padStart(2, '0'),
].join('-');

onMounted(() => {
    if (props.screen === 'catalog') {
        loadServices();
    }

    if (props.screen === 'bookings') {
        loadBookings();
    }

    if (['favorites', 'notifications', 'messages', 'account', 'confirmation'].includes(props.screen)) {
        loadBookings();
    }

    if (props.screen === 'favorites') loadServices();
    if (props.screen === 'account') loadAccount();
    if (props.screen === 'confirmation') loadConfirmation();
});

watch(() => selectedService.value?.artisan_id, (artisanId) => {
    bookingForm.booking_date = '';
    bookingForm.booking_time = '';
    availability.value = { days: [], booked: [] };
    if (artisanId) loadAvailability(artisanId);
});

watch(timeSlots, (slots) => {
    if (!slots.includes(bookingForm.booking_time)) bookingForm.booking_time = '';
});

function providerName(service) {
    return service.artisan?.business_name
        || service.artisan?.user?.name
        || service.artisan?.name
        || 'Artisan partenaire';
}

function formatPrice(price) {
    return new Intl.NumberFormat('fr-FR').format(Number(price || 0)) + ' FCFA';
}

function formatDate(date) {
    if (!date) return 'Date à confirmer';

    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(`${date}T12:00:00`));
}

function readFavorites() {
    try {
        return JSON.parse(localStorage.getItem('ebolo_favorites') || '[]');
    } catch {
        return [];
    }
}

function toggleFavorite(service) {
    favorites.value = favorites.value.includes(service.id)
        ? favorites.value.filter((id) => id !== service.id)
        : [...favorites.value, service.id];
    localStorage.setItem('ebolo_favorites', JSON.stringify(favorites.value));
}

async function loadAvailability(artisanId) {
    try {
        availability.value = await fetchClientData(`/client-api/prestataires/${artisanId}/disponibilites`);
    } catch (exception) {
        error.value = exception.message;
    }
}

function setFormError(response, body) {
    fieldErrors.value = body.errors ?? {};
    error.value = body.message || (response.status === 503
        ? 'Le service est momentanément indisponible.'
        : 'Une erreur est survenue. Vérifiez vos informations et réessayez.');
}

async function submitAuth(endpoint) {
    busy.value = true;
    error.value = '';
    fieldErrors.value = {};

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(form),
        });
        const body = await response.json();

        if (!response.ok) {
            setFormError(response, body);
            return;
        }

        window.location.assign(body.redirect);
    } catch {
        error.value = 'Impossible de joindre le serveur. Vérifiez votre connexion et réessayez.';
    } finally {
        busy.value = false;
    }
}

async function fetchClientData(path) {
    const response = await fetch(path, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    const body = await response.json();

    if (!response.ok) {
        throw new Error(body.message || 'Impossible de charger ces informations.');
    }

    return body;
}

async function loadServices() {
    loading.value = true;
    error.value = '';

    try {
        services.value = await fetchClientData('/client-api/services');
    } catch (exception) {
        error.value = exception.message;
    } finally {
        loading.value = false;
    }
}

async function loadBookings() {
    loading.value = true;
    error.value = '';

    try {
        bookings.value = await fetchClientData('/client-api/rendez-vous');
    } catch (exception) {
        error.value = exception.message;
    } finally {
        loading.value = false;
    }

    async function loadAccount() {
        try {
            const response = await fetchClientData('/client-api/profil');
            account.value = response.user;
        } catch (exception) {
            error.value = exception.message;
        }
    }

    async function saveAccount() {
        busy.value = true;
        error.value = '';
        try {
            const response = await fetch('/client-api/profil', {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(account.value),
            });
            const body = await response.json();
            if (!response.ok) return setFormError(response, body);
            user.value = body.user;
            error.value = 'Votre profil a été mis à jour.';
        } catch {
            error.value = 'Impossible de joindre le serveur. Réessayez.';
        } finally {
            busy.value = false;
        }
    }

    async function loadConversation(booking) {
        const providerId = booking.artisan?.user_id || booking.artisan?.user?.id;
        if (!providerId) {
            error.value = 'Le profil de cette prestataire est indisponible.';
            return;
        }
        selectedConversation.value = { booking, providerId };
        try {
            messages.value = await fetchClientData(`/client-api/messages/${providerId}`);
        } catch (exception) {
            error.value = exception.message;
        }
    }

    async function sendMessage() {
        if (!draftMessage.value.trim() || !selectedConversation.value) return;
        busy.value = true;
        try {
            const response = await fetch('/client-api/messages', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    receiver_id: selectedConversation.value.providerId,
                    content: draftMessage.value.trim(),
                }),
            });
            const body = await response.json();
            if (!response.ok) return setFormError(response, body);
            messages.value.push(body);
            draftMessage.value = '';
        } catch {
            error.value = 'Le message n’a pas pu être envoyé.';
        } finally {
            busy.value = false;
        }
    }

    function notificationsList() {
        return bookings.value.map((booking) => ({
            id: booking.id,
            title: booking.status === 'confirmed' ? 'Rendez-vous confirmé' : 'Demande de rendez-vous envoyée',
            detail: `${booking.service?.name || 'Prestation'} · ${formatDate(booking.booking_date)}`,
            status: booking.payment_status || 'En attente',
        }));
    }

    async function loadConfirmation() {
        await loadBookings();
        try {
            const saved = JSON.parse(sessionStorage.getItem('ebolo_last_confirmation') || 'null');
            if (saved?.booking?.id === props.bookingId) confirmation.value = saved;
        } catch {
            confirmation.value = null;
        }
    }
}

function openBooking(service) {
    selectedService.value = service;
    checkoutBooking.value = null;
    bookingForm.booking_date = '';
    bookingForm.booking_time = '';
    error.value = '';
    fieldErrors.value = {};
}

async function submitReview(booking) {
    const rating = reviewDrafts.value[booking.id];
    if (!rating || !booking.artisan_id || !booking.service_id) {
        error.value = 'Impossible d’envoyer cet avis pour le moment.';
        return;
    }

    busy.value = true;
    error.value = '';
    try {
        const response = await fetch('/client-api/avis', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                artisan_id: booking.artisan_id,
                service_id: booking.service_id,
                rating,
                comment: '',
            }),
        });
        const body = await response.json();
        if (!response.ok) return setFormError(response, body);
        error.value = 'Merci pour votre avis !';
        reviewDrafts.value[booking.id] = undefined;
    } catch {
        error.value = 'Votre avis n’a pas pu être envoyé.';
    } finally {
        busy.value = false;
    }
}

async function submitBooking() {
    if (!checkoutBooking.value && !selectedService.value?.artisan_id) {
        error.value = 'Cette prestation ne peut pas être réservée pour le moment.';
        return;
    }

    busy.value = true;
    error.value = '';
    fieldErrors.value = {};

    try {
        if (!checkoutBooking.value) {
            const response = await fetch('/client-api/rendez-vous', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    ...bookingForm,
                    booking_time: bookingForm.booking_time,
                    artisan_id: selectedService.value.artisan_id,
                    service_id: selectedService.value.id,
                }),
            });
            const body = await response.json();
            if (!response.ok) {
                setFormError(response, body);
                return;
            }
            checkoutBooking.value = body;
        }

        const response = await fetch('/client-api/paiement', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ booking_id: checkoutBooking.value.id, provider: paymentProvider.value }),
        });
        const payment = await response.json();
        if (!response.ok) {
            setFormError(response, payment);
            return;
        }
        sessionStorage.setItem('ebolo_last_confirmation', JSON.stringify({
            booking: checkoutBooking.value,
            payment: payment.payment,
            demo: payment.mode === 'demo',
        }));
        selectedService.value = null;
        window.location.assign(`/confirmation/${checkoutBooking.value.id}`);
    } catch {
        error.value = 'Impossible de joindre le serveur. Réessayez dans quelques instants.';
    } finally {
        busy.value = false;
    }
}

async function logout() {
    busy.value = true;

    try {
        const response = await fetch('/client-api/deconnexion', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });
        const body = await response.json();

        if (!response.ok) {
            error.value = body.message || 'La déconnexion a échoué.';
            return;
        }

        window.location.assign(body.redirect);
    } catch {
        error.value = 'Impossible de joindre le serveur. Réessayez dans quelques instants.';
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div v-if="screen === 'welcome'" class="welcome-page">
        <header class="welcome-header">
            <a class="brand" href="/"><span class="brand-mark">É</span> ébolo<span class="brand-dot">.</span></a>
            <a class="quiet-link" href="/connexion">Déjà membre ? <strong>Se connecter</strong></a>
        </header>
        <main class="welcome-main">
            <section class="welcome-copy">
                <p class="eyebrow"><span class="spark">✦</span> LE BEAUTÉ QUI VOUS RESSEMBLE</p>
                <h1>Votre prochain<br>moment <em>à vous.</em></h1>
                <p class="welcome-description">Découvrez les meilleures expertes beauté près de chez vous et réservez votre prochaine parenthèse, en toute sérénité.</p>
                <div class="welcome-actions">
                    <a class="button button-primary" href="/inscription">Créer mon compte <span>→</span></a>
                    <a class="text-link" href="/connexion">J’ai déjà un compte</a>
                </div>
                <div class="trust-row"><span class="trust-icon">✳</span><span>Des talents vérifiés, près de vous</span><span class="trust-separator">·</span><span>Douala &amp; Yaoundé</span></div>
            </section>
            <aside class="welcome-art" aria-label="Bienvenue sur Ébolo">
                <div class="art-sun"></div><div class="art-arch"></div>
                <div class="art-caption"><span class="art-caption-icon">✦</span><span><strong>La beauté, en toute confiance</strong><small>Les meilleures adresses, réunies pour vous.</small></span></div>
                <div class="art-stamp">VOTRE<br>INSTANT<br><em>précieux</em></div>
            </aside>
        </main>
        <footer class="welcome-footer"><span>© {{ new Date().getFullYear() }} Ébolo Cameroun</span><span>La beauté d’ici, tout simplement.</span></footer>
    </div>

    <RegistrationWizard v-else-if="screen === 'register'" />

    <div v-else-if="screen === 'login'" class="auth-page">
        <aside class="auth-story">
            <a class="brand brand-light" href="/"><span class="brand-mark">É</span> ébolo<span class="brand-dot">.</span></a>
            <div class="auth-story-copy">
                <p class="eyebrow eyebrow-light"><span class="spark">✦</span> BEAUTÉ &amp; BIEN-ÊTRE AU CAMEROUN</p>
                <h1>{{ screen === 'register' ? 'Un petit pas vers' : 'Heureuse de vous' }}<br><em>{{ screen === 'register' ? 'votre grand moment.' : 'retrouver.' }}</em></h1>
                <p>Les plus belles expertes beauté de votre ville, à portée de rendez-vous.</p>
            </div>
            <div class="story-bottom"><span>Douala · Yaoundé · partout au Cameroun</span><span>© {{ new Date().getFullYear() }} Ébolo</span></div>
            <div class="story-orbit"></div>
        </aside>
        <main class="auth-panel">
            <div class="auth-card">
                <a class="brand mobile-brand" href="/"><span class="brand-mark">É</span> ébolo<span class="brand-dot">.</span></a>
                <p class="eyebrow">{{ screen === 'register' ? 'VOTRE COMPTE CLIENT' : 'RAVIE DE VOUS REVOIR' }}</p>
                <h2>{{ screen === 'register' ? 'Créez votre compte' : 'Connectez-vous' }}</h2>
                <p class="auth-intro">{{ screen === 'register' ? 'Quelques informations et votre espace beauté est prêt.' : 'Retrouvez vos adresses et vos rendez-vous favoris.' }}</p>
                <form class="auth-form" @submit.prevent="submitAuth(screen === 'register' ? '/client-api/inscription' : '/client-api/connexion')">
                    <template v-if="screen === 'register'">
                        <label for="name">Nom complet</label>
                        <input id="name" v-model="form.name" autocomplete="name" placeholder="Ex. Vanessa Ebanda" required>
                        <p v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name[0] }}</p>

                        <label for="phone">Téléphone <span class="optional">(facultatif)</span></label>
                        <input id="phone" v-model="form.phone" type="tel" autocomplete="tel" placeholder="+237 6XX XXX XXX">
                        <p v-if="fieldErrors.phone" class="field-error">{{ fieldErrors.phone[0] }}</p>
                    </template>

                    <label for="email">Adresse e-mail</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="email" placeholder="nom@exemple.cm" required>
                    <p v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email[0] }}</p>

                    <label for="password">Mot de passe</label>
                    <input id="password" v-model="form.password" type="password" :autocomplete="screen === 'register' ? 'new-password' : 'current-password'" placeholder="8 caractères minimum" required minlength="8">
                    <p v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password[0] }}</p>

                    <template v-if="screen === 'register'">
                        <label for="password_confirmation">Confirmer le mot de passe</label>
                        <input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" placeholder="Saisissez-le à nouveau" required minlength="8">
                    </template>

                    <p v-if="fieldErrors.backend" class="field-error">{{ fieldErrors.backend[0] }}</p>
                    <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                    <button class="button button-primary submit-button" type="submit" :disabled="busy">
                        <span>{{ busy ? 'Un instant…' : screen === 'register' ? 'Créer mon compte' : 'Se connecter' }}</span><span>→</span>
                    </button>
                </form>
                <p class="auth-switch">
                    {{ screen === 'register' ? 'Déjà cliente ?' : 'Pas encore de compte ?' }}
                    <a :href="screen === 'register' ? '/connexion' : '/inscription'">{{ screen === 'register' ? 'Se connecter' : 'Créer un compte' }}</a>
                </p>
                <p class="privacy-note"><span>✦</span> Vos informations restent confidentielles.</p>
            </div>
        </main>
    </div>

    <div v-else class="client-layout">
        <header class="client-header">
            <a class="brand" href="/"><span class="brand-mark">É</span> ébolo<span class="brand-dot">.</span></a>
            <nav class="client-nav" aria-label="Navigation principale">
                <a href="/" :class="{ active: screen === 'catalog' }">Découvrir</a>
                <a href="/mes-rendez-vous" :class="{ active: screen === 'bookings' }">Mes rendez-vous</a>
                <a href="/favoris" :class="{ active: screen === 'favorites' }">Favoris</a>
                <a href="/messages" :class="{ active: screen === 'messages' }">Messages</a>
            </nav>
            <div class="client-account">
                <span class="avatar">{{ (user?.name || 'C').charAt(0).toUpperCase() }}</span>
                <a class="account-name" href="/mon-compte">{{ user?.name || 'Mon compte' }}</a>
                <a class="notification-link" href="/notifications">Notifications</a>
                <button class="logout-button" type="button" :disabled="busy" @click="logout">Quitter</button>
            </div>
        </header>

        <main class="client-main">
            <section v-if="screen === 'account'" class="bookings-section">
                <p class="eyebrow">ESPACE PRIVILÈGE · COMPTE CLIENT</p><h1>Mon compte</h1>
                <form class="account-form auth-form" @submit.prevent="saveAccount">
                    <label for="account-name">Nom complet</label><input id="account-name" v-model="account.name" required maxlength="255">
                    <label for="account-phone">Téléphone</label><input id="account-phone" v-model="account.phone" type="tel" maxlength="20">
                    <label for="account-city">Ville</label><input id="account-city" v-model="account.city" maxlength="255">
                    <label for="account-address">Adresse habituelle</label><textarea id="account-address" v-model="account.address" rows="3" maxlength="1000"></textarea>
                    <p v-if="error" class="form-error" role="status">{{ error }}</p>
                    <button class="button button-primary submit-button" type="submit" :disabled="busy">Enregistrer mon profil <span>→</span></button>
                </form>
                <a class="text-link" href="/mes-rendez-vous">Retrouver mes rendez-vous →</a>
            </section>

            <section v-else-if="screen === 'notifications'" class="bookings-section">
                <p class="eyebrow">VOTRE ACTIVITÉ</p><h1>Notifications</h1>
                <p v-if="loading" class="loading-state">Chargement…</p>
                <div v-else-if="notificationsList().length" class="booking-list">
                    <article v-for="notification in notificationsList()" :key="notification.id" class="booking-card">
                        <span class="booking-flower">✦</span><div class="booking-detail"><h2>{{ notification.title }}</h2><p>{{ notification.detail }}</p><small>{{ notification.status }}</small></div>
                        <a class="button button-outline" href="/mes-rendez-vous">Voir mes rendez-vous</a>
                    </article>
                </div>
                <div v-else class="empty-state"><h3>Aucune notification pour le moment.</h3><a href="/">Découvrir les prestations</a></div>
            </section>

            <section v-else-if="screen === 'messages'" class="bookings-section">
                <p class="eyebrow">ÉCHANGE AVEC VOS PRESTATAIRES</p><h1>Messages</h1>
                <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                <p v-if="loading" class="loading-state">Chargement des conversations…</p>
                <div v-else class="client-messages">
                    <aside class="message-bookings">
                        <button v-for="booking in bookings.filter((item) => item.artisan?.user_id)" :key="booking.id" type="button" @click="loadConversation(booking)">
                            {{ booking.artisan?.business_name || booking.artisan?.user?.name }} · {{ booking.service?.name }}
                        </button>
                    </aside>
                    <section v-if="selectedConversation" class="client-conversation">
                        <div class="client-message-list">
                            <p v-if="!messages.length" class="empty-state">Démarrez la conversation avec {{ selectedConversation.booking.artisan?.user?.name }}.</p>
                            <article v-for="message in messages" :key="message.id" class="client-bubble" :class="{ mine: message.sender_id === user?.id }">{{ message.content }}</article>
                        </div>
                        <form class="client-composer" @submit.prevent="sendMessage">
                            <input v-model="draftMessage" maxlength="2000" placeholder="Écrire un message…" :disabled="busy">
                            <button class="button button-primary" type="submit" :disabled="busy || !draftMessage.trim()">Envoyer</button>
                        </form>
                    </section>
                    <p v-else class="empty-state">Choisissez une réservation pour contacter votre prestataire.</p>
                </div>
            </section>

            <section v-else-if="screen === 'confirmation'" class="bookings-section confirmation-page">
                <p class="eyebrow">CONFIRMATION &amp; REÇU</p><h1>Votre rendez-vous est réservé.</h1>
                <article v-if="confirmation" class="confirmation-card">
                    <p><strong>{{ confirmation.booking.reference }}</strong></p>
                    <h2>{{ confirmation.booking.service?.name }}</h2>
                    <p>{{ confirmation.booking.artisan?.business_name || confirmation.booking.artisan?.user?.name }}</p>
                    <p>{{ formatDate(confirmation.booking.booking_date) }} · {{ confirmation.booking.booking_time }}</p>
                    <p class="price">{{ formatPrice(confirmation.booking.total_price) }}</p>
                    <div class="demo-notice">Paiement de démonstration : aucun montant réel n’a été débité. Le séquestre est simulé pour la présentation.</div>
                    <div class="escrow-pin"><span>CODE CLIENT À GARDER SECRET</span><strong>{{ confirmation.booking.escrow_code }}</strong></div>
                    <a class="button button-primary" href="/mes-rendez-vous">Voir mes rendez-vous <span>→</span></a>
                </article>
                <p v-else class="empty-state">Le reçu de cette session n’est plus disponible sur cet appareil. Retrouvez la réservation dans <a href="/mes-rendez-vous">Mes rendez-vous</a>.</p>
            </section>

            <template v-else-if="screen === 'catalog' || screen === 'favorites'">
                <section class="catalog-hero">
                    <div><p class="eyebrow">DOUALA &amp; YAOUNDÉ · AUJOURD’HUI, RIEN QUE POUR VOUS</p><h1>Bonjour{{ user?.name ? `, ${user.name.split(' ')[0]}` : '' }}.<br><em>On prend soin de vous ?</em></h1><p>Les gestes attentionnés et les belles adresses commencent ici.</p></div>
                    <div class="hero-flower" aria-hidden="true">✿</div>
                </section>
                <section class="discover-section">
                    <div class="section-heading">                    <div><p class="eyebrow">LE CARNET ÉBOLO</p><h2>{{ screen === 'favorites' ? 'Mes prestations favorites' : 'À découvrir près de vous' }}</h2></div><label class="search-box"><span>⌕</span><input v-model="search" aria-label="Rechercher une prestation" placeholder="Une envie, une adresse…"></label></div>
                    <div class="category-list" aria-label="Filtrer par catégorie">
                        <button v-for="category in categories" :key="category" type="button" :class="{ selected: selectedCategory === category }" @click="selectedCategory = category">{{ category }}</button>
                    </div>
                    <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                    <div v-if="loading" class="loading-state">Nous préparons votre sélection…</div>
                    <div v-else-if="filteredServices.length" class="service-grid">
                        <article v-for="(service, index) in filteredServices" :key="service.id" class="service-card">
                            <div class="service-art" :class="`service-art-${index % 4}`"><span class="service-category">{{ service.category || 'Soin' }}</span><span class="service-art-mark">{{ ['✿', '❋', '✦', '❀'][index % 4] }}</span></div>
                            <div class="service-content">
                                <button class="favorite-toggle" type="button" :aria-pressed="favorites.includes(service.id)" @click="toggleFavorite(service)">{{ favorites.includes(service.id) ? '♥ Retiré des favoris' : '♡ Ajouter aux favoris' }}</button>
                                <p class="provider-name">{{ providerName(service) }}</p>
                                <h3>{{ service.name }}</h3>
                                <p class="service-description">{{ service.description || 'Un moment de soin personnalisé, réalisé avec attention.' }}</p>
                                <div class="service-meta"><span>{{ service.duration_minutes || '—' }} min</span><strong>{{ formatPrice(service.price) }}</strong></div>
                                <button class="button button-outline" type="button" @click="openBooking(service)">Réserver ce soin <span>→</span></button>
                            </div>
                        </article>
                    </div>
                    <div v-else-if="!loading && !error" class="empty-state"><span>✿</span><h3>{{ screen === 'favorites' ? 'Aucun favori pour le moment.' : 'Votre prochaine belle adresse se prépare.' }}</h3><p>Aucune prestation ne correspond à cette recherche pour le moment.</p></div>
                </section>
            </template>

            <section v-else class="bookings-section">
                <p class="eyebrow">VOTRE CARNET PERSONNEL</p><h1>Vos rendez-vous<br><em>en un seul endroit.</em></h1>
                <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                <p v-if="loading" class="loading-state">Chargement de vos rendez-vous…</p>
                <div v-else-if="bookings.length" class="booking-list">
                    <article v-for="booking in bookings" :key="booking.id" class="booking-card">
                        <span class="booking-flower">✿</span>
                        <div class="booking-detail"><p class="eyebrow">{{ booking.service?.category || 'VOTRE RENDEZ-VOUS' }}</p><h2>{{ booking.service?.name || 'Prestation réservée' }}</h2><p>{{ booking.artisan?.user?.name || 'Votre experte beauté' }}</p></div>
                        <div class="booking-date"><strong>{{ formatDate(booking.booking_date) }}</strong><span>{{ booking.booking_time }}</span></div>
                        <span class="booking-status">{{ booking.status === 'confirmed' ? 'Confirmé' : booking.status === 'cancelled' ? 'Annulé' : 'En attente' }}</span>
                        <button v-if="booking.artisan?.user_id" class="text-link" type="button" @click="loadConversation(booking)">Contacter</button>
                        <form v-if="booking.status === 'completed'" class="review-form" @submit.prevent="submitReview(booking)">
                            <label>Donner une note <select v-model.number="reviewDrafts[booking.id]"><option :value="undefined">Choisir</option><option v-for="rating in [5,4,3,2,1]" :key="rating" :value="rating">{{ rating }} / 5</option></select></label>
                            <button class="text-link" type="submit">Envoyer un avis</button>
                        </form>
                    </article>
                </div>
                <div v-else-if="!loading && !error" class="empty-state"><span>✿</span><h3>Votre premier rendez-vous vous attend.</h3><p>Découvrez nos prestations et trouvez l’adresse qui vous ressemble.</p><a class="button button-primary" href="/">Découvrir les prestations <span>→</span></a></div>
            </section>
        </main>

        <footer class="client-footer"><span>Ébolo — la beauté d’ici, tout simplement.</span><span>Douala · Yaoundé</span></footer>

        <div v-if="selectedService" class="modal-backdrop" @click.self="selectedService = null">
            <section class="booking-modal" role="dialog" aria-modal="true" aria-labelledby="booking-title">
                <button class="modal-close" type="button" aria-label="Fermer" @click="selectedService = null">×</button>
                <p class="eyebrow">VOTRE MOMENT ÉBOLO</p><h2 id="booking-title">Réserver cette prestation</h2>
                <p class="modal-service">{{ selectedService.name }} · {{ formatPrice(selectedService.price) }}</p>
                <form class="auth-form" @submit.prevent="submitBooking">
                    <label for="booking_date">Date souhaitée</label><input id="booking_date" v-model="bookingForm.booking_date" type="date" :min="minimumDate" required>
                    <p v-if="fieldErrors.booking_date" class="field-error">{{ fieldErrors.booking_date[0] }}</p>
                    <label for="booking_time">Créneau disponible</label><select id="booking_time" v-model="bookingForm.booking_time" required><option value="">Choisir un créneau</option><option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option></select>
                    <p v-if="bookingForm.booking_date && !timeSlots.length" class="field-error">Aucun créneau disponible à cette date.</p>
                    <label for="location_type">Lieu de la prestation</label><select id="location_type" v-model="bookingForm.location_type"><option value="studio">Au studio</option><option value="salon">Au salon</option><option value="home">À domicile</option></select>
                    <template v-if="bookingForm.location_type === 'home'"><label for="location_details">Adresse de rendez-vous</label><input id="location_details" v-model="bookingForm.location_details" autocomplete="street-address" placeholder="Quartier, rue, repère" required></template>
                    <label for="notes">Un détail à partager <span class="optional">(facultatif)</span></label><textarea id="notes" v-model="bookingForm.notes" rows="2" placeholder="Une précision pour votre experte…"></textarea>
                    <label for="payment_provider">Paiement de démonstration</label><select id="payment_provider" v-model="paymentProvider"><option value="momo">MTN Mobile Money (simulation)</option><option value="orange">Orange Money (simulation)</option></select>
                    <p class="demo-notice">Pour la présentation uniquement : ce parcours ne débite pas d’argent réel.</p>
                    <p v-if="checkoutBooking" class="form-error">Réservation créée. Relance le paiement démo sans recréer la réservation.</p>
                    <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                    <button class="button button-primary submit-button" type="submit" :disabled="busy"><span>{{ busy ? 'Un instant…' : 'Confirmer ma demande' }}</span><span>→</span></button>
                </form>
            </section>
        </div>
    </div>
</template>
