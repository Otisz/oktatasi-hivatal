# Phase 6: Calculators - Research

**Researched:** 2026-02-28
**Domain:** PHP domain service classes — pure calculation logic over Value Objects
**Confidence:** HIGH

## Summary

Phase 6 creates two pure calculation services: `BasePointCalculator` and `BonusPointCalculator`. Both operate exclusively on Value Objects (`ExamResult`, `LanguageCertificate`) that are already built and unit-tested from prior phases. There are no database dependencies, no Eloquent models, and no framework concerns in this phase — these are plain PHP final classes in `App\Services\`.

The scoring rules are fully specified in `IMPLEMENTATION.md` and `PRD.md` and are confirmed by the seeded data. The mathematical formulas are straightforward; the complexity is in the deduplication logic in `BonusPointCalculator` and ensuring boundary caps are applied correctly. Unit tests must exercise every branching path without touching the database.

The project's established pattern for services is `final readonly class` with constructor-injected dependencies where needed. These two calculators have no injected dependencies — they are stateless functions wrapped in classes. They accept typed Value Object arrays and return `int`.

**Primary recommendation:** Implement both calculators as `final` classes (not readonly — they take array parameters via methods, not constructor injection). Follow the `final` class convention enforced by Pint's `final_class` rule. Write tests using datasets for boundary values. No mocking required — both classes are pure.

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| BIZ-03 | BasePointCalculator computes (mandatory + best_elective) x 2, max 400 | Formula verified in PRD §7.1 and IMPLEMENTATION.md §4; boundary at 100%+100%=400 confirmed |
| BIZ-04 | BonusPointCalculator accumulates emelt exam (+50 each) and language cert points with same-language dedup, caps at 100 | Rules verified in PRD §7.2 and IMPLEMENTATION.md §4; dedup strategy is per-language, keep highest points |
| TEST-06 | Unit tests for BasePointCalculator (formula, boundary cases) | ExamResultTest pattern applies; dataset-driven tests for boundary values |
| TEST-07 | Unit tests for BonusPointCalculator (emelt points, language certs, dedup, cap at 100) | Established test style from ExamResultTest/LanguageCertificateTest; no mocking needed |
</phase_requirements>

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.5.2 | Language | Project requirement |
| Laravel Framework | v12 | No direct use in calculators — only project context | Project standard |
| Pest | v4 | Unit test runner | Project's test framework (see pest-testing skill) |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Laravel Pint | v1 | Code formatting after file edits | Always — run `vendor/bin/pint --dirty --format agent` after PHP changes |
| Larastan | v3 | Static analysis | Enforces PHPDoc shapes and type correctness |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Pure PHP array processing | Laravel Collection | Collections are fine but add a dependency; plain array functions (`max()`, `array_map()`, `array_filter()`, `usort()`) are sufficient and keep calculators free of framework coupling |

**Installation:** No new packages needed. All dependencies already present.

## Architecture Patterns

### Recommended Project Structure
```
app/
└── Services/
    ├── BasePointCalculator.php    # new — pure calculation, no deps
    ├── BonusPointCalculator.php   # new — pure calculation, no deps
    ├── DatabaseProgramRequirements.php
    └── ProgramRegistry.php

tests/Unit/
└── Services/
    ├── BasePointCalculatorTest.php    # new
    ├── BonusPointCalculatorTest.php   # new
    ├── DatabaseProgramRequirementsTest.php
    └── ProgramRegistryTest.php
```

### Pattern 1: Final stateless service class

**What:** A final class with no constructor properties and a single `calculate()` method. Takes typed Value Objects, returns `int`. No side effects.

**When to use:** When a class exists only to perform a computation. No injection, no state, no framework coupling.

**Example:**
```php
// Source: established project pattern from DatabaseProgramRequirements.php + IMPLEMENTATION.md §4
final class BasePointCalculator
{
    public function calculate(ExamResult $mandatory, ExamResult $bestElective): int
    {
        return min(($mandatory->points() + $bestElective->points()) * 2, 400);
    }
}
```

Note: Do NOT use `readonly` on this class — `readonly` requires all properties to be set in the constructor. A stateless service with only methods has no constructor properties to promote, so `readonly` is inapplicable. Use `final` only.

### Pattern 2: Best-elective selection via array max

**What:** Given `array<ExamResult>` of exam results filtered to elective subjects, find the one with the highest `points()` return value.

**When to use:** Inside `AdmissionScoringService` before calling `BasePointCalculator`. The calculator itself receives the pre-selected `$mandatory` and `$bestElective` — it does not perform selection internally.

**Key insight from IMPLEMENTATION.md:** `BasePointCalculator::calculate()` signature is `(ExamResult $mandatory, ExamResult $bestElective): int`. Selection of the best elective is the caller's responsibility (will be done by `AdmissionScoringService` in Phase 7).

### Pattern 3: Language certificate deduplication

**What:** Group certificates by language string, then keep only the highest-points certificate per language.

**Algorithm:**
1. Build a `string => int` map of `language => max_points_so_far`.
2. Iterate `array<LanguageCertificate>` — for each cert, store `max($current, $cert->points())` keyed by `$cert->language()`.
3. Sum the map values.
4. Add advanced exam points (50 per `ExamResult` where `$result->isAdvancedLevel()` is true).
5. Return `min($total, 100)`.

**Example:**
```php
// Source: PRD §7.2, IMPLEMENTATION.md §4 (BonusPointCalculator description)
final class BonusPointCalculator
{
    /**
     * @param array<int, ExamResult>          $examResults
     * @param array<int, LanguageCertificate> $certificates
     */
    public function calculate(array $examResults, array $certificates): int
    {
        $emeltPoints = 0;
        foreach ($examResults as $result) {
            if ($result->isAdvancedLevel()) {
                $emeltPoints += 50;
            }
        }

        /** @var array<string, int> $langMap */
        $langMap = [];
        foreach ($certificates as $cert) {
            $lang = $cert->language();
            $langMap[$lang] = max($langMap[$lang] ?? 0, $cert->points());
        }

        $certPoints = array_sum($langMap);

        return min($emeltPoints + $certPoints, 100);
    }
}
```

### Anti-Patterns to Avoid

- **Making BasePointCalculator select the best elective itself:** The calculator should receive pre-selected `$mandatory` and `$bestElective` from the calling service. Mixing selection logic with calculation logic violates single responsibility.
- **Applying the 400 cap anywhere other than BasePointCalculator:** The cap belongs inside `calculate()`, not in the caller or in `Score`.
- **Applying the 100 bonus cap in Score VO:** `Score`'s constructor does not cap bonus points — the cap is applied by `BonusPointCalculator`. The `Score` VO trusts its inputs (confirmed by existing `ScoreTest` which accepts `new Score(400, 100)` without error).
- **Using `readonly` on these classes:** `readonly` classes require all properties to be set in the constructor. Stateless services have no promoted properties, so `readonly` is incorrect here.
- **Deduplicating by certificate type instead of language:** The rule is one entry per language, not one entry per type. Two C1 certificates for different languages both count.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Group-by-key then max | Custom linked-list grouping | PHP `array` with `max()` | Built-in array functions handle this in 3 lines |
| Sum array values | Manual accumulator loop | `array_sum()` | Already handles int arrays cleanly |
| Points from enum | Custom switch in calculator | `$cert->points()` on `LanguageCertificate` VO | Points are already encapsulated in the VO |

**Key insight:** Both calculators are thin orchestrators of already-built Value Object methods. Do not re-implement logic that `ExamResult::isAdvancedLevel()`, `ExamResult::points()`, and `LanguageCertificate::points()` already provide.

## Common Pitfalls

### Pitfall 1: Forgetting the 400 cap on BasePointCalculator
**What goes wrong:** `(mandatory->points() + bestElective->points()) * 2` returns up to 400 when both are 100%, but the formula should be `min(..., 400)` to enforce the cap explicitly.
**Why it happens:** With percentage capped at 100%, the max naturally reaches 400, so the cap seems redundant. But the requirement states "max 400" — it must be enforced.
**How to avoid:** Include `min(..., 400)` in the implementation. Test with inputs that would exceed 400 if the cap were absent (they can't with valid ExamResult VOs, but the test should assert the cap exists).
**Warning signs:** Tests pass without the cap because ExamResult percentage is capped at 100. Add an explicit boundary test: `calculate(100%, 100%) === 400`.

### Pitfall 2: Deduplicating language certs by type instead of by language
**What goes wrong:** Keeping only one B2 and one C1 instead of keeping one per language.
**Why it happens:** Misreading "same language" dedup rule as "same type" dedup.
**How to avoid:** Key the dedup map by `$cert->language()` (a string like `'angol'`), not by `$cert->type->value` (a string like `'B2'`). Test the case where two certs of the same language exist — only the higher-points one should count.
**Warning signs:** A test with B2 + C1 for the same language returning 68 instead of 40.

### Pitfall 3: Counting emelt-level exam results that are not in the applicant's submitted results
**What goes wrong:** The `BonusPointCalculator` receives all `ExamResult` objects, not a pre-filtered list. It must count emelt points for every result where `isAdvancedLevel()` is true — no additional filtering by subject name.
**Why it happens:** Over-engineering — thinking only "programme subjects" get bonus points.
**How to avoid:** Iterate all `$examResults` passed in and check `isAdvancedLevel()` on each. The caller (`AdmissionScoringService`) is responsible for passing only validated results.

### Pitfall 4: Not using `declare(strict_types=1)` at top of file
**What goes wrong:** Implicit type coercion silently corrupts int math in edge cases.
**Why it happens:** Forgetting the project-wide convention.
**How to avoid:** All PHP files in this project start with `declare(strict_types=1);` — confirmed by every existing file.

### Pitfall 5: Running Pint after forgetting to format
**What goes wrong:** CI or code review fails due to style violations.
**How to avoid:** Run `vendor/bin/pint --dirty --format agent` immediately after writing PHP files.

## Code Examples

Verified patterns from existing codebase:

### Test file structure (from ExamResultTest.php pattern)
```php
// Source: tests/Unit/ValueObjects/ExamResultTest.php — established project convention
<?php

declare(strict_types=1);

use App\ValueObjects\ExamResult;
use App\Enums\ExamLevel;
use App\Enums\SubjectName;

it('calculates base points for typical input', function (): void {
    $mandatory = new ExamResult(SubjectName::Mathematics, ExamLevel::Advanced, 90);
    $bestElective = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, 95);

    $calculator = new \App\Services\BasePointCalculator;

    expect($calculator->calculate($mandatory, $bestElective))->toBe(370);
});
```

### Dataset-driven boundary test (established pattern)
```php
// Source: tests/Unit/ValueObjects/ExamResultTest.php — ->with([...]) pattern
it('applies 400 cap to base points', function (int $mandatoryPct, int $elective Pct, int $expected): void {
    $mandatory = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $mandatoryPct);
    $bestElective = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, $electivePct);

    expect((new \App\Services\BasePointCalculator)->calculate($mandatory, $bestElective))
        ->toBe($expected);
})->with([
    'typical' => [90, 95, 370],
    'maximum' => [100, 100, 400],
    'equal low' => [50, 50, 200],
]);
```

### LanguageCertificate construction for tests (no factory needed)
```php
// Source: tests/Unit/ValueObjects/LanguageCertificateTest.php — direct VO construction
use App\Enums\LanguageCertificateType;
use App\ValueObjects\LanguageCertificate;

$certB2 = new LanguageCertificate(LanguageCertificateType::UpperIntermediate, 'angol');
$certC1 = new LanguageCertificate(LanguageCertificateType::Advanced, 'angol');
// Same language — only C1 (40pts) should count in BonusPointCalculator
```

### Seeder-verified expected values (ground truth for integration)
```
// Source: database/seeders/ApplicantSeeder.php + IMPLEMENTATION.md §9
Applicant 1:
  mandatory: matematika, emelt, 90%
  best elective: informatika, közép, 95%
  base = (90 + 95) * 2 = 370  ✓

  emelt: matematika → +50
  certs: B2 angol (28pts) + C1 német (40pts) = 68
  raw bonus = 50 + 68 = 118 → cap → 100  ✓
  total = 470  ✓

Applicant 2:
  mandatory: matematika, emelt, 90%
  best elective: fizika, közép, 98% (98 > informatika 95)
  base = (90 + 98) * 2 = 376  ✓
  bonus = 100 (same as applicant 1)
  total = 476  ✓
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Mutable service classes | `final` classes enforced by Pint | Project start | Prevents subclassing and mutation bugs |
| `declare(strict_types=1)` optional | Mandatory on every PHP file | Project start | Prevents silent type coercion |
| PHPDoc arrays as `array` | Typed PHPDoc shapes `array<int, ExamResult>` | Project start (Larastan v3) | PHPStan level enforcement |

**Deprecated/outdated:**
- `readonly` on classes that have no constructor properties: not applicable here; `readonly` is for classes whose state is entirely promoted constructor properties.

## Open Questions

1. **Should `BasePointCalculator` accept the full list of `ExamResult` objects and select the best elective itself, or receive pre-selected VOs?**
   - What we know: `IMPLEMENTATION.md §4` specifies the signature as `calculate(ExamResult $mandatory, ExamResult $bestElective): int` — pre-selected.
   - What's unclear: Nothing. The spec is explicit.
   - Recommendation: Follow the spec. Calculator receives two VOs. Selection is the caller's job (Phase 7).

2. **Are there bonus points for emelt-level exams that are NOT in the programme's elective/mandatory list?**
   - What we know: `PRD §7.2` says "+50 per advanced-level (`emelt`) matriculation exam" with no restriction to programme subjects.
   - What's unclear: The seeded data shows matematika emelt giving +50 for an ELTE IK applicant, and matematika IS the mandatory subject for that programme. No test case exists with emelt results outside the programme subjects.
   - Recommendation: Count all emelt results in the array passed to `BonusPointCalculator`. The `AdmissionScoringService` will pass all validated exam results; the calculator counts all emelt ones. This matches the Hungarian admission rules where any emelt result earns bonus points regardless of whether it's a programme-specific subject.

## Sources

### Primary (HIGH confidence)
- `IMPLEMENTATION.md` §4 (Calculators), §9 (Test cases) — exact method signatures, formulas, expected outputs
- `PRD.md` §7.1, §7.2 — authoritative scoring rules specification
- `database/seeders/ApplicantSeeder.php` — ground truth for expected calculated values
- `app/ValueObjects/ExamResult.php` — `points()` and `isAdvancedLevel()` methods confirmed
- `app/ValueObjects/LanguageCertificate.php` — `points()` and `language()` methods confirmed
- `tests/Unit/ValueObjects/ExamResultTest.php` — established test conventions (dataset pattern, `->with()`)
- `tests/Unit/Services/DatabaseProgramRequirementsTest.php` — established test conventions for Services unit tests

### Secondary (MEDIUM confidence)
- PHP 8.5 `max()`, `array_sum()`, `min()` built-in functions — standard library, no verification needed

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — no new packages; all existing conventions established over 5 phases
- Architecture: HIGH — formulas specified exactly in two authoritative documents; method signatures given
- Pitfalls: HIGH — derived from code inspection and mathematical analysis of the scoring rules

**Research date:** 2026-02-28
**Valid until:** Project completion (static seeded exercise, no moving targets)
