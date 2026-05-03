export class ReportApiError extends Error {
    /**
     * @param {string} message
     * @param {{ status?: number, body?: object }} [extra]
     */
    constructor(message, extra = {}) {
        super(message);
        this.name = 'ReportApiError';
        this.status = extra.status;
        this.body = extra.body;
    }
}

/**
 * @param {number} status
 * @param {object} data
 * @param {string} fallbackLabel
 */
export function messageFromLaravelBody(status, data, fallbackLabel) {
    if (status === 419) {
        return 'Сессия устарела (CSRF). Обновите страницу.';
    }
    if (typeof data.message === 'string') {
        return data.message;
    }
    if (data.errors && typeof data.errors === 'object') {
        const first = Object.values(data.errors)[0];
        return Array.isArray(first) ? first[0] : String(first);
    }

    return `${fallbackLabel} (${status}).`;
}

/**
 * @param {unknown} error
 * @param {string} fallback
 * @param {string} [logLabel] if set, logs non-ReportApiError to console
 */
export function reportClientErrorMessage(error, fallback, logLabel) {
    if (error instanceof ReportApiError) {
        return error.message;
    }
    if (logLabel) {
        console.error(logLabel, error);
    }
    return fallback;
}
