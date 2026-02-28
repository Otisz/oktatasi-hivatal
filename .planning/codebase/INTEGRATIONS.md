# External Integrations

**Analysis Date:** 2026-02-28

## APIs & External Services

**Not Detected** - This application does not integrate with external third-party APIs or services. All functionality is self-contained within the codebase.

## Data Storage

**Databases:**
- SQLite (Default)
  - Connection string: `database/database.sqlite`
  - Configuration key: `DB_CONNECTION=sqlite` (configurable in `.env`)
  - Client: Eloquent ORM (built into Laravel framework)
  - Used for: Applicants, Programs, Exam Results, Bonus Points, Sessions, Cache, Jobs

**Alternative Database Support:**
The application is configured to support MySQL and PostgreSQL alternatives through Laravel's database abstraction:
- MySQL configuration available in `server/config/database.php` (lines 48+)
- Requires environment variables: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

**File Storage:**
- Local filesystem only - configured via `FILESYSTEM_DISK=local` in `.env.example`
- Laravel Flysystem provides abstraction but no external cloud storage (S3, etc.) is configured

**Caching:**
- Database-backed caching (default)
  - Configuration key: `CACHE_STORE=database` in `.env`
  - Alternative support for Redis and Memcached configured but not enabled:
    - Redis connection available via `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`
    - Memcached available via `MEMCACHED_HOST` configuration

## Authentication & Identity

**Auth Provider:**
- Custom/None - The application has no configured authentication provider
- User model exists at `server/app/Models/User.php` but is not integrated into the API
- API endpoints are public with no authentication requirement
- Session management configured but not actively used in API routes

## Monitoring & Observability

**Error Tracking:**
- Not detected - No external error tracking service (Sentry, Rollbar, etc.) is configured

**Logs:**
- Local file-based logging
  - Configuration: `server/config/logging.php`
  - Channel: Stack logging with single file driver
  - Log level: Configured via `LOG_LEVEL=debug` in `.env`
  - Output: `storage/logs/laravel.log`

**Developer Tools:**
- Laravel Pail - Real-time log viewer (available via `php artisan pail`)
- PHPStan - Static analysis for code quality during development
- Biome - JavaScript/TypeScript linting during frontend development

## CI/CD & Deployment

**Hosting:**
- Not preconfigured - Application ready for any PHP-capable hosting or Docker environment

**Deployment Support:**
- Docker support available through Laravel Sail (optional development image)
- SSH/Command line deployment compatible
- Environment-based configuration via `.env` file

**CI Pipeline:**
- Not detected - No GitHub Actions, GitLab CI, or other CI service configured
- Test infrastructure present (Pest PHP) but not integrated into CI

## Environment Configuration

**Required env vars for API operation:**
- `APP_KEY` - Laravel application encryption key (generated via `php artisan key:generate`)
- `APP_URL` - Application base URL (defaults to `http://localhost`)
- `DB_CONNECTION` - Database type (default: `sqlite`)

**Required env vars for Frontend-Backend Communication:**
- `VITE_API_BASE_URL` - Frontend axios base URL for API requests
  - Configured in `client/src/lib/http.ts`
  - Set at build/runtime to point to backend (e.g., `http://localhost:8000/api/v1`)

**Optional Configuration:**
- `LOG_LEVEL` - Control logging verbosity
- `SESSION_DRIVER` - Session storage (default: database)
- `QUEUE_CONNECTION` - Background job processing (default: database)
- `CACHE_STORE` - Cache backend (default: database)
- Database credentials for MySQL/PostgreSQL if switching from SQLite

**Secrets location:**
- `.env` file in `server/` directory (NOT committed to git)
- Template provided: `server/.env.example` (safe to commit, used for documentation)

## Webhooks & Callbacks

**Incoming:**
- Not detected - No webhook endpoints configured

**Outgoing:**
- Not detected - No outgoing webhook or callback integrations

## HTTP Communication

**Client to Backend:**
- HTTP only (no WebSocket or real-time connections)
- Axios HTTP client configured in `client/src/lib/http.ts`
- Base URL: Environment variable `VITE_API_BASE_URL`
- Default headers: `Accept: application/json`, `Content-Type: application/json`

**API Endpoints:**

All endpoints prefixed with `/api/v1`:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/applicants` | GET | Retrieve all applicants with their program information |
| `/applicants/{applicant}/score` | GET | Calculate and retrieve admission score for specific applicant |

**Response Format:**
- JSON envelope: `{ "data": {...} }` (wrapper applied by Eloquent Resources)
- Error format: `{ "error": "message" }` (inferred from type definition)

**Response Caching:**
- Frontend: TanStack Vue Query caching with 30-minute stale time
  - Configuration: `client/src/lib/query.ts` (`staleTime: 1000 * 60 * 30`)

## Data Models & Relationships

**Applicant:**
- Belongs to Program
- Has Many ExamResults
- Has Many BonusPoints
- Contains: UUID id, program_id, timestamps

**Program:**
- Has Many Subjects
- Has Many Applicants
- Contains: UUID id, university, faculty, name, timestamps

**ProgramSubject:**
- Subject requirements for each program
- Contains: subject name, level requirements

**ApplicantExamResult:**
- Individual exam scores for applicant
- Contains: subject name, level, percentage score
- Related to admission scoring logic

**ApplicantBonusPoint:**
- Language certificates and bonus point sources
- Contains: bonus point type, language

---

*Integration audit: 2026-02-28*
