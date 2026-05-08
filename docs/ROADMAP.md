# План разработки

Опирается на [tz.md](./tz.md) и фактическое состояние в [IMPLEMENTATION.md](./IMPLEMENTATION.md).

## Уже есть

- Каркас Laravel + Vue: главная, форма сообщества и периода, **`GET /report`** (query `group`, `from`, `to`), валидация на сервере.
- **Дашборд после анализа:** карточка сообщества, KPI, линейный график (две оси), топ-10 столбцами, donut по типам контента; переключение форма ↔ дашборд, «Новый поиск» в шапке.
- Моки в **[`app/Integration/Vk/Mock/`](../app/Integration/Vk/Mock/)** (в т.ч. [`MockDashboardFixtureProvider`](../app/Integration/Vk/Mock/MockDashboardFixtureProvider.php)) + [`MockVkClient`](../app/Integration/Vk/MockVkClient.php); [`ReportService`](../app/Services/ReportService.php) собирает **`meta`** через `VkClient::getGroupById` и блоки дашборда через четыре сервиса в [`app/Services/Dashboard/`](../app/Services/Dashboard/).
- Контракты `VkClient`, [`DashboardFixtureProvider`](../app/Contracts/DashboardFixtureProvider.php); реализация живого API — [`HttpVkClient`](../app/Integration/Vk/HttpVkClient.php) + [`Method/`](../app/Integration/Vk/Method/) + DTO [`app/Data/Vk/`](../app/Data/Vk/); конфиг [`config/vk.php`](../config/vk.php); интеграционный тест [`VkHttpClientIntegrationTest`](../tests/Integration/VkHttpClientIntegrationTest.php) (нужен токен и сеть).
- UX формы: loading и ошибка; **chart.js** для графиков.
- Статические файлы для аватара в моке: [`public/media/vk/`](../public/media/vk/) (например `group-photo.svg`).
- HTML: семантика стартового экрана (`section`, `form`, `fieldset`), дашборда (`section`, `h1` для скринридеров, `h2` для названия сообщества), шапки (ссылка на главную, `nav`), основной контент в `#vk-main` в [`App.vue`](../resources/js/App.vue), топ-посты со ссылками на пост ВК и `<time>`.
- Заголовок вкладки: **`VK Insights`** на старте; при открытом отчёте **`VK Insights - {имя} - {период}`** ([`App.vue`](../resources/js/App.vue), [`formatPeriodRu`](../resources/js/utils/dashboardFormat.js)). Фавикон — [`public/favicon.svg`](../public/favicon.svg).
- Пустой дашборд: при пустых **`daily`** и **`top_posts`** — блок **`.vk-dashboard-empty`**, графики и таблицы скрыты ([`DashboardScreen.vue`](../resources/js/screens/DashboardScreen.vue)).
- В **`meta`** ответа **`GET /report`** — поле **`generated_at`** (время генерации на сервере); внизу дашборда выводится строка «Отчёт сгенерирован: …».
- Таблица **«Все посты»** на дашборде: **`POST /report/posts`** ([`ReportPostsController`](../app/Http/Controllers/ReportPostsController.php), [`ReportPostsService`](../app/Services/Posts/ReportPostsService.php), тот же мок-источник, что и у фикстуры отчёта); на фронте — [`DashboardAllPostsTable.vue`](../resources/js/components/dashboard/DashboardAllPostsTable.vue) (пагинация, сортировка, фильтр типа, поиск по тексту без автодополнения, [`reportJsonPost`](../resources/js/api/report/reportHttp.js) или импорт из [`reportFetch.js`](../resources/js/api/reportFetch.js)).
- Экспорт **JSON/CSV**: **`POST /report/export`** ([`ReportExportController`](../app/Http/Controllers/ReportExportController.php), [`ReportService::getExportData`](../app/Services/ReportService.php), [`ReportCsvExporter`](../app/Services/Export/ReportCsvExporter.php)); UI — [`DashboardProfileCard.vue`](../resources/js/components/dashboard/DashboardProfileCard.vue) (кнопка «Экспорт» + меню, [`reportExportDownload`](../resources/js/api/report/reportExportDownload.js)).
- Клиент отчёта на фронте разнесён по [`resources/js/api/report/`](../resources/js/api/report/) с barrel [`index.js`](../resources/js/api/report/index.js); совместимость — реэкспорт в [`reportFetch.js`](../resources/js/api/reportFetch.js); HTTP-слой — [`reportHttp.js`](../resources/js/api/report/reportHttp.js) (`reportJsonGet`, `reportJsonPost`, `fetchReportDashboard`; POST с CSRF для `/report/posts` и `/report/export`).
- Стартовый экран: [`fetchReportDashboard`](../resources/js/api/report/reportHttp.js) (`GET /report`) в [`StartScreen.vue`](../resources/js/screens/StartScreen.vue).
- Автотесты фронта (**Vitest** + jsdom): **`npm run test`** — слой [`resources/js/api/report/`](../resources/js/api/report/) (ошибки, HTTP, экспорт); подробности в [IMPLEMENTATION.md](./IMPLEMENTATION.md), раздел «Инструменты (JavaScript)».
- **Живой VK в продукте:** при **`VK_USE_MOCK=false`** — [`WallPostsForReportLoader`](../app/Services/Vk/WallPostsForReportLoader.php) (`groups.getById` + `wall.get`, фильтр по периоду), [`LiveDashboardFixtureProvider`](../app/Integration/Vk/Support/LiveDashboardFixtureProvider.php), ошибки/429 — [`HttpVkClient`](../app/Integration/Vk/HttpVkClient.php) + исключения в [`app/Integration/Vk/Exception/`](../app/Integration/Vk/Exception/).
- **Кэш выборки стены:** [`WallPostsForPeriodCache`](../app/Integration/Vk/Support/WallPostsForPeriodCache.php), TTL **`VK_CACHE_TTL`**, store **`CACHE_STORE`**, блокировка от параллельного пересчёта.
- **Парсинг ввода:** единый [`VkGroupInputParser`](../app/Integration/Vk/Support/VkGroupInputParser.php) (ссылки, `@slug`, числа; для чисто числового ввода `-123` берётся модуль ID).
- **`GET /health`:** JSON `status`, `vk_mode`, `time` ([`routes/web.php`](../routes/web.php)).
- **Наблюдаемость VK:** [`VkApiCallStats`](../app/Integration/Vk/VkApiCallStats.php), middleware [`LogVkApiCallStats`](../app/Http/Middleware/LogVkApiCallStats.php), канал лога **`vk`**, заголовки **`X-Vk-Calls`** / **`X-Vk-Total-Ms`** в `local`.

## Приоритет 1 (ядро сдачи)

1. ~~Живой VK, пагинация стены, отчёт из реальных постов~~ — сделано (см. выше). Доработки по желанию: более агрессивный backoff, метрики вне лога.
2. ~~Кэш 10–30 мин~~ — сделано (`VK_CACHE_TTL`, Redis/иной драйвер).
3. ~~`GET /health`~~ — сделано.
4. ~~Логирование числа/времени вызовов VK~~ — сделано (`LogVkApiCallStats`).
5. **REST и ТЗ (опционально):** привести к **`POST /analyze`** + **`GET /report/{id}`** из ТЗ или зафиксировать текущий **`GET /report?…`** как контракт (уже описано в README/IMPLEMENTATION).

## Приоритет 2 (интеграция и продукт)

6. Стабильная JSON-схема и версионирование ответа **`GET /report`** при эволюции полей; при необходимости — явная схема для **`all_posts`** в экспорте.

## Приоритет 3 (UX и оптимизация)

7. Доработка таблицы «Все посты» при появлении живых данных (экспорт выборки, производительность больших стен); **общие** состояния **loading / empty / error** для всего блока результатов дашборда (сейчас: empty только при пустых `daily` и `top_posts`; loading/error — у формы, таблицы постов и экспорта по отдельности); адаптив по макету ТЗ.
8. **PERF.md** — [`docs/PERF.md`](./PERF.md) заполнен; при сдаче можно добавить Lighthouse / Web Vitals с цифрами до/после.
9. **README** — при необходимости расширить: токен VK, мок/прод, демо/GIF (краткое описание API уже есть в блоке «VK Insights»).

## Критерии из ТЗ (напоминание)

| Критерий      | Как закрывать |
| ------------- | ------------- |
| VK 35%        | Пагинация, 429, стабильность, мок для разработки |
| Backend 25%   | Кэш, явные маршруты, связка `VkClient` + отчёт |
| UX 20%        | Состояния, читаемый дашборд |
| Оптимизация 20% | `PERF.md` с цифрами |

При нехватке времени ТЗ допускает урезать объём в пользу **VK + кэш + базовый дашборд**.
