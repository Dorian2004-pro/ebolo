const nbsp = ' ';

const nf = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });
export const number = (n) => nf.format(Math.round(Number(n) || 0)).replace(/[  ]/g, nbsp);

/** 145000 → « 145 000 FCFA » */
export const fcfa = (n) => `${number(n)}${nbsp}FCFA`;

/** Forme courte pour les axes de graphiques : 1 250 000 → « 1,3 M », 85 000 → « 85 k » */
export function compact(n) {
    const v = Number(n) || 0;
    if (v >= 1e6) return `${(v / 1e6).toFixed(v >= 1e7 ? 0 : 1).replace('.', ',')}${nbsp}M`;
    if (v >= 1e3) return `${Math.round(v / 1e3)}${nbsp}k`;
    return String(Math.round(v));
}

const dateFmt = new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
const dayFmt = new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short' });
const longFmt = new Intl.DateTimeFormat('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });

export const date = (v) => (v ? dateFmt.format(new Date(v)) : '—');
export const shortDate = (v) => (v ? dayFmt.format(new Date(v)) : '—');
export const longDate = (v = new Date()) => longFmt.format(new Date(v));

export function ago(v) {
    if (!v) return 'Jamais';
    const s = Math.round((Date.now() - new Date(v).getTime()) / 1000);
    if (s < 60) return "À l'instant";
    const m = Math.round(s / 60);
    if (m < 60) return `Il y a ${m}${nbsp}min`;
    const h = Math.round(m / 60);
    if (h < 24) return `Il y a ${h}${nbsp}h`;
    const d = Math.round(h / 24);
    if (d < 30) return `Il y a ${d}${nbsp}j`;
    return date(v);
}

export const initials = (a = '', b = '') => `${a.charAt(0)}${b.charAt(0)}`.toUpperCase() || '?';

export function greeting() {
    const h = new Date().getHours();
    return h < 12 ? 'Bonjour' : h < 18 ? 'Bon après-midi' : 'Bonsoir';
}

/* ---- Libellés + couleurs de badge par statut -------------------------------------------- */
const maps = {
    user: {
        active: ['Actif', 'ok'], suspended: ['Suspendu', 'bad'], inactive: ['Inactif', 'mute'],
    },
    booking: {
        pending: ['En attente', 'gold'], confirmed: ['Confirmée', 'plum'], in_progress: ['En cours', 'terra'],
        completed: ['Terminée', 'ok'], cancelled: ['Annulée', 'mute'],
    },
    provider: {
        pending: ['À valider', 'gold'], approved: ['Validé', 'ok'], rejected: ['Refusé', 'bad'], suspended: ['Suspendu', 'bad'],
    },
    transaction: {
        pending: ['En attente', 'gold'], held: ['Consigné', 'plum'], completed: ['Terminé', 'ok'],
        failed: ['Échoué', 'bad'], refunded: ['Remboursé', 'mute'],
    },
    dispute: {
        open: ['Ouvert', 'bad'], investigating: ['En analyse', 'gold'], resolved: ['Résolu', 'ok'], closed: ['Clos', 'mute'],
    },
    priority: { high: ['Haute', 'bad'], medium: ['Moyenne', 'gold'], low: ['Basse', 'mute'] },
};

export function badge(kind, value) {
    const [label, tone] = maps[kind]?.[value] || [value ?? '—', 'mute'];
    return { label, tone };
}

export const statusLabel = (kind, value) => badge(kind, value).label;

export const roleLabels = { customer: 'Cliente', provider: 'Prestataire', admin: 'Admin' };
export const txTypeLabels = { payment: 'Paiement', payout: 'Reversement', refund: 'Remboursement', commission: 'Commission' };
export const gatewayLabels = { mtn_momo: 'MTN MoMo', orange_money: 'Orange Money' };
export const locationLabels = { salon: 'Au salon', domicile: 'À domicile' };
export const reasonLabels = { service_quality: 'Qualité de service', no_show: 'Absence', payment_issue: 'Problème de paiement' };

/** Couleurs du donut des statuts de réservation. */
export const statusColors = {
    pending: '#e8a33d', confirmed: '#4a1d3f', in_progress: '#c75b39', completed: '#1d7f55', cancelled: '#c9bcc3',
};
