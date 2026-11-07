<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    screen: { type: String, required: true },
    user: { type: Object, default: null },
});

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const user = ref(props.user);
const services = ref([]);
const bookings = ref([]);
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

const categories = computed(() => [
    'Tout',
    ...new Set(services.value.map((service) => service.category).filter(Boolean)),
]);

const filteredServices = computed(() => services.value.filter((service) => {
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
}

function openBooking(service) {
    selectedService.value = service;
    error.value = '';
    fieldErrors.value = {};
}

async function submitBooking() {
    if (!selectedService.value?.artisan_id) {
        error.value = 'Cette prestation ne peut pas être réservée pour le moment.';
        return;
    }

    busy.value = true;
    error.value = '';
    fieldErrors.value = {};

    try {
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
                artisan_id: selectedService.value.artisan_id,
                service_id: selectedService.value.id,
            }),
        });
        const body = await response.json();

        if (!response.ok) {
            setFormError(response, body);
            return;
        }

        selectedService.value = null;
        window.location.assign('/mes-rendez-vous');
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

    <div v-else-if="screen === 'register' || screen === 'login'" class="auth-page">
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
            </nav>
            <div class="client-account">
                <span class="avatar">{{ (user?.name || 'C').charAt(0).toUpperCase() }}</span>
                <span class="account-name">{{ user?.name || 'Mon compte' }}</span>
                <button class="logout-button" type="button" :disabled="busy" @click="logout">Quitter</button>
            </div>
        </header>

        <main class="client-main">
            <template v-if="screen === 'catalog'">
                <section class="catalog-hero">
                    <div><p class="eyebrow">DOUALA &amp; YAOUNDÉ · AUJOURD’HUI, RIEN QUE POUR VOUS</p><h1>Bonjour{{ user?.name ? `, ${user.name.split(' ')[0]}` : '' }}.<br><em>On prend soin de vous ?</em></h1><p>Les gestes attentionnés et les belles adresses commencent ici.</p></div>
                    <div class="hero-flower" aria-hidden="true">✿</div>
                </section>
                <section class="discover-section">
                    <div class="section-heading"><div><p class="eyebrow">LE CARNET ÉBOLO</p><h2>À découvrir près de vous</h2></div><label class="search-box"><span>⌕</span><input v-model="search" aria-label="Rechercher une prestation" placeholder="Une envie, une adresse…"></label></div>
                    <div class="category-list" aria-label="Filtrer par catégorie">
                        <button v-for="category in categories" :key="category" type="button" :class="{ selected: selectedCategory === category }" @click="selectedCategory = category">{{ category }}</button>
                    </div>
                    <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                    <div v-if="loading" class="loading-state">Nous préparons votre sélection…</div>
                    <div v-else-if="filteredServices.length" class="service-grid">
                        <article v-for="(service, index) in filteredServices" :key="service.id" class="service-card">
                            <div class="service-art" :class="`service-art-${index % 4}`"><span class="service-category">{{ service.category || 'Soin' }}</span><span class="service-art-mark">{{ ['✿', '❋', '✦', '❀'][index % 4] }}</span></div>
                            <div class="service-content">
                                <p class="provider-name">{{ providerName(service) }}</p>
                                <h3>{{ service.name }}</h3>
                                <p class="service-description">{{ service.description || 'Un moment de soin personnalisé, réalisé avec attention.' }}</p>
                                <div class="service-meta"><span>{{ service.duration_minutes || '—' }} min</span><strong>{{ formatPrice(service.price) }}</strong></div>
                                <button class="button button-outline" type="button" @click="openBooking(service)">Réserver ce soin <span>→</span></button>
                            </div>
                        </article>
                    </div>
                    <div v-else-if="!loading && !error" class="empty-state"><span>✿</span><h3>Votre prochaine belle adresse se prépare.</h3><p>Aucune prestation ne correspond à cette recherche pour le moment.</p></div>
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
                    <label for="booking_time">Heure souhaitée</label><input id="booking_time" v-model="bookingForm.booking_time" type="time" required>
                    <label for="location_type">Lieu de la prestation</label><select id="location_type" v-model="bookingForm.location_type"><option value="studio">Au studio</option><option value="salon">Au salon</option><option value="home">À domicile</option></select>
                    <template v-if="bookingForm.location_type === 'domicile'"><label for="location_details">Adresse de rendez-vous</label><input id="location_details" v-model="bookingForm.location_details" autocomplete="street-address" placeholder="Quartier, rue, repère" required></template>
                    <label for="notes">Un détail à partager <span class="optional">(facultatif)</span></label><textarea id="notes" v-model="bookingForm.notes" rows="2" placeholder="Une précision pour votre experte…"></textarea>
                    <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                    <button class="button button-primary submit-button" type="submit" :disabled="busy"><span>{{ busy ? 'Un instant…' : 'Confirmer ma demande' }}</span><span>→</span></button>
                </form>
            </section>
        </div>
    </div>
</template>
