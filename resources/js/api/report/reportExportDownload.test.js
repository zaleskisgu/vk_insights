import { describe, it, expect, vi, beforeEach } from 'vitest';
import { readXsrfToken } from '@/csrf.js';
import { reportExportDownload, triggerBrowserDownload } from './reportExportDownload.js';

vi.mock('@/csrf.js', () => ({
    readXsrfToken: vi.fn(() => 'xsrf-token'),
}));

beforeEach(() => {
    vi.mocked(readXsrfToken).mockReturnValue('xsrf-token');
    globalThis.fetch = vi.fn();
});

const body = { group: 'vk', from: '2024-01-01', to: '2024-01-31' };

describe('reportExportDownload', () => {
    it('POSTs JSON export with Accept application/json and default filename', async () => {
        const blob = new Blob(['{}'], { type: 'application/json' });
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            headers: { get: () => null },
            blob: () => Promise.resolve(blob),
        });

        const result = await reportExportDownload('json', body);

        expect(result.blob).toBe(blob);
        expect(result.filename).toBe('report.json');
        expect(fetch).toHaveBeenCalledWith(
            '/report/export',
            expect.objectContaining({
                method: 'POST',
                credentials: 'same-origin',
                body: JSON.stringify({ ...body, format: 'json' }),
                headers: expect.objectContaining({
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': 'xsrf-token',
                }),
            }),
        );
    });

    it('uses text/csv Accept and default csv filename', async () => {
        const blob = new Blob(['a,b'], { type: 'text/csv' });
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            headers: { get: () => null },
            blob: () => Promise.resolve(blob),
        });

        const result = await reportExportDownload('csv', body);
        expect(result.filename).toBe('report.csv');
        expect(fetch).toHaveBeenCalledWith(
            '/report/export',
            expect.objectContaining({
                headers: expect.objectContaining({ Accept: 'text/csv' }),
                body: JSON.stringify({ ...body, format: 'csv' }),
            }),
        );
    });

    it('parses filename from Content-Disposition filename*', async () => {
        const blob = new Blob(['x']);
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            headers: {
                get: () => "attachment; filename*=UTF-8''my%20report.csv",
            },
            blob: () => Promise.resolve(blob),
        });

        const result = await reportExportDownload('csv', body);
        expect(result.filename).toBe('my report.csv');
    });

    it('parses filename from quoted Content-Disposition', async () => {
        const blob = new Blob(['x']);
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            headers: {
                get: () => 'attachment; filename="export-2024.json"',
            },
            blob: () => Promise.resolve(blob),
        });

        const result = await reportExportDownload('json', body);
        expect(result.filename).toBe('export-2024.json');
    });

    it('keeps default filename when filename* decode fails', async () => {
        const blob = new Blob(['x']);
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            headers: {
                get: () => "attachment; filename*=UTF-8''%E0%A4%A",
            },
            blob: () => Promise.resolve(blob),
        });

        const result = await reportExportDownload('csv', body);
        expect(result.filename).toBe('report.csv');
    });

    it('throws ReportApiError with Laravel body on non-OK response', async () => {
        const blobFn = vi.fn();
        vi.mocked(fetch).mockResolvedValue({
            ok: false,
            status: 422,
            json: () => Promise.resolve({ errors: { group: ['Неверный период'] } }),
            blob: blobFn,
        });

        await expect(reportExportDownload('json', body)).rejects.toMatchObject({
            name: 'ReportApiError',
            message: 'Неверный период',
            status: 422,
        });
        expect(blobFn).not.toHaveBeenCalled();
    });

    it('forwards AbortSignal to fetch', async () => {
        const blob = new Blob(['x']);
        const controller = new AbortController();
        vi.mocked(fetch).mockResolvedValue({
            ok: true,
            headers: { get: () => null },
            blob: () => Promise.resolve(blob),
        });

        await reportExportDownload('json', body, { signal: controller.signal });
        expect(fetch).toHaveBeenCalledWith(
            '/report/export',
            expect.objectContaining({ signal: controller.signal }),
        );
    });
});

describe('triggerBrowserDownload', () => {
    it('creates object URL, clicks anchor, removes node and revokes URL', () => {
        const blob = new Blob(['data']);
        const prevCreate = URL.createObjectURL;
        const prevRevoke = URL.revokeObjectURL;
        const createObjectURL = vi.fn().mockReturnValue('blob:mock-url');
        const revokeObjectURL = vi.fn();
        URL.createObjectURL = createObjectURL;
        URL.revokeObjectURL = revokeObjectURL;

        const click = vi.fn();
        const remove = vi.fn();
        const anchor = { href: '', download: '', rel: '', click, remove };
        const appendChild = vi.spyOn(document.body, 'appendChild').mockImplementation(() => {});
        const createElement = vi.spyOn(document, 'createElement').mockReturnValue(/** @type {any} */ (anchor));

        try {
            triggerBrowserDownload(blob, 'out.csv');

            expect(createObjectURL).toHaveBeenCalledWith(blob);
            expect(anchor.href).toBe('blob:mock-url');
            expect(anchor.download).toBe('out.csv');
            expect(anchor.rel).toBe('noopener');
            expect(appendChild).toHaveBeenCalledWith(anchor);
            expect(click).toHaveBeenCalledTimes(1);
            expect(remove).toHaveBeenCalledTimes(1);
            expect(revokeObjectURL).toHaveBeenCalledWith('blob:mock-url');
        } finally {
            appendChild.mockRestore();
            createElement.mockRestore();
            if (prevCreate) {
                URL.createObjectURL = prevCreate;
            } else {
                delete URL.createObjectURL;
            }
            if (prevRevoke) {
                URL.revokeObjectURL = prevRevoke;
            } else {
                delete URL.revokeObjectURL;
            }
        }
    });
});
