# Hungarian University Admission Score Calculator API

## What This Is

A Laravel 12 REST API that calculates Hungarian university admission scores for pre-seeded applicants. Given an applicant ID, it validates exam results against programme-specific rules (mandatory subjects, level requirements, minimum scores) and returns a structured score breakdown (base points + bonus points = total) or a descriptive Hungarian-language domain error. Two endpoints: list applicants and calculate score.

## Core Value

Correct, rule-compliant admission score calculation — the scoring engine must enforce all Hungarian admission rules in the right order and produce exact expected results for every test case.

## Requirements

### Validated

- ✓ Domain model: 3 backed string enums, 6 typed exception classes, 3 immutable Value Objects — v1.0
- ✓ Database schema: 5 tables with UUID PKs, typed relationships, enum casts, factories — v1.0
- ✓ Seed data: 2 programmes + 4 test applicants matching homework specification exactly — v1.0
- ✓ Strategy pattern: DatabaseProgramRequirements + ProgramRegistry for programme-specific rules — v1.0
- ✓ Base point calculation: (mandatory + best elective) × 2, max 400 — v1.0
- ✓ Bonus point calculation: +50 emelt, B2/C1 certs, same-language dedup, cap 100 — v1.0
- ✓ Validation chain: failed exam → missing global mandatory → missing programme mandatory → wrong level → missing elective — v1.0
- ✓ AdmissionScoringService orchestrating validation, VO mapping, and calculation — v1.0
- ✓ API endpoints: GET /api/v1/applicants (list) and GET /api/v1/applicants/{applicant}/score — v1.0
- ✓ Exception rendering: AdmissionException → 422 JSON with Hungarian error message — v1.0
- ✓ Full test suite: 73 tests (55 unit + 18 feature), all 4 acceptance cases passing — v1.0

### Active

(None — this is a complete homework exercise with no planned future scope)

### Out of Scope

- Authentication / authorization — not needed for this calculator
- CRUD for applicants or programmes — data is seeded, not user-managed
- Pagination — small dataset, unnecessary
- Internationalisation — error messages are in Hungarian only
- Frontend — API-only project
- Score caching — static seeded data, SQLite is fast enough
- Bulk score calculation — not in spec

## Context

Shipped v1.0 with 2,525 LOC PHP across app/, tests/, and database/.
Tech stack: Laravel 12, PHP 8.5, SQLite, Pest 4.
Architecture: Strategy pattern for programme requirements, Value Objects for domain logic, Service layer for orchestration.
All 4 acceptance test cases from the original homework specification pass.
Audit passed: 45/45 requirements satisfied, 10/10 cross-phase integrations wired, 2/2 E2E flows verified.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| DB-backed programme requirements (not hardcoded) | Supports arbitrary programmes without code changes | ✓ Good — clean separation |
| Single DatabaseProgramRequirements strategy class | All programmes share the same DB-driven logic | ✓ Good — no per-programme classes needed |
| Value Objects over raw arrays | Type safety, immutability, encapsulated validation | ✓ Good — caught bugs at construction time |
| Validation order matches exception hierarchy | First failing rule stops processing; deterministic errors | ✓ Good — all 4 acceptance cases pass |
| Hungarian field names in API response | Matches original homework specification | ✓ Good — spec compliance |
| UUID primary keys | Avoids sequential ID exposure | ✓ Good — works with SQLite |
| Interface extraction for scoring services | Final classes can't be mocked; interfaces enable test isolation | ✓ Good — full mock coverage |
| Singleton container bindings for stateless services | One instance shared across requests | ✓ Good — clean DI |

## Constraints

- **Tech stack**: Laravel 12, PHP 8.5, Pest for testing
- **Architecture**: Strategy pattern, Value Objects, Service layer
- **Database**: SQLite for dev/testing, schema per spec
- **Build order**: Bottom-up TDD (enums → VOs → DB → seeders → strategy → calculators → service → API)

---
*Last updated: 2026-02-28 after v1.0 milestone*
