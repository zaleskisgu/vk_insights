# Текущая реализация

Снимок по коду репозитория. Полное ТЗ: [tz.md](./tz.md).

## Стек

- **Backend:** **Laravel** (PHP). Маршруты в [`routes/web.php`](../routes/web.php) (группа `web`: сессия, middleware в т.ч. **VerifyCsrfToken** для `POST`/`PUT` и т.д.). Отдельного `routes/api.php` **нет** — только `web` и `console` ([`bootstrap/app.php`](../bootstrap/app.php)). Запрос **`GET /report`** CSRF-токеном не сопровождается (типичное поведение Laravel для безопасных методов).
- **Frontend:** **Vue 3** + **Vite** + **SCSS** ([`resources/scss/app.scss`](../resources/scss/app.scss)) + **PrimeVue** + **Chart.js** (через `primevue/chart`). Точка входа: [`resources/js/app.js`](../resources/js/app.js), корень — [`resources/js/App.vue`](../resources/js/App.vue). Оболочка: [`resources/views/app.blade.php`](../resources/views/app.blade.php). Главная `GET /` отдаёт это представление.

## Маршруты

| Метод и путь | Назначение | Отличие от [tz.md](./tz.md) |
|--------------|------------|-----------------------------|
| `GET /report?group=&from=&to=` | JSON дашборда: `meta` (в т.ч. **`generated_at`** — момент сборки ответа), `summary`, `daily`, `top_posts`, `content_types` (мок-данные + аватар из мока VK). Параметры запроса: **`group`**, **`from`**, **`to`** (даты). | В ТЗ — `POST /analyze` и путь с `groupId`; здесь один **`GET`** с query, без CSRF-тела. |
| `POST /report/export` | Скачивание отчёта: тело `group`, `from`, `to`, **`format`**: `json` \| `csv`. Ответ — файл (`application/json` или `text/csv; charset=UTF-8`, `Content-Disposition: attachment`). JSON = дашборд + **`all_posts`** (полный мок-список); CSV — блоки meta, summary, daily, top_posts, content_types, all_posts ([`ReportCsvExporter`](../app/Services/Export/ReportCsvExporter.php)). | В ТЗ — «экспорт отчёта»; формат запроса свой. |
| `POST /report/posts` | Постраничный список постов за период (мок): тело `group`, `from`, `to`, опционально `page`, `per_page`, `sort`, `order`, `q`, `type`; ответ `{ data, meta }` с `meta.total`, `meta.filtered`, пагинацией | В ТЗ отдельного эндпоинта нет; данные из того же мока, что и дашборд. |
| `GET /up` | Health Laravel | В ТЗ указан `GET /health`. |

## Доступ к `GET /report`, `POST /report/export` и `POST /report/posts`

Только с того же origin (сессия). Для **`POST /report/posts`** и **`POST /report/export`** — заголовок **`X-XSRF-TOKEN`** и JSON-тело ([`resources/js/csrf.js`](../resources/js/csrf.js)). Запрос **`GET /report`** без тела, CSRF для него не требуется. Отдельного API-токена нет.

Для **`POST /report/posts`** и **`POST /report/export`** поле **`group`** должно совпадать с **`meta.group_query`** из ответа **`GET /report`** (на дашборде подставляется автоматически).

## Слой VK и моки

- Контракт [`App\Contracts\VkClient`](../app/Contracts/VkClient.php): `getGroupById(int)`, `getWall(int $ownerId, int $count, int $offset)`.
- Папка **[`app/Integration/Vk/Mock/`](../app/Integration/Vk/Mock/)** — тестовые ответы «как VK API» и генерация дашборда:
  - [`MockGroupsGetByIdResponse`](../app/Integration/Vk/Mock/MockGroupsGetByIdResponse.php) — тело `groups.getById` (аватар `photo_*` → локальный [`public/media/vk/group-photo.svg`](../public/media/vk/group-photo.svg)).
  - [`MockWallGetItems`](../app/Integration/Vk/Mock/MockWallGetItems.php) — элементы `wall.get`.
  - [`MockDashboardData`](../app/Integration/Vk/Mock/MockDashboardData.php) — агрегаты по периоду (суточные ряды, summary, топ-10, типы контента) и полный список постов для «Все посты».
  - [`MockDashboardFixtureProvider`](../app/Integration/Vk/Mock/MockDashboardFixtureProvider.php) — реализация [`DashboardFixtureProvider`](../app/Contracts/DashboardFixtureProvider.php): один расчёт периода для [`Summary` / `Daily` / `TopPosts` / `ContentTypes`](../app/Services/Dashboard/) dashboard-сервисов и для экспорта/постраничного списка.
- [`MockVkClient`](../app/Integration/Vk/MockVkClient.php): без сети, проксирует в классы из `Mock/`.
- [`HttpVkClient`](../app/Integration/Vk/HttpVkClient.php): живой VK — **POST** `https://api.vk.com/method/{method}` (form), разбор `response` / `error`; внутри — [`GroupsGetByIdMethod`](../app/Integration/Vk/Method/GroupsGetByIdMethod.php), [`WallGetMethod`](../app/Integration/Vk/Method/WallGetMethod.php); ответы приводятся к массивам через DTO в **[`app/Data/Vk/`](../app/Data/Vk/)** (в т.ч. разбор объекта `response` с `groups` / `profiles` у `groups.getById`).
- [`AppServiceProvider`](../app/Providers/AppServiceProvider.php): **`VkClient`** — при `config('vk.use_mock')` **`MockVkClient`**, иначе **`HttpVkClient`** с `access_token` и `version` из [`config/vk.php`](../config/vk.php) (env: **`VK_USE_MOCK`**, **`VK_SERVICE_TOKEN`**, **`VK_API_VERSION`**). Пустой токен при живом клиенте даст ошибку при первом вызове API (см. `HttpVkClient`).

## Контроллер и отчёт

- [`ReportController`](../app/Http/Controllers/ReportController.php): валидирует `group`, `from`, `to` (`from`/`to` — даты, `to` не раньше `from`), передаёт в сервис.
- [`ReportService`](../app/Services/ReportService.php) (`getReportData` / `getExportData`): парсит ввод сообщества (ссылка `vk.com/...`, `@slug`, строка); **`meta`**: имя, `screen_name`, **`group_query`**, **`owner_id`**, `members_count`, период `from`/`to`, `photo_200` из ответа **`VkClient::getGroupById`**, **`generated_at`** — время генерации (`d.m.Y, H:i:s`, часовой пояс `config('app.timezone')`); блоки дашборда собирают четыре сервиса в [`app/Services/Dashboard/`](../app/Services/Dashboard/) по **`MockDashboardFixtureProvider`** (агрегаты **не** из реальной стены, а из мок-генератора по датам).
- [`ReportPostsController`](../app/Http/Controllers/ReportPostsController.php) + [`ReportPostsService::listPage`](../app/Services/Posts/ReportPostsService.php): фильтр по типу контента и подстроке **`q`**, сортировка и пагинация по тому же мок-списку, что и у фикстуры отчёта.
- [`ReportExportController`](../app/Http/Controllers/ReportExportController.php): валидация `group`, `from`, `to`, `format`; данные из **`ReportService::getExportData()`** (дашборд + **`all_posts`** через провайдер фикстур).

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

## Что из ТЗ пока не сделано

- Использование **`HttpVkClient` в HTTP-отчёте** (подстановка реального id группы из ввода, полная выборка стены за период, пагинация `wall.get`, фильтр по датам, политика 429 / retry).
- Связка агрегатов дашборда с **реальными** данными стены (сейчас только мок через `MockDashboardFixtureProvider` / `MockDashboardData`).
- Кэш 10–30 мин, логирование времени и числа запросов к VK.

При этом **клиент к API** (`HttpVkClient` + методы + DTO) уже есть; проверка — юнит-тест [`HttpVkClientTest`](../tests/Unit/HttpVkClientTest.php) и при наличии токена — интеграционный [`VkHttpClientIntegrationTest`](../tests/Integration/VkHttpClientIntegrationTest.php) (сеть, `VK_SERVICE_TOKEN`; опционально `VK_INTEGRATION_TEST_GROUP_ID`).

См. также описание оптимизаций и замеров сборки: [`PERF.md`](./PERF.md).

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

- **`VK_SERVICE_TOKEN`**, **`VK_API_VERSION`**, **`VK_USE_MOCK`**, **`VK_INTEGRATION_TEST_GROUP_ID`** — см. [`config/vk.php`](../config/vk.php) и [`.env.example`](../.env.example). При **`VK_USE_MOCK=false`** нужен непустой **`VK_SERVICE_TOKEN`**; иначе контейнер отдаст `HttpVkClient`, но вызовы API упадут с сообщением о пустом токене.

Итого: **форма → `GET /report?…` → мок-дашборд + `VkClient` (мок) для полей группы в `meta`**; таблица **«Все посты»** — **`POST /report/posts`**; экспорт — **`POST /report/export`** (JSON/CSV, мок); связка отчёта с живым VK, кэш, REST как в ТЗ и **PERF.md** — см. [ROADMAP.md](./ROADMAP.md).
