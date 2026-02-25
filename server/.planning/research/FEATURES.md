# Feature Landscape

**Domain:** Hungarian university admission score calculator API
**Researched:** 2026-02-25
**Confidence:** HIGH — based on official PRD and IMPLEMENTATION.md (primary source of truth)

---

## Table Stakes

Features a score calculator API must have or consumers cannot use it.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Score calculation endpoint | Core value — the entire product | High | `GET /api/v1/applicants/{applicant}/score` returning osszpontszam, alappont, tobbletpont |
| Base point formula | Without correct formula the scores are wrong | Medium | `(mandatory + best_elective) * 2`, cap 400 |
| Bonus point accumulation | Required by Hungarian admission rules | Medium | +50 emelt, +28 B2, +40 C1; cap 100 |
| Language certificate deduplication | Required rule — same language, higher cert wins | Low | Per-language dedup before summing |
| Validation chain with ordered rules | Deterministic errors; first violation stops processing | High | 5 rules in strict order (failed exam → global mandatory → programme mandatory → level → elective) |
| Descriptive domain error messages | Consumers must be able to diagnose which rule was violated | Medium | 422 with `{"error": "..."}` in Hungarian |
| List applicants endpoint | Consumers need to discover valid IDs before scoring | Low | `GET /api/v1/applicants` with programme details |
| 404 for unknown applicant | Standard REST expectation | Low | Laravel model binding handles this |
| API versioning | Future-proofs the contract | Low | `/api/v1` prefix |
| DB-backed programme requirements | Programmes must be configurable without code changes | High | Strategy pattern over `program_subjects` table |
| Global mandatory subject enforcement | Hungarian law: magyar, történelem, matematika always required | Medium | Checked before programme-specific rules |
| Programme mandatory subject enforcement | Each programme has its own required subject | Medium | Resolved from DB via ProgramRegistry |
| Advanced-level requirement enforcement | Some programmes (e.g. PPKE Anglisztika) require emelt level | Medium | Checked separately from subject presence |
| Elective subject requirement enforcement | At least one matching elective must be present | Low | After mandatory checks pass |
| Seed data for 4 acceptance cases | The homework spec defines exact expected outputs | Low | 2 programmes, 4 applicants covering success and error paths |
| JSON-only responses | REST API expectation | Low | No HTML, consistent envelope |

## Differentiators

Features that exceed the minimal spec and would add real value if this became a production system.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Multiple elective subject selection (best-of) | Automatically picks the highest-scoring elective so the applicant gets the best possible score | Low | Already in spec: `bestElective` selection within calculator |
| Separate score breakdown (alappont / tobbletpont) | Transparency — applicants can see exactly how their score was composed | Low | Already in spec response shape |
| Programme-level eager loading in list endpoint | Prevents N+1 on applicant list | Low | Standard Laravel eager load; already implied |
| Immutable Value Objects for domain logic | Type safety prevents silent calculation bugs | Medium | ExamResult, LanguageCertificate, Score VOs |
| Strategy pattern for programme requirements | Adding a new programme requires only a DB row, not a new class | Medium | Already in architecture; the single DatabaseProgramRequirements class handles all programmes |
| Exception hierarchy with meaningful subclasses | Precise error messages per rule violation | Low | 6 exception classes under AdmissionException |
| Acceptance tests derived from official spec | The 4 homework cases serve as regression guardrails | Low | Feature tests directly encode official expectations |

## Anti-Features

Features to explicitly NOT build. Including these would waste scope or introduce risk.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| Authentication / authorisation | Adds friction for a score calculator with no sensitive data ownership | Leave the API public; note it in docs if deployed |
| Applicant CRUD endpoints | Data is seeded from spec; dynamic creation is out of scope | Keep data static via seeders |
| Programme CRUD endpoints | Programmes are spec-defined; admin UI adds complexity with no benefit for this use case | Seed programmes at migration time |
| Pagination on applicant list | Only 4 seeded applicants; pagination adds client complexity for no benefit | Return all applicants in one response |
| Internationalisation of error messages | Error messages are specified in Hungarian; i18n machinery is overhead | Hungarian-only messages, hardcoded in exception constructors |
| Frontend / UI | API-only project; UI is a separate concern | Expose clean JSON; let consumers build their own UI |
| Score caching / memoisation | Input data is static (seeded); caching adds complexity without measurable gain | Calculate on each request; SQLite is fast enough |
| Bulk score calculation endpoint | Not in spec; premature API surface | Add only when a real consumer requests it |
| Per-programme exception classes | Would explode class count as programmes grow | Single DatabaseProgramRequirements handles all programmes via DB |
| Raw DB queries bypassing Eloquent | Breaks type safety, defeats Value Object mapping | Use Eloquent relationships throughout |

## Feature Dependencies

```
List applicants
  └── Applicant model with eager-loaded Programme

Score calculation
  ├── ProgramRegistry.findByApplicant()
  │   └── DatabaseProgramRequirements (needs program_subjects loaded)
  ├── VO mapping (ExamResult, LanguageCertificate)
  │   └── ExamResult constructor → throws FailedExamException if < 20%
  ├── Validation chain (ordered)
  │   ├── [1] FailedExamException (thrown during VO creation)
  │   ├── [2] MissingGlobalMandatorySubjectException
  │   ├── [3] MissingProgramMandatorySubjectException
  │   ├── [4] ProgramMandatorySubjectLevelException
  │   └── [5] MissingElectiveSubjectException
  ├── BasePointCalculator
  │   ├── Requires: mandatory ExamResult (from programme requirements)
  │   └── Requires: best elective ExamResult (selected from available results)
  └── BonusPointCalculator
      ├── Requires: all ExamResult VOs (to find emelt exams)
      └── Requires: all LanguageCertificate VOs (with dedup logic)

Score response
  └── ApplicantScoreResource wrapping Score VO (total, base, bonus)
```

## MVP Recommendation

This project is already a fully-specified homework exercise — the MVP is the complete spec as written. There is no phased delivery; all table stakes features must be present for the 4 acceptance test cases to pass.

**Prioritise in this implementation order (mirrors IMPLEMENTATION.md TDD sequence):**

1. Enums and exceptions (no business logic, no tests needed)
2. Value Objects with unit tests (ExamResult, LanguageCertificate, Score)
3. Database schema + models + factories (infrastructure)
4. Seed data (ProgramSeeder + ApplicantSeeder)
5. DatabaseProgramRequirements + ProgramRegistry with unit tests
6. BasePointCalculator + BonusPointCalculator with unit tests
7. AdmissionScoringService with unit tests (mock calculators)
8. API layer: controller, resource, routes, bootstrap/app.php
9. Feature tests: 4 acceptance cases + 404

**Defer to never:**
- Everything in the Anti-Features table

## Sources

- PRD.md (primary): Hungarian University Admission Score Calculator API v1.0, 2026-02-25
- IMPLEMENTATION.md (primary): Implementációs terv – DB-alapú Pontszámító Kalkulátor API
- PROJECT.md: project scope and key decisions
- Confidence: HIGH — all findings derived from first-party specification documents, not web research
