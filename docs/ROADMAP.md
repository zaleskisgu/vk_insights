# План разработки

Опирается на [tz.md](./tz.md) и фактическое состояние в [IMPLEMENTATION.md](./IMPLEMENTATION.md).

## Уже есть

- Каркас Laravel + Vue: главная, форма сообщества и периода, **`GET /report`** (query `group`, `from`, `to`), валидация на сервере.
- **Дашборд после анализа:** карточка сообщества, KPI, линейный график (две оси), топ-10 столбцами, donut по типам контента; переключение форма ↔ дашборд, «Новый поиск» в шапке.
- Моки в **[`app/Integration/Vk/Mock/`](../app/Integration/Vk/Mock/)** + тонкий [`MockVkClient`](../app/Integration/Vk/MockVkClient.php); [`ReportService`](../app/Services/Report/ReportService.php) собирает ответ из `MockDashboardData` и `getGroupById`.
- Контракт `VkClient`, заготовка `HttpVkClient`, конфиг `config/vk.php`.
- UX формы: loading и ошибка; **chart.js** для графиков.
- Статические файлы для аватара в моке: [`public/media/vk/`](../public/media/vk/) (например `group-photo.svg`).
- HTML: семантика стартового экрана (`section`, `form`, `fieldset`), дашборда (`section`, `h1` для скринридеров, `h2` для названия сообщества), шапки (ссылка на главную, `nav`), основной контент в `#vk-main` в [`App.vue`](../resources/js/App.vue), топ-посты со ссылками на пост ВК и `<time>`.
- Заголовок вкладки: **`VK Insights`** на старте; при открытом отчёте **`VK Insights - {имя} - {период}`** ([`App.vue`](../resources/js/App.vue), [`formatPeriodRu`](../resources/js/utils/dashboardFormat.js)). Фавикон — [`public/favicon.svg`](../public/favicon.svg).
- Пустой дашборд: при пустых **`daily`** и **`top_posts`** — блок **`.vk-dashboard-empty`**, графики и таблицы скрыты ([`DashboardScreen.vue`](../resources/js/screens/DashboardScreen.vue)).
- В **`meta`** ответа **`GET /report`** — поле **`generated_at`** (время генерации на сервере); внизу дашборда выводится строка «Отчёт сгенерирован: …».
- Таблица **«Все посты»** на дашборде: **`POST /report/posts`** ([`ReportPostsController`](../app/Http/Controllers/ReportPostsController.php), [`ReportPostsService`](../app/Services/Report/ReportPostsService.php), мок [`MockDashboardData::allPosts`](../app/Integration/Vk/Mock/MockDashboardData.php)); на фронте — [`DashboardAllPostsTable.vue`](../resources/js/components/dashboard/DashboardAllPostsTable.vue) (пагинация, сортировка, фильтр типа, поиск по тексту без автодополнения, [`reportJsonPost`](../resources/js/api/report/reportHttp.js) или импорт из [`reportFetch.js`](../resources/js/api/reportFetch.js)).
- Экспорт **JSON/CSV**: **`POST /report/export`** ([`ReportExportController`](../app/Http/Controllers/ReportExportController.php), [`ReportService::getExportData`](../app/Services/Report/ReportService.php), [`ReportCsvExporter`](../app/Services/Report/ReportCsvExporter.php)); UI — [`DashboardProfileCard.vue`](../resources/js/components/dashboard/DashboardProfileCard.vue) (кнопка «Экспорт» + меню, [`reportExportDownload`](../resources/js/api/report/reportExportDownload.js)).
- Клиент отчёта на фронте разнесён по [`resources/js/api/report/`](../resources/js/api/report/) с barrel [`index.js`](../resources/js/api/report/index.js); совместимость — реэкспорт в [`reportFetch.js`](../resources/js/api/reportFetch.js); HTTP-слой — [`reportHttp.js`](../resources/js/api/report/reportHttp.js) (`reportJsonGet`, `reportJsonPost`, `fetchReportDashboard`; POST с CSRF для `/report/posts` и `/report/export`).
- Стартовый экран: [`fetchReportDashboard`](../resources/js/api/report/reportHttp.js) (`GET /report`) в [`StartScreen.vue`](../resources/js/screens/StartScreen.vue).

## Приоритет 1 (ядро сдачи)

1. **`HttpVkClient`**
   - `groups.getById`, `wall.get` с `v` и `access_token` из конфига.
   - Пагинация `wall.get` до конца выборки или границы периода; фильтр постов по `from` / `to`.
   - Ошибки VK (`error` в JSON), 429 / сеть / таймауты — понятные ответы API; при 429 — retry/backoff по политике.
   - В `AppServiceProvider`: **`MockVkClient` | `HttpVkClient`** от `config('vk.use_mock')` и проверка токена при `use_mock = false`.

2. **Данные отчёта из VK**
   - Заменить или дополнить `MockDashboardData`: агрегаты из **реальной** стены (топ, среднее, типы, динамика по дням), парсинг сообщества → `owner_id`.

3. **Кэш** (10–30 мин)
   - Ключ по группе и периоду; драйвер по выбору; TTL из конфига; промах → VK → расчёт → запись.

4. **REST и ТЗ**
   - Отчёт уже отдаётся **`GET /report?group=&from=&to=`** (query вместо `groupId` в path); при желании привести к **`POST /analyze`** + отдельный **`GET /report/{id}`** из ТЗ **или** оставить текущий контракт и держать его в README/IMPLEMENTATION.
   - **`GET /health`**: алиас или отдельный маршрут рядом с `GET /up`.

5. **Наблюдаемость**
   - Лог: время обработки, число HTTP-вызовов к `api.vk.com`.

## Приоритет 2 (интеграция и продукт)

6. Стабильная JSON-схема для **реального** отчёта (согласовать с уже используемым фронтом и полем экспорта **`all_posts`** при необходимости).

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
