---
phase: 05-strategy-pattern
plan: 01
subsystem: domain
tags: [strategy-pattern, interface, services, unit-testing, pest]

# Dependency graph
requires:
  - phase: 03-database-schema-and-models
    provides: Program, ProgramSubject, Applicant models with RequirementType/SubjectName/ExamLevel enum casts
  - phase: 01-domain-primitives
    provides: SubjectName, ExamLevel, RequirementType enums and UnknownProgramException
provides:
  - ProgramRequirementsInterface contract (getMandatorySubject, getElectiveSubjects, getMandatorySubjectLevel)
  - DatabaseProgramRequirements — filters Program->subjects by RequirementType enum using closure comparisons
  - ProgramRegistry — resolves ProgramRequirementsInterface from an Applicant's program relationship
  - 6 unit tests covering all methods and the unknown program edge case
affects: [06-scoring-engine, 07-admission-calculator, 08-api-endpoints]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Strategy pattern via ProgramRequirementsInterface — scoring engine depends on contract, not raw models
    - final readonly class for stateless service implementations
    - Unit tests using setAttribute/setRelation only — zero DB access, no factories

key-files:
  created:
    - app/Contracts/ProgramRequirementsInterface.php
    - app/Services/DatabaseProgramRequirements.php
    - app/Services/ProgramRegistry.php
    - tests/Unit/Services/DatabaseProgramRequirementsTest.php
    - tests/Unit/Services/ProgramRegistryTest.php
  modified: []

key-decisions:
  - "Closure-based enum filtering over Collection::firstWhere/where — avoids loose equality edge cases with enum instances set via setAttribute"
  - "getMandatorySubjectLevel uses separate closure filter to avoid code duplication exception path from getMandatorySubject"

patterns-established:
  - "Strategy pattern: service classes implement interface, registry resolves them from domain models"
  - "Unit tests: real model instances with setAttribute/setRelation — no mocks, no DB, no factories"

requirements-completed: [DOM-08, BIZ-01, BIZ-02, TEST-04, TEST-05]

# Metrics
duration: 1min
completed: 2026-02-26
---

# Phase 05 Plan 01: Strategy Pattern — Program Requirements Summary

**ProgramRequirementsInterface contract with DatabaseProgramRequirements (closure-based enum filtering) and ProgramRegistry, backed by 6 passing unit tests using real model instances**

## Performance

- **Duration:** 1 min
- **Started:** 2026-02-26T15:41:37Z
- **Completed:** 2026-02-26T15:42:49Z
- **Tasks:** 2
- **Files modified:** 5

## Accomplishments

- ProgramRequirementsInterface defines the strategy contract with three method signatures and PHPDoc array type annotation
- DatabaseProgramRequirements implements the interface using closure-based enum comparison to filter Program->subjects by RequirementType, throwing UnknownProgramException when mandatory subject is absent
- ProgramRegistry delegates to DatabaseProgramRequirements via findByApplicant(), providing the bridge from Applicant to the requirements interface
- 6 unit tests pass without any database access — all use real model instances with setAttribute/setRelation

## Task Commits

Each task was committed atomically:

1. **Task 1: Create ProgramRequirementsInterface, DatabaseProgramRequirements, and ProgramRegistry** - `f5e364b` (feat)
2. **Task 2: Create unit tests for DatabaseProgramRequirements and ProgramRegistry** - `9a1490a` (test)

## Files Created/Modified

- `app/Contracts/ProgramRequirementsInterface.php` — Strategy contract with getMandatorySubject(), getElectiveSubjects(), getMandatorySubjectLevel()
- `app/Services/DatabaseProgramRequirements.php` — final readonly implementation filtering subjects by RequirementType via closure
- `app/Services/ProgramRegistry.php` — resolves DatabaseProgramRequirements from Applicant's program relation
- `tests/Unit/Services/DatabaseProgramRequirementsTest.php` — 5 tests covering mandatory subject, elective subjects, null level, advanced level, unknown program exception
- `tests/Unit/Services/ProgramRegistryTest.php` — 1 test verifying findByApplicant returns correct types

## Decisions Made

- Used closure-based enum filtering (`->first(fn (...) => $subject->requirement_type === RequirementType::Mandatory)`) throughout DatabaseProgramRequirements rather than `->firstWhere()` — the plan flagged this as potentially problematic when enums are set via setAttribute(), and the closure approach is unambiguous with strict equality
- getMandatorySubjectLevel() duplicates the closure lookup instead of calling getMandatorySubject() — avoids triggering UnknownProgramException on programs with no mandatory subject when only the level is needed

## Deviations from Plan

None - plan executed exactly as written. The plan pre-specified the closure-based filtering approach as the preferred option, so no mid-execution switch was needed.

## Issues Encountered

- Pre-existing ExampleTest failure (`GET /` returns 404) — confirmed pre-existing from before this plan, unrelated to changes made here, not a regression.

## Next Phase Readiness

- Strategy layer complete and tested — Phase 6 scoring engine can depend on ProgramRequirementsInterface without touching raw Eloquent models
- ProgramRegistry is ready for injection in Phase 8 API controllers with eager-loaded program relationships

---
*Phase: 05-strategy-pattern*
*Completed: 2026-02-26*
