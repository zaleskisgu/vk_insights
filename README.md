<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## VK Insights (этот репозиторий)

Мини-приложение по [ТЗ](./docs/tz.md): Laravel + Vue (Vite), форма на главной, **`POST /report`** только из браузера (CSRF). После отправки формы открывается **дашборд** (графики + KPI); данные отчёта и ответы «как VK API» пока из **моков** в [`app/Integration/Vk/Mock/`](./app/Integration/Vk/Mock/). Аватар группы в моке — файл в **`public/media/vk/`** (см. [docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md)).

- **Документация по коду:** [docs/IMPLEMENTATION.md](./docs/IMPLEMENTATION.md), план: [docs/ROADMAP.md](./docs/ROADMAP.md).

### Локальный запуск

1. Рабочий каталог — **корень репозитория** (где лежат `artisan`, `composer.json`, `package.json`).
2. `composer install`, скопировать `.env.example` → `.env`, `php artisan key:generate`.
3. `npm install`.
4. Разработка: в одном терминале **`npm run dev`**, в другом **`php artisan serve`** (или свой хост с корнем **`public/`**). Открыть URL Laravel (часто `http://127.0.0.1:8000`).
5. Продакшен-сборка фронта без `npm run dev`: **`npm run build`**.

### Команды

| Команда | Назначение |
|---------|------------|
| `composer phpstan` | Статический анализ PHP |
| `php artisan test` | PHPUnit (unit-тесты) |
| `npm run dev` / `npm run build` | Vite |

На Windows при ошибке `php artisan serve` на портах можно раздавать приложение через **`php -S 127.0.0.1:8090 -t public`** из корня проекта или через OSPanel.

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
