---
phase: 01-domain-primitives
plan: 02
subsystem: domain
tags: [exceptions, php, domain-errors, hungarian-admission]

# Dependency graph
requires:
  - phase: 01-domain-primitives/01-01
    provides: SubjectName and ExamLevel enums used in exception constructors
provides:
  - Abstract AdmissionException base class extending \Exception
  - FailedExamException with SubjectName + int readonly properties (Case 4 message)
  - MissingGlobalMandatorySubjectException with fixed Hungarian message (Case 3 message)
  - MissingProgramMandatorySubjectException with SubjectName readonly property
  - ProgramMandatorySubjectLevelException with SubjectName + ExamLevel readonly properties
  - MissingElectiveSubjectException with fixed Hungarian message
  - UnknownProgramException with fixed Hungarian message
affects: [scoring-service, api-layer, exception-handling]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Abstract base exception with final typed subclasses carrying readonly context properties
    - Pure domain exceptions with no HTTP awareness (no render/report methods)
    - Hungarian error messages embedded in constructors via string interpolation on enum values

key-files:
  created:
    - app/Exceptions/AdmissionException.php
    - app/Exceptions/FailedExamException.php
    - app/Exceptions/MissingGlobalMandatorySubjectException.php
    - app/Exceptions/MissingProgramMandatorySubjectException.php
    - app/Exceptions/ProgramMandatorySubjectLevelException.php
    - app/Exceptions/MissingElectiveSubjectException.php
    - app/Exceptions/UnknownProgramException.php
  modified: []

key-decisions:
  - "Abstract AdmissionException with empty body — prevents direct instantiation and exempts it from Pint's final_class rule"
  - "Readonly promoted constructor properties on all contextual exceptions — type safety and immutability"
  - "No render() or report() methods — pure domain exceptions, HTTP mapping deferred to API layer (Phase 8)"

patterns-established:
  - "Exception hierarchy: abstract base + final contextual subclasses with readonly properties"
  - "Hungarian messages constructed via enum value interpolation in constructors"

requirements-completed: [DOM-07]

# Metrics
duration: 1min
completed: 2026-02-25
---

# Phase 1 Plan 2: Exception Hierarchy Summary

**Abstract AdmissionException base and six final typed subclasses carrying readonly context properties with exact Hungarian admission error messages**

## Performance

- **Duration:** 1 min
- **Started:** 2026-02-25T21:54:15Z
- **Completed:** 2026-02-25T21:55:40Z
- **Tasks:** 2
- **Files modified:** 7

## Accomplishments

- Created abstract AdmissionException base preventing direct instantiation, extending \Exception
- Implemented FailedExamException and MissingGlobalMandatorySubjectException with exact Hungarian messages matching homework_input.php Cases 4 and 3
- Implemented four remaining subclasses (MissingProgramMandatorySubjectException, ProgramMandatorySubjectLevelException, MissingElectiveSubjectException, UnknownProgramException) with descriptive Hungarian messages
- All seven files pass PHPStan level 7 with zero errors and Pint formatting

## Task Commits

Each task was committed atomically:

1. **Task 1: Create AdmissionException base and two acceptance-tested exceptions** - `0b0a270` (feat)
2. **Task 2: Create the four remaining exception subclasses** - `19ce594` (feat)

**Plan metadata:** (docs commit — see final step)

## Files Created/Modified

- `app/Exceptions/AdmissionException.php` - Abstract base extending \Exception, empty body, strict types
- `app/Exceptions/FailedExamException.php` - Final, SubjectName + int readonly, Case 4 message
- `app/Exceptions/MissingGlobalMandatorySubjectException.php` - Final, no-arg, Case 3 message
- `app/Exceptions/MissingProgramMandatorySubjectException.php` - Final, SubjectName readonly, Hungarian message
- `app/Exceptions/ProgramMandatorySubjectLevelException.php` - Final, SubjectName + ExamLevel readonly, Hungarian message
- `app/Exceptions/MissingElectiveSubjectException.php` - Final, no-arg, fixed Hungarian message
- `app/Exceptions/UnknownProgramException.php` - Final, no-arg, fixed Hungarian message

## Decisions Made

- Abstract AdmissionException with empty body — no methods or properties, prevents direct instantiation, and abstract keyword exempts from Pint's final_class rule
- No render() or report() methods added — these are pure domain exceptions; HTTP status mapping is the API layer's responsibility (Phase 8)
- Readonly promoted constructor properties carry context data for all exceptions that have it

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Full exception hierarchy complete; scoring service (Phase 7) can throw these exceptions during ordered validation
- API layer (Phase 8) can map all AdmissionException subclasses to 422 responses using instanceof checks
- All 10 files in app/Enums/ and app/Exceptions/ pass PHPStan level 7

---
*Phase: 01-domain-primitives*
*Completed: 2026-02-25*
