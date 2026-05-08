# Текущая реализация

Снимок по коду репозитория. Полное ТЗ: [tz.md](./tz.md).

## Стек

- **Backend:** **Laravel** (PHP). Маршруты в [`routes/web.php`](../routes/web.php) (группа `web`: сессия, middleware в т.ч. **VerifyCsrfToken** для `POST`/`PUT` и т.д.). Отдельного `routes/api.php` **нет** — только `web` и `console` ([`bootstrap/app.php`](../bootstrap/app.php)). Запрос **`GET /report`** CSRF-токеном не сопровождается (типичное поведение Laravel для безопасных методов).
- **Frontend:** **Vue 3** + **Vite** + **SCSS** ([`resources/scss/app.scss`](../resources/scss/app.scss)) + **PrimeVue** + **Chart.js** (через `primevue/chart`). Точка входа: [`resources/js/app.js`](../resources/js/app.js), корень — [`resources/js/App.vue`](../resources/js/App.vue). Оболочка: [`resources/views/app.blade.php`](../resources/views/app.blade.php). Главная `GET /` отдаёт это представление.

## Маршруты

| Метод и путь | Назначение | Отличие от [tz.md](./tz.md) |
|--------------|------------|-----------------------------|
| `GET /health` | JSON: **`status`** (`ok`), **`vk_mode`** (`mock` \| `live` по `config('vk.use_mock')`), **`time`** (ISO 8601). | Соответствует ТЗ. |
| `GET /report?group=&from=&to=` | JSON дашборда: `meta` (в т.ч. **`generated_at`**), `summary`, `daily`, `top_posts`, `content_types`. Источник данных: **мок** или **живая стена VK** (см. ниже). Параметры: **`group`**, **`from`**, **`to`**. | В ТЗ — `POST /analyze` и путь с `groupId`; здесь **`GET`** с query. |
| `POST /report/export` | Тело `group`, `from`, `to`, **`format`**: `json` \| `csv`. JSON/CSV = дашборд + **`all_posts`**. | Формат запроса свой. |
| `POST /report/posts` | Постраничный список постов: те же `group`/`from`/`to`, опционально `page`, `per_page`, `sort`, `order`, `q`, `type`; ответ `{ data, meta }`. Данные из того же снимка, что и отчёт (мок или кэшированная выборка стены). | В ТЗ отдельного эндпоинта нет. |

## Доступ к `GET /report`, `POST /report/export` и `POST /report/posts`

Только с того же origin (сессия). Для **`POST /report/posts`** и **`POST /report/export`** — заголовок **`X-XSRF-TOKEN`** и JSON-тело ([`resources/js/csrf.js`](../resources/js/csrf.js)). Запрос **`GET /report`** без тела, CSRF для него не требуется. Отдельного API-токена нет.

Для **`POST /report/posts`** и **`POST /report/export`** поле **`group`** должно совпадать с **`meta.group_query`** из ответа **`GET /report`** (на дашборде подставляется автоматически).

## Слой VK и моки

- Контракт [`App\Contracts\VkClient`](../app/Contracts/VkClient.php): `getGroupById(int|string)`, `getWall(int $ownerId, int $count, int $offset)`.
- **Разбор ввода сообщества** (одна логика для API и для подсказок `meta`): [`VkGroupInputParser`](../app/Integration/Vk/Support/VkGroupInputParser.php) → [`VkGroupInputParseResult`](../app/Integration/Vk/Support/VkGroupInputParseResult.php): **`query`** (аргумент `group_ids`), **`displayHint`** (имя, если VK не вернул `name`), **`screenSlug`** (нижний регистр для `screen_name` в фолбэке). Используется в [`ReportService`](../app/Services/ReportService.php) и [`WallPostsForReportLoader`](../app/Services/Vk/WallPostsForReportLoader.php).
- Папка **[`app/Integration/Vk/Mock/`](../app/Integration/Vk/Mock/)** — ответы «как VK API» и генерация дашборда без сети:
  - [`MockGroupsGetByIdResponse`](../app/Integration/Vk/Mock/MockGroupsGetByIdResponse.php), [`MockWallGetItems`](../app/Integration/Vk/Mock/MockWallGetItems.php), [`MockDashboardData`](../app/Integration/Vk/Mock/MockDashboardData.php), [`MockDashboardFixtureProvider`](../app/Integration/Vk/Mock/MockDashboardFixtureProvider.php).
- [`MockVkClient`](../app/Integration/Vk/MockVkClient.php): реализация **`VkClient`** без HTTP.
- [`HttpVkClient`](../app/Integration/Vk/HttpVkClient.php): **POST** `https://api.vk.com/method/{method}`; [`GroupsGetByIdMethod`](../app/Integration/Vk/Method/GroupsGetByIdMethod.php), [`WallGetMethod`](../app/Integration/Vk/Method/WallGetMethod.php); DTO в **[`app/Data/Vk/`](../app/Data/Vk/)**; опционально [`VkApiCallStats`](../app/Integration/Vk/VkApiCallStats.php) (длительность и количество вызовов за запрос). Ошибки VK / HTTP / сеть мапятся в типизированные исключения [`app/Integration/Vk/Exception/`](../app/Integration/Vk/Exception/) (JSON-ответы для `expectsJson()` настраиваются в [`bootstrap/app.php`](../bootstrap/app.php)).
- **Живой отчёт:** [`WallPostsForReportLoader`](../app/Services/Vk/WallPostsForReportLoader.php) — `groups.getById` + постраничный `wall.get` (до 50 страниц по 100), фильтр постов по периоду. При **`VK_USE_MOCK=false`**, **`VK_CACHE_TTL` > 0** и настроенном store результат **`group` + нормализованные посты** кэшируется [`WallPostsForPeriodCache`](../app/Integration/Vk/Support/WallPostsForPeriodCache.php) с ключом вида `vk:wall:{owner_key}:{from}:{to}` и блокировкой от stampede (`Cache::lock`). Драйвер — **`CACHE_STORE`** ([`config/cache.php`](../config/cache.php)), TTL — [`config/vk.php`](../config/vk.php) (`VK_CACHE_TTL`, по умолчанию 1200 с).
- **Дашборд из живой стены:** [`LiveDashboardFixtureProvider`](../app/Integration/Vk/Support/LiveDashboardFixtureProvider.php) поверх списка постов из лоадера.
- [`AppServiceProvider`](../app/Providers/AppServiceProvider.php): **`VkClient`** — `MockVkClient` или `HttpVkClient` (+ stats); **`ReportService`** / **`ReportPostsService`** получают флаг «данные со стены» как `!config('vk.use_mock')`.
- **Наблюдаемость VK:** middleware [`LogVkApiCallStats`](../app/Http/Middleware/LogVkApiCallStats.php) (глобально в `bootstrap/app.php`) пишет в канал **`vk`** ([`config/logging.php`](../config/logging.php)); в **`local`** добавляет заголовки **`X-Vk-Calls`**, **`X-Vk-Total-Ms`** к ответу, если были вызовы API.

## Контроллер и отчёт

- [`ReportController`](../app/Http/Controllers/ReportController.php): валидирует `group`, `from`, `to`, передаёт в сервис.
- [`ReportService`](../app/Services/ReportService.php) (`getReportData` / `getExportData`): **`VkGroupInputParser`** для подсказок имени/screen; при **моке** — `MockDashboardFixtureProvider` + `getGroupById` из мок-клиента; при **live** — `WallPostsForReportLoader` + `LiveDashboardFixtureProvider`. **`meta`**: `name` / `screen_name` из VK с фолбэком на парсер; **`group_query`** — исходная строка; **`owner_id`**, `members_count`, `photo_200`, **`generated_at`**.
- [`ReportPostsController`](../app/Http/Controllers/ReportPostsController.php) + [`ReportPostsService::listPage`](../app/Services/Posts/ReportPostsService.php): тот же источник постов, что и отчёт (мок или лоадер с кэшем); фильтры, сортировка, пагинация в PHP.
- [`ReportExportController`](../app/Http/Controllers/ReportExportController.php): **`ReportService::getExportData()`** — дашборд + **`all_posts`**.

Структурированные модели ответа (мета отчёта, строки таблиц и т.д.) — в **[`app/Data/`](../app/Data/)** (`ReportMetaData`, элементы постов и др.) с методами `toArray()` там, где нужно для JSON/экспорта.

## Фронтенд

### Структура экранов и компонентов

- [`App.vue`](../resources/js/App.vue): оболочка [`AppHeader`](../resources/js/components/layout/AppHeader.vue), `<main id="vk-main" tabindex="-1">`; стартовый экран или дашборд по наличию `report`; дашборд — **`defineAsyncComponent`** + **`Suspense`** (ленивый чанк, см. [PERF.md](./PERF.md)); **`document.title`**: на старте **`VK Insights`**, при отчёте **`VK Insights - {имя группы} - {период дд.мм.гггг — дд.мм.гггг}`** ([`formatPeriodRu`](../resources/js/utils/dashboardFormat.js)).
- **Старт:** [`StartScreen.vue`](../resources/js/screens/StartScreen.vue) — hero (`<section>` + `h1`), форма анализа внутри **`<form novalidate @submit.prevent>`** (поля, `fieldset` для периода, пресеты в `<p role="group">`), запрос дашборда через [`fetchReportDashboard`](../resources/js/api/report/reportHttp.js) (**`GET /report`** с query `group`, `from`, `to`), loading / ошибка (`role="alert"`, `aria-live="polite"`).
- **Дашборд:** [`DashboardScreen.vue`](../resources/js/screens/DashboardScreen.vue) — `<section aria-labelledby>` + скрытый **`h1.vk-sr-only`** с названием сообщества и периодом; при пустых **`daily`** и **`top_posts`** — сообщение **`.vk-dashboard-empty`**, графики и таблицы скрыты (профиль и KPI остаются); блоки:
  - [`DashboardProfileCard.vue`](../resources/js/components/dashboard/DashboardProfileCard.vue) — `<section>`, имя как **`h2`**, аватар с осмысленным `alt`, кнопка **«Экспорт»** + всплывающее **`Menu`**: пункты «Скачать CSV» и «Скачать JSON»; [`reportExportDownload`](../resources/js/api/report/reportExportDownload.js) + **`triggerBrowserDownload`** (тот же файл), ошибки под кнопкой;
  - [`DashboardKpiCards.vue`](../resources/js/components/dashboard/DashboardKpiCards.vue) — `<section aria-label="Ключевые показатели…">`, четыре KPI;
  - [`DashboardDailyLineChart.vue`](../resources/js/components/dashboard/DashboardDailyLineChart.vue), [`DashboardTopBarChart.vue`](../resources/js/components/dashboard/DashboardTopBarChart.vue), [`DashboardContentTypesChart.vue`](../resources/js/components/dashboard/DashboardContentTypesChart.vue) — графики Chart.js;
  - [`DashboardTopPostsTable.vue`](../resources/js/components/dashboard/DashboardTopPostsTable.vue) — список топ-10: ссылка на пост через [`vkWallPostUrl`](../resources/js/utils/vkWallPostUrl.js), дата в **`<time datetime>`**;
  - [`DashboardAllPostsTable.vue`](../resources/js/components/dashboard/DashboardAllPostsTable.vue) — **«Все посты»**: ленивая таблица PrimeVue **DataTable** с серверной пагинацией и сортировкой (`POST /report/posts`), фильтр по типу контента, поле поиска по тексту (**`InputText`**, debounce, без автодополнения), в заголовке счётчик **`(отфильтровано из всего)`** с `toLocaleString('ru-RU')`; запросы через [`reportJsonPost`](../resources/js/api/report/reportHttp.js) с CSRF и `AbortController` при смене страницы/фильтров.

### Утилиты и мелкие компоненты

- [`vkWallPostUrl.js`](../resources/js/utils/vkWallPostUrl.js) — ссылка `https://vk.com/wall{owner_id}_{post_id}` для таблиц постов.
- [`DashboardPostTextLink.vue`](../resources/js/components/dashboard/DashboardPostTextLink.vue) — ячейка текста поста в «Все посты» (один расчёт `href`).
- [`chartTheme.js`](../resources/js/utils/chartTheme.js) — общие цвета/легенда для Chart.js (см. также блок «Стили графиков» ниже).

### Клиент API отчёта (Vue)

- Папка **[`resources/js/api/report/`](../resources/js/api/report/)**: [`reportErrors.js`](../resources/js/api/report/reportErrors.js) (ошибки API и Laravel + текст для UI), [`reportHttp.js`](../resources/js/api/report/reportHttp.js) (`reportJsonHeaders`, `reportJsonGet`, `reportJsonPost`, `fetchReportDashboard`), [`reportExportDownload.js`](../resources/js/api/report/reportExportDownload.js) (экспорт + `triggerBrowserDownload`), barrel [`index.js`](../resources/js/api/report/index.js). Рядом лежат Vitest-файлы **`*.test.js`** для этих трёх модулей (см. раздел «Инструменты (JavaScript)» ниже).
- [`resources/js/api/reportFetch.js`](../resources/js/api/reportFetch.js) — реэкспорт из `./report/index.js` для стабильного импорта `@/api/reportFetch.js` (в т.ч. `reportJsonHeaders`, `reportJsonGet`/`Post`, `fetchReportDashboard`, экспорт, ошибки).
  - Подпись **«Отчёт сгенерирован: …»** внизу [`DashboardScreen.vue`](../resources/js/screens/DashboardScreen.vue): текст из **`meta.generated_at`**, стили **`.vk-report-generated`** в `app.scss` (мелкий приглушённый шрифт, по центру, отступ сверху от таблицы).

### Шапка и разметка страницы

- [`AppHeader.vue`](../resources/js/components/layout/AppHeader.vue): бренд — ссылка **`/`** с названием приложения; при отчёте — **`<nav aria-label="Действия отчёта">`** с кнопкой «Новый поиск».
- [`app.blade.php`](../resources/views/app.blade.php): в `<head>` заголовок по умолчанию **«VK Insights»**, иконка **[`public/favicon.svg`](../public/favicon.svg)** (`#5181b8`, белая линия тренда). Динамический заголовок вкладки после загрузки отчёта задаётся в [`App.vue`](../resources/js/App.vue) (см. выше).

### Стили графиков и таблицы постов

Общие цвета/легенда/tooltip/палитра столбчатого топа для Chart.js — [`chartTheme.js`](../resources/js/utils/chartTheme.js) (`chartColors`, `chartTooltipPluginOptions`, …). Классы в [`app.scss`](../resources/scss/app.scss): `vk-chart-wrap--tall`, `vk-chart-wrap--compact`; у столбчатого топа — плагины подсветки бара и полосы, **встроенный** tooltip Chart.js с расширенными `callbacks` (дата, пост, лайки/комменты/репосты, engagement). Блок **«Все посты»** — префикс **`.vk-all-posts__*`** (тёмная карточка, шапка, поиск, селект типа, ячейки DataTable, пагинатор).

## Отличия от формулировок ТЗ

- Отчёт отдаётся **`GET /report?…`**, а не `POST /analyze` + `GET /report/:id`.
- Пути **`POST /report/posts`** и **`POST /report/export`** — расширение под UI (таблица «Все посты» и скачивание).

Проверка клиента VK: [`HttpVkClientTest`](../tests/Unit/HttpVkClientTest.php), при **`VK_SERVICE_TOKEN`** — [`VkHttpClientIntegrationTest`](../tests/Integration/VkHttpClientIntegrationTest.php). Оптимизации фронта и сборки: [`PERF.md`](./PERF.md).

## Инструменты (PHP)

- Статический анализ: `composer phpstan` ([`phpstan.neon`](../phpstan.neon)) — при ошибке bootstrap Laravel см. логи / `.env`.
- Тесты: **`php artisan test`** — в [`phpunit.xml`](../phpunit.xml) два suite: **Unit** (в т.ч. [`ReportServiceTest`](../tests/Unit/ReportServiceTest.php), сервисы дашборда, [`HttpVkClientTest`](../tests/Unit/HttpVkClientTest.php), посты, CSV) и **Integration** ([`VkHttpClientIntegrationTest`](../tests/Integration/VkHttpClientIntegrationTest.php), без токена помечается skipped).

## Инструменты (JavaScript)

- **Vitest** 3 + **jsdom**, окружение и маска файлов заданы в [`vite.config.js`](../vite.config.js) (`test.environment`, `test.include`: `resources/js/**/*.test.js`).
- Запуск из корня репозитория: **`npm run test`** (один прогон), **`npm run test:watch`** (режим наблюдения).
- Покрытие минимально сфокусировано на **обработке ошибок и клиенте отчёта**:
  - [`reportErrors.test.js`](../resources/js/api/report/reportErrors.test.js) — `ReportApiError`, `messageFromLaravelBody`, `reportClientErrorMessage`;
  - [`reportHttp.test.js`](../resources/js/api/report/reportHttp.test.js) — CSRF-заголовки, `reportJsonGet` / `reportJsonPost`, ошибки ответа, `fetchReportDashboard`;
  - [`reportExportDownload.test.js`](../resources/js/api/report/reportExportDownload.test.js) — `POST /report/export`, разбор `Content-Disposition`, ошибки без чтения `blob`, `triggerBrowserDownload` (в jsdom для `URL.createObjectURL` используется временная подмена).

## Локальный запуск

См. [README](../README.md): `composer install`, `npm install`, `.env`; для разработки — `npm run dev` и сервер Laravel (`php artisan serve` или аналог).

## Переменные окружения (backend)

- **VK:** **`VK_SERVICE_TOKEN`**, **`VK_API_VERSION`**, **`VK_USE_MOCK`**, **`VK_INTEGRATION_TEST_GROUP_ID`**, **`VK_CACHE_TTL`** — [`config/vk.php`](../config/vk.php), [`.env.example`](../.env.example).
- **Кэш:** **`CACHE_STORE`** (например `redis` или `database`), **`REDIS_*`** при Redis.
- При **`VK_USE_MOCK=false`** нужен непустой **`VK_SERVICE_TOKEN`**.

Итого: **форма → `GET /report?…` → дашборд (мок или live VK)**; **«Все посты»** — **`POST /report/posts`**; экспорт — **`POST /report/export`**; **health** — **`GET /health`**. План развития: [ROADMAP.md](./ROADMAP.md).
