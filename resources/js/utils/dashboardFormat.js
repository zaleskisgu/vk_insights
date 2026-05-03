export const ruNumber = new Intl.NumberFormat('ru-RU');

export function formatPeriodRu(from, to) {
    const opts = { day: '2-digit', month: '2-digit', year: 'numeric' };
    const a = new Date(from + 'T12:00:00');
    const b = new Date(to + 'T12:00:00');
    return `${a.toLocaleDateString('ru-RU', opts)} — ${b.toLocaleDateString('ru-RU', opts)}`;
}
