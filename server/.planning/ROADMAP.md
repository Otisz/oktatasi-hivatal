# Roadmap: Hungarian University Admission Score Calculator API

## Overview

Build a bottom-up TDD implementation of a read-only Laravel 12 REST API that calculates Hungarian university admission scores. The dependency graph is fully deterministic: enums and exceptions first, then Value Objects with unit tests, then the database schema and seeders, then the Strategy pattern and calculators, then the orchestrating service, and finally the API layer and acceptance tests. No phase can be inverted without forcing rewrites.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Domain Primitives** - Enums and exception hierarchy — zero-dependency foundation for all subsequent layers (completed 2026-02-25)
- [x] **Phase 2: Value Objects** - Immutable ExamResult, LanguageCertificate, Score VOs with full unit test coverage (completed 2026-02-26)
- [x] **Phase 3: Database Schema and Models** - Five migrations, five Eloquent models with typed relationships, factories (completed 2026-02-26)
- [x] **Phase 4: Seed Data** - Two programmes and four acceptance-test applicants seeded in correct FK order (completed 2026-02-26)
- [x] **Phase 5: Strategy Pattern** - ProgramRequirementsInterface, DatabaseProgramRequirements, ProgramRegistry with unit tests (completed 2026-02-26)
- [x] **Phase 6: Calculators** - BasePointCalculator and BonusPointCalculator with unit tests for all formula and edge cases (completed 2026-02-28)
- [x] **Phase 7: Scoring Service** - AdmissionScoringService orchestrating VO mapping, validation chain, and calculators (completed 2026-02-28)
- [ ] **Phase 8: API Layer** - Controller, resource, routes, exception renderer, service provider wiring, acceptance tests

## Phase Details

### Phase 1: Domain Primitives
**Goal**: The type-safe constants and exception hierarchy that every subsequent layer imports exist and are correct
**Depends on**: Nothing (first phase)
**Requirements**: DOM-01, DOM-02, DOM-03, DOM-07
**Success Criteria** (what must be TRUE):
  1. SubjectName, ExamLevel, and LanguageCertificateType enums exist with correct Hungarian string values and are importable from downstream code
  2. AdmissionException abstract base class and all six typed subclasses compile and carry the correct Hungarian error messages
  3. LanguageCertificateType::B2->points() returns 28 and LanguageCertificateType::C1->points() returns 40
  4. Pint and PHPStan pass with no errors on all new files
**Plans**: 2 plans
  - [x] 01-01-PLAN.md — Create three backed string enums (SubjectName, ExamLevel, LanguageCertificateType)
  - [ ] 01-02-PLAN.md — Create AdmissionException abstract base and six typed subclasses

### Phase 2: Value Objects
**Goal**: Immutable Value Objects with embedded validation exist and are fully unit-tested
**Depends on**: Phase 1
**Requirements**: DOM-04, DOM-05, DOM-06, TEST-01, TEST-02, TEST-03
**Success Criteria** (what must be TRUE):
  1. Constructing ExamResult with percentage < 20 throws FailedExamException
  2. ExamResult::points() returns the percentage value; isAdvancedLevel() returns true only for ExamLevel::Emelt
  3. LanguageCertificate::points() returns the correct value per type; language() returns the language string
  4. Score::total() equals basePoints + bonusPoints; basePoints() and bonusPoints() getters return stored values
  5. All three unit test files pass (ExamResultTest, LanguageCertificateTest, ScoreTest)
**Plans**: 2 plans
  - [ ] 02-01-PLAN.md — TDD: ExamResult VO with two-stage validation + ExamResultTest
  - [ ] 02-02-PLAN.md — TDD: LanguageCertificate and Score VOs + LanguageCertificateTest and ScoreTest

### Phase 3: Database Schema and Models
**Goal**: The five-table database schema is migrated and Eloquent models with typed relationships are ready for seeding and querying
**Depends on**: Phase 1
**Requirements**: DB-01, DB-02, DB-03, DB-04, DB-05, DB-06, DB-07
**Success Criteria** (what must be TRUE):
  1. php artisan migrate:fresh runs without error and creates all five tables with correct columns
  2. Program hasMany ProgramSubject; Applicant belongsTo Program; Applicant hasMany ApplicantExamResult and ApplicantBonusPoint — all relationships resolve without lazy-loading violations
  3. Factories for Applicant, ApplicantExamResult, and ApplicantBonusPoint create valid records
  4. Pint and PHPStan pass on all migration and model files
**Plans**: TBD

### Phase 4: Seed Data
**Goal**: Two programmes and four test applicants covering all acceptance cases are seeded in correct foreign-key order
**Depends on**: Phase 3
**Requirements**: SEED-01, SEED-02, SEED-03, SEED-04, SEED-05, SEED-06, SEED-07
**Success Criteria** (what must be TRUE):
  1. php artisan migrate:fresh --seed completes without error
  2. ELTE IK programme exists with matematika as mandatory and biologia/fizika/informatika/kemia as electives
  3. PPKE BTK Anglisztika programme exists with angol nyelv at emelt level as mandatory
  4. Four applicants exist with IDs 1-4 carrying the exact exam results and bonus points from the homework specification
**Plans**: 1 plan
  - [ ] 04-01-PLAN.md — Create ProgramSeeder, ApplicantSeeder, and update DatabaseSeeder with exact homework data

### Phase 5: Strategy Pattern
**Goal**: ProgramRequirementsInterface, DatabaseProgramRequirements, and ProgramRegistry exist and are unit-tested against mock models
**Depends on**: Phase 3
**Requirements**: DOM-08, BIZ-01, BIZ-02, TEST-04, TEST-05
**Success Criteria** (what must be TRUE):
  1. DatabaseProgramRequirements::getMandatorySubject() returns the correct SubjectName for a mocked Program
  2. DatabaseProgramRequirements::getElectiveSubjects() returns the correct SubjectName array for a mocked Program
  3. DatabaseProgramRequirements::getMandatorySubjectLevel() returns the correct ExamLevel or null
  4. ProgramRegistry::findByApplicant() returns a DatabaseProgramRequirements instance for a mock applicant
  5. All unit tests for Phase 5 pass without database access (mock models only)
**Plans**: TBD

### Phase 6: Calculators
**Goal**: BasePointCalculator and BonusPointCalculator are implemented and fully unit-tested including all edge cases
**Depends on**: Phase 2
**Requirements**: BIZ-03, BIZ-04, TEST-06, TEST-07
**Success Criteria** (what must be TRUE):
  1. BasePointCalculator returns (mandatory->points() + bestElective->points()) * 2, capped at 400
  2. BonusPointCalculator adds 50 per emelt-level exam result
  3. BonusPointCalculator deduplicates language certificates per language (not per type), taking the higher-scoring certificate
  4. BonusPointCalculator caps total bonus points at 100
  5. All calculator unit tests pass
**Plans**: 1 plan
  - [ ] 06-01-PLAN.md — TDD BasePointCalculator and BonusPointCalculator with full unit test coverage

### Phase 7: Scoring Service
**Goal**: AdmissionScoringService orchestrates VO mapping, the five-step ordered validation chain, and score calculation — fully unit-tested with mocks
**Depends on**: Phase 5, Phase 6
**Requirements**: BIZ-05, VAL-01, VAL-02, VAL-03, VAL-04, VAL-05, TEST-08
**Success Criteria** (what must be TRUE):
  1. VO mapping occurs as the first action, so FailedExamException is thrown before any explicit validation step
  2. MissingGlobalMandatorySubjectException is thrown when magyar/tortenelem/matematika are absent
  3. MissingProgramMandatorySubjectException, ProgramMandatorySubjectLevelException, and MissingElectiveSubjectException are thrown in steps 3-5 respectively
  4. calculateForApplicant() returns a Score VO with correct basePoints and bonusPoints when all validation passes
  5. AdmissionScoringServiceTest passes with all exception paths and the success path covered
**Plans**: 2 plans
  - [x] 07-01-PLAN.md — TDD AdmissionScoringService with 5-step validation chain and calculator delegation
  - [ ] 07-02-PLAN.md — Gap closure: register interface-to-concrete singleton bindings in AppServiceProvider

### Phase 8: API Layer
**Goal**: Two HTTP endpoints are live, exception rendering is wired, and all four acceptance cases plus the 404 case pass in feature tests
**Depends on**: Phase 4, Phase 7
**Requirements**: API-01, API-02, API-03, API-04, API-05, TEST-09, TEST-10, TEST-11, TEST-12, TEST-13
**Success Criteria** (what must be TRUE):
  1. GET /api/v1/applicants returns all four seeded applicants with programme details in the correct JSON shape
  2. GET /api/v1/applicants/1/score returns { data: { osszpontszam: 470, alappont: 370, tobbletpont: 100 } }
  3. GET /api/v1/applicants/2/score returns { data: { osszpontszam: 476, alappont: 376, tobbletpont: 100 } }
  4. GET /api/v1/applicants/3/score returns 422 with Hungarian error message for missing global mandatory subjects
  5. GET /api/v1/applicants/4/score returns 422 with Hungarian error message for magyar 15% below 20%
  6. GET /api/v1/applicants/999/score returns 404
**Plans**: 2 plans
  - [ ] 08-01-PLAN.md — Create API routes, controller, resources, and exception rendering in bootstrap/app.php
  - [ ] 08-02-PLAN.md — Feature tests for all 5 acceptance cases (scores, errors, 404)

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Domain Primitives | 2/2 | Complete   | 2026-02-25 |
| 2. Value Objects | 2/2 | Complete   | 2026-02-26 |
| 3. Database Schema and Models | 2/2 | Complete   | 2026-02-26 |
| 4. Seed Data | 1/1 | Complete   | 2026-02-26 |
| 5. Strategy Pattern | 1/1 | Complete   | 2026-02-26 |
| 6. Calculators | 1/1 | Complete   | 2026-02-28 |
| 7. Scoring Service | 2/2 | Complete   | 2026-02-28 |
| 8. API Layer | 1/2 | In Progress|  |
