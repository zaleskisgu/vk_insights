import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import {
    ReportApiError,
    messageFromLaravelBody,
    reportClientErrorMessage,
} from './reportErrors.js';

describe('ReportApiError', () => {
    it('exposes message, status and body', () => {
        const e = new ReportApiError('msg', { status: 422, body: { a: 1 } });
        expect(e.name).toBe('ReportApiError');
        expect(e.message).toBe('msg');
        expect(e.status).toBe(422);
        expect(e.body).toEqual({ a: 1 });
    });
});

describe('messageFromLaravelBody', () => {
    it('returns CSRF hint for 419', () => {
        expect(messageFromLaravelBody(419, {}, 'fallback')).toContain('CSRF');
    });

    it('uses string data.message when present', () => {
        expect(messageFromLaravelBody(400, { message: 'From server' }, 'fb')).toBe('From server');
    });

    it('uses first Laravel validation message from array', () => {
        expect(
            messageFromLaravelBody(422, { errors: { group: ['Неверная группа'] } }, 'fb'),
        ).toBe('Неверная группа');
    });

    it('uses first error when value is not an array', () => {
        expect(messageFromLaravelBody(422, { errors: { x: 'plain' } }, 'fb')).toBe('plain');
    });

    it('falls back to label and status', () => {
        expect(messageFromLaravelBody(500, {}, 'Ошибка запроса')).toBe('Ошибка запроса (500).');
    });
});

describe('reportClientErrorMessage', () => {
    let consoleSpy;

    beforeEach(() => {
        consoleSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        consoleSpy.mockRestore();
    });

    it('returns message for ReportApiError', () => {
        expect(
            reportClientErrorMessage(new ReportApiError('API failed'), 'fallback'),
        ).toBe('API failed');
    });

    it('returns fallback and logs for unknown error when logLabel is set', () => {
        const err = new Error('network');
        expect(reportClientErrorMessage(err, 'fallback', 'label')).toBe('fallback');
        expect(consoleSpy).toHaveBeenCalledWith('label', err);
    });

    it('returns fallback without logging when logLabel is omitted', () => {
        expect(reportClientErrorMessage(new Error('x'), 'fallback')).toBe('fallback');
        expect(consoleSpy).not.toHaveBeenCalled();
    });
});
