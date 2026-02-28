---
phase: 02-value-objects
plan: 01
subsystem: domain
tags: [value-objects, readonly, tdd, pest, phpstan, pint]

# Dependency graph
requires:
  - phase: 01-domain-primitives
    provides: SubjectName enum, ExamLevel enum, FailedExamException class
provides:
  - ExamResult readonly Value Object with two-stage constructor validation
  - Pest unit tests with full boundary coverage for ExamResult
affects: [03-scoring-engine, 04-admission-rules]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "final readonly class for immutable Value Objects"
    - "Two-stage constructor validation: range guard (InvalidArgumentException) before business rule guard (domain exception)"
    - "Yoda-style enum comparison: ExamLevel::Advanced === $this->level"

key-files:
  created:
    - app/ValueObjects/ExamResult.php
    - tests/Unit/ValueObjects/ExamResultTest.php
  modified: []

key-decisions:
  - "final readonly class — Pint final_class rule enforced; readonly ensures immutability without explicit property declarations"
  - "Two-stage validation order locked by tests: range check throws InvalidArgumentException before business rule throws FailedExamException"
  - "points() returns percentage unchanged — thin accessor enabling uniform interface for future VOs"

patterns-established:
  - "TDD RED→GREEN→REFACTOR for all Value Objects"
  - "Boundary datasets covering -1, 0, 1, 19, 20, 50, 99, 100, 101 for percentage-based VOs"

requirements-completed: [DOM-04, TEST-01]

# Metrics
duration: 1min
completed: 2026-02-26
---

# Phase 2 Plan 1: ExamResult Value Object Summary

**Immutable ExamResult readonly VO with two-stage validation: InvalidArgumentException for out-of-range (< 0 or > 100), FailedExamException for failed exam (< 20), backed by 15 passing Pest boundary tests**

## Performance

- **Duration:** 1 min
- **Started:** 2026-02-26T08:41:48Z
- **Completed:** 2026-02-26T08:43:12Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments

- `final readonly class ExamResult` with SubjectName, ExamLevel, int constructor-promoted properties
- Two-stage validation: range check fires first (InvalidArgumentException), business rule fires second (FailedExamException)
- 15 Pest unit tests across 6 test cases covering all boundary values (-1, 0, 1, 19, 20, 50, 75, 85, 99, 100, 101)
- PHPStan level 7 passes with zero errors; Pint applied `final_class` and yoda-style fixes

## Task Commits

Each task was committed atomically:

1. **Task 1: RED — Write failing ExamResultTest** - `05e97b4` (test)
2. **Task 2: GREEN + REFACTOR — Implement ExamResult readonly class** - `9c97797` (feat)

_Note: TDD tasks have two commits (test RED → feat GREEN+REFACTOR)_

## Files Created/Modified

- `app/ValueObjects/ExamResult.php` - Immutable readonly VO with two-stage constructor validation, points() and isAdvancedLevel() methods
- `tests/Unit/ValueObjects/ExamResultTest.php` - 6 test cases, 15 assertions, full boundary dataset coverage

## Decisions Made

- `final readonly class` — Pint enforces `final_class` rule; using readonly eliminates need for explicit readonly property declarations
- Two-stage validation order locked by tests: -1 and 101 must throw `InvalidArgumentException` (not `FailedExamException`), proving range check fires first
- `points()` as thin accessor returning `$this->percentage` — uniform interface pattern for scoring engine consumption

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- ExamResult VO complete and tested; ready for Plan 02 (LanguageCertificate VO) and Plan 03 (Score VO)
- Pattern established: TDD with boundary datasets for all percentage/score-based VOs
- No blockers

## Self-Check: PASSED

- app/ValueObjects/ExamResult.php: FOUND
- tests/Unit/ValueObjects/ExamResultTest.php: FOUND
- .planning/phases/02-value-objects/02-01-SUMMARY.md: FOUND
- Commit 05e97b4 (test RED): FOUND
- Commit 9c97797 (feat GREEN): FOUND

---
*Phase: 02-value-objects*
*Completed: 2026-02-26*
