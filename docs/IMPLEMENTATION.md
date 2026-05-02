# Текущая реализация

Снимок по коду репозитория. Полное ТЗ: [tz.md](./tz.md).

## Стек

- **Backend:** **Laravel** (PHP). Маршруты в [`routes/web.php`](../routes/web.php) (группа `web`: сессия + CSRF). Отдельного `routes/api.php` **нет** — только `web` и `console` ([`bootstrap/app.php`](../bootstrap/app.php)).
- **Frontend:** **Vue 3** + **Vite** + **SCSS** ([`resources/scss/app.scss`](../resources/scss/app.scss)) + **PrimeVue** + **Chart.js** (через `primevue/chart`). Точка входа: [`resources/js/app.js`](../resources/js/app.js), корень — [`resources/js/App.vue`](../resources/js/App.vue). Оболочка: [`resources/views/app.blade.php`](../resources/views/app.blade.php). Главная `GET /` отдаёт это представление.

## Маршруты

| Метод и путь | Назначение | Отличие от [tz.md](./tz.md) |
|--------------|------------|-----------------------------|
| `POST /report` | JSON дашборда: `meta`, `summary`, `daily`, `top_posts`, `content_types` (мок-данные + аватар из мока VK) | В ТЗ — `POST /analyze` и `GET /report/:groupId?from=&to=`; сейчас один `POST`, без `groupId` в URL. |
| `GET /up` | Health Laravel | В ТЗ указан `GET /health`. |

## Доступ к `POST /report`

Только с того же origin: сессия + заголовок **`X-XSRF-TOKEN`** ([`resources/js/csrf.js`](../resources/js/csrf.js)). Отдельного API-токена нет.

## Слой VK и моки

- Контракт [`App\Contracts\VkClient`](../app/Contracts/VkClient.php): `getGroupById(int)`, `getWall(int $ownerId, int $count, int $offset)`.
- Папка **[`app/Integration/Vk/Mock/`](../app/Integration/Vk/Mock/)** — все тестовые ответы «как VK API» и мок дашборда:
  - [`MockGroupsGetByIdResponse`](../app/Integration/Vk/Mock/MockGroupsGetByIdResponse.php) — тело `groups.getById` (аватар `photo_*` → локальный [`public/media/vk/group-photo.svg`](../public/media/vk/group-photo.svg)).
  - [`MockWallGetItems`](../app/Integration/Vk/Mock/MockWallGetItems.php) — элементы `wall.get`.
  - [`MockDashboardData`](../app/Integration/Vk/Mock/MockDashboardData.php) — агрегаты дашборда по периоду (суточные ряды, summary, топ-10, типы контента).
- [`MockVkClient`](../app/Integration/Vk/MockVkClient.php): без сети, проксирует в классы из `Mock/`.
- [`HttpVkClient`](../app/Integration/Vk/HttpVkClient.php): заготовка; живые запросы не реализованы.
- [`AppServiceProvider`](../app/Providers/AppServiceProvider.php): в контейнере всегда **`MockVkClient`**. В [`config/vk.php`](../config/vk.php) есть `use_mock`, `access_token`, `version`, но **переключение на HTTP по конфигу не подключено**.

## Контроллер и отчёт

- [`ReportController`](../app/Http/Controllers/ReportController.php): валидирует `group`, `from`, `to` (`from`/`to` — даты, `to` не раньше `from`), передаёт в сервис.
- [`ReportService::getReportData($group, $from, $to)`](../app/Services/Report/ReportService.php): парсит ввод сообщества (ссылка `vk.com/...`, `@slug`, строка); **`meta`** (имя, `screen_name`, `members_count`, период, `photo_200` из мока VK); блоки дашборда из **`MockDashboardData::build()`** (агрегаты **не** считаются из `wall.get`, а из мок-генератора по датам).

## Фронтенд

- [`App.vue`](../resources/js/App.vue): после успешного анализа показывается дашборд; «Новый поиск» в [`AppHeader.vue`](../resources/js/components/AppHeader.vue) сбрасывает состояние.
- [`AnalysisForm.vue`](../resources/js/components/AnalysisForm.vue): `POST /report`, loading / ошибка, при успехе **`emit('report', body)`**.
- [`DashboardView.vue`](../resources/js/components/DashboardView.vue): карточка сообщества (аватар, период, кнопка «Экспорт» disabled), четыре KPI-карточки, линейный график (две оси Y), столбцы топ-10, donut «типы контента». Стили графиков и высоты контейнеров — в `app.scss` (классы `vk-chart-wrap--tall`, `vk-chart-wrap--compact`, модификаторы карточек).

## Что из ТЗ пока не сделано

- Реальные `groups.getById` / `wall.get`, пагинация, фильтр постов по периоду из стены, обработка ошибок VK (429 и т.д.).
- Связка агрегатов дашборда с **реальными** данными стены (сейчас только мок `MockDashboardData`).
- Кэш 10–30 мин, логирование времени и числа запросов к VK.
- Экспорт CSV/JSON (кнопка на UI есть, без логики).
- Таблица постов, отдельное **empty**-состояние блока результатов.
- **PERF.md** в репозитории нет.

## Инструменты (PHP)

- Статический анализ: `composer phpstan` ([`phpstan.neon`](../phpstan.neon)) — при ошибке bootstrap Laravel см. логи / `.env`.
- Тесты: [`tests/Unit/ReportServiceTest.php`](../tests/Unit/ReportServiceTest.php) — ответ `ReportService` и вызов `VkClient::getGroupById`.

## Локальный запуск

См. [README](../README.md): `composer install`, `npm install`, `.env`; для разработки — `npm run dev` и сервер Laravel (`php artisan serve` или аналог).

## Переменные окружения (backend)

- `VK_SERVICE_TOKEN`, `VK_API_VERSION`, `VK_USE_MOCK` — в конфиге; на текущую привязку `VkClient` в `AppServiceProvider` **не влияют** (всегда мок).

Итого: **форма → `POST /report` → мок-дашборд + мок VK для аватара**; живой VK, кэш, REST как в ТЗ, экспорт и **PERF.md** — вперёд, см. [ROADMAP.md](./ROADMAP.md).
