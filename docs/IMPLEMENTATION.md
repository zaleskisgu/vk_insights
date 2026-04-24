# Текущая реализация (backend)

Снимок на момент разработки, без обещаний вне кода. Полное ТЗ: [tz.md](./tz.md).

## Стек

- **Laravel** (PHP), `routes/api.php` с префиксом `api/`.

## REST API (фактически)

| Метод и путь | Назначение | Заметка к ТЗ |
|--------------|------------|--------------|
| `POST /api/report` | Ответ с «сырыми» данными группы и стены | В [tz.md](./tz.md) указаны `POST /analyze` и `GET /report/:groupId?...` — сейчас **один** защищённый `POST` вместо пары. |
| `GET /up` | Проверка живости (встроенный health Laravel) | В ТЗ — `GET /health`; путь **другой**. |

Все пути из [tz.md](./tz.md) (анализ, отчёт, период, `groupId`) **ещё не реализованы** как отдельные контракты.

## Авторизация внутренних вызовов

- Middleware **`api.token`**: `Authorization: Bearer <token>` или `X-Api-Token: <token>`.
- Секрет: **`config('api.token')`** → переменная окружения `API_TOKEN`.
- Без валидного токена — `401` и JSON `{"message":"Unauthorized."}`.

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

- **Фронтенд** (экран ввода, дашборд, график, состояния, адаптив) — вне текущей среды/папок, если не появилось отдельно.
- **PERF.md** — ожидается по [tz.md](./tz.md) как отдельный артефакт.

## Переменные окружения (релевантно backend)

- `API_TOKEN` — доступ к `POST /api/report`.
- `VK_SERVICE_TOKEN`, `VK_API_VERSION` — на будущее для `HttpVkClient`.

Итог: **базовая точка API + мок VK**; **основной объём ТЗ** (реальный VK, отчёт с метриками, кэш, эндпоинты и фронт) **впереди** — см. [ROADMAP.md](./ROADMAP.md).
