<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## VK Insights (этот репозиторий)

Мини-приложение по [ТЗ](./docs/tz.md): Laravel + Vue (Vite), форма на главной, запрос отчёта **`GET /report`** с параметрами `group`, `from`, `to`; **`POST /report/posts`** и **`POST /report/export`** — с CSRF из браузера. После анализа открывается **дашборд** (графики + KPI и таблица **«Все посты»**).

Параметры **`from`** и **`to`** — календарные даты (удобный формат для клиента: **`YYYY-MM-DD`**). На сервере проверка «не позже сегодня» и максимальная длина периода (**`VK_PERIOD_MAX_DAYS`**, по умолчанию 365 дней) считаются в часовом поясе **`VK_TIMEZONE`** из [`config/vk.php`](./config/vk.php) (по умолчанию `Europe/Moscow`), чтобы «сегодня» совпадало с ожиданиями для постов VK. Три эндпоинта отчёта ограничены **`throttle:30,1`** (30 запросов в минуту с одного IP).

- **`VK_USE_MOCK=true`** (по умолчанию в `.env.example`): агрегаты и посты из **мок-фикстур** [`app/Integration/Vk/Mock/`](./app/Integration/Vk/Mock/), [`MockVkClient`](./app/Integration/Vk/MockVkClient.php).
- **`VK_USE_MOCK=false`** + **`VK_SERVICE_TOKEN`**: реальные **`groups.getById`** и **`wall.get`** ([`HttpVkClient`](./app/Integration/Vk/HttpVkClient.php)), дашборд из [`LiveDashboardFixtureProvider`](./app/Integration/Vk/Support/LiveDashboardFixtureProvider.php); повторные запросы с тем же сообществом и периодом кэшируются ([`WallPostsForPeriodCache`](./app/Integration/Vk/Support/WallPostsForPeriodCache.php), TTL **`VK_CACHE_TTL`**, store из **`CACHE_STORE`**, для продакшена обычно **`redis`**).

Сервисный эндпоинт **`GET /health`** — JSON `status`, `vk_mode` (`mock` \| `live`), `time`. Подробности: [docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md).

- **Документация по коду:** [docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md), план: [docs/ROADMAP.md](./docs/ROADMAP.md), оптимизации и замеры сборки: [docs/PERF.md](./docs/PERF.md).
- **Заголовок вкладки и фавикон:** по умолчанию в [`resources/views/app.blade.php`](./resources/views/app.blade.php); после открытия отчёта заголовок меняется в Vue ([`resources/js/App.vue`](./resources/js/App.vue)) — подробности в IMPLEMENTATION, раздел «Фронтенд».

### Docker (одна команда)

В корне репозитория: **`docker compose up --build`** ([`docker-compose.yml`](./docker-compose.yml)). Кратко здесь; подробности по составу образа и переменным — [docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md), раздел «Docker».

- **Браузер:** **http://localhost:8080** — с хоста порт **8080** проброшен на **8000** в контейнере. Строка в логах «Server running on http://0.0.0.0:8000» относится к процессу **внутри** контейнера; с ПК открывайте **localhost:8080**, а не `0.0.0.0:8000`.
- **Сервисы:** **`app`** (образ из [`Dockerfile`](./Dockerfile): PHP **8.4**; отдельная стадия Node выполняет **`npm ci`** и **`npm run build`**, в финальный образ попадает **`public/build`**) и **`redis`**. В Compose для приложения заданы **`CACHE_STORE=redis`** и **`REDIS_HOST=redis`**. Redis с машины: **127.0.0.1:6379**.
- **Данные:** тома Compose для **`database/`** (SQLite) и **`storage/`**. При старте [`docker/entrypoint.sh`](./docker/entrypoint.sh) при отсутствии `.env` копирует `.env.example`, создаёт ключ, выполняет **`migrate`**, запускает **`php artisan serve`**.
- **Образы:** базовые образы тянутся из **AWS Public ECR** (`public.ecr.aws/docker/library/…`), чтобы реже получать **429** при анонимном pull с Docker Hub.
- **Секреты VK:** свой **`.env`** в каталоге проекта; при необходимости смонтировать в **`app`**: **`./.env:/var/www/html/.env:ro`**.

### Локальный запуск

1. Рабочий каталог — **корень репозитория** (где лежат `artisan`, `composer.json`, `package.json`).
2. Установленный **PHP 8.4+** (см. [`composer.json`](./composer.json)), затем `composer install`, скопировать `.env.example` → `.env`, `php artisan key:generate`.
3. `npm install`.
4. Разработка: в одном терминале **`npm run dev`**, в другом **`php artisan serve`** (или свой хост с корнем **`public/`**). Открыть URL Laravel (часто `http://127.0.0.1:8000`).
5. Продакшен-сборка фронта без `npm run dev`: **`npm run build`**.
6. Прямая ссылка на отчёт в браузере: **`/?group=ид_или_имя&from=YYYY-MM-DD&to=YYYY-MM-DD`** — приложение само запросит **`GET /report`** с этими параметрами и покажет дашборд (без повторной отправки формы).

### Команды

| Команда | Назначение |
|---------|------------|
| `composer phpstan` | Статический анализ PHP |
| `php artisan test` | PHPUnit: unit + integration ([`tests/Integration/VkHttpClientIntegrationTest.php`](./tests/Integration/VkHttpClientIntegrationTest.php) пропускается без `VK_SERVICE_TOKEN`) |
| `npm run test` / `npm run test:watch` | Vitest: клиент отчёта (`reportErrors`, `reportHttp`, `reportExportDownload`), см. [docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md) |
| `npm run dev` / `npm run build` | Vite |

На Windows при ошибке `php artisan serve` на портах можно раздавать приложение через **`php -S 127.0.0.1:8090 -t public`** из корня проекта или через OSPanel.

Переменные в `.env` (см. `.env.example`):

- **VK:** **`VK_USE_MOCK`**, **`VK_SERVICE_TOKEN`** (обязателен при `false`), **`VK_API_VERSION`**, опционально **`VK_INTEGRATION_TEST_GROUP_ID`** (интеграционный тест).
- **Период и время:** **`VK_TIMEZONE`** (отображение дат постов, `generated_at`, граница «сегодня» при валидации), **`VK_PERIOD_MAX_DAYS`** (максимум дней в диапазоне `from`…`to`).
- **Стена (live):** **`VK_WALL_MAX_PAGES`**, **`VK_WALL_PAGE_SIZE`** (до 100, см. [`config/vk.php`](./config/vk.php)).
- **Кэш стены:** **`VK_CACHE_TTL`** — секунды (0 = без кэша; по умолчанию 1200). Драйвер — **`CACHE_STORE`** (для Redis: `redis` + **`REDIS_*`**).

---

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
