---
phase: 02-value-objects
plan: 02
subsystem: domain
tags: [value-objects, readonly, pest, tdd, php8, enums]

# Dependency graph
requires:
  - phase: 01-domain-primitives
    provides: LanguageCertificateType enum with points() method
provides:
  - LanguageCertificate readonly VO delegating points to enum
  - Score readonly VO with non-negative validation and total()
affects:
  - 06-calculators (Score is the output container for admission calculation results)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - final readonly class with constructor-promoted public properties
    - Delegation pattern — VO delegates business rule to enum (LanguageCertificate->points() calls type->points())
    - Dual accessor pattern — public readonly property + same-named method coexist in PHP 8.2+

key-files:
  created:
    - app/ValueObjects/LanguageCertificate.php
    - app/ValueObjects/Score.php
    - tests/Unit/ValueObjects/LanguageCertificateTest.php
    - tests/Unit/ValueObjects/ScoreTest.php
  modified: []

key-decisions:
  - "LanguageCertificate has no constructor validation — LanguageCertificateType is type-safe and language is any string"
  - "Score dual accessor pattern confirmed valid in PHP 8.2+ — public readonly $basePoints property and basePoints() method coexist without PHPStan errors"
  - "Both VOs marked final by Pint final_class rule — consistent with ExamResult"

patterns-established:
  - "Delegation to enum: VO.points() calls $this->type->points() — no business logic duplicated in VO"
  - "Non-negative integer validation with descriptive InvalidArgumentException messages including the invalid value"
  - "TDD RED commit (test:) before GREEN commit (feat:) for each VO plan"

requirements-completed: [DOM-05, DOM-06, TEST-02, TEST-03]

# Metrics
duration: 1min
completed: 2026-02-26
---

# Phase 02 Plan 02: LanguageCertificate and Score Value Objects Summary

**Two final readonly VOs: LanguageCertificate delegates points() to LanguageCertificateType enum; Score validates non-negative ints and computes total() for admission result output**

## Performance

- **Duration:** 1 min
- **Started:** 2026-02-26T10:02:19Z
- **Completed:** 2026-02-26T10:03:37Z
- **Tasks:** 2 (RED + GREEN/REFACTOR)
- **Files modified:** 4

## Accomplishments

- LanguageCertificate readonly class with type and language properties, points() delegation to enum
- Score readonly class with non-negative validation, total(), and dual accessor methods (property + method)
- 14 Pest unit tests covering dataset-driven B2/C1 point values, language parity, total calculation, and InvalidArgumentException for negatives
- PHPStan level 7 clean; Pint applied final_class rule to both VOs

## Task Commits

Each task was committed atomically:

1. **Task 1: RED — Write failing LanguageCertificateTest and ScoreTest** - `94291b9` (test)
2. **Task 2: GREEN + REFACTOR — Implement LanguageCertificate and Score readonly classes** - `886821d` (feat)

_Note: TDD tasks have separate RED (test) and GREEN (feat) commits_

## Files Created/Modified

- `app/ValueObjects/LanguageCertificate.php` - Final readonly VO wrapping LanguageCertificateType enum, delegates points() to $this->type->points()
- `app/ValueObjects/Score.php` - Final readonly VO with non-negative basePoints/bonusPoints validation, total() computation, and accessor methods
- `tests/Unit/ValueObjects/LanguageCertificateTest.php` - 7 test cases: B2/C1 dataset (2), language method, properties, method/property parity (3)
- `tests/Unit/ValueObjects/ScoreTest.php` - 7 test cases: total dataset (3), basePoints accessors, bonusPoints accessors, negative validation (2)

## Decisions Made

- LanguageCertificate requires no constructor validation — enum type enforces certificate type safety, language accepts any string
- Score dual accessor confirmed: PHP 8.2+ readonly property and same-named method coexist; PHPStan level 7 passes with no errors
- Pint final_class rule added `final` to both classes — consistent with ExamResult from plan 01

## Deviations from Plan

None - plan executed exactly as written. The plan correctly anticipated that `app/ValueObjects/` directory would exist from plan 01 (it did).

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- All three Phase 2 VOs complete: ExamResult, LanguageCertificate, Score
- Phase 3 (or next phase) can consume Score as the admission calculation result container
- Phase 6 calculators will construct Score instances with computed basePoints and bonusPoints

## Self-Check: PASSED

- FOUND: app/ValueObjects/LanguageCertificate.php
- FOUND: app/ValueObjects/Score.php
- FOUND: tests/Unit/ValueObjects/LanguageCertificateTest.php
- FOUND: tests/Unit/ValueObjects/ScoreTest.php
- FOUND: 02-02-SUMMARY.md
- FOUND: commit 94291b9 (test RED)
- FOUND: commit 886821d (feat GREEN+REFACTOR)

---
*Phase: 02-value-objects*
*Completed: 2026-02-26*
