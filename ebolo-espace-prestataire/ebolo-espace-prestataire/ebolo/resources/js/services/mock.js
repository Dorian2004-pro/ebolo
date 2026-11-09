/**
 * Adaptateur axios « démo » : répond aux routes de l'espace prestataire avec des
 * données en mémoire inspirées de la maquette (Marie K., Vanessa, Sandrine).
 * Activé par VITE_USE_MOCK=true. Code de déblocage du séquestre de démo : 7492.
 */
import { AxiosError } from 'axios';
import marieImg from '@img/marie.jpg';
import vanessaImg from '@img/vanessa.jpg';
import sandrineImg from '@img/sandrine.jpg';

const DEMO_CODE = '7492';
const wait = (ms = 350) => new Promise((r) => setTimeout(r, ms));

const at = (dayOffset, h, m = 0) => {
    const d = new Date();
    d.setDate(d.getDate() + dayOffset);
    d.setHours(h, m, 0, 0);
    return d;
};
const iso = (d) => d.toISOString();
const plusMin = (d, min) => new Date(d.getTime() + min * 60000);

const profile = {
    id: 12,
    name: 'Marie Kamga',
    phone: '+237 677 12 34 56',
    city: 'Douala',
    specialty: 'Artisane tresseuse & Soins capillaires signature',
    bio: 'Atelier Bonapriso — tresses, knotless braids et soins signature.',
    workplace: 'Atelier Bonapriso • Salon privé',
    location: { lat: 4.0511, lng: 9.7679, label: 'Bonapriso, Douala' },
    avatar_url: marieImg,
};

const availability = {
    days: [1, 2, 3, 4, 5, 6, 7].map((day) => ({
        day, // 1 = lundi … 7 = dimanche
        open: day !== 7,
        from: '08:00',
        to: '20:00',
    })),
};

const mkBooking = (id, ref, client, service, start, minutes, status, payment) => ({
    id,
    reference: ref,
    client,
    service: { ...service, duration_minutes: minutes },
    starts_at: iso(start),
    ends_at: iso(plusMin(start, minutes)),
    status,
    payment,
});

const SERVICES = {
    knotless: { id: 1, name: 'Knotless Braids moyennes', price: 8500 },
    fulani: { id: 2, name: 'Fulani Braids', price: 6500 },
    soin: { id: 3, name: 'Soin hydratant signature', price: 12000 },
};
const vanessa = { id: 31, name: 'Vanessa Ndongo', phone: '+237677894210', avatar_url: vanessaImg };
const sandrine = { id: 32, name: "Sandrine Eto'o", phone: '+237699112233', avatar_url: sandrineImg };
const clients = [
    { id: 33, name: 'Carine Mbarga', phone: '+237655000111', avatar_url: null },
    { id: 34, name: 'Estelle Fouda', phone: '+237677000222', avatar_url: null },
    { id: 35, name: 'Nadège Tchoua', phone: '+237699000333', avatar_url: null },
];

const buildBookings = () => {
    const now = new Date();
    const today = now.getDate();
    const list = [
        mkBooking(101, 'EBL-2411-0101', vanessa, SERVICES.knotless, at(0, 14), 210, 'confirmed', { method: 'momo', status: 'escrow' }),
        mkBooking(102, 'EBL-2411-0102', sandrine, SERVICES.fulani, at(0, 18), 90, 'confirmed', { method: 'orange', status: 'escrow' }),
        mkBooking(103, 'EBL-2411-0103', clients[0], SERVICES.soin, at(1, 10), 120, 'pending', { method: 'momo', status: 'escrow' }),
        mkBooking(104, 'EBL-2411-0104', clients[1], SERVICES.fulani, at(2, 15), 90, 'pending', { method: 'orange', status: 'escrow' }),
    ];

    // Historique du mois (paiements déjà libérés) : 145 000 FCFA au total.
    const amounts = [12000, 8500, 6500, 12000, 8500, 6500, 12000, 8500, 6500, 12000, 8500, 6500, 12000, 8500, 6500];
    let id = 200;
    let sum = 0;
    const target = 145000;
    for (let i = 0; sum < target; i++) {
        const price = Math.min(amounts[i % amounts.length], target - sum);
        const day = 1 + (i % Math.max(1, today - 1));
        const d = new Date(now.getFullYear(), now.getMonth(), day, 9 + (i % 6), 0, 0);
        list.push(mkBooking(id++, `EBL-H-${id}`, clients[i % 3], { id: 9, name: 'Prestation', price }, d, 120, 'completed', { method: i % 2 ? 'orange' : 'momo', status: 'released' }));
        sum += price;
    }

    // Mois précédent : 123 000 FCFA (→ +18 %).
    const prev = [60000, 63000];
    prev.forEach((price, i) => {
        const d = new Date(now.getFullYear(), now.getMonth() - 1, 5 + i * 10, 11, 0, 0);
        list.push(mkBooking(id++, `EBL-P-${id}`, clients[i], { id: 9, name: 'Prestation', price }, d, 120, 'completed', { method: 'momo', status: 'released' }));
    });
    return list;
};

let bookings = buildBookings();
let identity = { status: 'none', id_type: null, submitted_at: null, rejection_reason: null };

const respond = (config, data, status = 200) => ({ data: { data }, status, statusText: 'OK', headers: {}, config, request: {} });
const fail = (config, status, body) =>
    Promise.reject(new AxiosError(body.message ?? 'Erreur', String(status), config, {}, { data: body, status, statusText: 'ERR', headers: {}, config }));

export const mockAdapter = async (config) => {
    await wait();
    const method = (config.method || 'get').toLowerCase();
    const url = (config.url || '').replace(/\?.*$/, '');
    let m;

    if (url === '/artisan/profile' && method === 'get') return respond(config, profile);
    if (url === '/artisan/profile' && method === 'put') return respond(config, Object.assign(profile, JSON.parse(config.data || '{}')));
    if (url === '/artisan/availability' && method === 'get') return respond(config, availability);
    if (url === '/bookings' && method === 'get') return respond(config, bookings);

    if ((m = url.match(/^\/bookings\/(\d+)\/status$/)) && method === 'patch') {
        const b = bookings.find((x) => x.id === Number(m[1]));
        if (!b) return fail(config, 404, { message: 'Réservation introuvable.' });
        const { status } = JSON.parse(config.data);
        if (!['confirmed', 'rejected'].includes(status)) return fail(config, 422, { message: 'Statut invalide.' });
        b.status = status;
        return respond(config, b);
    }

    if ((m = url.match(/^\/bookings\/(\d+)\/release$/)) && method === 'post') {
        const b = bookings.find((x) => x.id === Number(m[1]));
        if (!b) return fail(config, 404, { message: 'Réservation introuvable.' });
        const { code } = JSON.parse(config.data);
        if (code !== DEMO_CODE) return fail(config, 422, { message: 'Code incorrect. Vérifiez auprès de votre cliente.' });
        b.payment.status = 'released';
        b.status = 'completed';
        return respond(config, b);
    }

    if (url === '/artisan/identity-verification' && method === 'get') return respond(config, identity);
    if (url === '/artisan/identity-verification' && method === 'post') {
        const form = config.data;
        if (!form.get('recto')) return fail(config, 422, { message: 'Le recto de la pièce est requis.' });
        identity = { status: 'pending', id_type: form.get('id_type'), submitted_at: new Date().toISOString(), rejection_reason: null };
        return respond(config, identity, 201);
    }

    return fail(config, 404, { message: `Route démo introuvable : ${method.toUpperCase()} ${url}` });
};
