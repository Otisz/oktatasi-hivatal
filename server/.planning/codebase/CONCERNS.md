# Codebase Concerns

**Analysis Date:** 2026-02-25

## Tech Debt

**Incomplete Application Implementation:**
- Issue: The application has a detailed implementation specification in `IMPLEMENTATION.md` but almost no corresponding code. The specification defines a complex domain model (Strategy pattern, Registry pattern, Value Objects, service layer orchestration) that has not been implemented yet.
- Files: `IMPLEMENTATION.md` (850+ lines of detailed spec), `app/` (only 3 PHP files)
- Impact: The implementation roadmap exists but the actual codebase is at ~2% completion. All domain classes, migrations, controllers, and tests specified in the plan are missing.
- Fix approach: Follow the TDD sequence outlined in `IMPLEMENTATION.md` section 8 and implement in order: Enums → Exceptions → ValueObjects → Contracts → Migrations/Models → Seeders → Services → Controllers → Routes → Bootstrap configuration.

**Test Infrastructure Incomplete:**
- Issue: Pest testing framework is configured but test structure is barebones. The spec calls for 15+ specific test classes but only example skeleton tests exist.
- Files: `tests/Pest.php` (RefreshDatabase commented out), `tests/Feature/ExampleTest.php`, `tests/Unit/ExampleTest.php`, `tests/TestCase.php`
- Impact: No actual business logic is testable yet. Feature tests for the 4 homework examples are not implemented. Unit tests for value objects, services, and calculators are missing.
- Fix approach: Implement test suite following section 8 of `IMPLEMENTATION.md`. Start with unit tests for ValueObjects and domain services, then feature tests for the HTTP API endpoints.

**Bootstrap Configuration Missing API Routing:**
- Issue: `bootstrap/app.php` defines only web and console routing, not API routing. The specification requires `routes/api/v1.php` with exception handling for `AdmissionException`.
- Files: `bootstrap/app.php` (lines 8-12), `routes/web.php`, `routes/console.php`
- Impact: API routes for applicant scoring cannot be registered. Exception rendering for domain errors is not configured, so custom error responses will not be properly serialized.
- Fix approach: Add `api: __DIR__.'/../routes/api/v1.php'` to `withRouting()` and add custom exception renderer for `AdmissionException` in `withExceptions()` (see section 7 of `IMPLEMENTATION.md`).

**Service Registration Gap:**
- Issue: `AppServiceProvider::register()` sets `Model::unguard()` and prevents lazy loading, but does not register the `ProgramRegistry` singleton required by the scoring service.
- Files: `app/Providers/AppServiceProvider.php` (lines 13-17)
- Impact: Dependency injection for the registry will fail at runtime. Each request will try to instantiate a new `ProgramRegistry` instead of using a shared instance.
- Fix approach: Add `$this->app->singleton(ProgramRegistry::class);` to the `register()` method (see section 7 of `IMPLEMENTATION.md`).

## Known Gaps

**No Domain Models:**
- Files affected: `app/Models/` (only has default `User.php`)
- Missing: `Program`, `ProgramSubject`, `Applicant`, `ApplicantExamResult`, `ApplicantBonusPoint` models with relationships
- Blocking: All business logic depends on these models existing with proper Eloquent relationships and factories

**No Database Migrations:**
- Files affected: `database/migrations/` (only has default auth tables)
- Missing: 5 migrations for programs, program_subjects, applicants, applicant_exam_results, applicant_bonus_points
- Blocking: Cannot create or test any data structures

**No Enums:**
- Files affected: `app/Enums/` (directory doesn't exist)
- Missing: `SubjectName`, `ExamLevel`, `LanguageCertificateType` with all values and methods
- Blocking: Domain logic cannot type-hint exam levels or certificate types safely

**No Contracts:**
- Files affected: `app/Contracts/` (directory doesn't exist)
- Missing: `ProgramRequirementsInterface` that defines the strategy pattern contract
- Blocking: Database requirements class cannot be typed against an interface

**No Value Objects:**
- Files affected: `app/ValueObjects/` (directory doesn't exist)
- Missing: `ExamResult`, `LanguageCertificate`, `Score` immutable domain objects
- Blocking: Service layer cannot work with domain concepts; currently would use raw arrays/models

**No Exceptions:**
- Files affected: `app/Exceptions/` (directory doesn't exist)
- Missing: 7 exception classes for domain validation errors
- Blocking: Service layer cannot throw specific domain exceptions; HTTP layer cannot render custom error responses

**No Services:**
- Files affected: `app/Services/` (directory doesn't exist)
- Missing: `AdmissionScoringService`, `ProgramRegistry`, `BasePointCalculator`, `BonusPointCalculator`, `DatabaseProgramRequirements`
- Blocking: All business logic (scoring calculation, validation, registry) cannot be implemented

**No Controllers:**
- Files affected: `app/Http/Controllers/Api/V1/` (directory doesn't exist)
- Missing: `ApplicantController` with endpoints for listing applicants and calculating scores
- Blocking: HTTP API has no entry points

**No Resources:**
- Files affected: `app/Http/Resources/Api/V1/` (directory doesn't exist)
- Missing: `ApplicantScoreResource` for JSON response serialization
- Blocking: API responses cannot be formatted correctly

**No API Routes:**
- Files affected: `routes/api/` (directory doesn't exist)
- Missing: `v1.php` with GET `/api/v1/applicants` and GET `/api/v1/applicants/{applicant}/score`
- Blocking: No HTTP endpoints are wired to controllers

**No Seeders:**
- Files affected: `database/seeders/` (only has `DatabaseSeeder.php`)
- Missing: `ProgramSeeder`, `ApplicantSeeder` to populate test data from homework examples
- Blocking: Cannot run feature tests against seeded data

## Security Considerations

**Model Protection Settings vs. Production Readiness:**
- Risk: `AppServiceProvider::register()` calls `Model::unguard()` which disables mass assignment protection. This is useful for development but dangerous in production if left enabled.
- Files: `app/Providers/AppServiceProvider.php` (line 15)
- Current mitigation: This is appropriate for a development-stage application
- Recommendations: When moving to production, implement proper `$fillable` arrays on all models and remove `Model::unguard()` or conditionally apply it only in non-production environments.

**Debug Mode Default:**
- Risk: `.env.example` has `APP_DEBUG=true` which is appropriate for development, but must be explicitly set to false in production.
- Files: `.env.example` (line 4), `.env` (not readable per security policy)
- Current mitigation: The .env file is in .gitignore and not committed
- Recommendations: Document that production deploys must set `APP_DEBUG=false`. Consider environment-specific validation in `config/app.php`.

**No Authentication Configured:**
- Risk: The API has no authentication mechanism. Any caller can request applicant scores.
- Files: `bootstrap/app.php` (no middleware registered), no auth guards configured
- Current mitigation: This is a homework/evaluation API, not a production system
- Recommendations: Before any real use, implement API authentication (sanctum tokens, OAuth, or IP whitelisting).

**Database Foreign Key Constraints:**
- Risk: SQLite uses `DB_FOREIGN_KEYS=true` by default but this can be disabled in config or at runtime.
- Files: `config/database.php` (line 39)
- Current mitigation: Default is enabled
- Recommendations: Verify foreign key constraints are enabled in tests before deleting records. Add database seeding verification.

## Performance Bottlenecks

**Potential N+1 Query Problem in Service Layer:**
- Problem: The specification shows `AdmissionScoringService::calculateForApplicant()` retrieving exam results and bonus points from the applicant model. If these relationships are not eager-loaded in the controller, will cause N+1 queries.
- Files: `IMPLEMENTATION.md` (section 4, lines 269-282 show the issue pattern)
- Cause: The service receives an `Applicant` model; if `with('examResults', 'bonusPoints', 'program.subjects')` is not called before passing to the service, each access triggers queries.
- Improvement path: Always eager-load relationships in controllers before passing models to services. Add comments/patterns in controller to show best practice.

**Database Query for Program Requirements on Every Request:**
- Problem: `ProgramRegistry::findByApplicant()` performs a query to fetch program and eager-load subjects for each scoring request.
- Files: `IMPLEMENTATION.md` (section 4, line 255 shows pattern), but code not yet implemented
- Cause: No caching of program requirements
- Improvement path: Consider caching programs in Redis or memory after first load within request scope. For now, acceptable since programs are static and relatively few.

**Bonus Point Calculation Loop:**
- Problem: `BonusPointCalculator` must group certificates by language to deduplicate. Could be O(n²) with large certificate lists.
- Files: `IMPLEMENTATION.md` (section 4, lines 226-230 describe the logic)
- Cause: Multiple certificates per language need to be filtered to keep only the highest-value ones
- Improvement path: Use `groupBy('language')` collection method to group then select max, keeping algorithm efficient. Current design should handle it well; monitor if needed.

## Fragile Areas

**Domain Logic Spread Across Specification Document:**
- Files: `IMPLEMENTATION.md` (850+ lines)
- Why fragile: The entire specification is in a markdown file, not in code. If implementation deviates from the spec, there's no automated way to detect it. Formulas for points (lines 220-230), validation rules (lines 291-298), and seed data (lines 314-357) are all documented but not enforced.
- Safe modification: Keep `IMPLEMENTATION.md` as the source of truth. When implementing each component, write tests that verify the spec formulas match the code. Pin exact values from spec as test assertions.
- Test coverage: Test specs in `section 9` must be implemented exactly; any deviation in points or validation rules breaks the spec.

**Enum-to-Database String Mapping:**
- Files: `IMPLEMENTATION.md` (lines 184-193 describe SubjectName enum with case names and database values)
- Why fragile: Enums have TitleCase case names (e.g., `MagyarNyelvEsIrodalom`) but database stores lowercase strings (e.g., `'magyar nyelv és irodalom'`). If the mapping is wrong, validations will silently fail.
- Safe modification: Define enum values precisely matching the strings used in seed data and homework examples. Add tests that verify `SubjectName::from($dbString)->value === $dbString`.
- Test coverage: Unit test each enum value to ensure round-trip conversion works.

**Validation Rule Order Sensitivity:**
- Files: `IMPLEMENTATION.md` (section 4, lines 288-298 define validation order)
- Why fragile: The service validates in a specific order (failed exams → global mandatory → program mandatory → level check → elective). If order changes, error messages may be confusing or tests may fail with wrong exception type.
- Safe modification: Document the validation order in code comments. Write unit tests for each validation rule independently and in combination. Do not reorder without testing all 4 homework examples.
- Test coverage: Feature tests must verify exact error messages match spec for each example case.

**Language Certificate Deduplication Logic:**
- Files: `IMPLEMENTATION.md` (lines 56-58 describe the logic: same language, keep max points)
- Why fragile: If a person has "B2 English" and "C1 English", the higher C1 (40 pts) should be used instead of B2 (28 pts). If the logic is implemented as simple sum instead of max-per-language, tests will miss it.
- Safe modification: Implement `BonusPointCalculator::calculate()` with explicit grouping by language. Write unit tests with both single and multiple certificates per language.
- Test coverage: Unit test for B2+C1 same language yields 40 points, not 68.

**Point Cap at 100:**
- Files: `IMPLEMENTATION.md` (lines 52-54 and 230 describe 100-point bonus cap)
- Why fragile: Multiple bonus sources (advanced exams +50 each, language certs +28/40) can exceed 100. The cap is applied at the end, not per-category. If cap is forgotten or applied in wrong place, scores will be wrong.
- Safe modification: Apply the cap in `BonusPointCalculator::calculate()` return value with `min(sum, 100)`. Test cases must verify capping works.
- Test coverage: Example 1 has 118 total bonus pts (28+40+50) capped to 100; example 2 has 128 pts capped to 100.

## Missing Critical Features

**No Input Validation at Controller Level:**
- Problem: The controller will receive HTTP requests with exam result percentages, but spec defines strict rules: must be 0-100%, must be >= 20% for each subject, certain subjects are mandatory.
- Blocks: Cannot validate HTTP inputs before passing to service
- Recommendation: Create Form Request validation classes or define validation rules in `ApplicantController` to validate exam results before calling service. Use Laravel's validator or a dedicated validation library.

**No API Documentation:**
- Problem: No OpenAPI/Swagger spec, no API docs explaining request/response formats
- Blocks: API consumers cannot discover endpoints or format payloads correctly
- Recommendation: Add PHPDoc to controller methods and responses. Consider using `laravel-openapi` or manual OpenAPI file for documentation.

**No Soft Deletes or Auditing:**
- Problem: If an applicant or exam result is ever deleted, there's no audit trail. Spec doesn't mention this, so likely not required, but worth noting for production use.
- Blocks: Cannot track historical data
- Recommendation: Not needed for MVP but consider for future versions if regulatory requirements emerge.

## Test Coverage Gaps

**Feature Test Suite:**
- What's not tested: The 4 homework examples from section 9 of `IMPLEMENTATION.md` are not yet implemented as tests
- Files: `tests/Feature/Api/V1/ApplicantScoreTest.php` (missing)
- Risk: Without feature tests, HTTP integration cannot be verified. The exact JSON response formats and status codes cannot be tested.
- Priority: High - these are the acceptance criteria for the entire feature

**Service Unit Tests:**
- What's not tested: `AdmissionScoringService`, `BasePointCalculator`, `BonusPointCalculator`, `ProgramRegistry`, `DatabaseProgramRequirements`
- Files: `tests/Unit/Services/` (missing all 5 test classes)
- Risk: Core business logic is untested. A regression in point calculation formulas could go unnoticed.
- Priority: High - these are the most critical components

**Value Object Unit Tests:**
- What's not tested: `ExamResult`, `LanguageCertificate`, `Score` construction, validation, and methods
- Files: `tests/Unit/ValueObjects/` (missing all 3 test classes)
- Risk: Value object behavior (20% minimum for exams, points calculations, immutability) is untested
- Priority: Medium - these are foundational but relatively simple

**Database Integration Tests:**
- What's not tested: Seeding, relationship loading, migration consistency
- Files: `database/seeders/` (missing ProgramSeeder and ApplicantSeeder)
- Risk: Database schema may not match model relationships; seed data may not align with test expectations
- Priority: Medium - needed for feature tests to run correctly

## Dependencies at Risk

**None Identified:**
- All production dependencies in `composer.json` (Laravel 12, Tinker) are stable and actively maintained
- Development dependencies (Pest 4, PHPUnit 12, Pint, Rector, Larastan) are current and well-supported
- No outdated or abandoned packages detected

---

*Concerns audit: 2026-02-25*
