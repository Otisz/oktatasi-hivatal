# Phase 7: Scoring Service - Research

**Researched:** 2026-02-28
**Domain:** Service orchestration, validation chain, VO mapping, Mockery unit testing
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **Validation behavior**: Fail-fast; stop at the first validation failure per the ordered chain
- **Step order**: 1 (VO mapping / <20% check) → 2 (global mandatory) → 3 (programme mandatory) → 4 (mandatory level) → 5 (elective presence)
- An applicant with multiple problems only sees the first error matching the chain order
- **Exception constructors**: Existing exception signatures may be modified during implementation if the service orchestration reveals a better pattern

### Claude's Discretion

- **Error message detail level** — whether exception messages list specific missing subjects or use generic messages; whether level mismatches include expected vs actual
- **Exception data properties** — whether exceptions carry typed contextual data beyond the message string, guided by what Phase 8's API-03 needs (422 JSON with Hungarian message)
- **Step 1 encounter order** — when multiple exams fail <20%, which one triggers the exception first (deterministic sort vs insertion order)
- **Best elective selection** — highest-scoring exam from the elective list; whether mandatory subject can also count as elective; how to resolve ties
- **Mandatory exam lookup** — matching applicant exams by programme's getMandatorySubject() subject name
- **Bonus point input** — whether all ExamResult VOs or only a subset are passed to BonusPointCalculator
- **Test approach** — mock strategy (all three dependencies vs registry only), scenario coverage (all 5 error paths + happy path vs seeded cases), Pest datasets vs individual test methods, call order verification vs behavior-only assertions
- **Validation chain structure** — separate classes, private methods, or other patterns for organizing the 5 steps

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BIZ-05 | AdmissionScoringService maps Eloquent rows to VOs first (triggering step-1 validation), then runs ordered validation chain (steps 2-5), then delegates to calculators, returns Score VO | VO mapping pattern, validation chain architecture, calculator delegation |
| VAL-01 | Step 1 — Any exam < 20% throws FailedExamException (enforced by ExamResult constructor during VO mapping) | ExamResult constructor auto-throws; no explicit validation needed |
| VAL-02 | Step 2 — Missing magyar/tortenelem/matematika throws MissingGlobalMandatorySubjectException | SubjectName::globallyMandatory() method available; check mapped ExamResult subjects |
| VAL-03 | Step 3 — Missing programme mandatory subject throws MissingProgramMandatorySubjectException | ProgramRequirementsInterface::getMandatorySubject(); filter mapped ExamResults by subject |
| VAL-04 | Step 4 — Programme mandatory subject at wrong level throws ProgramMandatorySubjectLevelException | getMandatorySubjectLevel() returns ?ExamLevel; check ExamResult::isAdvancedLevel() |
| VAL-05 | Step 5 — No matching elective subject throws MissingElectiveSubjectException | getElectiveSubjects() returns array<int, SubjectName>; find any match in mapped ExamResults |
| TEST-08 | Unit tests for AdmissionScoringService (all exception paths, correct orchestration with mocks) | Mockery 1.6 installed; three injectable dependencies to mock |
</phase_requirements>

## Summary

AdmissionScoringService is a pure orchestrator: it accepts an `Applicant` Eloquent model, maps its DB rows to typed VOs, runs the five-step validation chain in order, then delegates to `BasePointCalculator` and `BonusPointCalculator` to produce a `Score` VO. The service has no business logic of its own — all rules live in existing components. Its job is sequencing.

Step 1 validation is implicit: constructing `ExamResult` VOs from `ApplicantExamResult` rows automatically throws `FailedExamException` for any percentage below 20. Steps 2-5 are explicit checks against the mapped VO collection. The service never touches the DB directly; it reads already-eager-loaded relationships from the passed `Applicant`.

The test strategy requires Mockery (already installed at ^1.6) to mock all three collaborators (`ProgramRegistry`, `BasePointCalculator`, `BonusPointCalculator`) injected via constructor. Since `ExamResult` VOs cannot be mocked (final readonly class), test scenarios are assembled by constructing real model stubs with `setRelation()` and `setAttribute()` — matching the pattern already used in `DatabaseProgramRequirementsTest` and `ProgramRegistryTest`.

**Primary recommendation:** Implement `AdmissionScoringService` as a `final class` in `app/Services/` with constructor-injected `ProgramRegistry`, `BasePointCalculator`, and `BonusPointCalculator`; private methods for each validation step; all five exception paths plus the happy path covered in `AdmissionScoringServiceTest`.

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.5.2 | Language; `final readonly class`, promoted props | Project baseline |
| mockery/mockery | ^1.6 | Mock ProgramRegistry, BasePointCalculator, BonusPointCalculator | Already installed; Pest docs recommend it |
| pestphp/pest | ^4 | Test runner | Project standard |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Illuminate\Database\Eloquent\Model | Laravel 12 | Applicant model with eager-loaded relations | Already provided by test bootstrap |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Mockery | PHPUnit built-in mocks | PHPUnit mocks are more verbose; Mockery already installed and used |
| Constructor injection | Service locator | Constructor injection is the project pattern; service locator not used anywhere |

**Installation:** No new packages needed. Mockery ^1.6 is already in `composer.json` dev dependencies.

## Architecture Patterns

### Recommended Project Structure

```
app/Services/
├── AdmissionScoringService.php   # new — the orchestrator
├── BasePointCalculator.php       # existing
├── BonusPointCalculator.php      # existing
├── DatabaseProgramRequirements.php # existing
└── ProgramRegistry.php           # existing

tests/Unit/Services/
├── AdmissionScoringServiceTest.php  # new
├── BasePointCalculatorTest.php      # existing
├── BonusPointCalculatorTest.php     # existing
├── DatabaseProgramRequirementsTest.php # existing
└── ProgramRegistryTest.php          # existing
```

### Pattern 1: Constructor Injection with final class

All services in this project are `final class` (not `final readonly class` — calculators have no constructor props). `AdmissionScoringService` injects all three collaborators and has no state.

```php
// app/Services/AdmissionScoringService.php
declare(strict_types=1);

namespace App\Services;

use App\Exceptions\MissingElectiveSubjectException;
use App\Exceptions\MissingGlobalMandatorySubjectException;
use App\Exceptions\MissingProgramMandatorySubjectException;
use App\Exceptions\ProgramMandatorySubjectLevelException;
use App\Models\Applicant;
use App\ValueObjects\ExamResult;
use App\ValueObjects\LanguageCertificate;
use App\ValueObjects\Score;

final class AdmissionScoringService
{
    public function __construct(
        private ProgramRegistry $programRegistry,
        private BasePointCalculator $basePointCalculator,
        private BonusPointCalculator $bonusPointCalculator,
    ) {}

    public function calculateForApplicant(Applicant $applicant): Score { ... }

    // private validation step methods
}
```

### Pattern 2: VO Mapping from Eloquent Rows

The `Applicant` model has `examResults()` (HasMany to `ApplicantExamResult`) and `bonusPoints()` (HasMany to `ApplicantBonusPoint`). Both relationships must be eager-loaded before the service receives the model. The service maps them to typed VOs:

```php
// Step 1 is implicit — ExamResult constructor throws FailedExamException when percentage < 20
/** @var array<int, ExamResult> $examResults */
$examResults = $applicant->examResults
    ->map(fn (ApplicantExamResult $row): ExamResult =>
        new ExamResult($row->subject_name, $row->level, $row->percentage)
    )
    ->values()
    ->all();

/** @var array<int, LanguageCertificate> $certificates */
$certificates = $applicant->bonusPoints
    ->map(fn (ApplicantBonusPoint $row): LanguageCertificate =>
        new LanguageCertificate($row->type, $row->language)
    )
    ->values()
    ->all();
```

`ApplicantExamResult` casts `subject_name` to `SubjectName` and `level` to `ExamLevel`. `ApplicantBonusPoint` casts `type` to `LanguageCertificateType`. No manual casting is needed in the service.

### Pattern 3: Five-Step Validation Chain as Private Methods

Each validation step is a focused private method. The service calls them in order from `calculateForApplicant()`. Fail-fast: the first throw stops execution.

```php
private function validateGlobalMandatorySubjects(array $examResults): void
{
    $subjectNames = array_map(fn (ExamResult $r): SubjectName => $r->subject, $examResults);
    foreach (SubjectName::globallyMandatory() as $required) {
        if (! in_array($required, $subjectNames, true)) {
            throw new MissingGlobalMandatorySubjectException;
        }
    }
}

private function validateProgramMandatorySubject(array $examResults, ProgramRequirementsInterface $requirements): ExamResult
{
    $mandatorySubject = $requirements->getMandatorySubject();
    foreach ($examResults as $result) {
        if ($result->subject === $mandatorySubject) {
            return $result; // return for use in steps 4 and score calculation
        }
    }
    throw new MissingProgramMandatorySubjectException($mandatorySubject);
}

private function validateMandatoryLevel(ExamResult $mandatory, ProgramRequirementsInterface $requirements): void
{
    $requiredLevel = $requirements->getMandatorySubjectLevel();
    if ($requiredLevel !== null && ! $mandatory->isAdvancedLevel()) {
        throw new ProgramMandatorySubjectLevelException($mandatory->subject, $requiredLevel);
    }
}

private function findBestElective(array $examResults, ProgramRequirementsInterface $requirements): ExamResult
{
    $electiveSubjects = $requirements->getElectiveSubjects();
    $best = null;
    foreach ($examResults as $result) {
        if (in_array($result->subject, $electiveSubjects, true)) {
            if ($best === null || $result->points() > $best->points()) {
                $best = $result;
            }
        }
    }
    if ($best === null) {
        throw new MissingElectiveSubjectException;
    }
    return $best;
}
```

### Pattern 4: Mockery Unit Test with Model Stubs

Following the same approach as `DatabaseProgramRequirementsTest` (uses `setRelation()` and `setAttribute()`) and `ProgramRegistryTest` (builds unsaved model instances). For `AdmissionScoringServiceTest`, mock the three injected services; build `Applicant` stubs with `setRelation()` to attach `ApplicantExamResult` and `ApplicantBonusPoint` collections.

```php
use App\Contracts\ProgramRequirementsInterface;
use App\Services\AdmissionScoringService;
use App\Services\BasePointCalculator;
use App\Services\BonusPointCalculator;
use App\Services\ProgramRegistry;
use Mockery;

it('returns a Score VO on the happy path', function (): void {
    $requirements = Mockery::mock(ProgramRequirementsInterface::class);
    $requirements->shouldReceive('getMandatorySubject')->andReturn(SubjectName::Mathematics);
    $requirements->shouldReceive('getMandatorySubjectLevel')->andReturn(null);
    $requirements->shouldReceive('getElectiveSubjects')->andReturn([SubjectName::Informatics]);

    $registry = Mockery::mock(ProgramRegistry::class);
    $registry->shouldReceive('findByApplicant')->once()->andReturn($requirements);

    $baseCalc = Mockery::mock(BasePointCalculator::class);
    $baseCalc->shouldReceive('calculate')->once()->andReturn(370);

    $bonusCalc = Mockery::mock(BonusPointCalculator::class);
    $bonusCalc->shouldReceive('calculate')->once()->andReturn(100);

    $applicant = buildApplicantStub([...]);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);
    $score = $service->calculateForApplicant($applicant);

    expect($score->basePoints)->toBe(370)
        ->and($score->bonusPoints)->toBe(100);
});
```

### Anti-Patterns to Avoid

- **Lazy loading in service**: The service accesses `$applicant->examResults` and `$applicant->bonusPoints` as already-loaded collections — never call `->load()` inside the service. Eager loading is the caller's responsibility (controller in Phase 8).
- **Re-checking step 1 explicitly**: Do not add an explicit `< 20%` check in the service. `ExamResult` constructor handles it. Adding a second check would create two code paths for the same rule.
- **Using `first()` on the mapped array to find mandatory exam**: Use a foreach loop with strict `===` comparison on enum instances (project decision: closure-based filtering over `firstWhere` to avoid loose equality edge cases — see STATE.md decision [05-01]).
- **Passing only the mandatory ExamResult to BonusPointCalculator**: BonusPointCalculator needs all exam results to count emelt bonuses. Pass the full `$examResults` array.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Mock collaborators | Custom test doubles | Mockery::mock() with shouldReceive/andReturn | Mockery already installed; correct teardown handled automatically |
| Validate enum membership | String comparison | Strict `in_array($enum, $array, true)` or `===` | Enum instances compare by identity; loose comparison breaks |
| Global mandatory check | Hardcoded subject list | SubjectName::globallyMandatory() | Centralised in enum; already exists |

**Key insight:** The service has almost no novel logic. It assembles existing pieces. The risk is in the assembly: wrong order, wrong VO construction, wrong argument passing.

## Common Pitfalls

### Pitfall 1: Accessing Uncast Attributes on Unsaved Model Stubs

**What goes wrong:** In tests, `ApplicantExamResult` is instantiated with `new ApplicantExamResult` and attributes set via `setAttribute()`. When the service reads `$row->subject_name`, the cast to `SubjectName` enum runs correctly only if `setAttribute` is used (not setting the property directly). `getAttribute` triggers casting; direct property access does not.

**Why it happens:** Eloquent casts only fire through `getAttribute()` / `setAttribute()`. Bypassing the accessor breaks the cast.

**How to avoid:** Always use `$model->setAttribute('subject_name', SubjectName::Mathematics)` in tests. The existing `DatabaseProgramRequirementsTest` already demonstrates this pattern.

**Warning signs:** PHPStan errors about `mixed` vs `SubjectName`, or `in_array` comparisons always returning false.

### Pitfall 2: Step 1 Not Actually Being "Step 1" in Ordering

**What goes wrong:** If the service first resolves program requirements (which can throw `UnknownProgramException`) before mapping VOs, a `FailedExamException` that should have fired first (step 1) will not appear.

**Why it happens:** Developer resolves the registry early to use requirements in the VO mapping loop, inadvertently changing the exception priority.

**How to avoid:** Map ALL exam results to VOs first (triggering step 1), then call `$programRegistry->findByApplicant()` for steps 3-5. The CONTEXT.md decision locks this order: VO mapping occurs as the first action.

**Warning signs:** Success Criterion 1 fails — test expects `FailedExamException` but receives `UnknownProgramException`.

### Pitfall 3: Best Elective Ties

**What goes wrong:** When two elective exams have identical `points()`, array iteration order determines which is "best". This is non-deterministic across PHP versions or reordered input.

**Why it happens:** No tie-breaking rule was specified.

**How to avoid:** First-encountered wins (use strict `>` not `>=` when comparing, so ties keep the current best). Document this as the chosen rule in a PHPDoc comment. The test should not create tie scenarios unless testing that exact rule.

### Pitfall 4: final class vs final readonly class for Service

**What goes wrong:** Making the service `final readonly class` forces all constructor params to be promoted properties with `public`/`protected`/`private`, and the class cannot be mocked by Mockery's default proxy approach (readonly prevents writing mock properties).

**Why it happens:** Confusion between calculator pattern (no props, no readonly) and VO pattern (all props, readonly).

**How to avoid:** Use `final class` (not `final readonly class`) — matching `BasePointCalculator` and `BonusPointCalculator`. STATE.md documents: "[06-01]: BasePointCalculator/BonusPointCalculator are final class (not readonly) — no constructor properties; no-arg constructors prohibited by project rules." The AdmissionScoringService has constructor properties but should still be `final class` to remain mockable.

**Note:** Constructor properties in `final class` ARE allowed; they just don't carry the `readonly` class modifier. Individual properties can be declared `private readonly` inside a `final class`.

### Pitfall 5: Empty Constructor Prohibition

**What goes wrong:** PHPStan + Pint rules prohibit empty `__construct()` with zero parameters. `AdmissionScoringService` will have three parameters — this is fine. The prohibition only applies to no-arg empty constructors.

**Why it happens:** Project code style rule from CLAUDE.md.

**How to avoid:** The three-dependency constructor satisfies this rule automatically.

## Code Examples

Verified patterns from existing codebase:

### Full calculateForApplicant() Skeleton

```php
public function calculateForApplicant(Applicant $applicant): Score
{
    // Step 1: Map Eloquent rows to VOs — ExamResult constructor throws FailedExamException if < 20%
    /** @var array<int, ExamResult> $examResults */
    $examResults = $applicant->examResults
        ->map(fn (ApplicantExamResult $row): ExamResult =>
            new ExamResult($row->subject_name, $row->level, $row->percentage)
        )
        ->values()
        ->all();

    /** @var array<int, LanguageCertificate> $certificates */
    $certificates = $applicant->bonusPoints
        ->map(fn (ApplicantBonusPoint $row): LanguageCertificate =>
            new LanguageCertificate($row->type, $row->language)
        )
        ->values()
        ->all();

    // Step 2: Global mandatory check
    $this->validateGlobalMandatorySubjects($examResults);

    // Resolve program requirements (after step 1 to preserve exception priority)
    $requirements = $this->programRegistry->findByApplicant($applicant);

    // Step 3: Programme mandatory subject present
    $mandatoryResult = $this->validateProgramMandatorySubject($examResults, $requirements);

    // Step 4: Programme mandatory subject level
    $this->validateMandatoryLevel($mandatoryResult, $requirements);

    // Step 5: Elective subject present
    $bestElective = $this->findBestElective($examResults, $requirements);

    // Calculate score
    $basePoints = $this->basePointCalculator->calculate($mandatoryResult, $bestElective);
    $bonusPoints = $this->bonusPointCalculator->calculate($examResults, $certificates);

    return new Score($basePoints, $bonusPoints);
}
```

### Model Stub Helper for Tests

Follows the `setRelation()` + `setAttribute()` pattern from `DatabaseProgramRequirementsTest`:

```php
function makeExamResultRow(SubjectName $subject, ExamLevel $level, int $percentage): ApplicantExamResult
{
    $row = new ApplicantExamResult;
    $row->setAttribute('subject_name', $subject);
    $row->setAttribute('level', $level);
    $row->setAttribute('percentage', $percentage);
    return $row;
}

function makeBonusPointRow(LanguageCertificateType $type, string $language): ApplicantBonusPoint
{
    $row = new ApplicantBonusPoint;
    $row->setAttribute('type', $type);
    $row->setAttribute('language', $language);
    return $row;
}

function makeApplicantWithExams(array $examRows, array $bonusRows = []): Applicant
{
    $applicant = new Applicant;
    $applicant->setRelation('examResults', Collection::make($examRows));
    $applicant->setRelation('bonusPoints', Collection::make($bonusRows));
    return $applicant;
}
```

### Mockery teardown (automatic with Pest)

Pest + Mockery automatically calls `Mockery::close()` after each test when using the default PHPUnit integration. No explicit `afterEach` needed.

### Exception path test example

```php
it('throws FailedExamException when any exam is below 20%', function (): void {
    $registry = Mockery::mock(ProgramRegistry::class);
    $baseCalc = Mockery::mock(BasePointCalculator::class);
    $bonusCalc = Mockery::mock(BonusPointCalculator::class);

    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 15),
        // FailedExamException thrown during ExamResult construction above
    ]);

    $service = new AdmissionScoringService($registry, $baseCalc, $bonusCalc);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(FailedExamException::class);
});
```

Note: Because `ExamResult` construction itself throws (before `findByApplicant` is called), the registry mock will receive zero calls — Mockery's default `shouldReceive` with no call count constraint is fine. If you want to assert it was NOT called, use `$registry->shouldNotReceive('findByApplicant')`.

## Seeded Test Case Arithmetic (for test scenario design)

These are the concrete expected values from the seeder, useful for designing the happy path test:

**Applicant 1 — ELTE IK Programtervező:**
- Mandatory: Matematika 90% (emelt) → 90 pts
- Best elective: Informatika 95% → 95 pts
- Base: (90 + 95) × 2 = 370
- Bonus: emelt matematika (+50) + B2 angol (28) + C1 német (40) = 118 → capped at 100
- Total: 470

**Applicant 2 — ELTE IK Programtervező (+ fizika):**
- Mandatory: Matematika 90%
- Best elective: Fizika 98% (beats Informatika 95%)
- Base: (90 + 98) × 2 = 376
- Bonus: same as Applicant 1 → 100
- Total: 476

**Applicant 3 — Missing magyar and történelem:** Step 2 throws `MissingGlobalMandatorySubjectException`

**Applicant 4 — Magyar at 15%:** Step 1 throws `FailedExamException` (during VO mapping)

## Open Questions

1. **Best elective: can the mandatory subject also count as elective?**
   - What we know: The mandatory subject is found by subject name match. Electives are a separate list from `getElectiveSubjects()`.
   - What's unclear: If the programme's mandatory subject happens to also appear in the electives list, should it double-count?
   - Recommendation: In the two seeded programmes, the mandatory subject is never in the electives list. Implement without overlap handling; if a subject appears in both lists, allow it to also be the best elective. This is the simplest interpretation.

2. **What to pass as `$examResults` to `BonusPointCalculator`?**
   - What we know: `BonusPointCalculator::calculate(array $examResults, array $certificates)` needs all exam results to find emelt exams.
   - What's unclear: Should it receive all exam results, or only those that passed (all, because step 1 already filtered out failures).
   - Recommendation: Pass all `$examResults` (the entire mapped array). Step 1 guarantees every element is valid (any failure throws before reaching the bonus calculation).

## Validation Architecture

> `workflow.nyquist_validation` is not present in `.planning/config.json` — this section is included because the phase has explicit test requirements (TEST-08).

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 4 |
| Config file | `tests/Pest.php` (Unit tests extend PHPUnit TestCase directly, Feature tests extend `Tests\TestCase`) |
| Quick run command | `php artisan test --compact --filter=AdmissionScoringServiceTest` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| BIZ-05 | calculateForApplicant() returns Score VO with correct basePoints/bonusPoints | unit | `php artisan test --compact --filter="returns a Score"` | No — Wave 0 |
| VAL-01 | FailedExamException when exam < 20% (step 1) | unit | `php artisan test --compact --filter="FailedExamException"` | No — Wave 0 |
| VAL-02 | MissingGlobalMandatorySubjectException when magyar/tortenelem/matematika absent (step 2) | unit | `php artisan test --compact --filter="MissingGlobalMandatory"` | No — Wave 0 |
| VAL-03 | MissingProgramMandatorySubjectException when programme mandatory absent (step 3) | unit | `php artisan test --compact --filter="MissingProgramMandatory"` | No — Wave 0 |
| VAL-04 | ProgramMandatorySubjectLevelException when mandatory at wrong level (step 4) | unit | `php artisan test --compact --filter="ProgramMandatorySubjectLevel"` | No — Wave 0 |
| VAL-05 | MissingElectiveSubjectException when no elective present (step 5) | unit | `php artisan test --compact --filter="MissingElective"` | No — Wave 0 |
| TEST-08 | All exception paths + success path with mocks | unit | `php artisan test --compact --filter=AdmissionScoringServiceTest` | No — Wave 0 |

### Sampling Rate

- **Per task commit:** `php artisan test --compact --filter=AdmissionScoringServiceTest`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/Unit/Services/AdmissionScoringServiceTest.php` — covers all TEST-08 paths (created via `php artisan make:test --pest --unit Services/AdmissionScoringServiceTest`)
- [ ] `app/Services/AdmissionScoringService.php` — the service itself (created via `php artisan make:class`)

*(Existing test infrastructure — Pest 4, PHPUnit 12, Mockery 1.6, phpunit.xml — is complete. No framework install needed.)*

## Sources

### Primary (HIGH confidence)

- Direct codebase inspection: `app/Services/`, `app/ValueObjects/`, `app/Exceptions/`, `app/Models/`, `app/Enums/`, `tests/Unit/Services/` — all patterns verified
- `composer.json` — mockery/mockery ^1.6 confirmed installed
- Pest 4 mocking docs (via `search-docs`) — Mockery::mock(), shouldReceive(), andReturn() syntax confirmed

### Secondary (MEDIUM confidence)

- STATE.md accumulated decisions — especially [05-01] closure-based enum filtering, [06-01] final class not final readonly for services

### Tertiary (LOW confidence)

- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — confirmed from codebase and composer.json
- Architecture: HIGH — all patterns verified from existing sibling files
- Pitfalls: HIGH — derived from actual code constraints (readonly, Eloquent casting, exception ordering) verified in source

**Research date:** 2026-02-28
**Valid until:** Until Phase 7 implementation changes exception signatures or model relationships (stable)
