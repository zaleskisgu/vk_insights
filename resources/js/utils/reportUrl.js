/**
 * Query-параметры отчёта в URL: ?group=&from=&to= (формат дат YYYY-MM-DD).
 */

/**
 * @returns {{ group: string, from: string, to: string } | null}
 */
export function readReportQuery() {
    const p = new URLSearchParams(window.location.search);
    const group = (p.get('group') ?? '').trim();
    const from = p.get('from') ?? '';
    const to = p.get('to') ?? '';
    if (!group || !from || !to) {
        return null;
    }
    return { group, from, to };
}

/**
 * @param {{ group: string, from: string, to: string }} q
 */
export function pushReportUrl(q) {
    const params = new URLSearchParams();
    params.set('group', q.group);
    params.set('from', q.from);
    params.set('to', q.to);
    const url = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState(null, '', url);
}

/** Новая запись в истории: только путь без query (кнопка «Новый поиск», шаг назад к форме). */
export function pushPathWithoutQuery() {
    const path = window.location.pathname || '/';
    window.history.pushState(null, '', path);
}

/** Текущий URL без query (ошибка загрузки по ссылке с параметрами). */
export function replacePathWithoutQuery() {
    const path = window.location.pathname || '/';
    window.history.replaceState(null, '', path);
}
