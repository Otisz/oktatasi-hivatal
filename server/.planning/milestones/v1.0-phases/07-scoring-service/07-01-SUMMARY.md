---
phase: 07-scoring-service
plan: 01
subsystem: scoring
tags: [service, orchestration, tdd, validation, value-objects]
dependency_graph:
  requires:
    - app/Services/ProgramRegistry.php
    - app/Services/BasePointCalculator.php
    - app/Services/BonusPointCalculator.php
    - app/ValueObjects/ExamResult.php
    - app/ValueObjects/LanguageCertificate.php
    - app/ValueObjects/Score.php
    - app/Contracts/ProgramRequirementsInterface.php
  provides:
    - app/Services/AdmissionScoringService.php
    - app/Contracts/ProgramRegistryInterface.php
    - app/Contracts/BasePointCalculatorInterface.php
    - app/Contracts/BonusPointCalculatorInterface.php
  affects:
    - tests/Unit/Services/AdmissionScoringServiceTest.php
tech_stack:
  added: []
  patterns:
    - TDD (RED-GREEN cycle with Pest 4 + Mockery)
    - Interface-based dependency injection for testability
    - Strict ordered validation chain (5 steps)
    - VO construction as implicit step 1 validation
key_files:
  created:
    - app/Services/AdmissionScoringService.php
    - app/Contracts/ProgramRegistryInterface.php
    - app/Contracts/BasePointCalculatorInterface.php
    - app/Contracts/BonusPointCalculatorInterface.php
    - tests/Unit/Services/AdmissionScoringServiceTest.php
  modified:
    - app/Services/ProgramRegistry.php
    - app/Services/BasePointCalculator.php
    - app/Services/BonusPointCalculator.php
decisions:
  - "[07-01]: Extracted ProgramRegistryInterface, BasePointCalculatorInterface, BonusPointCalculatorInterface — final concrete classes cannot be mocked by Mockery; interfaces enable full test isolation"
  - "[07-01]: AdmissionScoringService injects interfaces not concrete classes — follows existing ProgramRequirementsInterface pattern in app/Contracts/"
metrics:
  duration: 9 min
  completed_date: 2026-02-28
  tasks_completed: 2
  files_created: 5
  files_modified: 3
---

# Phase 7 Plan 1: AdmissionScoringService Summary

**One-liner:** Orchestrating scoring service wiring VO mapping, 5-step ordered validation chain, and calculator delegation via interface-based DI.

## What Was Built

`AdmissionScoringService::calculateForApplicant(Applicant $applicant): Score` is the central orchestrator that:

1. Maps `ApplicantExamResult` Eloquent rows to `ExamResult` VOs — the VO constructor auto-throws `FailedExamException` for `percentage < 20` (step 1, implicit)
2. Maps `ApplicantBonusPoint` rows to `LanguageCertificate` VOs
3. Checks all three globally mandatory subjects are present (`SubjectName::globallyMandatory()`) — throws `MissingGlobalMandatorySubjectException` (step 2)
4. Resolves programme requirements via `ProgramRegistryInterface::findByApplicant()` (only after steps 1+2)
5. Finds the programme mandatory subject in exam results — throws `MissingProgramMandatorySubjectException` (step 3)
6. Validates the mandatory subject level — throws `ProgramMandatorySubjectLevelException` (step 4)
7. Finds the best-scoring elective from programme elective subjects — throws `MissingElectiveSubjectException` (step 5)
8. Delegates to `BasePointCalculatorInterface::calculate()` and `BonusPointCalculatorInterface::calculate()`
9. Returns `new Score($basePoints, $bonusPoints)`

Three contracts were created in `app/Contracts/` to make the injected dependencies mockable in tests, and the concrete service classes were updated to implement their respective interfaces.

## Tasks Completed

| Task | Description | Commit |
|------|-------------|--------|
| 1 RED | 9 failing tests for AdmissionScoringService | 69ccb47 |
| 2 GREEN | AdmissionScoringService + 3 contracts; all 9 tests pass | 5826a31 |

## Tests

- 9 tests in `tests/Unit/Services/AdmissionScoringServiceTest.php`
- All 9 pass; no regressions in existing 64 unit tests
- Covers: step 1 (FailedExam), step 2 (3 global mandatory subjects), step 3 (programme mandatory absent), step 4 (wrong level), step 5 (no elective), happy path, ordering guarantee (step 1 fires before step 2)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Extracted interfaces for injectable final classes**

- **Found during:** Task 2 (GREEN) — Mockery cannot mock `final` classes
- **Issue:** `ProgramRegistry`, `BasePointCalculator`, and `BonusPointCalculator` are all `final class`. Mockery throws `"The class is marked final and its methods cannot be replaced"` when attempting `Mockery::mock(ProgramRegistry::class)`.
- **Fix:** Created `ProgramRegistryInterface`, `BasePointCalculatorInterface`, and `BonusPointCalculatorInterface` in `app/Contracts/`. Updated concrete classes to `implements` their interfaces. Updated `AdmissionScoringService` constructor to accept interfaces (not concrete types). Updated tests to mock the interfaces.
- **Files modified:** `app/Contracts/ProgramRegistryInterface.php` (created), `app/Contracts/BasePointCalculatorInterface.php` (created), `app/Contracts/BonusPointCalculatorInterface.php` (created), `app/Services/ProgramRegistry.php`, `app/Services/BasePointCalculator.php`, `app/Services/BonusPointCalculator.php`, `app/Services/AdmissionScoringService.php`, `tests/Unit/Services/AdmissionScoringServiceTest.php`
- **Commits:** 5826a31

## Self-Check: PASSED
