# Requirements: Hungarian University Admission Score Calculator API

**Defined:** 2026-02-25
**Core Value:** Correct, rule-compliant admission score calculation — the scoring engine must enforce all Hungarian admission rules in the right order and produce exact expected results for every test case.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Domain Model

- [x] **DOM-01**: SubjectName enum defines all 13 matriculation subjects with Hungarian string values
- [x] **DOM-02**: ExamLevel enum defines kozep and emelt levels
- [x] **DOM-03**: LanguageCertificateType enum defines B2 (28 pts) and C1 (40 pts) with points() method
- [x] **DOM-04**: ExamResult VO validates percentage 0-100, throws FailedExamException if < 20%, exposes points() and isAdvancedLevel()
- [x] **DOM-05**: LanguageCertificate VO encapsulates certificate type and language, exposes points() and language()
- [x] **DOM-06**: Score VO stores basePoints and bonusPoints immutably, exposes total()
- [x] **DOM-07**: AdmissionException abstract base class with 6 typed subclasses (FailedExam, MissingGlobalMandatory, MissingProgramMandatory, ProgramMandatoryLevel, MissingElective, UnknownProgram)
- [ ] **DOM-08**: ProgramRequirementsInterface contract with getMandatorySubject(), getElectiveSubjects(), getMandatorySubjectLevel()

### Database

- [x] **DB-01**: Programs table migration (id, university, faculty, name)
- [x] **DB-02**: ProgramSubjects table migration (id, program_id FK, subject_name, requirement_type, required_level nullable)
- [x] **DB-03**: Applicants table migration (id, program_id FK)
- [x] **DB-04**: ApplicantExamResults table migration (id, applicant_id FK, subject_name, level, percentage)
- [x] **DB-05**: ApplicantBonusPoints table migration (id, applicant_id FK, category, type, language nullable)
- [ ] **DB-06**: Eloquent models for all 5 tables with typed relationships and eager loading support
- [ ] **DB-07**: Factories for Applicant, ApplicantExamResult, ApplicantBonusPoint

### Seed Data

- [ ] **SEED-01**: ProgramSeeder creates ELTE IK Programtervezo informatikus (mandatory: matematika, electives: biologia/fizika/informatika/kemia)
- [ ] **SEED-02**: ProgramSeeder creates PPKE BTK Anglisztika (mandatory: angol nyelv emelt, electives: francia/nemet/olasz/orosz/spanyol/tortenelem)
- [ ] **SEED-03**: ApplicantSeeder creates Applicant 1 (ELTE IK, expected score: 470)
- [ ] **SEED-04**: ApplicantSeeder creates Applicant 2 (ELTE IK + fizika, expected score: 476)
- [ ] **SEED-05**: ApplicantSeeder creates Applicant 3 (ELTE IK, missing magyar + tortenelem, expected: error)
- [ ] **SEED-06**: ApplicantSeeder creates Applicant 4 (ELTE IK, magyar 15%, expected: error)
- [ ] **SEED-07**: DatabaseSeeder calls ProgramSeeder then ApplicantSeeder in correct FK order

### Business Logic

- [ ] **BIZ-01**: DatabaseProgramRequirements implements ProgramRequirementsInterface using Program model's eager-loaded subjects
- [ ] **BIZ-02**: ProgramRegistry resolves ProgramRequirementsInterface for an Applicant via eager-loaded program.subjects
- [ ] **BIZ-03**: BasePointCalculator computes (mandatory + best_elective) x 2, max 400
- [ ] **BIZ-04**: BonusPointCalculator accumulates emelt exam (+50 each) and language cert points with same-language dedup, caps at 100
- [ ] **BIZ-05**: AdmissionScoringService maps Eloquent rows to VOs first (triggering step-1 validation), then runs ordered validation chain (steps 2-5), then delegates to calculators, returns Score VO

### Validation

- [ ] **VAL-01**: Step 1 — Any exam < 20% throws FailedExamException (enforced by ExamResult constructor during VO mapping)
- [ ] **VAL-02**: Step 2 — Missing magyar/tortenelem/matematika throws MissingGlobalMandatorySubjectException
- [ ] **VAL-03**: Step 3 — Missing programme mandatory subject throws MissingProgramMandatorySubjectException
- [ ] **VAL-04**: Step 4 — Programme mandatory subject at wrong level throws ProgramMandatorySubjectLevelException
- [ ] **VAL-05**: Step 5 — No matching elective subject throws MissingElectiveSubjectException

### API

- [ ] **API-01**: GET /api/v1/applicants returns list of applicants with programme details (university, faculty, name)
- [ ] **API-02**: GET /api/v1/applicants/{applicant}/score returns { data: { osszpontszam, alappont, tobbletpont } } on success (200)
- [ ] **API-03**: AdmissionException subclasses render as 422 JSON { error: "<Hungarian message>" } via bootstrap/app.php
- [ ] **API-04**: Unknown applicant returns 404 (Laravel default model binding)
- [ ] **API-05**: ProgramRegistry bound as singleton in AppServiceProvider

### Testing

- [x] **TEST-01**: Unit tests for ExamResult (constructor validation, points(), isAdvancedLevel(), FailedExamException)
- [x] **TEST-02**: Unit tests for LanguageCertificate (points() B2/C1, language() getter)
- [x] **TEST-03**: Unit tests for Score (total() calculation, getters)
- [ ] **TEST-04**: Unit tests for DatabaseProgramRequirements (mock Program model, mandatory/elective/level queries)
- [ ] **TEST-05**: Unit tests for ProgramRegistry (mock Applicant/Program, correct resolution)
- [ ] **TEST-06**: Unit tests for BasePointCalculator (formula, boundary cases)
- [ ] **TEST-07**: Unit tests for BonusPointCalculator (emelt points, language certs, dedup, cap at 100)
- [ ] **TEST-08**: Unit tests for AdmissionScoringService (all exception paths, correct orchestration with mocks)
- [ ] **TEST-09**: Feature test Case 1 — Applicant 1 scores 470 (370 base + 100 bonus)
- [ ] **TEST-10**: Feature test Case 2 — Applicant 2 scores 476 (376 base + 100 bonus)
- [ ] **TEST-11**: Feature test Case 3 — Applicant 3 returns 422 (missing global mandatory subjects)
- [ ] **TEST-12**: Feature test Case 4 — Applicant 4 returns 422 (magyar 15% < 20%)
- [ ] **TEST-13**: Feature test — Unknown applicant returns 404

## v2 Requirements

None — this is a complete homework exercise with no planned future scope.

## Out of Scope

| Feature | Reason |
|---------|--------|
| Authentication / authorization | No sensitive data ownership; API is public |
| Applicant CRUD endpoints | Data is seeded from spec, not user-managed |
| Programme CRUD endpoints | Programmes defined by spec; admin UI unnecessary |
| Pagination on applicant list | Only 4 seeded applicants; adds client complexity for no benefit |
| Internationalisation of error messages | Messages are specified in Hungarian; i18n is overhead |
| Frontend / UI | API-only project per spec |
| Score caching | Static seeded data; SQLite is fast enough |
| Bulk score calculation | Not in spec; premature API surface |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| DOM-01 | Phase 1 | Pending |
| DOM-02 | Phase 1 | Pending |
| DOM-03 | Phase 1 | Pending |
| DOM-04 | Phase 2 | Complete |
| DOM-05 | Phase 2 | Complete |
| DOM-06 | Phase 2 | Complete |
| DOM-07 | Phase 1 | Complete |
| DOM-08 | Phase 5 | Pending |
| DB-01 | Phase 3 | Complete |
| DB-02 | Phase 3 | Complete |
| DB-03 | Phase 3 | Complete |
| DB-04 | Phase 3 | Complete |
| DB-05 | Phase 3 | Complete |
| DB-06 | Phase 3 | Pending |
| DB-07 | Phase 3 | Pending |
| SEED-01 | Phase 4 | Pending |
| SEED-02 | Phase 4 | Pending |
| SEED-03 | Phase 4 | Pending |
| SEED-04 | Phase 4 | Pending |
| SEED-05 | Phase 4 | Pending |
| SEED-06 | Phase 4 | Pending |
| SEED-07 | Phase 4 | Pending |
| BIZ-01 | Phase 5 | Pending |
| BIZ-02 | Phase 5 | Pending |
| BIZ-03 | Phase 6 | Pending |
| BIZ-04 | Phase 6 | Pending |
| BIZ-05 | Phase 7 | Pending |
| VAL-01 | Phase 7 | Pending |
| VAL-02 | Phase 7 | Pending |
| VAL-03 | Phase 7 | Pending |
| VAL-04 | Phase 7 | Pending |
| VAL-05 | Phase 7 | Pending |
| API-01 | Phase 8 | Pending |
| API-02 | Phase 8 | Pending |
| API-03 | Phase 8 | Pending |
| API-04 | Phase 8 | Pending |
| API-05 | Phase 8 | Pending |
| TEST-01 | Phase 2 | Complete |
| TEST-02 | Phase 2 | Complete |
| TEST-03 | Phase 2 | Complete |
| TEST-04 | Phase 5 | Pending |
| TEST-05 | Phase 5 | Pending |
| TEST-06 | Phase 6 | Pending |
| TEST-07 | Phase 6 | Pending |
| TEST-08 | Phase 7 | Pending |
| TEST-09 | Phase 8 | Pending |
| TEST-10 | Phase 8 | Pending |
| TEST-11 | Phase 8 | Pending |
| TEST-12 | Phase 8 | Pending |
| TEST-13 | Phase 8 | Pending |

**Coverage:**
- v1 requirements: 45 total
- Mapped to phases: 45
- Unmapped: 0

---
*Requirements defined: 2026-02-25*
*Last updated: 2026-02-25 after roadmap creation — all 45 requirements mapped*
