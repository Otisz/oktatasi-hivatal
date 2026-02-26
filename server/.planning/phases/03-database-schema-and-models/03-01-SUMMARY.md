---
phase: 03-database-schema-and-models
plan: 01
subsystem: database
tags: [sqlite, uuid, migrations, enums, foreign-keys, cascade-delete]

# Dependency graph
requires: []
provides:
  - RequirementType backed string enum (mandatory/elective)
  - programs table with UUID PK (university, faculty, name)
  - program_subjects table with UUID PK and FK to programs (subject_name, requirement_type, nullable required_level)
  - applicants table with UUID PK and FK to programs
  - applicant_exam_results table with UUID PK and FK to applicants (subject_name, level, unsignedTinyInteger percentage)
  - applicant_bonus_points table with UUID PK and FK to applicants (category, type, nullable language)
affects:
  - 03-02-models (Eloquent models map to these tables)
  - 04-seeders (seeder data inserts into these tables)
  - 05-business-logic (scoring engine reads from these tables)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - UUID primary keys via uuid('id')->primary() on all domain tables
    - foreignUuid()->constrained()->cascadeOnDelete() for all FK constraints
    - declare(strict_types=1) on all migration files
    - Void return type on Blueprint closures (function (Blueprint $table): void)
    - Migration timestamps ordered sequentially to respect FK dependency chain

key-files:
  created:
    - app/Enums/RequirementType.php
    - database/migrations/2026_02_26_092752_create_programs_table.php
    - database/migrations/2026_02_26_092754_create_program_subjects_table.php
    - database/migrations/2026_02_26_092755_create_applicants_table.php
    - database/migrations/2026_02_26_092756_create_applicant_exam_results_table.php
    - database/migrations/2026_02_26_092757_create_applicant_bonus_points_table.php
  modified: []

key-decisions:
  - "Migration timestamps assigned sequentially (092752/092754/092755/092756/092757) to guarantee FK dependency order — artisan assigned same second to four migrations which would run alphabetically, breaking FK constraints in strict DBs"
  - "All domain tables use UUID primary keys, not auto-increment bigInt"
  - "Cascade delete on all FK constraints — child rows removed when parent is deleted"

patterns-established:
  - "UUID PK pattern: $table->uuid('id')->primary() on every domain table"
  - "FK pattern: $table->foreignUuid('x_id')->constrained()->cascadeOnDelete()"
  - "Migration strict types: declare(strict_types=1) after <?php"
  - "Blueprint void return: function (Blueprint $table): void"

requirements-completed: [DB-01, DB-02, DB-03, DB-04, DB-05]

# Metrics
duration: 2min
completed: 2026-02-26
---

# Phase 3 Plan 01: Database Schema and Migrations Summary

**Five UUID-keyed domain tables with FK cascade constraints plus RequirementType backed string enum, all verified via migrate:fresh**

## Performance

- **Duration:** 2 min
- **Started:** 2026-02-26T09:27:42Z
- **Completed:** 2026-02-26T09:29:27Z
- **Tasks:** 2
- **Files modified:** 6

## Accomplishments

- Created RequirementType enum with Mandatory ('mandatory') and Elective ('elective') backed string cases
- Created all five domain tables with UUID PKs and correct foreign key constraints with cascade delete
- Fixed migration timestamp ordering to ensure FK dependency chain is respected

## Task Commits

Each task was committed atomically:

1. **Task 1: Create RequirementType enum and five database migrations** - `140f9c4` (feat)
2. **Task 2: Verify database schema matches specification** - no commit (verification only)

## Files Created/Modified

- `app/Enums/RequirementType.php` - Backed string enum: Mandatory='mandatory', Elective='elective'
- `database/migrations/2026_02_26_092752_create_programs_table.php` - UUID PK, university/faculty/name columns
- `database/migrations/2026_02_26_092754_create_program_subjects_table.php` - UUID PK, FK to programs, subject_name, requirement_type, nullable required_level
- `database/migrations/2026_02_26_092755_create_applicants_table.php` - UUID PK, FK to programs
- `database/migrations/2026_02_26_092756_create_applicant_exam_results_table.php` - UUID PK, FK to applicants, subject_name, level, unsignedTinyInteger percentage
- `database/migrations/2026_02_26_092757_create_applicant_bonus_points_table.php` - UUID PK, FK to applicants, category, type, nullable language

## Decisions Made

- Migration timestamps assigned sequentially to guarantee FK dependency order. Artisan assigned the same second (`092753`) to four migrations, which would run alphabetically — `applicant_bonus_points` and `applicant_exam_results` before `applicants`, breaking FK constraints in strict databases. Renamed to `092754`/`092755`/`092756`/`092757`.

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed migration timestamp collision causing incorrect FK dependency order**
- **Found during:** Task 1 (after running migrate:fresh)
- **Issue:** Four migrations generated with identical timestamp `2026_02_26_092753_*`. Alphabetical sort placed `applicant_bonus_points` and `applicant_exam_results` before `applicants`, which would fail with FK constraint violations in MySQL/PostgreSQL (SQLite defers FK checks).
- **Fix:** Renamed files with unique sequential timestamps: `092754` (program_subjects), `092755` (applicants), `092756` (applicant_exam_results), `092757` (applicant_bonus_points).
- **Files modified:** Four migration file renames
- **Verification:** `php artisan migrate:fresh` runs clean with tables in correct dependency order
- **Committed in:** `140f9c4` (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (Rule 1 - timestamp ordering bug)
**Impact on plan:** Essential fix for FK constraint correctness in production databases. No scope creep.

## Issues Encountered

None — migration content written correctly on first pass. Timestamp collision was the only structural issue.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- All five domain tables created with correct schema matching IMPLEMENTATION.md Section 2
- UUID PKs and FK constraints in place for Eloquent model mapping in Plan 02
- RequirementType enum ready for use in ProgramSubject model casting
- Schema verified via tinker: nullable columns confirmed, enum values confirmed

---
*Phase: 03-database-schema-and-models*
*Completed: 2026-02-26*
