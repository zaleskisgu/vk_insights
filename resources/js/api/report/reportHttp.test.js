import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { readXsrfToken } from '@/csrf.js';
import { reportJsonGet, reportJsonPost, reportJsonHeaders, fetchReportDashboard } from './reportHttp.js';
import { ReportApiError } from './reportErrors.js';

vi.mock('@/csrf.js', () => ({
    readXsrfToken: vi.fn(() => 'xsrf-token'),
}));

beforeEach(() => {
    vi.mocked(readXsrfToken).mockReturnValue('xsrf-token');
});

describe('reportJsonHeaders', () => {
    it('throws ReportApiError when XSRF token is missing', () => {
        vi.mocked(readXsrfToken).mockReturnValue('');
        expect(() => reportJsonHeaders('application/json')).toThrow(ReportApiError);
        expect(() => reportJsonHeaders('application/json')).toThrow(/CSRF/);
    });

    it('returns headers when token exists', () => {
        vi.mocked(readXsrfToken).mockReturnValue('abc');
        expect(reportJsonHeaders('application/json')).toMatchObject({
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': 'abc',
            'X-Requested-With': 'XMLHttpRequest',
        });
    });
});

describe('reportJsonGet', () => {
    beforeEach(() => {
        globalThis.fetch = vi.fn();
    });

    afterEach(() => {
        vi.mocked(fetch).mockReset();
    });

    it('returns parsed JSON on success', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ ok: true }),
        });
        await expect(reportJsonGet('/report?x=1')).resolves.toEqual({ ok: true });
        expect(fetch).toHaveBeenCalledWith(
            '/report?x=1',
            expect.objectContaining({
                method: 'GET',
                credentials: 'same-origin',
                headers: expect.objectContaining({ Accept: 'application/json' }),
            }),
        );
    });

    it('throws ReportApiError with Laravel validation message on 422', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: false,
            status: 422,
            json: () => Promise.resolve({ errors: { group: ['Некорректное значение'] } }),
        });
        await expect(reportJsonGet('/report')).rejects.toMatchObject({
            name: 'ReportApiError',
            message: 'Некорректное значение',
            status: 422,
        });
    });

    it('maps 419 to CSRF message', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: false,
            status: 419,
            json: () => Promise.resolve({}),
        });
        await expect(reportJsonGet('/report')).rejects.toMatchObject({
            name: 'ReportApiError',
            status: 419,
        });
        await expect(reportJsonGet('/report')).rejects.toThrow(/CSRF/);
    });
});

describe('reportJsonPost', () => {
    beforeEach(() => {
        globalThis.fetch = vi.fn();
    });

    afterEach(() => {
        vi.mocked(fetch).mockReset();
    });

    it('throws before fetch when CSRF token is missing', async () => {
        vi.mocked(readXsrfToken).mockReturnValue('');
        await expect(reportJsonPost('/report/export', {})).rejects.toThrow(ReportApiError);
        expect(fetch).not.toHaveBeenCalled();
    });

    it('throws ReportApiError on non-OK response', async () => {
        vi.mocked(readXsrfToken).mockReturnValue('xsrf');
        vi.mocked(fetch).mockResolvedValue({
            ok: false,
            status: 500,
            json: () => Promise.resolve({}),
        });
        await expect(reportJsonPost('/report/export', { a: 1 })).rejects.toMatchObject({
            name: 'ReportApiError',
            status: 500,
        });
    });
});

describe('fetchReportDashboard', () => {
    beforeEach(() => {
        globalThis.fetch = vi.fn();
    });

    afterEach(() => {
        vi.mocked(fetch).mockReset();
    });

    it('calls GET /report with encoded query', async () => {
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            json: () => Promise.resolve({ meta: {} }),
        });
        await fetchReportDashboard({ group: 'vk', from: '2024-01-01', to: '2024-02-01' });
        expect(fetch).toHaveBeenCalledWith(
            '/report?group=vk&from=2024-01-01&to=2024-02-01',
            expect.any(Object),
        );
    });
});
