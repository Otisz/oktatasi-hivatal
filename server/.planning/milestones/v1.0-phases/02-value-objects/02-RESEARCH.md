# Phase 2: Value Objects - Research

**Researched:** 2026-02-25
**Domain:** PHP readonly classes, immutable Value Objects, Pest 4 unit testing
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

#### ExamResult data shape
- ExamResult carries SubjectName, ExamLevel, and percentage (int) — not a pure score container
- All three stored as public readonly properties via constructor promotion
- Computed methods: points() returns percentage value, isAdvancedLevel() returns true only for ExamLevel::Emelt
- Single all-in-one constructor: new ExamResult(SubjectName, ExamLevel, int $percentage)

#### LanguageCertificate data shape
- Same pattern as ExamResult: constructor with all fields
- new LanguageCertificate(LanguageCertificateType $type, string $language)
- Public $language property for direct access, points() as computed method (delegates to $type->points())

#### Score data shape
- Stores basePoints and bonusPoints as int, both non-negative validated
- total() method returns basePoints + bonusPoints
- No cap enforcement — Score is a dumb container. Caps (400 base, 100 bonus) are the calculators' responsibility (Phase 6)

#### Validation boundaries
- ExamResult validates range 0-100 first (throws InvalidArgumentException), then < 20% business rule (throws FailedExamException)
- Percentage is strictly int — no floats
- Score validates basePoints and bonusPoints are non-negative (throws InvalidArgumentException)

#### VO construction style
- All VOs use PHP 8.2+ readonly class
- Public properties via constructor promotion (no private + getters)
- Simple data = public property, computed/derived = method
- No static factory methods — plain constructors only

#### Test patterns
- Pest datasets for parametric boundary coverage
- Full boundary testing: 0, 19, 20, 100, -1, 101 for ExamResult percentage
- VO tests only test VO behavior — don't re-test Phase 1 enum logic
- Three test files: ExamResultTest, LanguageCertificateTest, ScoreTest

### Claude's Discretion
- Namespace/directory structure for VOs (under App\Domain or App\Domain\ValueObjects)
- Exact dataset organization in tests
- PHPDoc block content
- Error message wording for InvalidArgumentException

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DOM-04 | ExamResult VO validates percentage 0-100, throws FailedExamException if < 20%, exposes points() and isAdvancedLevel() | PHP `readonly class` + ordered constructor validation + Pest `toThrow()` |
| DOM-05 | LanguageCertificate VO encapsulates certificate type and language, exposes points() and language() | PHP `readonly class` with public property + delegate method pattern |
| DOM-06 | Score VO stores basePoints and bonusPoints immutably, exposes total() | PHP `readonly class` + non-negative guard + simple accessor methods |
| TEST-01 | Unit tests for ExamResult (constructor validation, points(), isAdvancedLevel(), FailedExamException) | Pest datasets for boundary values + `toThrow()` + `toBeTrue()`/`toBeFalse()` |
| TEST-02 | Unit tests for LanguageCertificate (points() B2/C1, language() getter) | Pest datasets with LanguageCertificateType enum cases + `toBe()` |
| TEST-03 | Unit tests for Score (total() calculation, getters) | Pest datasets for sums + `toThrow()` for negative inputs |
</phase_requirements>

## Summary

Phase 2 produces three immutable Value Objects — ExamResult, LanguageCertificate, and Score — each implemented as a PHP 8.2 `readonly class` with constructor-promotion public properties and embedded validation. The phase has no external dependencies: it consumes Phase 1 enums (SubjectName, ExamLevel, LanguageCertificateType) and exceptions (FailedExamException), and produces objects that all downstream phases work with.

The testing story is straightforward: three Pest unit test files, each exercising boundary values through datasets, exception throwing via `toThrow()`, and computed method return values via `toBe()`. Unit tests for VOs do not extend the Laravel TestCase; they extend the base PHPUnit TestCase since there is no Laravel bootstrapping needed. PHPStan level 7 is active on `app/` — readonly classes fully satisfy immutability analysis without requiring extra annotations.

The primary implementation risk is the two-stage validation ordering in ExamResult: range check (0-100, throws `\InvalidArgumentException`) must execute before the business rule check (< 20%, throws `FailedExamException`). Inverting this order or combining them would break the documented validation semantics. Tests must explicitly cover the -1 and 101 boundary to pin down that `InvalidArgumentException` fires before `FailedExamException`.

**Primary recommendation:** Implement all three VOs as `readonly class` in `app/ValueObjects/`, write tests in `tests/Unit/ValueObjects/`, keep each test file focused on a single VO, and use inline Pest datasets for all boundary-value parametric coverage.

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP readonly class | 8.2+ | Enforces compile-time immutability on all properties | Language feature; zero overhead; PHPStan understands it natively |
| pestphp/pest | ^4.4 | Unit test runner with fluent API | Project already uses it; datasets and `toThrow()` are built-in |
| phpunit/phpunit | ^12 | Underlying test engine Pest wraps | Transitive dependency; no direct usage needed in VO tests |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| larastan/larastan | ^3.9 | PHPStan level 7 static analysis | Run `vendor/bin/phpstan analyse` after implementation to catch type errors |
| laravel/pint | ^1.24 | Code style formatter | Run `vendor/bin/pint --dirty --format agent` after all PHP files are written |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `readonly class` | Final class + private properties + getters | `readonly class` is cleaner PHP 8.2 idiomatic; avoids boilerplate getter methods |
| Inline Pest datasets | Separate `tests/Datasets/` files | For 3 small VO test files, inline datasets are easier to read and maintain |
| `\InvalidArgumentException` | Custom domain exception | `InvalidArgumentException` is appropriate for programmer errors (bad input types/ranges); FailedExamException is for domain rule violations |

**Installation:** No new packages needed — all dependencies already in `composer.json`.

## Architecture Patterns

### Recommended Project Structure

```
app/
└── ValueObjects/
    ├── ExamResult.php
    ├── LanguageCertificate.php
    └── Score.php

tests/
└── Unit/
    └── ValueObjects/
        ├── ExamResultTest.php
        ├── LanguageCertificateTest.php
        └── ScoreTest.php
```

The IMPLEMENTATION.md specifies `app/ValueObjects/` (flat, no `App\Domain` namespace layer). This aligns with the existing `app/Enums/` and `app/Exceptions/` flat structure from Phase 1. The planner should follow this established pattern.

### Pattern 1: PHP 8.2 Readonly Class with Constructor Validation

**What:** A `readonly class` makes all constructor-promoted properties automatically immutable. The constructor performs validation before the properties are assigned; if validation fails it throws before the object exists.

**When to use:** Any VO that must be immutable and whose invariants are checked at construction time.

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Exceptions\FailedExamException;

readonly class ExamResult
{
    public function __construct(
        public SubjectName $subject,
        public ExamLevel $level,
        public int $percentage,
    ) {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException(
                "Percentage must be between 0 and 100, got {$percentage}.",
            );
        }

        if ($percentage < 20) {
            throw new FailedExamException($subject, $percentage);
        }
    }

    public function points(): int
    {
        return $this->percentage;
    }

    public function isAdvancedLevel(): bool
    {
        return $this->level === ExamLevel::Advanced;
    }
}
```

Note: `ExamLevel::Advanced` maps to the `'emelt'` backing value per Phase 1 decisions (English case names, Hungarian backing values).

### Pattern 2: Pest Dataset for Boundary Value Testing

**What:** An inline `->with([...])` dataset provides multiple input/output pairs to a single test closure. Pest runs the test once per entry and labels it automatically.

**When to use:** Any boundary test where the same assertion logic applies across multiple inputs — percentage ranges, enum cases, integer arithmetic.

**Example (ExamResultTest.php):**
```php
<?php

declare(strict_types=1);

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Exceptions\FailedExamException;
use App\ValueObjects\ExamResult;

// Valid construction
it('accepts valid percentages', function (int $percentage) {
    $result = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage);
    expect($result->percentage)->toBe($percentage);
})->with([20, 50, 99, 100]);

// Out-of-range guard
it('throws InvalidArgumentException for out-of-range percentage', function (int $percentage) {
    expect(fn () => new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage))
        ->toThrow(\InvalidArgumentException::class);
})->with([-1, 101]);

// Business rule guard
it('throws FailedExamException for percentage below 20', function (int $percentage) {
    expect(fn () => new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage))
        ->toThrow(FailedExamException::class);
})->with([0, 1, 19]);

// points() accessor
it('returns percentage as points', function (int $percentage) {
    $result = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage);
    expect($result->points())->toBe($percentage);
})->with([20, 75, 100]);

// isAdvancedLevel()
it('detects advanced level correctly', function (ExamLevel $level, bool $expected) {
    $result = new ExamResult(SubjectName::Mathematics, $level, 50);
    expect($result->isAdvancedLevel())->toBe($expected);
})->with([
    'intermediate' => [ExamLevel::Intermediate, false],
    'advanced'     => [ExamLevel::Advanced, true],
]);
```

### Pattern 3: Delegate Method on VO

**What:** A VO method that delegates its computation to a property (enum or nested VO). The VO does not duplicate logic — it calls the enum's own method.

**When to use:** `LanguageCertificate::points()` delegates to `$this->type->points()`. The VO does not need to know B2=28 or C1=40; the enum owns that.

**Example:**
```php
readonly class LanguageCertificate
{
    public function __construct(
        public LanguageCertificateType $type,
        public string $language,
    ) {}

    public function points(): int
    {
        return $this->type->points();
    }

    public function language(): string
    {
        return $this->language;
    }
}
```

Note: `language()` as a method and `$language` as a public property would both exist per the locked decision. The method accessor is redundant but listed in REQUIREMENTS.md DOM-05 ("exposes language()"). Both are fine to provide.

### Pattern 4: Unit Tests NOT Using Laravel TestCase

**What:** Pure unit tests for VOs have no Laravel dependencies (no DB, no IoC container). They should be plain `PHPUnit\Framework\TestCase` tests, not extend `Tests\TestCase`.

**When to use:** Any test in `tests/Unit/` that does not need HTTP, DB, or service container bootstrapping.

**How:** Pest's `tests/Pest.php` already binds `Tests\TestCase` only to `Feature` tests:
```php
pest()->extend(Tests\TestCase::class)->in('Feature');
```
Tests in `tests/Unit/` automatically use `PHPUnit\Framework\TestCase` — no action needed. This is already the correct default.

### Anti-Patterns to Avoid

- **Mutable properties in "readonly" VOs:** Declaring `readonly class` but passing objects that are internally mutable (e.g., a mutable collection) — not a risk here since all VO properties are scalars or backed enums.
- **Combining range check and business rule in one if:** `if ($percentage < 0 || $percentage < 20)` collapses the two-exception semantics into one. Keep them as two separate `if` blocks in order.
- **Testing enum logic in VO tests:** The VO tests should NOT assert that `ExamLevel::Advanced->value === 'emelt'` or that `LanguageCertificateType::Advanced->points() === 40`. Those were Phase 1 concerns. Only assert that VO methods return the correct result given a known enum input.
- **Float percentages:** All percentage parameters are strictly `int`. PHP's strict_types=1 will reject float arguments at the call site.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Immutability enforcement | Custom clone-on-write or `__set` override | `readonly class` PHP 8.2 | Language-level; PHPStan understands it; no runtime overhead |
| Parametric test repetition | Copy-paste test methods with different inputs | Pest `->with([...])` datasets | Built-in; auto-labeling; single source of truth for boundary values |
| Exception assertions | Manual `try/catch` blocks in tests | `expect(fn() => ...)->toThrow(ExceptionClass::class)` | Clean; Pest native; handles re-throw semantics correctly |

**Key insight:** `readonly class` is not just syntactic sugar — PHPStan treats it as a guarantee and will catch any attempt to assign to properties outside the constructor. This is strictly better than the old `private $x; public function __construct($x) { $this->x = $x; }` pattern.

## Common Pitfalls

### Pitfall 1: Two-Stage Validation Order in ExamResult

**What goes wrong:** Developer writes the `< 20` check before the range check, so passing `-1` throws `FailedExamException` instead of `InvalidArgumentException`. The test dataset for `-1` would then fail.

**Why it happens:** The business rule (fail below 20) is more prominent in the spec than the sanity range check.

**How to avoid:** In the constructor body, always write range check first (`< 0 || > 100`), then the business rule (`< 20`). Tests must include a dataset entry for `-1` and `101` asserting `InvalidArgumentException`, AND separate entries for `0`, `1`, `19` asserting `FailedExamException`.

**Warning signs:** Test for `-1` throws `FailedExamException` when it should throw `InvalidArgumentException`.

### Pitfall 2: ExamLevel Enum Case Name Mismatch

**What goes wrong:** Phase 2 code references `ExamLevel::Emelt` but Phase 1 decided case names are English: `ExamLevel::Advanced` and `ExamLevel::Intermediate`.

**Why it happens:** IMPLEMENTATION.md (written before Phase 1 context) uses Hungarian case names (`Emelt`, `Kozep`). Phase 1 CONTEXT.md locked English case names.

**How to avoid:** Use `ExamLevel::Advanced` for the emelt level throughout. The backing value is `'emelt'` but the case name is `Advanced`.

**Warning signs:** PHPStan reports `Access to undefined constant App\Enums\ExamLevel::Emelt`.

### Pitfall 3: LanguageCertificateType Case Name Mismatch

**What goes wrong:** Code references `LanguageCertificateType::B2` or `LanguageCertificateType::C1` but Phase 1 decided descriptive English names: `UpperIntermediate` (B2, 28pts) and `Advanced` (C1, 40pts).

**Why it happens:** The spec and IMPLEMENTATION.md use B2/C1 as case names. Phase 1 CONTEXT.md overrode this.

**How to avoid:** Use `LanguageCertificateType::UpperIntermediate` for B2 and `LanguageCertificateType::Advanced` for C1 in VO tests.

**Warning signs:** PHPStan reports `Access to undefined constant App\Enums\LanguageCertificateType::B2`.

### Pitfall 4: Unit Tests Unnecessarily Bootstrapping Laravel

**What goes wrong:** Developer uses `php artisan make:test --pest tests/Unit/ValueObjects/ExamResultTest.php` without noticing it places tests in `Feature/` by default, or places them in `Unit/` but with the wrong TestCase.

**Why it happens:** The default Artisan test generator places tests in `Feature/` unless `--unit` is passed.

**How to avoid:** Use `php artisan make:test --pest --unit ValueObjects/ExamResultTest` to place tests in `tests/Unit/`. The Pest.php configuration only binds Laravel TestCase to `Feature/`, so Unit tests automatically use the lightweight PHPUnit base.

**Warning signs:** Test runs slower than expected; Laravel boots for each VO test.

### Pitfall 5: `language()` Method vs `$language` Property Duplication

**What goes wrong:** Both `public string $language` (constructor-promoted) and `public function language(): string { return $this->language; }` exist. The method name clashes with the property name at the language level in some PHP versions.

**Why it happens:** PHP allows properties and methods with the same name, but it can be confusing.

**How to avoid:** Per the locked decision, `$language` is a public property for direct access and `language()` is also listed in DOM-05. Both can coexist cleanly in PHP. The test should verify both `$cert->language` and `$cert->language()` return the expected string.

## Code Examples

Verified patterns from official sources:

### ExamResult (full implementation)
```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Exceptions\FailedExamException;

readonly class ExamResult
{
    public function __construct(
        public SubjectName $subject,
        public ExamLevel $level,
        public int $percentage,
    ) {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException(
                "Percentage must be between 0 and 100, got {$percentage}.",
            );
        }

        if ($percentage < 20) {
            throw new FailedExamException($subject, $percentage);
        }
    }

    public function points(): int
    {
        return $this->percentage;
    }

    public function isAdvancedLevel(): bool
    {
        return $this->level === ExamLevel::Advanced;
    }
}
```

### LanguageCertificate (full implementation)
```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\LanguageCertificateType;

readonly class LanguageCertificate
{
    public function __construct(
        public LanguageCertificateType $type,
        public string $language,
    ) {}

    public function points(): int
    {
        return $this->type->points();
    }

    public function language(): string
    {
        return $this->language;
    }
}
```

### Score (full implementation)
```php
<?php

declare(strict_types=1);

namespace App\ValueObjects;

readonly class Score
{
    public function __construct(
        public int $basePoints,
        public int $bonusPoints,
    ) {
        if ($basePoints < 0) {
            throw new \InvalidArgumentException(
                "Base points must be non-negative, got {$basePoints}.",
            );
        }

        if ($bonusPoints < 0) {
            throw new \InvalidArgumentException(
                "Bonus points must be non-negative, got {$bonusPoints}.",
            );
        }
    }

    public function total(): int
    {
        return $this->basePoints + $this->bonusPoints;
    }

    public function basePoints(): int
    {
        return $this->basePoints;
    }

    public function bonusPoints(): int
    {
        return $this->bonusPoints;
    }
}
```

Note: Score has both public properties AND accessor methods (`basePoints()`, `bonusPoints()`). The public property and method have the same name — this is valid PHP. Tests can verify both `$score->basePoints` and `$score->basePoints()`.

### ScoreTest (full example)
```php
<?php

declare(strict_types=1);

use App\ValueObjects\Score;

it('calculates total correctly', function (int $base, int $bonus, int $expected) {
    $score = new Score($base, $bonus);
    expect($score->total())->toBe($expected);
})->with([
    'zero base and bonus'    => [0, 0, 0],
    'typical admission'      => [370, 100, 470],
    'max possible'           => [400, 100, 500],
]);

it('exposes basePoints via property and method', function () {
    $score = new Score(370, 100);
    expect($score->basePoints)->toBe(370)
        ->and($score->basePoints())->toBe(370);
});

it('exposes bonusPoints via property and method', function () {
    $score = new Score(370, 100);
    expect($score->bonusPoints)->toBe(100)
        ->and($score->bonusPoints())->toBe(100);
});

it('throws InvalidArgumentException for negative base points', function () {
    expect(fn () => new Score(-1, 0))->toThrow(\InvalidArgumentException::class);
});

it('throws InvalidArgumentException for negative bonus points', function () {
    expect(fn () => new Score(0, -1))->toThrow(\InvalidArgumentException::class);
});
```

### Artisan commands for test file creation
```bash
php artisan make:test --pest --unit --no-interaction ValueObjects/ExamResultTest
php artisan make:test --pest --unit --no-interaction ValueObjects/LanguageCertificateTest
php artisan make:test --pest --unit --no-interaction ValueObjects/ScoreTest
```

### Run only VO tests
```bash
php artisan test --compact --filter=ValueObjects
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `final class` + `private` properties + getters | `readonly class` with public promoted properties | PHP 8.2 (Nov 2022) | Eliminates all getter boilerplate; immutability is compile-time enforced |
| `$this->assertTrue($a === $b)` in PHPUnit | `expect($a)->toBe($b)` in Pest | Pest v1+ | Fluent, readable; Pest 4 is the current version in this project |
| Separate dataset files in `tests/Datasets/` | Inline `->with([...])` on the test | Always possible | For small test files, inline is simpler to follow |

**Deprecated/outdated:**
- Manual `try { ... } catch (X $e) { $this->assertInstanceOf(...) }` in tests: replaced by `expect(fn() => ...)->toThrow(X::class)`.
- `$casts` property on models for type casting: replaced by `casts()` method in Laravel 12 — not relevant to this phase but consistent with project convention.

## Open Questions

1. **Score property vs method naming collision**
   - What we know: PHP permits a property and method with the same identifier (`basePoints` property + `basePoints()` method). This has been valid since PHP 7.
   - What's unclear: PHPStan level 7 behavior when `readonly class` has identically-named public property and public method.
   - Recommendation: Implement it and run `vendor/bin/phpstan analyse` after — if PHPStan flags it, rename either the properties (e.g., `$base`, `$bonus`) or omit the methods (since public properties are already directly accessible). The locked decision says "getters return stored values" so they must exist, but the property names could be changed to avoid collision if needed.

2. **FailedExamException constructor signature**
   - What we know: Phase 1 locked that exceptions carry rich context data as typed properties. FailedExamException must store subject name + percentage.
   - What's unclear: Whether the constructor signature is `(SubjectName $subject, int $percentage)` or `(string $subjectName, int $percentage)` — depends on Phase 1 implementation.
   - Recommendation: Check the Phase 1 PLAN files and implementation before calling `new FailedExamException(...)` in ExamResult. Use whatever Phase 1 established.

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 4.4 (pestphp/pest ^4.4) |
| Config file | `phpunit.xml.dist` + `tests/Pest.php` |
| Quick run command | `php artisan test --compact --filter=ValueObjects` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DOM-04 | ExamResult validates 0-100, throws FailedExamException < 20%, points(), isAdvancedLevel() | unit | `php artisan test --compact --filter=ExamResultTest` | Wave 0 |
| DOM-05 | LanguageCertificate points() returns type's points, language() returns language string | unit | `php artisan test --compact --filter=LanguageCertificateTest` | Wave 0 |
| DOM-06 | Score total() = base + bonus, non-negative validation, getters | unit | `php artisan test --compact --filter=ScoreTest` | Wave 0 |
| TEST-01 | ExamResult unit test coverage | unit | `php artisan test --compact --filter=ExamResultTest` | Wave 0 |
| TEST-02 | LanguageCertificate unit test coverage | unit | `php artisan test --compact --filter=LanguageCertificateTest` | Wave 0 |
| TEST-03 | Score unit test coverage | unit | `php artisan test --compact --filter=ScoreTest` | Wave 0 |

### Sampling Rate

- **Per task commit:** `php artisan test --compact --filter=ValueObjects`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green + `vendor/bin/phpstan analyse` clean before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `tests/Unit/ValueObjects/ExamResultTest.php` — covers DOM-04, TEST-01
- [ ] `tests/Unit/ValueObjects/LanguageCertificateTest.php` — covers DOM-05, TEST-02
- [ ] `tests/Unit/ValueObjects/ScoreTest.php` — covers DOM-06, TEST-03
- [ ] `app/ValueObjects/ExamResult.php` — implementation under test
- [ ] `app/ValueObjects/LanguageCertificate.php` — implementation under test
- [ ] `app/ValueObjects/Score.php` — implementation under test

## Sources

### Primary (HIGH confidence)

- Pest 4 official docs (via laravel-boost search-docs) — datasets, `toThrow()`, `toBeReadonly()` arch test, `toBe()`, `and()`, `expect()` API
- PHP 8.2 `readonly class` — language specification, constructor promotion interaction, PHPStan compatibility
- `phpunit.xml.dist` in project — confirms Unit testsuite points to `tests/Unit/`, Feature to `tests/Feature/`
- `tests/Pest.php` in project — confirms TestCase binding only applies to Feature, Unit tests use PHPUnit base
- `phpstan.neon` in project — confirms level 7, paths: [app], excludePaths: [tests]
- `IMPLEMENTATION.md` — authoritative directory structure (`app/ValueObjects/`, `tests/Unit/ValueObjects/`)
- Phase 1 CONTEXT.md — authoritative enum case naming (English names, Hungarian backing values)

### Secondary (MEDIUM confidence)

- PHP documentation: property and method with same name coexistence in readonly class — valid PHP, behavior verified via PHP manual

### Tertiary (LOW confidence)

- PHPStan level 7 behavior with identically-named property/method in readonly class — flagged as Open Question; needs empirical verification after implementation.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Pest 4 docs verified, PHP readonly class is stable language feature since 8.2
- Architecture: HIGH — IMPLEMENTATION.md defines exact directory layout, Phase 1 CONTEXT.md defines enum names
- Pitfalls: HIGH for validation order and enum naming (directly from spec analysis); MEDIUM for PHPStan property/method name collision (needs empirical confirmation)

**Research date:** 2026-02-25
**Valid until:** 2026-03-27 (stable domain — PHP and Pest APIs don't change rapidly)
