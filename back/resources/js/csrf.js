/** Значение куки XSRF-TOKEN для заголовка X-XSRF-TOKEN (Laravel web + CSRF). */
export function readXsrfToken() {
    if (typeof document === 'undefined') {
        return '';
    }
    const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}
