import http from './http';

/**
 * Couche d'accès à l'API Ébolo — espace prestataire.
 * Les routes ci-dessous suivent le tableau de spécification ; celles marquées
 * « À CONVENIR » ne figurent pas encore dans le contrat backend (voir README).
 */

// ── Profil ────────────────────────────────────────────────────────────────
export const getProfile = () => http.get('/artisan/profile').then((r) => r.data.data ?? r.data);
export const updateProfile = (payload) => http.put('/artisan/profile', payload).then((r) => r.data.data ?? r.data);

// ── Réservations reçues (rôle artisan) ────────────────────────────────────
export const getBookings = () => http.get('/bookings').then((r) => r.data.data ?? r.data);

/** status : 'confirmed' | 'rejected' */
export const updateBookingStatus = (bookingId, status) =>
    http.patch(`/bookings/${bookingId}/status`, { status }).then((r) => r.data.data ?? r.data);

// ── Horaires — À CONVENIR ─────────────────────────────────────────────────
export const getAvailability = () => http.get('/artisan/availability').then((r) => r.data.data ?? r.data);
export const updateAvailability = (payload) => http.put('/artisan/availability', payload).then((r) => r.data.data ?? r.data);

// ── Déblocage du séquestre par code client à 4 chiffres — À CONVENIR ─────
export const releaseEscrow = (bookingId, code) =>
    http.post(`/bookings/${bookingId}/release`, { code }).then((r) => r.data.data ?? r.data);

// ── Vérification d'identité — À CONVENIR ──────────────────────────────────
export const getIdentityVerification = () =>
    http.get('/artisan/identity-verification').then((r) => r.data.data ?? r.data);

/** id_type : 'cni' | 'passport' | 'residence_permit' ; fichiers : File (image) */
export const submitIdentityVerification = ({ idType, recto, verso }) => {
    const body = new FormData();
    body.append('id_type', idType);
    body.append('recto', recto);
    if (verso) body.append('verso', verso);
    return http
        .post('/artisan/identity-verification', body, { headers: { 'Content-Type': 'multipart/form-data' } })
        .then((r) => r.data.data ?? r.data);
};

// ── Authentification ────────────────────────────────────────────────────────
export const login = (credentials) => http.post('/login', credentials).then((r) => r.data);
export const register = (payload) => http.post('/register', payload).then((r) => r.data);
export const getCurrentUser = () => http.get('/me').then((r) => r.data.user);
export const logout = () => http.post('/logout').then((r) => r.data);

// ── Prestations ────────────────────────────────────────────────────────────
export const getOwnServices = () => http.get('/artisan/services').then((r) => r.data.data ?? r.data);
export const createService = (payload) => http.post('/artisan/services', payload).then((r) => r.data.data ?? r.data);
export const updateService = (id, payload) =>
    http.put(`/artisan/services/${id}`, payload).then((r) => r.data.data ?? r.data);
export const deleteService = (id) => http.delete(`/artisan/services/${id}`).then((r) => r.data);

// ── Messages ───────────────────────────────────────────────────────────────
export const getConversation = (userId) =>
    http.get(`/chat/${userId}/messages`).then((r) => r.data.data ?? r.data);
export const sendMessage = (receiverId, content) =>
    http.post('/chat/send', { receiver_id: receiverId, content }).then((r) => r.data.data ?? r.data);
