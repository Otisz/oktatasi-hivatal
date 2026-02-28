# Technology Stack

**Analysis Date:** 2026-02-28

## Languages

**Primary:**
- PHP 8.4 - Backend (Laravel framework)
- TypeScript 5.8 - Frontend (Vue 3 application)
- JavaScript (ES modules) - Build tooling

**Secondary:**
- SQL (SQLite) - Database queries

## Runtime

**Environment:**
- PHP 8.4+ for backend server
- Node.js 22 (specified in `.nvmrc`) for frontend build and dev server

**Package Manager:**
- **Backend:** Composer - Manages PHP dependencies with `composer.json`
- **Frontend:** npm - Manages JavaScript dependencies with `package.json` and `package-lock.json`

## Frameworks

**Core - Backend:**
- Laravel 12 (^12.0) - Full-stack PHP framework for API and application logic
  - Framework provides: routing, ORM (Eloquent), migrations, authentication, service provider system

**Core - Frontend:**
- Vue 3 (^3.5.29) - Progressive JavaScript framework for reactive UI components
- Vue Router (^5.0.3) - Client-side routing with lazy-loaded views

**UI & Styling:**
- Tailwind CSS (^4.2.1) - Utility-first CSS framework for styling components
- @tailwindcss/vite (^4.2.1) - Vite plugin for Tailwind CSS integration

**HTTP Client:**
- Axios (^1.13.6) - Promise-based HTTP client for API requests

**Data Management & Queries:**
- TanStack Vue Query (^5.92.9) - Data fetching, caching, and synchronization library for Vue 3
  - Used through: `@tanstack/vue-query`

**Build & Dev Tools:**
- Vite (^7.3.1) - Fast frontend build tool and dev server
  - Config: `client/vite.config.ts`
- Vue TSC (^3.2.5) - TypeScript compiler for Vue Single File Components
- @vitejs/plugin-vue (^6.0.4) - Vite plugin for Vue 3 SFC support

**Testing & Linting:**
- Pest PHP (^4.4) - Modern PHP testing framework with Laravel plugin
  - pestphp/pest-plugin-laravel (^4.1) for Laravel-specific testing features
- Biome (^2.4.4) - Fast JavaScript/TypeScript linter and formatter
  - Config: `client/biome.json`
- Laravel Pint (^1.24) - PHP code formatter for Laravel projects
- PHPStan (^3.9 via larastan) - PHP static analysis with Laravel support
- PHP CodeSniffer (^4.0) - PHP code style checking

**Code Quality & Architecture:**
- Rector (^2.0) - PHP code modernization tool via `driftingly/rector-laravel`
- PHPMD (^2.15) - PHP static analysis for mess detection
- Laravel Boost (^2.0) - Performance optimization and tooling

**Development & Debugging:**
- Laravel Tinker (^2.10.1) - Interactive REPL for Laravel
- Laravel Pail (^1.2.2) - Real-time log viewer
- Laravel Sail (^1.41) - Docker environment for Laravel (optional, development-focused)
- FakerPHP (^1.23) - PHP fake data generator for testing
- Mockery (^1.6) - Mock object library for PHP testing
- Nunomaduro Collision (^8.6) - Beautiful exception display

## Key Dependencies

**Critical - Backend:**
- `laravel/framework` (v12) - Core framework providing request routing, response handling, Eloquent ORM, migrations, and service architecture
- `laravel/tinker` (^2.10.1) - Interactive PHP shell for debugging and database exploration

**Critical - Frontend:**
- `vue` (^3.5.29) - Core reactive UI framework
- `axios` (^1.13.6) - HTTP client for all API communication
- `@tanstack/vue-query` (^5.92.9) - Server state management and caching for API responses

**Infrastructure - Backend:**
- Database: Built-in Laravel database support with SQLite as default
- Session: Database-backed sessions via Laravel session driver
- Caching: Database-backed caching via Laravel cache system
- Queue: Database-backed job queue via Laravel queue system

**Development Quality:**
- `larastan/larastan` (^3.9) - Static analysis for Laravel code quality
- `pestphp/pest` (^4.4) - Modern test framework for PHP with assertions and data providers

## Configuration

**Environment:**
- Backend: `.env` file (`.env.example` template provided at `server/.env.example`)
  - Key variables: `APP_NAME`, `APP_ENV`, `DB_CONNECTION`, `SESSION_DRIVER`, `QUEUE_CONNECTION`
- Frontend: Vite environment variables via `import.meta.env.VITE_*`
  - `VITE_API_BASE_URL` - Base URL for axios HTTP client (configured in `client/src/lib/http.ts`)

**Build:**
- **Frontend:**
  - `client/vite.config.ts` - Vite build configuration with Vue and Tailwind plugins
  - `client/tsconfig.json` - TypeScript compiler options for the project
  - `client/tsconfig.app.json` - DOM-specific TypeScript config with path aliases
  - `client/tsconfig.node.json` - Node-specific TypeScript config for build files
- **Backend:**
  - `server/phpstan.neon` - PHPStan static analysis configuration (level 7)
  - `server/pint.json` - Laravel Pint code formatting configuration
  - `server/rector.php` - Rector code modernization configuration
  - `server/phpcs.xml` - PHP CodeSniffer configuration
  - `server/phpmd.xml` - PHPMD analysis configuration
  - `server/phpunit.xml.dist` - PHPUnit test configuration

## Platform Requirements

**Development:**
- Node.js 22 (specified via `.nvmrc` in client directory)
- PHP 8.4 with Composer
- SQLite 3 (included with most PHP installations)

**Production:**
- PHP 8.4+ runtime
- Any PHP-compatible web server (Apache, Nginx, or PHP built-in server)
- SQLite database support or configured MySQL/PostgreSQL alternative
- Optional: Node.js for asset compilation (if rebuilding frontend assets)

---

*Stack analysis: 2026-02-28*
