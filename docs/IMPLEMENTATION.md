# Реализация

[tz.md](./tz.md) · [README](../README.md)

## Стек

- **PHP:** Laravel, только [`routes/web.php`](../routes/web.php) + `console` ([`bootstrap/app.php`](../bootstrap/app.php)); `GET /report` без CSRF; POST с `VerifyCsrfToken`.
- **JS:** Vue 3, Vite, SCSS [`resources/scss/app.scss`](../resources/scss/app.scss), PrimeVue, Chart.js (`primevue/chart`). Вход: [`resources/js/app.js`](../resources/js/app.js) → [`App.vue`](../resources/js/App.vue); шаблон [`resources/views/app.blade.php`](../resources/views/app.blade.php).

## Дерево

```text
.
├── app/Contracts/              # VkClient, DashboardFixtureProvider
├── app/Data/{Dashboard,Export,Post,Report,Vk}/
├── app/Http/{Controllers,Middleware,Requests}/
├── app/Integration/Vk/{Exception,Method,Mock,Support,HttpVkClient,MockVkClient}
├── app/Providers/AppServiceProvider.php
├── app/Services/{Dashboard,Export,Posts,Vk,ReportService}
├── bootstrap/  config/  database/  docker/entrypoint.sh  docs/
├── public/                     # index, favicon, build/
├── resources/js/{api/report,components,locales,screens,utils,App.vue,app.js,csrf.js}
├── resources/scss/  resources/views/
├── routes/web.php
├── tests/{Unit,Integration,TestCase.php}
├── artisan  composer.json  docker-compose.yml  Dockerfile
├── package.json  phpstan.neon  phpunit.xml  vite.config.js
```

Не в дереве: `vendor/`, `node_modules/`, `bootstrap/cache/`, `storage/framework/views/`, логи.

## Команды

Корень репо, PHP 8.4+.

| Команда | Действие |
|---------|----------|
| `composer install` | PHP deps |
| `composer setup` | install, `.env` из example при отсутствии, `key:generate`, `migrate --force`, `npm install --ignore-scripts`, `npm run build` |
| `composer dev` | `serve` + `queue:listen` + `npm run dev` (concurrently) |
| `composer test` | `config:clear` + `php artisan test` |
| `composer phpstan` | `phpstan analyse` ([`phpstan.neon`](../phpstan.neon)) |
| `php artisan test` / `serve` | без очистки конфига / dev-сервер `public/` |
| `npm install` / `dev` / `build` / `test` / `test:watch` | deps / Vite / prod → `public/build/` / Vitest / watch |
| `docker compose up --build` | см. [Docker](#docker) |

**PHPUnit:** `tests/Unit/*` (дашборд, ReportService, ReportPosts, CSV, HttpVkClient); `tests/Integration/VkHttpClientIntegrationTest.php` — live, без `VK_SERVICE_TOKEN` → skipped; опционально `VK_INTEGRATION_TEST_GROUP_ID`.

**Vitest:** [`reportErrors.test.js`](../resources/js/api/report/reportErrors.test.js), [`reportHttp.test.js`](../resources/js/api/report/reportHttp.test.js), [`reportExportDownload.test.js`](../resources/js/api/report/reportExportDownload.test.js) — маска в [`vite.config.js`](../vite.config.js).

Опционально: `vendor/bin/pint`. Локально без Docker — [README](../README.md).

## Маршруты

| Метод | Путь | Ответ / тело |
|-------|------|--------------|
| GET | `/health` | `status`, `vk_mode`, `time` |
| GET | `/report` | `group`,`from`,`to` (даты `YYYY-MM-DD`); `meta`, `summary`, `daily`, `top_posts`, `content_types`; `throttle:30,1` |
| POST | `/report/export` | JSON: `group`,`from`,`to`,`format` json\|csv; дашборд + `all_posts`; CSV stream; `throttle:30,1` |
| POST | `/report/posts` | пагинация/сорт/фильтры; `{data,meta}`; тот же снимок что отчёт; `throttle:30,1` |

**ТЗ:** было `POST /analyze` + `GET /report/:id` — здесь `GET /report?…`; `posts`/`export` — расширение UI.

## Доступ

Сессия, тот же origin. POST: `X-XSRF-TOKEN`, JSON, [`csrf.js`](../resources/js/csrf.js). `export`/`posts`: `group` = `meta.group_query` из `GET /report`.

## Период

[`ReportPeriodRequest`](../app/Http/Requests/ReportPeriodRequest.php) + [`ReportRequest`](../app/Http/Requests/ReportRequest.php), [`ReportPostsRequest`](../app/Http/Requests/ReportPostsRequest.php), [`ReportExportRequest`](../app/Http/Requests/ReportExportRequest.php). Валидатор `after`: «не позже сегодня» и `VK_PERIOD_MAX_DAYS` в `config('vk.timezone')`. Фронт: [`StartScreen.vue`](../resources/js/screens/StartScreen.vue) шлёт `YYYY-MM-DD` без `toISOString()`.

## VK

- Контракт [`VkClient`](../app/Contracts/VkClient.php); [`VkGroupInputParser`](../app/Integration/Vk/Support/VkGroupInputParser.php) + [`VkGroupInputParseResult`](../app/Integration/Vk/Support/VkGroupInputParseResult.php).
- Мок: [`app/Integration/Vk/Mock/`](../app/Integration/Vk/Mock/), [`MockVkClient`](../app/Integration/Vk/MockVkClient.php).
- HTTP: [`HttpVkClient`](../app/Integration/Vk/HttpVkClient.php), [`GroupsGetByIdMethod`](../app/Integration/Vk/Method/GroupsGetByIdMethod.php), [`WallGetMethod`](../app/Integration/Vk/Method/WallGetMethod.php), DTO `app/Data/Vk`, исключения `Integration/Vk/Exception`, [`VkApiCallStats`](../app/Integration/Vk/Support/VkApiCallStats.php); JSON-ошибки в [`bootstrap/app.php`](../bootstrap/app.php).
- Live: [`WallPostsForReportLoader`](../app/Services/Vk/WallPostsForReportLoader.php); лимиты [`config/vk.php`](../config/vk.php); [`WallPostsForPeriodCache`](../app/Integration/Vk/Support/WallPostsForPeriodCache.php) (`VK_CACHE_TTL`, `CACHE_STORE`, lock); [`LiveDashboardFixtureProvider`](../app/Integration/Vk/Support/LiveDashboardFixtureProvider.php).
- Общая точка: [`DashboardFixtureFactory`](../app/Services/Dashboard/DashboardFixtureFactory.php) → [`DashboardFixtureBundle`](../app/Services/Dashboard/DashboardFixtureBundle.php); [`ReportService`](../app/Services/ReportService.php), [`ReportPostsService`](../app/Services/Posts/ReportPostsService.php).
- Мок-аватар: [`MockCommunityAvatar`](../app/Integration/Vk/Mock/MockCommunityAvatar.php) (data URL). [`AppServiceProvider`](../app/Providers/AppServiceProvider.php): биндинги `VkClient`, синглтоны кэша/лоадера.
- Логи: [`LogVkApiCallStats`](../app/Http/Middleware/LogVkApiCallStats.php) → канал `vk`; в `local`: заголовки `X-Vk-Calls`, `X-Vk-Total-Ms`.

## Контроллеры / данные

[`ReportController`](../app/Http/Controllers/ReportController.php), [`ReportPostsController`](../app/Http/Controllers/ReportPostsController.php), [`ReportExportController`](../app/Http/Controllers/ReportExportController.php). [`ReportService`](../app/Services/ReportService.php), [`ReportCsvExporter::streamTo`](../app/Services/Export/ReportCsvExporter.php). Модели ответа: [`app/Data/`](../app/Data/).

## Фронт

[`App.vue`](../resources/js/App.vue): header, bootstrapping по query [`reportUrl.js`](../resources/js/utils/reportUrl.js), async [`DashboardScreen.vue`](../resources/js/screens/DashboardScreen.vue) + Suspense, title [`dashboardFormat.js`](../resources/js/utils/dashboardFormat.js). [`StartScreen.vue`](../resources/js/screens/StartScreen.vue) → [`fetchReportDashboard`](../resources/js/api/report/reportHttp.js). Дашборд: [`DashboardProfileCard`](../resources/js/components/dashboard/DashboardProfileCard.vue), Kpi, три Chart ([`chartTheme.js`](../resources/js/utils/chartTheme.js)), [`DashboardTopPostsTable`](../resources/js/components/dashboard/DashboardTopPostsTable.vue) ([`vkWallPostUrl.js`](../resources/js/utils/vkWallPostUrl.js)), [`DashboardAllPostsTable`](../resources/js/components/dashboard/DashboardAllPostsTable.vue) → `POST /report/posts`, [`DashboardPostTextLink.vue`](../resources/js/components/dashboard/DashboardPostTextLink.vue). API: [`resources/js/api/report/`](../resources/js/api/report/), [`reportFetch.js`](../resources/js/api/reportFetch.js). Стили: [`app.scss`](../resources/scss/app.scss) (`.vk-chart-*`, `.vk-all-posts__*`, `.vk-report-generated`). Шапка: [`AppHeader.vue`](../resources/js/components/layout/AppHeader.vue). Метрики: [PERF.md](./PERF.md).

## Docker

[`docker-compose.yml`](../docker-compose.yml): `app`, `redis`. [`Dockerfile`](../Dockerfile): Node stage `npm ci` + `build` → PHP `php:8.4-cli` + intl, pdo_sqlite, zip, redis. Compose override: `APP_URL=http://localhost:8080`, `REDIS_HOST=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`, `DB_CONNECTION=sqlite`. Порт **8080:8000**. Тома `database/`, `storage/`; [`entrypoint.sh`](../docker/entrypoint.sh) без `.env` — copy example + migrate.

## Env

[`.env.example`](../.env.example), [`config/vk.php`](../config/vk.php): `VK_*`, `CACHE_STORE`, `REDIS_*`.

**Режим:** пустой / отсутствующий **`VK_SERVICE_TOKEN`** → всегда мок (`config('vk.use_mock')`), ответ **`meta.mock_notice`** и баннер на дашборде. **С токеном:** `VK_USE_MOCK=true` → мок; `false` или не задано → live API.

Фикстуры мока: `app/Integration/Vk/Mock/`.
