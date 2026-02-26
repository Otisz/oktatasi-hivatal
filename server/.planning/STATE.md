---
gsd_state_version: 1.0
milestone: v1.0
milestone_name: milestone
status: unknown
last_updated: "2026-02-26T09:34:25.000Z"
progress:
  total_phases: 4
  completed_phases: 3
  total_plans: 6
  completed_plans: 6
---

# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-02-25)

**Core value:** Correct, rule-compliant admission score calculation — the scoring engine must enforce all Hungarian admission rules in the right order and produce exact expected results for every test case.
**Current focus:** Phase 3 — Database Schema and Models

## Current Position

Phase: 3 of 8 (Database Schema and Models) — COMPLETE
Plan: 2 of 2 in current phase — COMPLETE
Status: Phase complete — ready for Phase 4
Last activity: 2026-02-26 — Completed 03-02 (Eloquent Models and Factories)

Progress: [████░░░░░░] 37%

## Performance Metrics

**Velocity:**
- Total plans completed: 6
- Average duration: 2 min
- Total execution time: 0.17 hours

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-domain-primitives | 2 | 6 min | 3 min |
| 02-value-objects | 2 | 2 min | 1 min |
| 03-database-schema-and-models | 2 | 4 min | 2 min |

**Recent Trend:**
- Last 5 plans: 5min, 1min, 1min, 1min, 2min
- Trend: fast and stable

*Updated after each plan completion*

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- DB-backed programme requirements (not hardcoded) — supports arbitrary programmes without code changes
- Single DatabaseProgramRequirements strategy class — all programmes share the same DB-driven logic
- Value Objects over raw arrays — type safety, immutability, encapsulated validation
- Validation order matches exception hierarchy — first failing rule stops processing
- English TitleCase case names with accented Hungarian backing values — SubjectName::EnglishLanguage = 'angol nyelv'
- Enum helper methods co-locate business rules: globallyMandatory() on SubjectName, points() on LanguageCertificateType
- LanguageCertificateType uses descriptive English names: UpperIntermediate (B2, 28pts), Advanced (C1, 40pts)
- Abstract AdmissionException with empty body — prevents direct instantiation, exempts from Pint final_class rule
- No render()/report() on exceptions — pure domain objects, HTTP mapping deferred to API layer
- Readonly promoted constructor properties carry context on all typed exception subclasses
- [Phase 02-value-objects]: final readonly class for ExamResult VO — Pint final_class rule enforced; two-stage validation order locked by tests
- [Phase 02-value-objects]: points() as thin accessor pattern — uniform interface for scoring engine to consume all VOs
- [02-02]: LanguageCertificate requires no constructor validation — enum type safety + language accepts any string
- [02-02]: Score dual accessor confirmed valid in PHP 8.2+ — public readonly property and same-named method coexist without PHPStan errors
- [Phase 03-database-schema-and-models]: Migration timestamps assigned sequentially to guarantee FK dependency order — artisan same-second collision fixed
- [Phase 03-database-schema-and-models]: All domain tables use UUID primary keys via uuid('id')->primary() — not auto-increment bigInt
- [Phase 03-database-schema-and-models]: Cascade delete on all FK constraints — child rows removed when parent deleted
- [03-02]: No $fillable/$guarded/$with on models — Model::unguard() active; all eager loading is explicit
- [03-02]: Factory enum values stored as .value strings to avoid DB type errors; factory has() requires explicit relationship name when method name differs from Laravel's auto-guess
- [03-02]: Default percentage in ApplicantExamResultFactory is 20-100 to avoid triggering FailedExamException; failingExam() state sets 0-19

### Pending Todos

None yet.

### Blockers/Concerns

- Research flag: PHP readonly class semantics and PHPStan level 7 array shape annotations for Phase 2 VOs
- Research flag: Mockery constructor mocking syntax with Pest 4 / PHPUnit 12 for Phase 7
- `AngoNyelv` enum key typo in IMPLEMENTATION.md — use `AngolNyelv` (correct spelling) during Phase 1

## Session Continuity

Last session: 2026-02-26
Stopped at: Completed 03-02-PLAN.md (Eloquent Models and Factories)
Resume file: .planning/phases/04-seeder/04-01-PLAN.md
