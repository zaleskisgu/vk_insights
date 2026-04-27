# Текущая реализация

Снимок по коду репозитория. Полное ТЗ: [tz.md](./tz.md).

## Стек

- **Backend:** **Laravel** (PHP). Маршруты в [`routes/web.php`](../routes/web.php) (группа `web`: сессия + CSRF). Отдельного `routes/api.php` **нет** — только `web` и `console` ([`bootstrap/app.php`](../bootstrap/app.php)).
- **Frontend:** **Vue 3** + **Vite** + **SCSS** ([`resources/scss/app.scss`](../resources/scss/app.scss)) + **PrimeVue** (тёмная тема в разметке). Точка входа: [`resources/js/app.js`](../resources/js/app.js), корень — [`resources/js/App.vue`](../resources/js/App.vue). Оболочка: [`resources/views/app.blade.php`](../resources/views/app.blade.php). Главная `GET /` отдаёт это представление.

## Маршруты

| Метод и путь | Назначение | Отличие от [tz.md](./tz.md) |
|--------------|------------|-----------------------------|
| `POST /report` | JSON с полями `group`, `wall` с мок-клиента VK | В ТЗ — `POST /analyze` и `GET /report/:groupId?from=&to=`; сейчас один `POST`, без `groupId` в URL. |
| `GET /up` | Health Laravel | В ТЗ указан `GET /health`. |

## Доступ к `POST /report`

Только с того же origin: сессия + заголовок **`X-XSRF-TOKEN`** ([`resources/js/csrf.js`](../resources/js/csrf.js)). Отдельного API-токена нет.

## Слой VK

- Контракт [`App\Contracts\VkClient`](../app/Contracts/VkClient.php): `getGroupById(int)`, `getWall(int $ownerId, int $count, int $offset)`.
- [`MockVkClient`](../app/Integration/Vk/MockVkClient.php): без сети; мок группы и стены (`likes` / `comments` / `reposts`, `attachments`).
- [`HttpVkClient`](../app/Integration/Vk/HttpVkClient.php): заготовка; при непустом токене методы по-прежнему бросают `RuntimeException` (живые запросы к API не реализованы).
- [`AppServiceProvider`](../app/Providers/AppServiceProvider.php): в контейнере всегда привязан **`MockVkClient`**. В [`config/vk.php`](../config/vk.php) есть `use_mock`, `access_token`, `version`, но **переключение на HTTP по конфигу не подключено**.

## Контроллер и бизнес-логика

- [`ReportController`](../app/Http/Controllers/ReportController.php): валидирует `group`, `from`, `to` (`from`/`to` — даты, `to` не раньше `from`).
- [`ReportService::getReportData()`](../app/Services/Report/ReportService.php): **не получает** введённые поля; жёстко `groupId = 1`, `ownerId = -1`; возвращает сырой `group` + `wall` без агрегатов (топ, среднее, типы, динамика по дням).

То есть форма и API принимают сообщество и период, но отчёт по ним **не строится**.

## Что из ТЗ пока не сделано

- Реальные `groups.getById` / `wall.get`, пагинация, фильтр по периоду, обработка ошибок VK (429 и т.д.).
- Кэш 10–30 мин, логирование времени и числа запросов к VK.
- Агрегаты дашборда, экспорт CSV/JSON.
- **Фронтенд:** экран ввода и запрос [`AnalysisForm.vue`](../resources/js/components/AnalysisForm.vue) — есть **loading** и **ошибка**; при успехе данные только **`console.log`**, таблицы, графиков, экспорта и отдельного **пустого** состояния результата нет.
- **PERF.md** в репозитории нет.

## Инструменты (PHP)

- Статический анализ: `composer phpstan` ([`phpstan.neon`](../phpstan.neon)).
- Тесты: [`tests/Unit/ReportServiceTest.php`](../tests/Unit/ReportServiceTest.php) — юнит-связка мока `VkClient` и `ReportService`.

## Локальный запуск

См. [README](../README.md): `composer install`, `npm install`, `.env`; для разработки — `npm run dev` и сервер Laravel (`php artisan serve` или аналог).

## Переменные окружения (backend)

- `VK_SERVICE_TOKEN`, `VK_API_VERSION`, `VK_USE_MOCK` — описаны в конфиге; на текущую привязку `VkClient` в `AppServiceProvider` **не влияют** (всегда мок).

Итого: **форма + `POST /report` + мок VK и сырой ответ**; метрики дашборда, кэш, REST как в ТЗ, живой VK и **PERF.md** — вперёд, см. [ROADMAP.md](./ROADMAP.md).
