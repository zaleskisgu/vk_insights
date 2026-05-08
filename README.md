# VK Insights

## О проекте

Дашборд по стене VK за период: `wall.get` / `groups.getById`, мок или live, экспорт CSV/JSON. ТЗ: [docs/tz.md](./docs/tz.md).

## Плюсы

- PHPUnit (сервисы, CSV, HttpVkClient) + опционально live; Vitest — `resources/js/api/report/*.test.js`.
- `VkClient`, `DashboardFixtureFactory` → один путь мок/live; DTO `app/Data`; VK в `app/Integration/Vk`.
- `VK_USE_MOCK=true` — без сети; кэш стены, `throttle`, CSV stream, `composer phpstan`.
- Доки: `docs/` (tz, IMPLEMENTATION, ROADMAP, PERF).

## Запуск

### Docker

```bash
docker compose up --build
```

URL: **http://localhost:8080** (8080→8000 в контейнере). PHP 8.4, `public/build` из образа, Redis, тома `database/`, `storage/`. Нет `.env` — [`docker/entrypoint.sh`](./docker/entrypoint.sh) копирует [`.env.example`](./.env.example) (VK уже описаны, мок по умолчанию); live-токен — в `.env`. Опционально: `./.env:/var/www/html/.env:ro`.

Подробнее: [docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md#docker).

### Локально

`composer install` → `.env.example` → `.env`, `php artisan key:generate` → `npm install` → `npm run dev` + `php artisan serve` (`public/`). Сборка: `npm run build`. Deep link: `/?group=…&from=YYYY-MM-DD&to=YYYY-MM-DD`. Порт занят: `php -S 127.0.0.1:8090 -t public`.

## Стек / доки

Laravel 13, PHP 8.4, Vue 3, Vite, PrimeVue, Chart.js; только `routes/web.php` (CSRF на POST).

[docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md) — дерево, команды, API, VK, Docker, env.  
[docs/PERF.md](./docs/PERF.md) — Lighthouse.
