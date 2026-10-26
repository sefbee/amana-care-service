# Admin Console

A Laravel administration console for managing users, roles, permissions, and application access. The bundled Blade interface provides authentication, role based navigation, and account management.

## Stack

- PHP 8.2+ and Laravel 12
- Blade with the bundled admin theme assets
- Vite, Tailwind CSS, and npm
- MySQL by default, with Laravel's supported database drivers
- Laravel authentication, role and permission management, migrations, and seeders

## Setup

1. Install PHP/Composer and Node.js/npm versions supported by the project.
2. Copy `.env.example` to `.env` and configure the database, mail, and any optional integrations.
3. Run `composer install` and `npm install`.
4. Generate an application key with `php artisan key:generate`.
5. Create the configured database, then run `php artisan migrate`.
6. Set `SUPERADMIN_USERID`, `SUPERADMIN_NAME`, `SUPERADMIN_EMAIL`, a strong `SUPERADMIN_PASSWORD`, and a `USER_DEFAULT_PASSWORD` in `.env` before running `php artisan db:seed`.
7. Run `npm run build` for production assets, or `npm run dev` during development.

The super administrator password is assigned when the account is first created. Changing the environment value later does not reset an existing account password.

## Branding

`APP_NAME`, `APP_PURPOSE`, `APP_LOGO`, and `APP_PRIMARY_COLOR` control the application name, purpose, optional logo, and primary color. Set `APP_LOGO` to a path under `public` (for example, `images/logo.svg`) or leave it blank to display the application name as text.

## Project structure

- `app/`, `bootstrap/`, and `config/`: Laravel application, bootstrap, and configuration
- `routes/`: web, API, and authentication routes
- `resources/views/`: shared layouts, UI components, and application pages
- `public/theme/` and `public/vendor/`: bundled theme and published runtime assets; retain third-party license notices when redistributing
- `database/`: migrations, factories, and seeders
- `tests/`: existing Laravel feature tests

Local `.env` files, SQL dumps, SQLite databases, uploaded files, dependencies, caches, and generated build assets are excluded from Git. Keep credentials and private data out of tracked files.
# amana-care-service
