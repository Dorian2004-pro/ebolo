const nf = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 });

/** 145000 -> « 145 000 » (espaces insécables, comme dans la maquette). */
export const formatAmount = (n) => nf.format(Number(n) || 0).replace(/[  ]/g, ' ');

export const formatFCFA = (n) => `${formatAmount(n)} FCFA`;

export const formatTime = (iso) =>
    new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

export const formatRange = (start, end) => `${formatTime(start)} – ${formatTime(end)}`;

export const formatDay = (iso) => {
    const s = new Date(iso).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long' });
    return s.charAt(0).toUpperCase() + s.slice(1);
};

export const isSameDay = (a, b) => {
    const x = new Date(a);
    const y = new Date(b);
    return x.getFullYear() === y.getFullYear() && x.getMonth() === y.getMonth() && x.getDate() === y.getDate();
};

export const monthKey = (d) => {
    const x = new Date(d);
    return x.getFullYear() * 12 + x.getMonth();
};

/** « Dans 1h30 », « En cours », « Terminé »… */
export const relativeStart = (start, end, now = new Date()) => {
    const s = new Date(start).getTime();
    const e = new Date(end).getTime();
    const t = now.getTime();
    if (t >= e) return 'Terminé';
    if (t >= s) return 'En cours';
    const mins = Math.round((s - t) / 60000);
    if (mins < 60) return `Dans ${mins} min`;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return `Dans ${h}h${m ? String(m).padStart(2, '0') : ''}`;
};

export const initials = (name = '') =>
    name.split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]?.toUpperCase()).join('');

export const PAYMENT_LABELS = { momo: 'MTN MoMo', orange: 'Orange Money' };
