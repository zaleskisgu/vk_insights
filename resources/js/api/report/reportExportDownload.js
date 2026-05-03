import { messageFromLaravelBody, ReportApiError } from '@/api/report/reportErrors.js';
import { reportJsonHeaders } from '@/api/report/reportHttp.js';

/**
 * @param {'json' | 'csv'} format
 * @param {{ group: string, from: string, to: string }} body
 * @param {{ signal?: AbortSignal }} [options]
 * @returns {Promise<{ blob: Blob, filename: string }>}
 */
export async function reportExportDownload(format, body, options = {}) {
    const accept = format === 'json' ? 'application/json' : 'text/csv';

    const res = await fetch('/report/export', {
        method: 'POST',
        credentials: 'same-origin',
        signal: options.signal,
        headers: reportJsonHeaders(accept),
        body: JSON.stringify({ ...body, format }),
    });

    if (!res.ok) {
        const data = await res.json().catch(() => ({}));
        const message = messageFromLaravelBody(res.status, data, 'Ошибка экспорта');
        throw new ReportApiError(message, { status: res.status, body: data });
    }

    const blob = await res.blob();
    const cd = res.headers.get('Content-Disposition');
    let filename = format === 'csv' ? 'report.csv' : 'report.json';
    if (cd) {
        const star = /filename\*=UTF-8''([^;]+)/i.exec(cd);
        if (star?.[1]) {
            try {
                filename = decodeURIComponent(star[1].trim());
            } catch {
                /* keep default */
            }
        } else {
            const quoted = /filename="([^"]+)"/i.exec(cd);
            if (quoted?.[1]) {
                filename = quoted[1];
            }
        }
    }

    return { blob, filename };
}

/**
 * @param {Blob} blob
 * @param {string} filename
 */
export function triggerBrowserDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
