---
phase: 03-database-schema-and-models
plan: 02
subsystem: database
tags: [eloquent, models, factories, uuid, enums, hasUuids, hasFactory]

# Dependency graph
requires:
  - phase: 03-01-database-schema-and-models
    provides: "Five migration files creating programs, program_subjects, applicants, applicant_exam_results, applicant_bonus_points tables with UUID PKs and FK constraints"
  - phase: 01-domain-primitives
    provides: "SubjectName, ExamLevel, RequirementType, LanguageCertificateType enums that models cast to"
provides:
  - "Five Eloquent models with HasUuids + HasFactory traits, typed relationships, and enum casts"
  - "Five factories with enum-based defaults and named states for test isolation"
  - "Applicant::CASE_1-4_UUID constants for Phase 4 seeder and Phase 8 test fixtures"
affects:
  - phase-04-seeder
  - phase-05-strategy-pattern
  - phase-07-scoring-service
  - phase-08-acceptance-tests

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "HasUuids trait on all domain models (UUID primary keys)"
    - "HasFactory trait with typed PHPDoc @use HasFactory<XxxFactory>"
    - "Enum casts via casts() method returning array<string, class-string>"
    - "Factory enum values stored as .value strings (not enum objects) to avoid DB type errors"
    - "Named factory states for test scenario setup (failingExam, advancedLevel, b2Certificate, etc.)"
    - "Explicit relationship name in has() factory calls when method name differs from Laravel convention"

key-files:
  created:
    - app/Models/Program.php
    - app/Models/ProgramSubject.php
    - app/Models/Applicant.php
    - app/Models/ApplicantExamResult.php
    - app/Models/ApplicantBonusPoint.php
    - database/factories/ProgramFactory.php
    - database/factories/ProgramSubjectFactory.php
    - database/factories/ApplicantFactory.php
    - database/factories/ApplicantExamResultFactory.php
    - database/factories/ApplicantBonusPointFactory.php
  modified: []

key-decisions:
  - "No $fillable/$guarded/$with on any model — Model::unguard() active, lazy loading prevented globally"
  - "Enum casts return enum instances from DB reads — accessing subject_name returns SubjectName enum, not raw string"
  - "Factory has() calls require explicit relationship name when method differs from Laravel's auto-guess (e.g., 'subjects' not 'programSubjects')"
  - "Default percentage in ApplicantExamResultFactory is 20-100 to avoid accidentally triggering FailedExamException in tests"

patterns-established:
  - "Model casts() method with array<string, class-string> return type for PHPStan compatibility"
  - "Factory states return ': array' typed closures for Pint compliance"
  - "Factory definition() uses enum->value strings, states also use enum->value strings"

requirements-completed: [DB-06, DB-07]

# Metrics
duration: 2min
completed: 2026-02-26
---

# Phase 3 Plan 02: Eloquent Models and Factories Summary

**Five UUID-based Eloquent models with typed enum casts and relationships, plus five factories with named states covering all acceptance test scenarios**

## Performance

- **Duration:** 2 min
- **Started:** 2026-02-26T09:31:46Z
- **Completed:** 2026-02-26T09:34:25Z
- **Tasks:** 3
- **Files modified:** 10

## Accomplishments

- Created five final Eloquent models with HasUuids, HasFactory, typed relationships, and enum casts — no fillable/guarded/with
- Created five factories with enum->value defaults and named states (failingExam, advancedLevel, b2Certificate, etc.) for isolated unit testing
- Verified full stack: factories insert valid records, eager-loaded relationships resolve correctly, enum casting returns enum instances (not raw strings)

## Task Commits

Each task was committed atomically:

1. **Task 1: Create five Eloquent models with HasUuids, typed relationships, and enum casts** - `9abeb10` (feat)
2. **Task 2: Create five factories with enum-based defaults and named states** - `751eda5` (feat)
3. **Task 3: Verify models and factories work together with database** - no commit (verification only, no file changes)

## Files Created/Modified

- `app/Models/Program.php` - Final model with subjects() and applicants() HasMany relationships
- `app/Models/ProgramSubject.php` - Final model with program() BelongsTo; casts subject_name/requirement_type/required_level to enums
- `app/Models/Applicant.php` - Final model with program() BelongsTo, examResults()/bonusPoints() HasMany, CASE_1-4_UUID constants
- `app/Models/ApplicantExamResult.php` - Final model with applicant() BelongsTo; casts subject_name/level to enums
- `app/Models/ApplicantBonusPoint.php` - Final model with applicant() BelongsTo; casts type to LanguageCertificateType
- `database/factories/ProgramFactory.php` - Factory with university/faculty/name fake data
- `database/factories/ProgramSubjectFactory.php` - Factory with mandatory/elective/requiredAdvancedLevel/forSubject states
- `database/factories/ApplicantFactory.php` - Factory with program_id FK via Program::factory()
- `database/factories/ApplicantExamResultFactory.php` - Factory with failingExam/advancedLevel/intermediateLevel/forSubject/withPercentage states
- `database/factories/ApplicantBonusPointFactory.php` - Factory with b2Certificate/c1Certificate/forLanguage states

## Decisions Made

- No `$fillable`, `$guarded`, or `$with` on any model — `Model::unguard()` is active globally; lazy loading is prevented globally so all relationship access must be eager-loaded.
- Enum casts use `class-string` annotation (`array<string, class-string>`) in `casts()` method return type — PHPStan level 7 compatible when both models and factories are analyzed together.
- Factory `has()` calls need explicit relationship name string when the relationship method name differs from Laravel's auto-guess convention (e.g., `subjects` not `programSubjects`).
- Default `percentage` in `ApplicantExamResultFactory` is 20-100 (not 0-100) to prevent accidentally creating failing exams; `failingExam()` state explicitly sets 0-19 range.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

PHPStan reported "class not found" errors for factory classes when analysing only the `app/Models/` directory (factories weren't in scope yet). Running PHPStan across both `app/Models/` and `database/factories/` together resolved all errors with zero issues. This is expected PHPStan behavior for cross-directory type references.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- All five Eloquent models ready for Phase 4 seeder — `Applicant::CASE_1-4_UUID` constants provide deterministic IDs for the four acceptance test fixtures
- All five factories ready for Phase 5+ unit tests — named states cover all scoring scenarios (failing exams, advanced level, B2/C1 certificates)
- `Model::preventLazyLoading()` is active — all Phase 5+ tests must eager-load relationships via `with()` or `load()`

---
*Phase: 03-database-schema-and-models*
*Completed: 2026-02-26*

## Self-Check: PASSED

- FOUND: app/Models/Program.php
- FOUND: app/Models/ProgramSubject.php
- FOUND: app/Models/Applicant.php
- FOUND: app/Models/ApplicantExamResult.php
- FOUND: app/Models/ApplicantBonusPoint.php
- FOUND: database/factories/ProgramFactory.php
- FOUND: database/factories/ProgramSubjectFactory.php
- FOUND: database/factories/ApplicantFactory.php
- FOUND: database/factories/ApplicantExamResultFactory.php
- FOUND: database/factories/ApplicantBonusPointFactory.php
- FOUND: commit 9abeb10 (models)
- FOUND: commit 751eda5 (factories)
