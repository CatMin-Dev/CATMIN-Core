<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## CATMIN Development Workflow

### Branch strategy

- `main`: production-ready history only.
- `dev`: integration branch for validated work before promotion to `main`.
- `feature/<topic>`: short-lived implementation branches.

### Commit convention

- Prefer explicit conventional-style commits: `feat(...)`, `fix(...)`, `refactor(...)`, `docs(...)`, `test(...)`, `chore(...)`.
- When work is driven by a numbered prompt, mention the scope clearly in the message.
- Keep one logical change per commit whenever practical.

### Versioning

- Modules/addons use semantic versioning `A.B.C`.
- Dashboard version follows the active prompt series: `V3-dev` for the current 3xx cycle.
- Do not reintroduce external public API scope through CI or workflow automation.

### Local quality checks

```bash
composer lint
composer test
npm run build
```

### Optional local hook

Enable the provided pre-commit hook template:

```bash
git config core.hooksPath .githooks
chmod +x .githooks/pre-commit
```

### GitHub Actions

The repository now provides a simple CI pipeline in `.github/workflows/ci.yml`:

- PHP syntax lint
- Laravel test suite
- Frontend build verification with Vite

### Release packaging

Prompt 351 adds a release packaging script that builds a deployable ZIP with a
versioned naming convention.

```bash
composer release:build
```

Generated output:

- archive: `runtime/releases/catmin-<version>.zip`
- git tag: `release/<version>`

Defaults:

- if `--version` is omitted, version is auto-derived from `DASHBOARD_VERSION`
	+ timestamp
- excludes dev artifacts (`node_modules`, `tests`, `.env*`, `scripts/dev`, etc.)
- includes production code, built assets, and `.env.example`

Examples:

```bash
composer release:build -- --version v3-dev-20260401
composer release:build -- --version v3-dev-20260401 --skip-tag
```

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
