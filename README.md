# oktatasi-hivatal

Applicant scoring system for educational programs. Laravel 12 API + Vue 3 SPA.

## Prerequisites

- PHP >= 8.4
- Composer
- Node.js (LTS)
- SQLite

## Server

```bash
cd server
composer setup
```

This runs `composer install`, copies `.env.example` to `.env`, generates an app key, and runs migrations.

Seed the database:

```bash
php artisan db:seed
```

Start the dev server:

```bash
php artisan serve
```

API runs on `http://localhost:8000` by default.

## Client

```bash
cd client
cp .env.example .env
npm install
npm run dev
```

The `VITE_API_BASE_URL` in `.env` points to the API (defaults to `http://localhost:8000`). Change it if your server runs elsewhere.

## Running both

Two terminals -- `php artisan serve` in `server/`, `npm run dev` in `client/`.

## Tests

```bash
cd server
php artisan test
```

## Linting

Server:

```bash
cd server
composer lint
```

Client:

```bash
cd client
npm run lint
```
