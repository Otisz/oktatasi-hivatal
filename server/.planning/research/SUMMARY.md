# Project Research Summary

**Project:** Hungarian University Admission Score Calculator API
**Domain:** Laravel scoring engine — pure domain logic over a fixed-seed SQLite database
**Researched:** 2026-02-25
**Confidence:** HIGH

## Executive Summary

This is a fully-specified homework exercise: a read-only Laravel 12 REST API that calculates Hungarian university admission scores for four seeded applicants across two programmes. The architecture is a DDD-lite layered design where the scoring engine (Value Objects, calculators, strategy) is entirely isolated from HTTP and persistence concerns. There is no ambiguity in scope — the PRD and IMPLEMENTATION.md define every data model, every formula, every validation rule, and the exact expected outputs for all four acceptance cases. The stack is not a choice; it is pre-configured in the repository with strict tooling constraints (PHPStan level 7, `final_class`, `declare_strict_types`, `preventLazyLoading`).

The recommended implementation strategy is strict bottom-up TDD following the eight-layer dependency graph: enums and exceptions first, then Value Objects, then DB schema and models, then seeders, then service classes and calculators, and finally the API layer. This order is not optional — calculators depend on VOs, the service depends on calculators and the registry, and the controller depends on the service. Inverting any step forces rewrites. The single most important discipline is keeping VO mapping as the very first action in `AdmissionScoringService::calculateForApplicant()`, because `ExamResult`'s constructor is the enforcement mechanism for validation step 1.

The primary risks are all correctness bugs rather than infrastructure risks: wrong validation ordering produces wrong error responses for acceptance cases 3 and 4; incorrect language certificate deduplication (per-type instead of per-language) undercounts bonus points; and including the mandatory subject in the elective candidate pool would silently produce wrong base scores. All three bugs are invisible with casual testing but are caught immediately by the full four-case acceptance test suite. Build the feature tests before trusting any calculator output.

## Key Findings

### Recommended Stack

The stack is entirely pre-determined by `composer.lock` and the existing project skeleton. No new packages should be installed. The domain layer — Value Objects, enums, the strategy interface, and the exception hierarchy — is implemented in pure PHP 8.5 within Laravel conventions, with no DDD library needed.

**Core technologies:**
- PHP 8.5.2: runtime — `readonly` properties and backed enums are load-bearing for Value Objects; `declare(strict_types=1)` is enforced globally by Pint
- Laravel 12.53.0: application framework — IoC container used for `ProgramRegistry` singleton; `bootstrap/app.php` declarative exception rendering; Eloquent with `preventLazyLoading()` enforced
- Pest 4.4.1 + PHPUnit 12: test runner — `expect()` API for VO assertions; `RefreshDatabase` + seeders for feature tests; Mockery for service unit tests
- SQLite (file-based): database — zero-config, appropriate for a fixed-seed read-only dataset
- Pint + Larastan (level 7) + Rector: quality gate — run as `composer lint` before committing; `final_class` and `yoda_style` rules enforced

See `.planning/research/STACK.md` for exact locked versions and tooling configuration details.

### Expected Features

This project is an all-or-nothing delivery: all table-stakes features must be present for the acceptance tests to pass. There is no phased feature rollout.

**Must have (table stakes):**
- Score calculation endpoint (`GET /api/v1/applicants/{applicant}/score`) — core product value
- Base point formula: `(mandatory + best_elective) * 2`, cap 400 — required by spec
- Bonus point accumulation: +50 emelt, +28 B2 lang cert, +40 C1 lang cert, cap 100 — required by spec
- Language certificate deduplication per language (not per type) — required by Hungarian admission rules
- Five-step ordered validation chain with fail-fast semantics — deterministic 422 error messages
- Descriptive Hungarian domain error messages per rule violation — acceptance test cases 3 and 4 assert exact messages
- List applicants endpoint (`GET /api/v1/applicants`) — consumers need to discover valid IDs
- DB-backed programme requirements via Strategy pattern — programmes configurable without code changes
- Seed data for all four acceptance cases — regression guardrail for the scoring engine

**Should have (already in spec, add real value):**
- Best-of elective selection — automatically picks highest-scoring eligible elective
- Immutable Value Objects — type safety prevents silent calculation bugs
- Typed exception hierarchy with six subclasses under `AdmissionException` — precise per-rule error messages
- API versioning (`/api/v1`) — future-proofs the contract

**Defer to never (anti-features):**
- Authentication, applicant/programme CRUD, pagination, i18n, frontend, score caching, bulk endpoints

See `.planning/research/FEATURES.md` for the full feature dependency graph and implementation order.

### Architecture Approach

A four-layer DDD-lite architecture with strict unidirectional dependencies: HTTP layer (controller + resource) calls the service layer (scoring service, registry, calculators), which maps Eloquent models to domain layer objects (Value Objects, enums, exceptions) via a contracts interface. The persistence layer (five Eloquent models) is touched only by the registry and the service during initial data loading — calculators have zero knowledge of the database.

**Major components:**
1. `ApplicantController` — route model binding, delegates to `AdmissionScoringService`, returns `ApplicantScoreResource` or catches `AdmissionException` via `bootstrap/app.php` renderer
2. `AdmissionScoringService` — orchestrates the full pipeline: resolve programme requirements, map Eloquent rows to VOs (triggering step-1 validation), run the ordered validation chain (steps 2–5), delegate to calculators, return `Score` VO
3. `ProgramRegistry` + `DatabaseProgramRequirements` — Strategy implementation; eager-loads `program.subjects` and returns `ProgramRequirementsInterface` instance wrapping the DB data
4. `BasePointCalculator` / `BonusPointCalculator` — pure arithmetic; receive only VOs; `BonusPointCalculator` owns the 100-point cap
5. `ExamResult`, `LanguageCertificate`, `Score` — immutable Value Objects; `ExamResult` is a validation gate (throws `FailedExamException` on construction if `$percentage < 20`)
6. `AdmissionException` hierarchy (6 subclasses) — typed domain errors rendered as 422 JSON by `bootstrap/app.php`

See `.planning/research/ARCHITECTURE.md` for the full component boundary table, data flow diagrams, and anti-patterns.

### Critical Pitfalls

1. **Validation order inversion** — Keep `ExamResult` VO mapping as the very first action in `calculateForApplicant()`. The constructor throw IS validation step 1. Moving VO mapping after any explicit validation check silently swaps case 3 and case 4 error responses.

2. **Language certificate dedup per-type instead of per-language** — Group `LanguageCertificate` objects by `$cert->language()` string, not by type. B2 English + C1 German = 68 points (no dedup); B2 English + C1 English = 40 points (same-language dedup).

3. **Mandatory subject included in elective candidate pool** — Filter exam results to `$requirements->getElectiveSubjects()` before selecting the best elective. Never take `max()` across all exam results.

4. **Hungarian diacritics in enum values cause silent `ValueError`** — Reference `SubjectName::X->value` in seeders and tests. Never retype strings. All PHP source files must be saved as UTF-8.

5. **Route file path mismatch with `apiPrefix`** — Routes go in `routes/api/v1.php`; `bootstrap/app.php` must set `apiPrefix: 'api/v1'`. Verify with `php artisan route:list` before writing feature tests.

See `.planning/research/PITFALLS.md` for seven critical pitfalls and four moderate pitfalls with phase-specific warnings.

## Implications for Roadmap

The dependency graph is fully deterministic. Build order is dictated by what each layer requires from layers below it. There is no room for parallel feature tracks in a solo implementation — each phase must complete before the next begins.

### Phase 1: Domain Primitives — Enums and Exceptions

**Rationale:** Zero dependencies; every subsequent layer imports from here. Enums are the type-safe constants that prevent the diacritics `ValueError` pitfall across the entire codebase.
**Delivers:** `SubjectName`, `ExamLevel`, `LanguageCertificateType` enums; `AdmissionException` abstract base and all six subclasses.
**Addresses:** Table-stakes validation error messages; API versioning foundation.
**Avoids:** Pitfall 2 (diacritics `ValueError`) — enums define the canonical string values once.

### Phase 2: Value Objects

**Rationale:** VOs are the second dependency layer; calculators and the service cannot be written without them. `ExamResult` embeds validation step 1 — defining it here locks the correct validation ordering.
**Delivers:** `ExamResult`, `LanguageCertificate`, `Score` immutable classes with full unit test coverage.
**Addresses:** Base point formula inputs; bonus point inputs; score result shape.
**Avoids:** Pitfall 1 (validation order) — VO constructor is the mechanism; Pitfall 7 (cap placement) — `Score` stores raw values, cap goes in calculator.

### Phase 3: Database Schema, Models, and Factories

**Rationale:** Persistence layer must exist before seeders can populate it and before the registry can query it.
**Delivers:** Five migrations (`programs`, `program_subjects`, `applicants`, `applicant_exam_results`, `applicant_bonus_points`), five Eloquent models with typed relationships, factories for all models.
**Addresses:** DB-backed programme requirements; eager loading foundation.
**Avoids:** Pitfall 5 (N+1) — relationships must be defined on models before eager loading can be used.

### Phase 4: Seed Data

**Rationale:** Feature tests are the acceptance gate for the entire project. Seeded data must be in place and stable before feature tests are written.
**Delivers:** `ProgramSeeder` (2 programmes + subjects), `ApplicantSeeder` (4 applicants covering all acceptance cases), `DatabaseSeeder` orchestrating both in order.
**Addresses:** All four acceptance test cases; 404 case for unknown applicant.
**Avoids:** Pitfall 2 (diacritics) — seeders must use `SubjectName::X->value` not string literals; Pitfall 8 (ID drift) — `RefreshDatabase` + seeders in the correct order.

### Phase 5: Strategy Pattern — Programme Requirements

**Rationale:** `ProgramRequirementsInterface` contract + `DatabaseProgramRequirements` + `ProgramRegistry` must exist before `AdmissionScoringService` can resolve programme rules.
**Delivers:** `ProgramRequirementsInterface`, `DatabaseProgramRequirements` with unit tests (using `ProgramSubject::make()` stubs — no DB), `ProgramRegistry` with unit tests.
**Addresses:** Programme-specific validation rules; elective list source for pitfall 3 prevention.
**Avoids:** Pitfall 9 (integration tests masquerading as unit tests) — mock models, no `RefreshDatabase` in unit tests.

### Phase 6: Calculators

**Rationale:** Pure arithmetic layers; depend only on VOs (already complete). Can be fully unit-tested with mocked input.
**Delivers:** `BasePointCalculator` with unit tests (formula, cap, elective filtering); `BonusPointCalculator` with unit tests (dedup scenarios, cap).
**Addresses:** Base point formula; bonus point accumulation with language dedup.
**Avoids:** Pitfall 3 (mandatory subject in elective pool) — filter to `getElectiveSubjects()` before selecting best; Pitfall 4 (dedup per type) — group by `language()` string.

### Phase 7: Orchestration — AdmissionScoringService

**Rationale:** The service depends on all domain layers. Build it last among service classes to mock its dependencies cleanly.
**Delivers:** `AdmissionScoringService` with unit tests mocking `ProgramRegistry`, `BasePointCalculator`, `BonusPointCalculator`; correct VO mapping order; ordered validation chain steps 2–5.
**Addresses:** All five validation steps; score pipeline orchestration.
**Avoids:** Pitfall 1 (validation order) — VO mapping is step 1, explicit; Pitfall 14 (nullable language column) — filter bonus points to `Nyelvvizsga` category before mapping.

### Phase 8: API Layer and Wiring

**Rationale:** HTTP concerns come last; everything they depend on is now complete.
**Delivers:** `ApplicantScoreResource`, `ApplicantController` (index + score actions), `routes/api/v1.php`, `bootstrap/app.php` exception renderer for `AdmissionException`, `AppServiceProvider` singleton binding for `ProgramRegistry`.
**Addresses:** JSON response shape; 404 on unknown applicant; 422 on domain errors.
**Avoids:** Pitfall 6 (overly broad exception handler) — type-hinted `AdmissionException` parameter, not `\Throwable`; Pitfall 10 (route file path mismatch) — verify with `php artisan route:list`; Pitfall 11 (singleton state bleed) — `ProgramRegistry` must remain stateless.

### Phase 9: Acceptance and Feature Tests

**Rationale:** The four acceptance cases from the PRD are the definition of done. Write them last so each case tests the fully assembled pipeline.
**Delivers:** `ApplicantScoreTest` with `RefreshDatabase` covering all four acceptance cases and the 404 case; `ApplicantIndexTest` for the list endpoint.
**Addresses:** All table-stakes features verified end-to-end.
**Avoids:** Pitfall 8 (ID drift) — `RefreshDatabase` + seeder order.

### Phase Ordering Rationale

- Phases 1-2 are purely PHP and require no database, making them the fastest to validate in isolation.
- Phases 3-4 establish the stable data foundation that feature tests depend on; changing seed data after feature tests are written causes widespread test failures.
- Phases 5-6 are independent service-layer units that can each be fully tested without HTTP infrastructure.
- Phase 7 integrates all service-layer units; the service's unit tests are the highest-value tests in the project because they assert the ordering and arithmetic contracts.
- Phase 8 is thin glue: the controller has no logic of its own.
- Phase 9 serves as the integration regression suite. Running these four cases at any point after phase 8 is the definitive health check.

### Research Flags

Phases with well-documented, standard patterns (no deeper research needed):
- **Phase 1 (Enums/Exceptions):** Standard PHP 8.1+ backed enums and exception hierarchies — no research needed.
- **Phase 3 (DB/Models):** Standard Laravel 12 Eloquent patterns — `make:migration`, `make:model` commands; well-documented.
- **Phase 8 (API Layer):** Standard Laravel 12 API controller and resource patterns; `bootstrap/app.php` exception renderer documented in IMPLEMENTATION.md.
- **Phase 9 (Feature Tests):** Standard Pest + `RefreshDatabase` patterns.

Phases that may need targeted research during planning:
- **Phase 2 (Value Objects):** PHP `readonly` class semantics and PHPStan level 7 array shape annotations — worth checking Larastan docs if annotation errors surface.
- **Phase 7 (Scoring Service):** Mockery integration with Pest 4 and PHPUnit 12 — verify constructor mocking syntax if version-specific issues arise.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Derived entirely from `composer.lock`, `pint.json`, `phpstan.neon` — no guesswork |
| Features | HIGH | PRD and IMPLEMENTATION.md are first-party authoritative specs; acceptance cases are fully enumerated |
| Architecture | HIGH | IMPLEMENTATION.md defines exact class names, method signatures, and layer boundaries |
| Pitfalls | HIGH | Derived from spec analysis and established PHP/Laravel patterns; all seven critical pitfalls trace to explicit spec constraints |

**Overall confidence:** HIGH

### Gaps to Address

- **`unsignedTinyInteger` percentage upper bound (Pitfall 13):** IMPLEMENTATION.md does not specify upper-bound validation in `ExamResult`. For a seeded-data-only project this is low priority, but the constructor should reject `$percentage > 100`. Decide during Phase 2 whether to add a guard or leave it as a known limitation.
- **`AngoNyelv` enum key spelling (Pitfall 12):** IMPLEMENTATION.md has a minor typo in the enum key name (`AngoNyelv` vs the correct `AngolNyelv`). The value `'angol nyelv'` is what matters for runtime correctness. Standardise the key name during Phase 1 and document the decision.
- **`ApplicantScoreResource` envelope shape:** The PRD shows `{ data: { osszpontszam, alappont, tobbletpont } }`. Verify whether Laravel API Resources wrap in `data` by default or if this requires explicit `wrap` configuration during Phase 8.

## Sources

### Primary (HIGH confidence)
- `PRD.md` — API contract, validation rules, scoring formula, acceptance test case definitions
- `IMPLEMENTATION.md` — authoritative architecture spec: class names, method signatures, layer boundaries, TDD order
- `kovetelmenyek.md` + `homework_input.php` — original Hungarian homework specification and reference inputs/outputs
- `composer.lock` — exact locked package versions
- `pint.json`, `phpstan.neon`, `rector.php` — code quality tooling configuration
- `app/Providers/AppServiceProvider.php` — `preventLazyLoading()` and `preventAccessingMissingAttributes()` enforcement
- `.planning/codebase/CONVENTIONS.md`, `.planning/codebase/ARCHITECTURE.md`, `.planning/codebase/STRUCTURE.md` — codebase analysis

### Secondary (MEDIUM confidence)
- Laravel 12 exception handling documentation — `withExceptions()->render()` `instanceof`-based matching behavior
- PHP 8 strict types and enum `::from()` documented language behavior

---
*Research completed: 2026-02-25*
*Ready for roadmap: yes*
