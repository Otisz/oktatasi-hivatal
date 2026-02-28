---
phase: 06-calculators
plan: 01
subsystem: scoring
tags: [calculator, base-points, bonus-points, tdd, unit-test, pest]

# Dependency graph
requires:
  - phase: 02-value-objects
    provides: ExamResult and LanguageCertificate VOs consumed by both calculators
  - phase: 01-domain-primitives
    provides: SubjectName, ExamLevel, LanguageCertificateType enums

provides:
  - BasePointCalculator: pure stateless service, formula (mandatory + bestElective) * 2, capped at 400
  - BonusPointCalculator: pure stateless service, emelt +50 each, per-language cert dedup, total capped at 100

affects: [07-admission-scoring, 08-api-or-integration]

# Tech tracking
tech-stack:
  added: []
  patterns: [pure stateless final service classes, no-constructor services (no property promotion needed), array<string, int> language dedup map]

key-files:
  created:
    - app/Services/BasePointCalculator.php
    - app/Services/BonusPointCalculator.php
    - tests/Unit/Services/BasePointCalculatorTest.php
    - tests/Unit/Services/BonusPointCalculatorTest.php
  modified: []

key-decisions:
  - "BasePointCalculator is final class (not final readonly) — no constructor properties to promote, per plan guidance"
  - "BonusPointCalculator uses array<string, int> langMap with max() dedup — language string is the dedup key, not cert type"
  - "Both calculators are dependency-injection-ready pure services with no dependencies — no constructor needed"

patterns-established:
  - "Stateless calculator pattern: final class with single public calculate() method, no state, no constructor"
  - "PHPDoc array shapes on service method parameters for Larastan type safety"

requirements-completed: [BIZ-03, BIZ-04, TEST-06, TEST-07]

# Metrics
duration: 1min
completed: 2026-02-28
---

# Phase 06 Plan 01: Calculators Summary

**BasePointCalculator and BonusPointCalculator as pure stateless services: formula (mandatory + bestElective) * 2 capped at 400, and emelt +50 per exam with per-language cert dedup capped at 100**

## Performance

- **Duration:** 1 min
- **Started:** 2026-02-28T14:09:56Z
- **Completed:** 2026-02-28T14:11:00Z
- **Tasks:** 2
- **Files modified:** 4

## Accomplishments

- BasePointCalculator with `calculate(ExamResult, ExamResult): int` — formula with 400 cap, 7 unit tests
- BonusPointCalculator with `calculate(ExamResult[], LanguageCertificate[]): int` — emelt counting, same-language dedup (keeps max), 100 cap, 12 unit tests
- 55 total unit tests passing (19 new + 36 existing), no regressions

## Task Commits

Each task was committed atomically:

1. **Task 1: TDD BasePointCalculator** - `0e4d0da` (feat)
2. **Task 2: TDD BonusPointCalculator** - `259c11d` (feat)

_Note: TDD tasks use single commit (test + implementation together per file pair)_

## Files Created/Modified

- `app/Services/BasePointCalculator.php` - Pure stateless service, formula `min((mandatory + elective) * 2, 400)`
- `app/Services/BonusPointCalculator.php` - Pure stateless service, emelt +50 each, per-language cert dedup with max(), total capped at 100
- `tests/Unit/Services/BasePointCalculatorTest.php` - 7 unit tests: typical, max, min, dataset, cap boundary
- `tests/Unit/Services/BonusPointCalculatorTest.php` - 12 unit tests: emelt-only, cert-only (B2/C1), same-language dedup, cross-language count, mixed cap scenarios

## Decisions Made

- Both calculators are `final class` (not `final readonly class`) — no constructor properties exist; no-arg constructors are prohibited by CLAUDE.md
- BonusPointCalculator dedup uses `array<string, int>` map keyed by `language()` string — correct per spec (same language string = same language, different strings = different languages even if same cert type)
- No constructor injection needed — both services are pure functions with no internal state or dependencies

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Both calculators ready for consumption by `AdmissionScoringService` in Phase 7
- Services are dependency-injection-ready as no-arg constructors or can be resolved from container
- Full unit test coverage ensures correctness before integration

## Self-Check: PASSED

- app/Services/BasePointCalculator.php: FOUND
- app/Services/BonusPointCalculator.php: FOUND
- tests/Unit/Services/BasePointCalculatorTest.php: FOUND
- tests/Unit/Services/BonusPointCalculatorTest.php: FOUND
- .planning/phases/06-calculators/06-01-SUMMARY.md: FOUND
- Commit 0e4d0da (Task 1): FOUND
- Commit 259c11d (Task 2): FOUND
