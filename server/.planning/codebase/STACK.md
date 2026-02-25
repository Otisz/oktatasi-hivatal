# Technology Stack

**Analysis Date:** 2026-02-25

## Languages

**Primary:**
- PHP 8.5.2 - Server-side application logic, API endpoints, database interactions

## Runtime

**Environment:**
- PHP 8.5.2 (with Xdebug 3.5.0 and OPcache)

**Package Manager:**
- Composer - PHP dependency management
- Lockfile: `composer.lock` (present)

## Frameworks

**Core:**
- Laravel 12.0 (`laravel/framework`) - Full-stack PHP web framework
- Laravel Tinker 2.10.1 (`laravel/tinker`) - Interactive REPL for debugging

**Development Tools:**
- Laravel Pint 1.24 (`laravel/pint`) - Code formatting and standards
- Laravel Sail 1.41 (`laravel/sail`) - Docker development environment
- Laravel Pail 1.2.2 (`laravel/pail`) - Log monitoring tool
- Laravel Boost 2.0 (`laravel/boost`) - Development enhancement tools

**Testing:**
- Pest 4.4 (`pestphp/pest`) - Testing framework
- Pest Plugin for Laravel 4.1 (`pestphp/pest-plugin-laravel`) - Laravel integration for Pest
- PHPUnit 12 (via Pest) - Underlying test runner

**Static Analysis & Quality:**
- Larastan 3.9 (`larastan/larastan`) - PHPStan wrapper for Laravel static analysis
- Rector 2.0 (`driftingly/rector-laravel`) - Automated code refactoring
- PHPMD 2.15 (`phpmd/phpmd`) - PHP Mess Detector for code issues
- PHP CodeSniffer 4.0 (`squizlabs/php_codesniffer`) - Code style compliance

**Testing Utilities:**
- Mockery 1.6 (`mockery/mockery`) - Mocking library for tests
- FakerPHP 1.23 (`fakerphp/faker`) - Fake data generation for testing
- Collision 8.6 (`nunomaduro/collision`) - Error visualization for console

## Key Dependencies

**Critical:**
- laravel/framework ^12.0 - Core framework enabling MVC, routing, ORM, middleware
- laravel/tinker ^2.10.1 - Interactive debugging capability (development)

**Development & Quality:**
- pestphp/pest ^4.4 - Test execution and assertions
- larastan/larastan ^3.9 - Type safety and static analysis
- laravel/pint ^1.24 - Code formatting compliance
- driftingly/rector-laravel ^2.1 - Code modernization and refactoring

## Configuration

**Environment:**
- `.env` - Application environment variables (database, app, mail, queue, cache settings)
- `.env.example` - Template for required environment variables
- Configuration files in `config/` directory:
  - `app.php` - Application name, debug mode, timezone, locale
  - `database.php` - Database connections (SQLite, MySQL, MariaDB, PostgreSQL, SQL Server support)
  - `auth.php` - Authentication guards and user providers
  - `mail.php` - Mailer configuration (SMTP, SES, Postmark, Resend, log)
  - `cache.php` - Cache stores (database, file, Redis, Memcached, DynamoDB)
  - `queue.php` - Queue drivers (database, Redis, SQS, Beanstalkd, sync, deferred)
  - `filesystems.php` - File storage disks (local, public, S3)
  - `logging.php` - Log channels (stack, single, daily, Slack, Papertrail, stderr)
  - `services.php` - Third-party service credentials

**Build:**
- Laravel uses Vite for frontend asset bundling (if frontend assets exist)
- No custom build configuration files detected

## Database

**Default:**
- SQLite (file-based, configured in `database.php` to use `database/database.sqlite`)

**Supported Drivers:**
- MySQL/MariaDB - via credentials in env vars
- PostgreSQL - via credentials in env vars
- SQL Server - via credentials in env vars
- Custom: Multiple connections can be configured via `DB_CONNECTION` env var

## Session & Storage

**Session Driver:**
- Database (`SESSION_DRIVER=database`) - Sessions stored in database table

**File Storage:**
- Local filesystem (default: `storage/app/private`)
- Public storage available at `storage/app/public` (linked at `/storage`)
- S3 support configured (requires AWS credentials)

## Caching

**Cache Store:**
- Database (default: `cache` table)
- Supports: File, Redis, Memcached, DynamoDB, Array, Failover

**Cache Configuration:**
- `CACHE_STORE=database` - Default cache backend
- Redis available for distributed caching (requires Redis server)

## Queue System

**Default Queue:**
- Database (`QUEUE_CONNECTION=database`)

**Supported Drivers:**
- Database - Jobs stored in `jobs` table
- Redis - For high-performance queuing
- SQS (AWS) - Cloud queue service
- Beanstalkd - Distributed queue
- Sync - Synchronous execution
- Deferred/Background - Alternative processing

## Mail Configuration

**Default Mailer:**
- Log driver (`MAIL_MAILER=log`) - Writes emails to logs for development

**Available Mailers:**
- SMTP - Standard email protocol
- SES/SES-v2 - AWS Simple Email Service
- Postmark - Transactional email service
- Resend - Modern email API
- Sendmail - System sendmail
- Array - Testing driver

## Authentication

**Default Guard:**
- Web guard with session driver
- Eloquent user provider (uses `App\Models\User` model)
- Password reset tokens stored in `password_reset_tokens` table

## Logging

**Default Log Channel:**
- Stack channel with single file output
- Log level: debug (development) / adjustable via `LOG_LEVEL`
- Log file: `storage/logs/laravel.log`

**Available Channels:**
- Stack - Multiple channels
- Single - Single file
- Daily - Daily rotated files
- Slack - Webhook integration
- Papertrail - Remote log service
- Stderr - PHP stderr stream
- Syslog - System logs
- Null - Disabled logging

## Platform Requirements

**Development:**
- PHP 8.2+ (currently 8.5.2)
- Composer for dependency management
- SQLite, MySQL, PostgreSQL, or SQL Server available
- Optional: Redis for caching/queues
- Optional: Docker with Sail for containerized development

**Production:**
- PHP 8.2+ runtime
- Database server (MySQL 8.0+, PostgreSQL 12+, SQL Server 2019+, or SQLite)
- Optional: Redis for improved performance
- File storage accessible at `storage/` path
- Queue processing worker for background jobs

---

*Stack analysis: 2026-02-25*
