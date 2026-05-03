import { readXsrfToken } from '@/csrf.js';
import { messageFromLaravelBody, ReportApiError } from '@/api/report/reportErrors.js';

/**
 * @param {string} accept
 * @returns {Record<string, string>}
 */
export function reportJsonHeaders(accept) {
    const xsrf = readXsrfToken();
    if (!xsrf) {
        throw new ReportApiError('Нет CSRF-куки. Обновите страницу.', { status: 0 });
    }
    return {
        Accept: accept,
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': xsrf,
        'X-Requested-With': 'XMLHttpRequest',
    };
}

/**
 * @param {string} url
 * @param {{ signal?: AbortSignal }} [options]
 * @returns {Promise<Record<string, unknown>>}
 */
export async function reportJsonGet(url, options = {}) {
    const res = await fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        signal: options.signal,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        const message = messageFromLaravelBody(res.status, data, 'Ошибка запроса');
        throw new ReportApiError(message, { status: res.status, body: data });
    }

    return data;
}

/**
 * @param {string} url
 * @param {Record<string, unknown>} body
 * @param {{ signal?: AbortSignal }} [options]
 * @returns {Promise<Record<string, unknown>>}
 */
export async function reportJsonPost(url, body, options = {}) {
    const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        signal: options.signal,
        headers: reportJsonHeaders('application/json'),
        body: JSON.stringify(body),
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        const message = messageFromLaravelBody(res.status, data, 'Ошибка запроса');
        throw new ReportApiError(message, { status: res.status, body: data });
    }

    return data;
}

/**
 * @param {{ group: string, from: string, to: string }} query
 * @param {{ signal?: AbortSignal }} [options]
 * @returns {Promise<Record<string, unknown>>}
 */
export async function fetchReportDashboard(query, options = {}) {
    const params = new URLSearchParams();
    params.set('group', query.group);
    params.set('from', query.from);
    params.set('to', query.to);
    return reportJsonGet(`/report?${params.toString()}`, options);
}
