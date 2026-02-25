# Architecture

**Analysis Date:** 2026-02-25

## Pattern Overview

**Overall:** Modern Laravel 12 MVC with service provider pattern and middleware-based request handling.

**Key Characteristics:**
- Streamlined Laravel 12 bootstrap structure with declarative middleware/exception configuration
- Eloquent ORM-based data access layer with relationship abstractions
- Service provider-driven application initialization and service registration
- Test-first approach with Pest PHP 4 test framework
- Strict model behavior enforcement via AppServiceProvider (prevent lazy loading and mass assignment)

## Layers

**Presentation (HTTP):**
- Purpose: Handle incoming HTTP requests and generate responses
- Location: `app/Http/Controllers/`
- Contains: Controller classes for request handling
- Depends on: Models, service classes, form requests
- Used by: Routes in `routes/web.php`

**Business Logic (Models & Services):**
- Purpose: Encapsulate domain logic and data transformations
- Location: `app/Models/`, potential service layer in `app/Services/` (not yet created)
- Contains: Eloquent models with relationships, domain logic, factories
- Depends on: Database schema, configuration
- Used by: Controllers, other services, tests

**Data Access (Eloquent ORM):**
- Purpose: Abstract database interactions and provide query builder interface
- Location: `app/Models/` with database schema in `database/migrations/`
- Contains: Model definitions, relationships, attribute casting
- Depends on: Database connection configuration
- Used by: Controllers, services, seeders

**Infrastructure (Providers & Configuration):**
- Purpose: Bootstrap and configure application services
- Location: `bootstrap/app.php`, `bootstrap/providers.php`, `app/Providers/`
- Contains: Service providers, middleware registration, exception handling, routing configuration
- Depends on: Configuration files in `config/`
- Used by: Application bootstrapping during startup

**Database (Migrations & Seeders):**
- Purpose: Define and populate database schema
- Location: `database/migrations/`, `database/seeders/`
- Contains: Migration classes for schema changes, seeder classes for test data
- Depends on: Eloquent models
- Used by: Database initialization and testing workflows

## Data Flow

**HTTP Request Processing:**

1. Request enters through `public/index.php`
2. Bootstrap loads application via `bootstrap/app.php`
3. Request routing determined in `routes/web.php`
4. Middleware pipeline processes request (configured in `bootstrap/app.php` via `withMiddleware()`)
5. Route handler (controller method) executes business logic
6. Database queries executed via Eloquent models
7. Response generated and returned through middleware
8. Response sent to client

**Model Initialization:**

1. Service providers registered in `bootstrap/providers.php`
2. `AppServiceProvider::register()` executes - enforces Eloquent safety constraints
3. `AppServiceProvider::boot()` executes for post-registration initialization
4. Models loaded on-demand when instantiated by controllers/services

**State Management:**
- Request-scoped state: Request data in HTTP request object
- Session state: Stored in sessions table (see `database/migrations/0001_01_01_000000_create_users_table.php`)
- Persistent state: Eloquent models interact with SQLite database
- Test state: Factories generate test data; seeders populate test databases

## Key Abstractions

**Eloquent Model:**
- Purpose: Represent database records and relationships
- Examples: `app/Models/User.php`
- Pattern: Active Record pattern - models encapsulate both data and behavior
- Key methods: `::create()`, `::find()`, `::where()`, relationship accessors

**Service Provider:**
- Purpose: Register and bootstrap application services and configuration
- Examples: `app/Providers/AppServiceProvider.php`
- Pattern: Inversion of Control (IoC) container registration
- Methods: `register()` for service binding, `boot()` for post-initialization

**Factory:**
- Purpose: Generate test/seed data with realistic defaults
- Examples: `database/factories/UserFactory.php`
- Pattern: Builder pattern for flexible object creation
- Usage: `User::factory()->create()`, `User::factory(10)->create()`

**Migration:**
- Purpose: Version database schema and maintain history
- Examples: `database/migrations/0001_01_01_000000_create_users_table.php`
- Pattern: Declarative schema definition with up/down methods
- Usage: `php artisan migrate`, `php artisan migrate:rollback`

## Entry Points

**HTTP Entry Point:**
- Location: `public/index.php`
- Triggers: All HTTP requests to the application
- Responsibilities: Define LARAVEL_START timing, require autoloader, bootstrap application via `bootstrap/app.php`, handle request

**Console Entry Point:**
- Location: `artisan` (executable script)
- Triggers: Manual command execution via `php artisan {command}`
- Responsibilities: Bootstrap application, parse console input, execute Artisan commands

**Bootstrap Entry Point:**
- Location: `bootstrap/app.php`
- Triggers: Application initialization (both HTTP and console)
- Responsibilities: Configure routing, middleware, exception handling; return configured Application instance

## Error Handling

**Strategy:** Centralized exception handling via Laravel's exception handler configured in `bootstrap/app.php`

**Patterns:**
- Exception catching and transformation in middleware
- HTTP status code mapping for known exceptions
- Detailed error pages in development mode
- Logging via Laravel's logging system (configured in `config/logging.php`)
- Custom exception rendering registered in `withExceptions()` callback

## Cross-Cutting Concerns

**Logging:**
- Framework: Laravel's Monolog-based logging facade
- Configuration: `config/logging.php`
- Usage: Automatic request/exception logging; custom logging via `Log` facade

**Validation:**
- Approach: Form Request classes (pattern: `app/Http/Requests/{Feature}Request.php`) - not yet created
- Validation rules: Declarative in Form Request `rules()` method
- Error messages: Customizable via `messages()` method in Form Request

**Authentication:**
- Framework: Laravel Sanctum (or session-based per config)
- Configuration: `config/auth.php`
- Guards: Web guard for sessions, Sanctum for API tokens
- User model: `app/Models/User.php` extends Authenticatable

**Authorization:**
- Approach: Gates and Policies (not yet created)
- Location: `app/Policies/` for policy classes
- Pattern: Policy methods receive authenticated user and model instance

**Database:**
- Connection: SQLite via `database/database.sqlite` (see `config/database.php`)
- Transaction support: Database transactions via `DB::transaction()` or Eloquent's transaction helpers
- Relationship loading: Eager loading via `with()` to prevent N+1 queries
- Safety constraints enforced in `AppServiceProvider::register()`:
  - `Model::unguard()` allows mass assignment (use fillable/guarded carefully)
  - `Model::preventLazyLoading()` throws exception on lazy loading
  - `Model::preventAccessingMissingAttributes()` throws exception on accessing undefined attributes

---

*Architecture analysis: 2026-02-25*
