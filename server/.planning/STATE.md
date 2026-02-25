# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-02-25)

**Core value:** Correct, rule-compliant admission score calculation — the scoring engine must enforce all Hungarian admission rules in the right order and produce exact expected results for every test case.
**Current focus:** Phase 1 — Domain Primitives

## Current Position

Phase: 1 of 8 (Domain Primitives)
Plan: 0 of ? in current phase
Status: Ready to plan
Last activity: 2026-02-25 — Roadmap created

Progress: [░░░░░░░░░░] 0%

## Performance Metrics

**Velocity:**
- Total plans completed: 0
- Average duration: — min
- Total execution time: 0 hours

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| - | - | - | - |

**Recent Trend:**
- Last 5 plans: —
- Trend: —

*Updated after each plan completion*

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- DB-backed programme requirements (not hardcoded) — supports arbitrary programmes without code changes
- Single DatabaseProgramRequirements strategy class — all programmes share the same DB-driven logic
- Value Objects over raw arrays — type safety, immutability, encapsulated validation
- Validation order matches exception hierarchy — first failing rule stops processing

### Pending Todos

None yet.

### Blockers/Concerns

- Research flag: PHP readonly class semantics and PHPStan level 7 array shape annotations for Phase 2 VOs
- Research flag: Mockery constructor mocking syntax with Pest 4 / PHPUnit 12 for Phase 7
- `AngoNyelv` enum key typo in IMPLEMENTATION.md — use `AngolNyelv` (correct spelling) during Phase 1

## Session Continuity

Last session: 2026-02-25
Stopped at: Phase 1 context gathered
Resume file: .planning/phases/01-domain-primitives/01-CONTEXT.md
