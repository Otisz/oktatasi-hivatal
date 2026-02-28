# Codebase Structure

**Analysis Date:** 2026-02-28

## Directory Layout

```
/Users/otisz/Projects/oktatasi-hivatal/
├── client/                 # Vue 3 + TypeScript frontend application
├── server/                 # Laravel 12 backend application
├── .planning/              # GSD phase and milestone documentation
├── README.md               # Project metadata
└── *.pdf                   # Assignment and specification PDFs
```

## Directory Purposes

**`client/`:**
- Purpose: Vue 3 single-page application for admission scoring UI
- Contains: TypeScript source, Vite build config, Tailwind CSS styling, node_modules
- Key files: `src/main.ts` (entry), `src/App.vue` (root), `src/router/index.ts` (routing), `src/views/`, `src/composables/`

**`client/src/`:**
- Purpose: Source code root
- Contains: Components, views, composables, utilities, type definitions, styling

**`client/src/views/`:**
- Purpose: Top-level page components (routed)
- Contains: `ApplicantsView.vue` (list), `ApplicantDetailView.vue` (detail with score calculation)
- Pattern: Each view component imports composables for data, handles UI states (loading/error/empty/success)

**`client/src/composables/`:**
- Purpose: Reusable Vue composition functions for logic
- Contains: `useApplicants()` (fetch list), `useApplicantScore(id)` (fetch single score), `useProgress` (navigation state)
- Pattern: Wrap TanStack Vue Query hooks; return reactive query state; handle error translation

**`client/src/lib/`:**
- Purpose: Shared utilities and client configuration
- Contains: `http.ts` (Axios instance), `query.ts` (TanStack QueryClient config)
- Pattern: Export singletons used throughout app

**`client/src/types/`:**
- Purpose: TypeScript type definitions
- Contains: `api.ts` with `Applicant`, `Program`, `ScoreResult`, `ApiResponse<T>`, `ApiError` interfaces
- Pattern: Match Laravel API response shapes exactly

**`client/src/assets/`:**
- Purpose: Static and CSS assets
- Contains: `main.css` (Tailwind directives)

**`client/src/router/`:**
- Purpose: Vue Router configuration
- Contains: `index.ts` defining routes, navigation guards, lazy-loaded components
- Routes: `/` → `/applicants` (list), `/applicants/:id` (detail)

**`client/src/components/`:**
- Purpose: Reusable UI components (non-routed)
- Contains: `.gitkeep` (currently empty; future shared components)
- Pattern: Place button, card, modal, form components here

**`server/`:**
- Purpose: Laravel 12 API backend for admission scoring
- Contains: PHP source, database, configuration, routes, migrations, seeders, factories

**`server/app/`:**
- Purpose: Application code root (PSR-12 autoloaded)
- Contains: Controllers, Models, Services, Resources, Exceptions, ValueObjects, Contracts, Enums

**`server/app/Http/Controllers/`:**
- Purpose: Request handlers
- Contains: `ApplicantController` with `index()` and `score()` actions
- Pattern: Inject services via constructor; return API Resources

**`server/app/Http/Resources/`:**
- Purpose: Eloquent API Resources for JSON transformation
- Contains: `ApplicantResource` (applicant with program), `ScoreResource` (total/base/bonus points)
- Pattern: Extend `JsonResource`; implement `toArray(Request)` with return type hint; wrap in `ApiResponse` envelope

**`server/app/Models/`:**
- Purpose: Eloquent ORM models (database abstraction)
- Contains: `Applicant` (UUID, relationships to program/results/bonuses), `Program`, `ProgramSubject`, `ApplicantExamResult`, `ApplicantBonusPoint`, `User`
- Pattern: Use `HasUuids` trait; define relationships with proper return types; use `HasFactory`

**`server/app/Services/`:**
- Purpose: Business logic orchestration and domain calculations
- Contains: `AdmissionScoringService` (multi-step validation + scoring), `BasePointCalculator`, `BonusPointCalculator`, `ProgramRegistry`, `DatabaseProgramRequirements`
- Pattern: Services are readonly, injected with dependencies; throw domain exceptions; compose smaller calculators

**`server/app/Contracts/`:**
- Purpose: Dependency inversion interfaces
- Contains: `BasePointCalculatorInterface`, `BonusPointCalculatorInterface`, `ProgramRegistryInterface`, `ProgramRequirementsInterface`
- Pattern: Define behavior contracts; allow multiple implementations

**`server/app/ValueObjects/`:**
- Purpose: Immutable domain objects with validation
- Contains: `Score` (base + bonus with negative check), `ExamResult` (subject/level/percentage), `LanguageCertificate` (type/language)
- Pattern: Readonly constructor properties; throw on invalid state; provide semantic methods

**`server/app/Exceptions/`:**
- Purpose: Domain-specific exception hierarchy
- Contains: `AdmissionException` (base), `FailedExamException`, `MissingGlobalMandatorySubjectException`, `MissingProgramMandatorySubjectException`, `MissingElectiveSubjectException`, `ProgramMandatorySubjectLevelException`, `UnknownProgramException`
- Pattern: All extend `AdmissionException`; messages describe why validation failed; caught by exception renderer

**`server/app/Enums/`:**
- Purpose: Type-safe enumerations
- Contains: `SubjectName` (HUNGARIAN, MATHEMATICS, etc. with `globallyMandatory()` method)

**`server/app/Providers/`:**
- Purpose: Service provider registrations and bindings
- Contains: Application-specific providers for dependency injection

**`server/routes/`:**
- Purpose: Route definitions
- Contains: `api.php` (versioned `/api/v1/*` routes), `web.php` (not used for API), `console.php` (CLI commands)

**`server/database/`:**
- Purpose: Database schema and seeding
- Contains: `migrations/` (schema files), `factories/` (model factories for testing), `seeders/` (initial data loading)

**`server/database/migrations/`:**
- Purpose: Database schema evolution
- Contains: Creates `programs`, `applicants`, `program_subjects`, `applicant_exam_results`, `applicant_bonus_points` tables
- Pattern: Use `HasUuids` migration methods; foreign keys with cascade; timestamps

**`server/database/factories/`:**
- Purpose: Fake data generation for tests
- Contains: Factories for each model (Applicant, Program, ApplicantExamResult, etc.)
- Pattern: Extend `Factory`; define states for test scenarios

**`server/database/seeders/`:**
- Purpose: Populate test/development data
- Contains: `ProgramSeeder` (creates programs), `ApplicantSeeder` (creates test applicants), `DatabaseSeeder` (orchestrates all)

**`server/config/`:**
- Purpose: Environment-aware configuration
- Contains: `app.php` (name, debug, etc.), `database.php` (SQLite), `auth.php`, `mail.php`, `cache.php`, `session.php`, etc.
- Pattern: Read from `.env` via `env()` only in config files; reference via `config('key')` in code

**`server/bootstrap/`:**
- Purpose: Application bootstrap
- Contains: `app.php` (main application configuration in Laravel 12 style), `providers.php` (service provider list)
- Pattern: `app.php` replaces `Kernel.php`; configures middleware, exceptions, routing via fluent API

**`server/public/`:**
- Purpose: Web root
- Contains: `index.php` (entry point that boots Laravel)

**`server/routes/console.php`:**
- Purpose: Console command definitions (Artisan commands)
- Contains: Scheduled tasks or custom commands

**`.planning/`:**
- Purpose: GSD documentation (phases, milestones, research)
- Contains: `codebase/` (this directory with ARCHITECTURE.md, STRUCTURE.md), `milestones/`, `phases/`, `research/`

## Key File Locations

**Entry Points:**
- Backend: `server/public/index.php` - Boots Laravel framework
- Frontend: `client/src/main.ts` - Mounts Vue app
- Router: `client/src/router/index.ts` - Defines client routes
- API Routes: `server/routes/api.php` - Defines API endpoints

**Configuration:**
- Backend: `server/bootstrap/app.php` - Application setup (middleware, exceptions, routing)
- Frontend: `client/vite.config.ts` - Vite bundler config
- Database: `server/config/database.php` - SQLite connection
- Environment: `.env` files (secrets, not committed)

**Core Logic:**
- Scoring: `server/app/Services/AdmissionScoringService.php` - Admission calculation algorithm
- Program Rules: `server/app/Services/DatabaseProgramRequirements.php` - Program requirements lookup
- Point Calculation: `server/app/Services/BasePointCalculator.php` and `BonusPointCalculator.php`
- Validation: `server/app/ValueObjects/` - ExamResult, Score with invariants

**Testing:**
- Backend Tests: `server/tests/` (Pest PHP)
- Factories: `server/database/factories/` - Generate test data
- Seeders: `server/database/seeders/` - Populate test database

**Database:**
- Migrations: `server/database/migrations/` - Schema definitions
- Models: `server/app/Models/` - Eloquent abstractions
- Factory Methods: `server/database/factories/` - Test data builders

## Naming Conventions

**Files:**
- Controllers: `{Model}Controller.php` (e.g., `ApplicantController.php`)
- Models: Singular PascalCase (e.g., `Applicant.php`)
- Services: `{Domain}Service.php` (e.g., `AdmissionScoringService.php`)
- Resources: `{Model}Resource.php` (e.g., `ApplicantResource.php`)
- Exceptions: `{Reason}Exception.php` (e.g., `MissingElectiveSubjectException.php`)
- Value Objects: Singular PascalCase (e.g., `Score.php`)
- Composables (Vue): `use{Concept}.ts` (e.g., `useApplicants.ts`)
- Views (Vue): `{FeatureName}View.vue` (e.g., `ApplicantsView.vue`)
- Type Definitions: Plural or descriptive (e.g., `api.ts`)

**Directories:**
- Lowercase plural for collections (e.g., `migrations/`, `factories/`)
- PascalCase for namespaced code (e.g., `app/Http/`, `app/Services/`)
- Kebab-case for composables and views directory (e.g., `src/composables/`, `src/views/`)

**Classes:**
- PascalCase for all classes
- Final keyword used on concrete implementations (`final class AdmissionScoringService`)
- Interfaces use `Interface` suffix (e.g., `ProgramRegistryInterface`)
- Traits use `Trait` suffix (implied; e.g., `HasFactory`)

**PHP Methods:**
- camelCase for all methods
- Use readonly property promotion in constructors
- Declare strict types at top of file
- Use declare(strict_types=1) in all PHP files

**TypeScript/Vue:**
- camelCase for variables and functions
- PascalCase for components and types
- `use` prefix for composables (Vue convention)

## Where to Add New Code

**New Feature (e.g., bonus point modifier):**
- Primary code: `server/app/Services/` (create `BonusModifierService.php`)
- Integrate into: `server/app/Services/AdmissionScoringService.php` via dependency injection
- Tests: `server/tests/Feature/` or `server/tests/Unit/` (use Pest)
- Migrations: `server/database/migrations/` if schema changes needed
- Models: Update relationships in `server/app/Models/` if needed
- Resources: Create new Resource if API endpoint needed

**New View (e.g., statistics page):**
- Component: `client/src/views/{FeatureName}View.vue`
- Route: Add to `client/src/router/index.ts`
- Composable: Create `client/src/composables/use{Concept}.ts` for data
- Backend Endpoint: Add to `server/routes/api.php` and create controller method
- Resource: Create `server/app/Http/Resources/` if needed

**New Shared Component:**
- Implementation: `client/src/components/{ComponentName}.vue`
- Usage: Import in views or other components
- Styling: Use Tailwind CSS classes (no external CSS files)

**Database Model:**
- Migration: `server/database/migrations/{timestamp}_{description}.php`
- Model: `server/app/Models/{ModelName}.php` with relationships and type hints
- Factory: `server/database/factories/{ModelName}Factory.php` for tests
- Resource: `server/app/Http/Resources/{ModelName}Resource.php` if API endpoint needed

**Utility Function/Helper:**
- Frontend: `client/src/lib/{concept}.ts`
- Backend: `server/app/Services/` (prefer services over helpers)

**Exception/Error:**
- Backend: `server/app/Exceptions/{ReasonName}Exception.php` (extend `AdmissionException`)
- Frontend: Handle in composables; transform to domain or generic error type

## Special Directories

**`.planning/`:**
- Purpose: GSD orchestrator documentation (generated, not committed to src)
- Generated: By `/gsd:map-codebase`, `/gsd:plan-phase`, `/gsd:execute-phase`
- Committed: Yes, to track progress and context
- Pattern: Structure reflects GSD workflow (milestones → phases → execution logs)

**`server/database/`:**
- Purpose: Database state and builders
- Generated: Migrations after running `php artisan migrate`
- Committed: Migrations and seeders yes; `database.sqlite` generally no (depends on project config)
- Pattern: All changes tracked in migrations; never modify schema via raw SQL

**`client/dist/`:**
- Purpose: Built frontend artifacts
- Generated: By `npm run build` (Vite)
- Committed: No (in `.gitignore`)
- Pattern: Regenerated on each build; ignored from git

**`server/bootstrap/cache/`:**
- Purpose: Laravel optimization cache
- Generated: By `php artisan config:cache`, `php artisan route:cache`
- Committed: No
- Pattern: Development uses on-demand; production pre-caches

**`node_modules/` (both client and server)**
- Purpose: Installed dependencies
- Generated: By `npm install`, `composer install`
- Committed: No (in `.gitignore`)
- Pattern: Lock files (`package-lock.json`, `composer.lock`) tracked for reproducibility

---

*Structure analysis: 2026-02-28*
