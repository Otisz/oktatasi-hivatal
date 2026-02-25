---
phase: 01-domain-primitives
plan: 01
subsystem: domain
tags: [enums, php, backed-string-enum, hungarian-admission]

# Dependency graph
requires: []
provides:
  - SubjectName backed string enum with 13 Hungarian subject cases and globallyMandatory()/isLanguage() helpers
  - ExamLevel backed string enum with Intermediate ('közép') and Advanced ('emelt') cases
  - LanguageCertificateType backed string enum with UpperIntermediate (B2, 28pts) and Advanced (C1, 40pts) with points() method
affects: [02-value-objects, 03-exceptions, 04-models, 05-calculator, 06-service, 07-tests, 08-api]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Backed string PHP enums with accented Hungarian values as type-safe vocabulary layer
    - Static helper methods on enums (globallyMandatory) for co-locating business rules
    - Instance predicate methods on enums (isLanguage) using strict in_array comparison

key-files:
  created:
    - app/Enums/SubjectName.php
    - app/Enums/ExamLevel.php
    - app/Enums/LanguageCertificateType.php
  modified: []

key-decisions:
  - "English TitleCase case names with accented Hungarian backing values — SubjectName::EnglishLanguage = 'angol nyelv'"
  - "globallyMandatory() returns array of 3 core subjects; isLanguage() uses strict in_array for 6 foreign languages"
  - "LanguageCertificateType points() uses exhaustive match expression — PHPStan verifies all cases covered"

patterns-established:
  - "Enum-as-vocabulary: all admission domain strings flow through typed enums, never raw strings"
  - "Helper methods on enums: business rules (mandatory subjects, language classification) live on the enum itself"

requirements-completed: [DOM-01, DOM-02, DOM-03]

# Metrics
duration: 5min
completed: 2026-02-25
---

# Phase 1 Plan 1: Domain Primitives — Enum Vocabulary Summary

**Three backed string PHP enums establishing the type-safe Hungarian admission scoring vocabulary: 13 subject cases, exam levels with Hungarian backing values, and language certificate types with points mapping**

## Performance

- **Duration:** 5 min
- **Started:** 2026-02-25T10:09:40Z
- **Completed:** 2026-02-25T10:14:49Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- SubjectName enum with all 13 Hungarian secondary school subjects as backed string cases, plus globallyMandatory() and isLanguage() helper methods
- ExamLevel enum mapping Intermediate/Advanced to Hungarian 'közép'/'emelt' backing values
- LanguageCertificateType enum with UpperIntermediate (B2) and Advanced (C1) cases and a points() method returning 28 and 40 respectively

## Task Commits

Each task was committed atomically:

1. **Task 1: Create SubjectName enum with 13 cases and helper methods** - `ea44cea` (feat)
2. **Task 2: Create ExamLevel and LanguageCertificateType enums** - `4eff015` (feat)

**Plan metadata:** (docs commit — see below)

## Files Created/Modified
- `app/Enums/SubjectName.php` - 13-case backed string enum; globallyMandatory() returns 3 core subjects; isLanguage() identifies 6 foreign language subjects
- `app/Enums/ExamLevel.php` - 2-case backed string enum: Intermediate ('közép'), Advanced ('emelt')
- `app/Enums/LanguageCertificateType.php` - 2-case backed string enum with points() method: UpperIntermediate (B2, 28pts), Advanced (C1, 40pts)

## Decisions Made
- English TitleCase case names with accented Hungarian backing values sourced from homework_input.php (authoritative)
- `isLanguage()` uses `in_array($this, [...], true)` strict comparison per plan specification
- `globallyMandatory()` PHPDoc annotated with `@return array<int, self>` for PHPStan level 7 compatibility

## Deviations from Plan

None - plan executed exactly as written.

**Note (out-of-scope):** Pre-existing project files (migrations, config, routes) have Pint formatting issues that predated this plan. Logged as deferred — not caused by this task's changes. Our enum files passed `--dirty` check.

## Issues Encountered
- `php artisan make:enum SubjectName --string` created the file in `app/SubjectName.php` instead of `app/Enums/SubjectName.php`. Removed scaffolded file and created the enum directly in the correct location with the correct `App\Enums` namespace.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- All three enum files are in `app/Enums/` with correct namespace, strict types, and PHPStan level 7 compliance
- Ready for Phase 2 (Value Objects) which imports SubjectName and ExamLevel
- Ready for Phase 3 (Exceptions) which imports SubjectName for error messages

---
*Phase: 01-domain-primitives*
*Completed: 2026-02-25*

## Self-Check: PASSED

- FOUND: app/Enums/SubjectName.php
- FOUND: app/Enums/ExamLevel.php
- FOUND: app/Enums/LanguageCertificateType.php
- FOUND: commit ea44cea (feat: SubjectName enum)
- FOUND: commit 4eff015 (feat: ExamLevel and LanguageCertificateType enums)
