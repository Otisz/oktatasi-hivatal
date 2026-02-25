# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-02-25)

**Core value:** Correct, rule-compliant admission score calculation — the scoring engine must enforce all Hungarian admission rules in the right order and produce exact expected results for every test case.
**Current focus:** Phase 1 — Domain Primitives

## Current Position

Phase: 1 of 8 (Domain Primitives)
Plan: 2 of 3 in current phase
Status: In progress
Last activity: 2026-02-25 — Completed 01-02 (Exception Hierarchy)

Progress: [██░░░░░░░░] 17%

## Performance Metrics

**Velocity:**
- Total plans completed: 2
- Average duration: 3 min
- Total execution time: 0.10 hours

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-domain-primitives | 2 | 6 min | 3 min |

**Recent Trend:**
- Last 5 plans: 5min, 1min
- Trend: establishing baseline

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

### Pending Todos

None yet.

### Blockers/Concerns

- Research flag: PHP readonly class semantics and PHPStan level 7 array shape annotations for Phase 2 VOs
- Research flag: Mockery constructor mocking syntax with Pest 4 / PHPUnit 12 for Phase 7
- `AngoNyelv` enum key typo in IMPLEMENTATION.md — use `AngolNyelv` (correct spelling) during Phase 1

## Session Continuity

Last session: 2026-02-25
Stopped at: Completed 01-02-PLAN.md (Domain Primitives — Exception Hierarchy)
Resume file: .planning/phases/01-domain-primitives/01-03-PLAN.md
