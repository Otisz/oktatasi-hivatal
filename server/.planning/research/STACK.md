# Technology Stack

**Project:** Hungarian University Admission Score Calculator API
**Researched:** 2026-02-25
**Confidence:** HIGH — stack is fully determined by the existing project skeleton; versions extracted directly from `composer.lock`

---

## Stack Status: Fixed

This project has a pre-configured Laravel 12 skeleton with all quality tooling already installed. The stack is **not a choice to be made**; it is a constraint to be honored. Every recommendation below reflects what is already locked in `composer.lock` and configured in `pint.json`, `phpstan.neon`, and `rector.php`.

There are **no third-party scoring or DDD libraries to add**. The domain logic (Value Objects, Strategy pattern, validation chain, calculators) is implemented in pure PHP 8.5 within Laravel's conventions.

---

## Recommended Stack

### Core Runtime

| Technology | Locked Version | Purpose | Why |
|------------|---------------|---------|-----|
| PHP | 8.5.2 | Runtime | Provides `readonly` properties (8.1+), enums (8.1+), and intersection types needed for Value Objects; `declare(strict_types=1)` enforced globally |
| laravel/framework | v12.53.0 | Application framework | Streamlined bootstrap structure, Eloquent ORM, IoC container for singleton registry, declarative exception rendering in `bootstrap/app.php` — all used directly by this project |
| laravel/tinker | v2.11.1 | REPL debugging | Useful during development for quick Eloquent queries; already installed |

### Domain Architecture (No New Packages)

The domain layer is built entirely from PHP language features:

| Pattern | PHP Feature Used | Location |
|---------|-----------------|---------|
| Value Objects | PHP classes with typed constructor params and `readonly` semantics | `app/ValueObjects/` |
| Enums | PHP 8.1+ backed enums with string values | `app/Enums/` |
| Strategy pattern | PHP interface + concrete implementing class | `app/Contracts/` + `app/Services/` |
| Exception hierarchy | Abstract base exception class with typed subclasses | `app/Exceptions/` |
| Service orchestration | Plain PHP classes resolved via Laravel IoC container | `app/Services/` |

**Rationale:** No external DDD package (e.g., `spatie/laravel-data`, `hirethunk/verbs`) is needed or appropriate. The domain is small and fully specified. Adding a package for three Value Objects and one interface would introduce indirection with zero benefit.

### Database

| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| SQLite | File-based (bundled) | Development and test database | Specified in PRD; zero-config, fast for small seeded datasets; `database/database.sqlite` already present |
| Eloquent ORM | (via laravel/framework v12.53.0) | Data access | `Model::preventLazyLoading()` enforced in `AppServiceProvider`; eager loading via `with('subjects')` is the mandated pattern |

**What NOT to use:** Do not use `DB::` facade for raw queries. The project enforces Eloquent relationships throughout. `Model::unguard()` is set globally, so mass assignment is open — factories use this.

### Testing

| Library | Locked Version | Purpose | Why |
|---------|---------------|---------|-----|
| pestphp/pest | v4.4.1 | Test runner and assertion API | Already installed; concise `expect()` syntax fits unit testing of Value Objects well |
| pestphp/pest-plugin-laravel | v4.1.0 | Laravel integration (RefreshDatabase, HTTP test helpers) | Provides `$this->getJson()`, `RefreshDatabase`, and `seed()` for feature tests |
| phpunit/phpunit | 12.5.12 | Underlying test engine (used by Pest) | Drives Pest; Mockery integration goes through PHPUnit |
| mockery/mockery | 1.6.12 | Mock objects in unit tests | Used to mock `ProgramRegistry`, `BasePointCalculator`, `BonusPointCalculator` in `AdmissionScoringServiceTest` |
| fakerphp/faker | v1.24.1 | Realistic fake data in factories | Standard Laravel factory tooling |
| nunomaduro/collision | v8.9.1 | Error display in console | Improves test failure output readability |

**Test split:** Unit tests (Value Objects, Calculators, Registry, Service with mocks) live in `tests/Unit/`; Feature tests (HTTP acceptance cases) live in `tests/Feature/`. `RefreshDatabase` + seeders provide deterministic state for feature tests.

### Code Quality Tooling

| Tool | Locked Version | Purpose | Configuration |
|------|---------------|---------|--------------|
| laravel/pint | v1.27.1 | Opinionated PHP formatter | `pint.json`: `laravel` preset + `declare_strict_types`, `final_class`, `yoda_style`, `strict_comparison`, `void_return` |
| larastan/larastan | v3.9.2 | PHPStan wrapper for Laravel | `phpstan.neon`: level 7, includes Pest extension, excludes `tests/` |
| driftingly/rector-laravel | 2.1.2 | Automated refactoring to Laravel 12 idioms | `rector.php`: targets `app/`, `database/`, `routes/`, `tests/` — runs before committing |
| phpmd/phpmd | v2.15 | Mess detection (complexity, unused vars) | `phpmd.xml` |
| squizlabs/php_codesniffer | v4.0 | Standards compliance checks | `phpcs.xml` |

**Run order for lint gate:** `rector` → `phpstan` → `pint --parallel` (matches `composer lint` script).

### Development Environment

| Tool | Locked Version | Purpose | Why |
|------|---------------|---------|-----|
| laravel/sail | v1.41 | Docker dev environment | Available if Docker is preferred; not required — SQLite works locally without it |
| laravel/pail | v1.2.2 | Real-time log tailing | Useful during feature test debugging |
| laravel/boost | v2.0 | MCP server for Claude tooling | Project-specific AI development assist |

---

## Alternatives Considered

| Category | Chosen | Alternative | Why Not |
|----------|--------|-------------|---------|
| Value Objects | Plain PHP classes | `spatie/laravel-data` DTOs | Over-engineered for 3 VOs; adds hydration complexity with no gain |
| Enum validation | PHP 8.1 backed enums | String constants + validation | Enums provide type safety at the language level; no validation code needed |
| Strategy resolution | `ProgramRegistry` service + IoC singleton | Laravel service container tags | Tags add indirection; single concrete strategy (`DatabaseProgramRequirements`) makes tags unnecessary |
| Exception rendering | `bootstrap/app.php` `->withExceptions()` | `app/Exceptions/Handler.php` | Laravel 12 has no `Handler.php` — declarative rendering in `bootstrap/app.php` is the correct pattern |
| Test assertions | Pest `expect()` API | PHPUnit `$this->assert*()` | Pest's fluent API is more readable for VO and calculator assertions; already the project standard |
| Database | SQLite | MySQL/PostgreSQL | PRD constraint; appropriate for a fixed-seed, read-only scoring API |

---

## Key Architectural Constraints from Existing Config

These are enforced by the tooling, not optional:

1. **`declare(strict_types=1)` in every file** — Pint `declare_strict_types` rule enforces this automatically.
2. **All classes are `final` by default** — Pint `final_class` rule. The exception hierarchy (`AdmissionException` abstract base) must use `abstract` to prevent final enforcement.
3. **Yoda comparisons** — `null === $value`, not `$value === null`. Pint enforces this.
4. **PHPStan level 7** — All return types, parameter types, and array shapes must satisfy level 7. PHPDoc array shapes are required on methods returning typed collections.
5. **No lazy loading** — `Model::preventLazyLoading()` in `AppServiceProvider` throws exceptions on any Eloquent lazy load. Every relationship must be eager-loaded via `with()`.
6. **No accessing missing attributes** — `Model::preventAccessingMissingAttributes()` is set. Models must have all accessed attributes present in `$fillable` or loaded via relationships.
7. **Constructor property promotion** — CLAUDE.md convention; use `public function __construct(private readonly ProgramRegistry $registry)` everywhere.

---

## Installation

Nothing additional to install. The full stack is present in `vendor/`. Run:

```bash
composer install       # install/restore all dependencies
php artisan migrate --seed  # set up SQLite schema and seed data
php artisan test --compact  # run full test suite
composer lint               # rector + phpstan + pint
```

---

## Sources

- `composer.lock` — exact locked versions for all packages (HIGH confidence)
- `pint.json` — formatting rules including `final_class`, `declare_strict_types`, `yoda_style` (HIGH confidence)
- `phpstan.neon` — static analysis level and extensions (HIGH confidence)
- `rector.php` — refactoring configuration and Laravel rule sets (HIGH confidence)
- `app/Providers/AppServiceProvider.php` — `preventLazyLoading()`, `preventAccessingMissingAttributes()` enforcement (HIGH confidence)
- `.planning/codebase/CONVENTIONS.md` — naming patterns, PHPDoc requirements, type declaration conventions (HIGH confidence)
- `IMPLEMENTATION.md` — domain patterns (Strategy, Value Objects, Registry, Service layer) and TDD build order (HIGH confidence)
- `.planning/PROJECT.md` — scope, constraints, and key architectural decisions (HIGH confidence)
