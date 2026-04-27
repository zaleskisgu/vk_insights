# Текущая реализация (backend)

Снимок на момент разработки, без обещаний вне кода. Полное ТЗ: [tz.md](./tz.md).

## Стек

- **Backend:** **Laravel** (PHP). Отчёт по форме: **`POST /report`** в [`routes/web.php`](../back/routes/web.php) (стек `web`: сессия + CSRF). Отдельного `api`-файла маршрутов **нет** — намеренно, только `web` + `console`.
- **Frontend:** **Vue 3** + **Vite** + **Tailwind CSS 4** + **PrimeVue** (тема Aura, тёмный режим). Точка входа: [`back/resources/js/app.js`](../back/resources/js/app.js), корневой компонент — [`back/resources/js/App.vue`](../back/resources/js/App.vue). Оболочка страницы: [`back/resources/views/app.blade.php`](../back/resources/views/app.blade.php). Главная (`GET /`) отдаёт это представление ([`back/routes/web.php`](../back/routes/web.php)).

## Маршруты (фактически)

| Метод и путь | Назначение | Заметка к ТЗ |
|--------------|------------|--------------|
| `POST /report` | JSON-ответ с «сырыми» данными группы и стены (только из браузера, CSRF) | Тело запроса валидируется (`group`, `from`, `to`); сервис отчёта пока **не** использует эти поля. В [tz.md](./tz.md) указаны `POST /analyze` и `GET /report/:groupId?...` — сейчас **один** `POST` вместо пары. |
| `GET /up` | Проверка живости (встроенный health Laravel) | В ТЗ — `GET /health`; путь **другой**. |

**Файлы маршрутов:** только [`routes/web.php`](../back/routes/web.php) и [`routes/console.php`](../back/routes/console.php). Файла **`routes/api.php` нет** — префикса `/api` у приложения нет (см. [`bootstrap/app.php`](../back/bootstrap/app.php)).

Все пути из [tz.md](./tz.md) (анализ, отчёт, период, `groupId`) **ещё не реализованы** как отдельные контракты.

## Доступ к `POST /report`

- Отдельного **API-токена** и middleware вроде `VerifyApiToken` **нет**.
- Доступ только **из браузера** на том же origin: сессия Laravel + заголовок **`X-XSRF-TOKEN`** (как у обычных web-форм). Это защита от CSRF, не вход пользователя по логину/паролю.

## Слой VK

- Контракт **`App\Contracts\VkClient`**: `getGroupById(int)`, `getWall(int $ownerId, $count, $offset)` — в ответе ожидается форма тела, близкая к [groups.getById](https://dev.vk.com/ru/method/groups.getById) и [wall.get](https://dev.vk.com/ru/method/wall.get) (см. мок).
- **`App\Integration\Vk\MockVkClient`**: сети нет; отдаёт структурированные мок-данные (группы + `profiles`, стена `count` + `items` с `likes`/`comments`/`reposts` и `attachments` под типы контента).
- **`App\Integration\Vk\HttpVkClient`**: заготовка; методы **пока бросают** `RuntimeException` (живой VK в коде **не** подключён).
- В **`AppServiceProvider`** в контейнере привязан **только** `MockVkClient` (без переключения `VK_USE_MOCK` / HTTP).

`config/vk.php` ( `VK_USE_MOCK`, `VK_SERVICE_TOKEN`, `VK_API_VERSION` ) есть под будущий реальный клиент, но **на выбор реализации в рантайме сейчас не влияет**.

## Бизнес-логика

- **`App\Services\Report\ReportService`**: жёстко `groupId = 1` / `ownerId = -1`, вызывает `getGroupById` и `getWall`, кладёт результаты в `group` и `wall` без агрегатов.
- **Нет**: периода `from`/`to`, пагинации, парсинга ссылки, топа, среднего, распределения по типам, динамики по дням, экспорта, кэша, логирования метрик VK.

## Что в репозитории не покрыто ТЗ (ещё)

- **Фронтенд:** реализован **первый экран** (форма + `POST /report` с CSRF). **Дашборд** (таблица, график, экспорт), полная связка с отчётом и состояниями по ТЗ — **впереди**.
- **PERF.md** — ожидается по [tz.md](./tz.md) как отдельный артефакт.

## Инструменты разработки (PHP)

- Статический анализ: **`composer phpstan`** ([`phpstan.neon`](../back/phpstan.neon), Larastan).
- Тесты: **`php artisan test`** или **`vendor/bin/phpunit`** — в [`phpunit.xml`](../back/phpunit.xml) подключён только suite **`Unit`** ([`tests/Unit/ReportServiceTest.php`](../back/tests/Unit/ReportServiceTest.php)).

## Локальный запуск приложения

Краткая инструкция: [README проекта `back/`](../back/README.md). Типовой сценарий: каталог **`back/`**, `composer install`, `npm install`, настройка `.env`, при разработке пара процессов — **`npm run dev`** (Vite) и сервер Laravel (**`php artisan serve`** или встроенный `php -S … -t public` / виртуальный хост на `public`). Для проверки без dev-сервера фронта: **`npm run build`** и один процесс PHP.

## Переменные окружения (релевантно backend)

- `VK_SERVICE_TOKEN`, `VK_API_VERSION` — на будущее для `HttpVkClient`.

Итог: **POST /report (web + CSRF) + мок VK**; **основной объём ТЗ** (реальный VK, отчёт с метриками, кэш, фронт) **впереди** — см. [ROADMAP.md](./ROADMAP.md).
