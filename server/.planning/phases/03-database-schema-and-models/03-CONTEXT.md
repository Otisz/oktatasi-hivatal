# Phase 3: Database Schema and Models - Context

**Gathered:** 2026-02-25 (updated)
**Status:** Ready for planning

<domain>
## Phase Boundary

Five-table database schema (programs, program_subjects, applicants, applicant_exam_results, applicant_bonus_points) with Eloquent models, typed relationships, and factories. The persistence layer that Phase 4 (seeding) and Phase 5+ (business logic) build on. Column definitions come from the PRD Section 10.

</domain>

<decisions>
## Implementation Decisions

### Enum Casting Strategy
- Store all enum-backed columns as plain varchar (string) columns in migrations
- Cast to Phase 1 PHP enums at the Eloquent model layer using Laravel's enum casting
- subject_name columns cast to SubjectName enum, level columns cast to ExamLevel enum
- Create a new RequirementType enum (Mandatory, Elective) for program_subjects.requirement_type
- Cast applicant_bonus_points.type to LanguageCertificateType enum (B2/C1)
- Cast applicant_bonus_points.category as plain string (no enum needed)
- Cast program_subjects.required_level to ExamLevel enum (nullable)

### Primary Key Strategy
- All five tables use ordered UUIDs (Laravel's HasUuids trait with Str::orderedUuid())
- Migrations use `$table->uuid('id')->primary()` instead of bigIncrements
- Foreign key columns use `$table->foreignUuid('program_id')` / `$table->foreignUuid('applicant_id')`
- All models include the `HasUuids` trait — route model binding resolves by UUID automatically
- Define named UUID constants on models for seeded test data (e.g., Applicant::CASE_1_UUID) — Phase 4 seeder and Phase 8 tests reference applicants by these constants
- API routes use UUID in URL: `/api/v1/applicants/{uuid}/score`

### Schema Conventions
- Include Laravel timestamps (created_at, updated_at) on all tables
- Foreign keys only — no CHECK constraints or unique constraints; validation lives in Value Objects and service layer
- Default string(255) for all varchar columns (university, faculty, name, subject_name, etc.)
- Use unsignedTinyInteger for percentage column on applicant_exam_results (documents 0-100 range intent)

### Factory Design
- Factories for all five models (Program, ProgramSubject, Applicant, ApplicantExamResult, ApplicantBonusPoint)
- Include named states for common testing scenarios (e.g., ->failingExam(), ->advancedLevel(), ->b2Certificate())
- Explicit relation creation — no afterCreating callbacks; tests chain ->has() for full control
- Factory defaults use enum values (e.g., SubjectName::cases() random element) rather than hardcoded strings

### Relationship Loading
- No default $with on any model — all eager loading is explicit via ->with() in controllers/services
- Enable Model::preventLazyLoading() in AppServiceProvider to catch N+1 issues during development
- Standard Laravel naming conventions: examResults(), bonusPoints(), program(), subjects()
- Full return type hints on all relationship methods (BelongsTo, HasMany, etc.)

### Claude's Discretion
- Exact factory state names and what scenarios they cover beyond the obvious ones
- Migration ordering (single migration or one per table)
- Whether to use fillable or guarded on models
- PHPDoc blocks on model properties for IDE support

</decisions>

<specifics>
## Specific Ideas

- PRD Section 10 defines exact columns for all five tables — follow that spec precisely
- Enum integration should feel natural: accessing $examResult->subject_name returns a SubjectName enum instance
- preventLazyLoading() ensures the scoring service in Phase 7 is forced to eager-load properly

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-database-schema-and-models*
*Context gathered: 2026-02-25 (updated)*
