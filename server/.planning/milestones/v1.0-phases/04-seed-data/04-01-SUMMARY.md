---
phase: 04-seed-data
plan: 01
subsystem: database
tags: [seeder, eloquent, uuid, enums]

# Dependency graph
requires:
  - phase: 03-database-schema-and-models
    provides: Program, ProgramSubject, Applicant, ApplicantExamResult, ApplicantBonusPoint models with HasUuids and enum casts
provides:
  - ProgramSeeder with ELTE IK (5 subjects) and PPKE BTK (7 subjects) using deterministic UUID constants
  - ApplicantSeeder with 4 acceptance-test applicants (19 exam results, 8 bonus points) matching homework_input.php exactly
  - DatabaseSeeder orchestrating ProgramSeeder then ApplicantSeeder in FK order
affects: [phase-08-scoring-engine-tests, phase-08-feature-tests]

# Tech tracking
tech-stack:
  added: []
  patterns: [deterministic UUID constants on Seeder class, PHPDoc outcome comments per applicant block, cross-seeder FK reference via ProgramSeeder::ELTE_IK_UUID]

key-files:
  created:
    - database/seeders/ProgramSeeder.php
    - database/seeders/ApplicantSeeder.php
  modified:
    - database/seeders/DatabaseSeeder.php

key-decisions:
  - "UUID constants defined on ProgramSeeder (ELTE_IK_UUID, PPKE_BTK_UUID) for cross-referencing from ApplicantSeeder without string literals"
  - "Enum instances passed directly to Eloquent create() — model casts() handles serialization, no ->value needed"
  - "DatabaseSeeder has no WithoutModelEvents trait — models have no observers per Phase 3 locked decision"
  - "All 4 applicants seeded against ELTE IK program — Phase 8 tests apply program-specific scoring on top"

patterns-established:
  - "Seeder UUID constants pattern: define public const string on Seeder class, reference cross-seeder via ClassName::CONSTANT"
  - "PHPDoc outcome comments on each seeder applicant block document expected scoring engine result"

requirements-completed: [SEED-01, SEED-02, SEED-03, SEED-04, SEED-05, SEED-06, SEED-07]

# Metrics
duration: 3min
completed: 2026-02-26
---

# Phase 4 Plan 01: Seed Data Summary

**Two programmes (12 subject requirements) and four acceptance-test applicants (19 exams, 8 bonus points) seeded with deterministic UUIDs and exact homework_input.php data**

## Performance

- **Duration:** 3 min
- **Started:** 2026-02-26T09:32:18Z
- **Completed:** 2026-02-26T09:35:00Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments
- ProgramSeeder creates ELTE IK Programtervező informatikus (1 mandatory matematika + 4 elective) and PPKE BTK Anglisztika (1 mandatory emelt angol + 6 elective) with deterministic UUID constants
- ApplicantSeeder creates 4 acceptance-test applicants with deterministic UUIDs (CASE_1..4_UUID), exact exam results and bonus points from homework_input.php
- DatabaseSeeder rewritten without WithoutModelEvents, calling seeders in FK order — migrate:fresh --seed exits cleanly

## Task Commits

Each task was committed atomically:

1. **Task 1: Create ProgramSeeder and ApplicantSeeder with exact homework data** - `ea3edf8` (feat)
2. **Task 2: Update DatabaseSeeder and verify migrate:fresh --seed** - `9e9c041` (feat)

**Plan metadata:** (docs commit follows)

## Files Created/Modified
- `database/seeders/ProgramSeeder.php` - Seeds ELTE IK and PPKE BTK programmes with subject requirements; exposes ELTE_IK_UUID and PPKE_BTK_UUID constants
- `database/seeders/ApplicantSeeder.php` - Seeds 4 acceptance-test applicants with exact exam results and bonus points; PHPDoc outcome comments on each
- `database/seeders/DatabaseSeeder.php` - Orchestrates ProgramSeeder then ApplicantSeeder; no WithoutModelEvents, no User factory

## Decisions Made
- UUID constants defined on ProgramSeeder class rather than in config — keeps FK references within seeder domain, no magic strings in ApplicantSeeder
- Enum instances passed directly (e.g., `SubjectName::Mathematics`) — model `casts()` handles DB serialization
- All 4 test applicants assigned to ELTE IK program_id — Phase 8 scoring tests will verify program-specific rules

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered
None.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Seed data complete and verified: 2 programmes, 12 subjects, 4 applicants, 19 exam results, 8 bonus points
- migrate:fresh --seed runs without error
- Applicant::CASE_*_UUID constants ready for Phase 8 feature tests to reference
- ProgramSeeder::ELTE_IK_UUID constant available for any seeder that needs program_id FK

---
*Phase: 04-seed-data*
*Completed: 2026-02-26*
