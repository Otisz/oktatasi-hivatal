# Phase 7: Scoring Service - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning

<domain>
## Phase Boundary

AdmissionScoringService orchestrates VO mapping, the five-step ordered validation chain, and score calculation. It connects all previously built components (VOs, exceptions, ProgramRegistry, calculators) into a single service that accepts an Applicant and returns a Score VO or throws the appropriate AdmissionException. API layer and controller are Phase 8.

</domain>

<decisions>
## Implementation Decisions

### Validation behavior
- Fail-fast: stop at the first validation failure per the ordered chain
- Step order: 1 (VO mapping / <20% check) → 2 (global mandatory) → 3 (programme mandatory) → 4 (mandatory level) → 5 (elective presence)
- An applicant with multiple problems only sees the first error matching the chain order

### Exception constructors
- Existing exception signatures may be modified during implementation if the service orchestration reveals a better pattern (e.g., adding contextual data to constructors)
- Current pattern: FailedExamException carries subject+percentage; others have hardcoded messages

### Claude's Discretion
- **Error message detail level** — whether exception messages list specific missing subjects or use generic messages; whether level mismatches include expected vs actual
- **Exception data properties** — whether exceptions carry typed contextual data beyond the message string, guided by what Phase 8's API-03 needs (422 JSON with Hungarian message)
- **Step 1 encounter order** — when multiple exams fail <20%, which one triggers the exception first (deterministic sort vs insertion order)
- **Best elective selection** — highest-scoring exam from the elective list; whether mandatory subject can also count as elective; how to resolve ties
- **Mandatory exam lookup** — matching applicant exams by programme's getMandatorySubject() subject name
- **Bonus point input** — whether all ExamResult VOs or only a subset are passed to BonusPointCalculator
- **Test approach** — mock strategy (all three dependencies vs registry only), scenario coverage (all 5 error paths + happy path vs seeded cases), Pest datasets vs individual test methods, call order verification vs behavior-only assertions
- **Validation chain structure** — separate classes, private methods, or other patterns for organizing the 5 steps

</decisions>

<specifics>
## Specific Ideas

No specific requirements — user trusts Claude to make implementation decisions based on existing code patterns, seeded test data expectations (470, 476 scores; specific error cases), and the prescriptive requirements (BIZ-05, VAL-01 through VAL-05, TEST-08).

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `BasePointCalculator::calculate(ExamResult $mandatory, ExamResult $bestElective): int` — computes (mandatory + best_elective) x 2, max 400
- `BonusPointCalculator::calculate(array $examResults, array $certificates): int` — emelt +50 each, language cert dedup, cap 100
- `ProgramRegistry::findByApplicant(Applicant): ProgramRequirementsInterface` — resolves programme requirements
- `DatabaseProgramRequirements` — getMandatorySubject(), getElectiveSubjects(), getMandatorySubjectLevel()
- `ExamResult` VO — constructor auto-throws FailedExamException when percentage < 20 (step 1 validation is built-in)
- `LanguageCertificate` VO — encapsulates type and language, exposes points()
- `Score` VO — immutable, stores basePoints and bonusPoints, exposes total()
- 6 typed exceptions (FailedExam, MissingGlobalMandatory, MissingProgramMandatory, ProgramMandatoryLevel, MissingElective, UnknownProgram)

### Established Patterns
- Services in `app/Services/` as `final class` with constructor injection
- `declare(strict_types=1)` on all files
- PHPStan level 7, Pint with `final_class` and `strict_comparison` rules
- Pest 4 for tests, TDD approach used in prior phases
- Eloquent models prevent lazy loading — eager loading required

### Integration Points
- `Applicant` model with `examResults()`, `bonusPoints()`, `program()` relationships
- `ApplicantExamResult` casts subject_name to SubjectName enum, level to ExamLevel enum
- `ApplicantBonusPoint` casts type to LanguageCertificateType enum, has `language` and `category` columns
- Service will live in `app/Services/AdmissionScoringService.php`
- Test will live in `tests/Unit/Services/AdmissionScoringServiceTest.php`

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 07-scoring-service*
*Context gathered: 2026-02-28*
