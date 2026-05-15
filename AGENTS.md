# AGENTS

## Purpose
This repository is a small PHP + MySQL ticketing application. This file helps AI coding agents understand the project structure, conventions, and the best place to make changes.

## Key facts
- PHP 8.x app using PDO and MySQL.
- No Node/npm or Composer build system; this is plain PHP, HTML, CSS, and vanilla JavaScript.
- The app is structured for shared hosting and can run from a subfolder using `APP_BASE_URL`.

## Important files
- `README.md` and `SPEC.md` contain project overview, deployment notes, and business flow.
- `config/database.php` defines database connection, environment variables, session settings, and base URL helpers.
- `includes/functions.php` contains core helpers for auth, CSRF, URL generation, ticket generation, and MercadoPago requests.
- `public/` holds the public storefront routes and checkout flow.
- `admin/` holds administrator pages for events, orders, and zones.
- `user/` holds authenticated user pages for profile, orders, and tickets.
- `sql/database.sql` defines the database schema and seed structure.
- `public/api/mercadopago-webhook.php` is the webhook endpoint for MercadoPago.
- `tests/test_functions.php` is the existing test helper file.

## Project conventions
- Use `url('path')` or `assetUrl('path')` for internal links and assets so subfolder deployment works.
- `APP_BASE_URL` may be empty for root deployment or set to a subfolder path for hosted installs.
- Sensitive directories such as `config/`, `includes/`, and `sql/` are expected to be blocked from public access by `.htaccess` on deployed hosting.
- CSRF protection is implemented via `generateCsrfToken()`, `csrfField()`, and `verifyCsrfToken()`.
- Session authentication is centralized in `includes/functions.php` with `isLoggedIn()` and `isAdmin()`.
- Order/payment state transitions are important: `pending`, `confirmed`, `cancelled`, `paid`, `reserved`, `sold`.

## When editing code
- Preserve the simple PHP architecture and avoid introducing framework-specific patterns.
- Prefer changes inside the existing `public/`, `admin/`, `user/`, `includes/`, and `config/` directories.
- For URL changes, use the helper functions to maintain subfolder compatibility.
- For payment or order flow updates, follow the status model described in `SPEC.md`.

## Notes for agents
- If a feature touches deployment or environment config, check `README.md` first for hosting expectations.
- If extending payment integration, use `public/api/mercadopago-webhook.php` and the MercadoPago helper in `includes/functions.php`.
- There is no centralized routing framework; each page is a standalone PHP entry file.
