# Hungarian University Admission Score Calculator API

## What This Is

A Laravel REST API that calculates Hungarian university admission scores for pre-seeded applicants. Given an applicant ID, it validates exam results against programme-specific rules (mandatory subjects, level requirements, minimum scores) and returns a structured score breakdown (base points + bonus points) or a descriptive domain error. Two endpoints: list applicants and calculate score.

## Core Value

Correct, rule-compliant admission score calculation — the scoring engine must enforce all Hungarian admission rules in the right order and produce exact expected results for every test case.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Domain model: enums (SubjectName, ExamLevel, LanguageCertificateType), Value Objects (ExamResult, LanguageCertificate, Score), custom exceptions
- [ ] Database schema: programs, program_subjects, applicants, applicant_exam_results, applicant_bonus_points — with migrations, models, factories
- [ ] Seed data: 2 programmes (ELTE IK Programtervező informatikus, PPKE BTK Anglisztika) and 4 test applicants with exam results and bonus points
- [ ] Programme requirements via Strategy pattern: DatabaseProgramRequirements implementing ProgramRequirementsInterface, resolved by ProgramRegistry
- [ ] Base point calculation: (mandatory + best elective) × 2, max 400
- [ ] Bonus point calculation: +50 per advanced exam, +28 B2 / +40 C1 language cert, same-language dedup, cap at 100
- [ ] Validation chain in strict order: failed exam (<20%) → missing global mandatory subjects → missing programme mandatory → wrong level → missing elective
- [ ] AdmissionScoringService orchestrating validation, VO mapping, and calculation
- [ ] API endpoints: GET /api/v1/applicants (list) and GET /api/v1/applicants/{applicant}/score (calculate)
- [ ] API resource for score response (osszpontszam, alappont, tobbletpont)
- [ ] Exception rendering: AdmissionException subclasses → 422 JSON with error message
- [ ] Unit tests: Value Objects, DatabaseProgramRequirements, ProgramRegistry, BasePointCalculator, BonusPointCalculator, AdmissionScoringService
- [ ] Feature tests: 4 acceptance cases (470pt, 476pt, missing subjects error, <20% error) + 404

### Out of Scope

- Authentication / authorization — not needed for this calculator
- CRUD for applicants or programmes — data is seeded, not user-managed
- Pagination — small dataset, unnecessary
- Internationalisation — error messages are in Hungarian only
- Frontend — API-only project

## Context

- Built on existing Laravel 12 skeleton with Pint, Pest, Larastan, and Rector already configured
- The IMPLEMENTATION.md defines a specific TDD build order that phases should follow
- Hungarian-language domain: enum values, error messages, and API field names use Hungarian terms
- 4 acceptance test cases from the original homework specification serve as the source of truth

## Constraints

- **Tech stack**: Laravel 12, PHP 8.5, Pest for testing — already set up
- **Architecture**: Strategy pattern for programme requirements, Value Objects for domain logic, Service layer for orchestration — as specified in IMPLEMENTATION.md
- **Build order**: Follow IMPLEMENTATION.md TDD sequence (enums → exceptions → VOs → contracts → migrations/models → seeders → services → API layer → tests)
- **Database**: SQLite for dev/testing, schema exactly as specified in the PRD

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| DB-backed programme requirements (not hardcoded) | Supports arbitrary programmes without code changes | — Pending |
| Single DatabaseProgramRequirements strategy class | All programmes share the same DB-driven logic; no per-programme classes needed | — Pending |
| Value Objects over raw arrays | Type safety, immutability, encapsulated validation | — Pending |
| Validation order matches exception hierarchy | First failing rule stops processing; deterministic error messages | — Pending |
| Hungarian field names in API response | Matches original homework specification (osszpontszam, alappont, tobbletpont) | — Pending |

---
*Last updated: 2026-02-25 after initialization*
