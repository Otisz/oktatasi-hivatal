# Phase 5: Strategy Pattern - Research

**Researched:** 2026-02-26
**Domain:** PHP interfaces, Strategy pattern in Laravel, Mockery unit testing of Eloquent models without DB
**Confidence:** HIGH

## Summary

Phase 5 introduces the Strategy pattern layer that sits between Eloquent models and the scoring engine. Three artefacts are needed: `ProgramRequirementsInterface` (DOM-08), `DatabaseProgramRequirements` (BIZ-01), and `ProgramRegistry` (BIZ-02). All three are pure PHP classes with no controller, route, or API surface — they are consumed by the Phase 7 scoring service.

The critical challenge in this phase is that unit tests must pass **without database access** — the success criteria explicitly requires mock models only. `Model::preventLazyLoading()` is active unconditionally in `AppServiceProvider::register()`, which means any Eloquent relationship access on a non-eager-loaded model will throw at test time. Unit tests must avoid creating real models entirely; instead they mock `Program` and `Applicant` using Mockery so the test assertions are driven by controlled return values on `->subjects` collection.

The mocking strategy must account for how `DatabaseProgramRequirements` filters subjects: it iterates over a `Program` model's `subjects` (a `HasMany` collection of `ProgramSubject` models) and filters by `requirement_type` (`RequirementType::Mandatory` / `RequirementType::Elective`). In tests, the subjects collection is a plain `Illuminate\Database\Eloquent\Collection` of real `ProgramSubject` objects created without database persistence, or a mocked collection. Since `ProgramSubject` is a `final class`, Mockery cannot mock it directly — the test must create lightweight real instances by setting attributes manually (bypassing mass-assignment with `Model::unguard()`) or use a collection of real seeded-attribute objects.

**Primary recommendation:** Define `ProgramRequirementsInterface` in `app/Contracts/` (or `app/Services/`), implement `DatabaseProgramRequirements` in `app/Services/` as a final readonly class accepting a `Program` model, implement `ProgramRegistry` in `app/Services/` accepting an `Applicant` via `findByApplicant()`, and write unit tests in `tests/Unit/Services/` using Mockery for `Program` and `Applicant` while creating real `ProgramSubject` instances with manually-set attributes for subject filtering assertions.

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DOM-08 | ProgramRequirementsInterface contract with getMandatorySubject(), getElectiveSubjects(), getMandatorySubjectLevel() | PHP interface; return types are SubjectName, SubjectName[], ExamLevel|null |
| BIZ-01 | DatabaseProgramRequirements implements ProgramRequirementsInterface using Program model's eager-loaded subjects | Filters Program->subjects by RequirementType cast; final class; constructor takes Program |
| BIZ-02 | ProgramRegistry resolves ProgramRequirementsInterface for an Applicant via eager-loaded program.subjects | findByApplicant(Applicant $applicant): ProgramRequirementsInterface; accesses $applicant->program->subjects |
| TEST-04 | Unit tests for DatabaseProgramRequirements (mock Program model, mandatory/elective/level queries) | Mockery mock for Program; real ProgramSubject instances with attribute assignment; no DB |
| TEST-05 | Unit tests for ProgramRegistry (mock Applicant/Program, correct resolution) | Mockery mock for Applicant with mocked program relationship; asserts return type |
</phase_requirements>

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP interface | 8.5 | `ProgramRequirementsInterface` contract — enforces method signatures | Language feature; already used idiomatically in Laravel |
| laravel/framework | v12 | `Illuminate\Database\Eloquent\Collection` for subject filtering; service container singleton binding in Phase 8 | Already installed |
| mockery/mockery | ^1.6 | Create test doubles for `Program` and `Applicant` without touching the DB | Already in `composer.json` as dev dependency |
| pestphp/pest | ^4.4 | Unit test runner | Already in use across Phase 1–4 |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| larastan/larastan | ^3.9 | PHPStan level 7 type checking on `app/` | Run after implementation; interface and return types must satisfy level 7 |
| laravel/pint | ^1.24 | Code style (final_class, declare_strict_types) | Run `vendor/bin/pint --dirty --format agent` after all PHP files |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Mockery for Program mock | Real Program with factory + RefreshDatabase | Factory approach requires DB access, violating the "no database" success criterion |
| Real ProgramSubject instances (attribute-set) for subject list | Mockery mock of ProgramSubject | ProgramSubject is `final class` — Mockery cannot mock final classes by default; real instances with set attributes are the correct approach |
| `app/Services/` namespace for implementations | `app/Repositories/` or `app/Domain/` | Project has no existing `Repositories/` or `Domain/` directory; `app/Services/` matches the layer description in ARCHITECTURE.md "potential service layer in app/Services/"; follow sibling-file conventions |

**Installation:** No new packages needed.

---

## Architecture Patterns

### Recommended Project Structure

```
app/
├── Contracts/
│   └── ProgramRequirementsInterface.php   (NEW — interface)
└── Services/
    ├── DatabaseProgramRequirements.php    (NEW — implements interface)
    └── ProgramRegistry.php                (NEW — resolves interface for applicant)

tests/
└── Unit/
    └── Services/
        ├── DatabaseProgramRequirementsTest.php  (NEW)
        └── ProgramRegistryTest.php              (NEW)
```

Note: Neither `app/Contracts/` nor `app/Services/` currently exist in the project. Both are standard Laravel conventions. `app/Contracts/` for interfaces and `app/Services/` for implementations. Check sibling conventions — if there is no preferred directory, `app/Contracts/` for the interface and `app/Services/` for service classes is the standard Laravel 12 approach.

### Pattern 1: PHP Interface Definition

**What:** A PHP interface defines the contract that `DatabaseProgramRequirements` must fulfill. It has no implementation, only method signatures. The scoring service (Phase 7) depends only on this interface, not the concrete class.

**When to use:** `ProgramRequirementsInterface` — the single point of variability between DB-backed and any future alternative implementation.

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;

interface ProgramRequirementsInterface
{
    public function getMandatorySubject(): SubjectName;

    /** @return array<int, SubjectName> */
    public function getElectiveSubjects(): array;

    public function getMandatorySubjectLevel(): ?ExamLevel;
}
```

### Pattern 2: DatabaseProgramRequirements — Filter Eager-Loaded Subjects

**What:** The concrete implementation accepts a `Program` model (with subjects already eager-loaded) and filters its `subjects` collection by `RequirementType`. It returns typed values from the subject's enum-cast properties.

**When to use:** Phase 5 sole implementation. Downstream scoring service instantiates this via `ProgramRegistry`.

**Key constraint:** `Program::$subjects` must already be loaded when the `Program` is passed in. The `ProgramRegistry` is responsible for ensuring eager loading. If `preventLazyLoading()` fires inside `DatabaseProgramRequirements`, the bug is in `ProgramRegistry` not loading subjects eagerly.

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProgramRequirementsInterface;
use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use App\Models\Program;

final readonly class DatabaseProgramRequirements implements ProgramRequirementsInterface
{
    public function __construct(private Program $program) {}

    public function getMandatorySubject(): SubjectName
    {
        return $this->program->subjects
            ->firstWhere('requirement_type', RequirementType::Mandatory)
            ->subject_name;
    }

    /** @return array<int, SubjectName> */
    public function getElectiveSubjects(): array
    {
        return $this->program->subjects
            ->where('requirement_type', RequirementType::Elective)
            ->values()
            ->map(fn ($s) => $s->subject_name)
            ->all();
    }

    public function getMandatorySubjectLevel(): ?ExamLevel
    {
        return $this->program->subjects
            ->firstWhere('requirement_type', RequirementType::Mandatory)
            ?->required_level;
    }
}
```

**PHPStan note:** `->firstWhere()` on an Eloquent Collection returns `Model|null`. PHPStan level 7 will flag `->subject_name` access on a nullable. Use a null-safe operator or add a runtime guard (throw `UnknownProgramException` if mandatory subject is not found). The test for `getMandatorySubjectLevel()` already returns `?ExamLevel` so null is expected there, but `getMandatorySubject()` must always return a non-null `SubjectName`. Consider whether to throw or assert non-null.

### Pattern 3: ProgramRegistry — Resolve Requirements from Applicant

**What:** A service class that, given an `Applicant` (with its `program.subjects` relationship eager-loaded), creates a `DatabaseProgramRequirements` instance and returns it.

**When to use:** Phase 8 will bind `ProgramRegistry` as a singleton in `AppServiceProvider`. Phase 5 only needs the class and its unit test.

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProgramRequirementsInterface;
use App\Models\Applicant;

final class ProgramRegistry
{
    public function findByApplicant(Applicant $applicant): ProgramRequirementsInterface
    {
        return new DatabaseProgramRequirements($applicant->program);
    }
}
```

**Note on eager loading:** `findByApplicant()` accesses `$applicant->program`. This will throw `LazyLoadingViolationException` unless the `Applicant` was retrieved with `->with('program.subjects')`. In production (Phase 8 scoring service), the caller must eager-load. In unit tests (Phase 5), the mock supplies `program` as a property so no lazy loading occurs.

### Pattern 4: Mockery for final Eloquent Models in Unit Tests

**What:** Mockery's default `Mockery::mock()` cannot mock `final` classes. However, since `Program` and `Applicant` are both declared `final`, a different strategy is needed for unit tests.

**Options (in priority order):**
1. **Use `Mockery::mock()` with the `allow_mocking_protected_methods` config** — does not help with `final`.
2. **Use PHP-DI Mockery proxy** — requires additional setup.
3. **Create real lightweight instances using `new Program()` + `$model->setRelation('subjects', Collection::make([...]))`** — no DB access, no Mockery needed for the model.
4. **Use `Mockery::mock()` without the `final` restriction using `PHPUNIT_FINAL_CLASSES=false`** — environment-level hack, not recommended.

**Recommended approach:** Create real `Program` instances using `$program = new Program(); $program->setRelation('subjects', Collection::make([...]))`. Create real `ProgramSubject` instances using `new ProgramSubject()` and set attributes: `$subject->subject_name = SubjectName::Mathematics; $subject->requirement_type = RequirementType::Mandatory; $subject->required_level = null;`.

Since `Model::unguard()` is called globally in `AppServiceProvider`, attributes can be mass-assigned. But since these are unit tests (no Laravel bootstrap in `tests/Unit/`), `unguard()` is NOT called automatically. Use `$subject->setAttribute('subject_name', SubjectName::Mathematics)` or call `Model::unguard()` at the top of the test file.

**Alternative for ProgramRegistry test:** Mock `Applicant` using Mockery when `Applicant` relationship access can be stubbed:
```php
$program = new Program();
$program->setRelation('subjects', Collection::make([]));

$applicant = Mockery::mock(Applicant::class);
$applicant->shouldReceive('getAttribute')->with('program')->andReturn($program);
```

But note: `Applicant` is also `final` — same `final` class restriction applies.

**Practical resolution:** The cleanest approach that avoids all Mockery/final-class issues is to use real model instances with `setRelation()` and `setAttribute()`:

```php
use Illuminate\Database\Eloquent\Collection;

$subject = new ProgramSubject();
$subject->setAttribute('subject_name', SubjectName::Mathematics);
$subject->setAttribute('requirement_type', RequirementType::Mandatory);
$subject->setAttribute('required_level', null);

$program = new Program();
$program->setRelation('subjects', Collection::make([$subject]));

$requirements = new DatabaseProgramRequirements($program);
expect($requirements->getMandatorySubject())->toBe(SubjectName::Mathematics);
```

This pattern:
- Does not touch the database
- Avoids the `preventLazyLoading()` trap (setRelation bypasses lazy loading)
- Does not require Mockery at all for the `DatabaseProgramRequirements` tests
- Works with PHPStan level 7 since real typed models are used

For `ProgramRegistry` tests, create a real `Applicant` instance and set the `program` relation:
```php
$program = new Program();
$program->setRelation('subjects', Collection::make([]));

$applicant = new Applicant();
$applicant->setRelation('program', $program);

$registry = new ProgramRegistry();
$result = $registry->findByApplicant($applicant);
expect($result)->toBeInstanceOf(DatabaseProgramRequirements::class);
```

### Anti-Patterns to Avoid

- **`Mockery::mock(Program::class)` on a final class:** Mockery cannot mock `final` classes without a proxy generator. Attempting this will throw a `RuntimeException` at test time.
- **Creating models with `Program::factory()->make()`:** Factory-made models still need a DB connection for some operations and require the Laravel test container bootstrap; unit tests in `tests/Unit/` don't have this unless extended via `Tests\TestCase`.
- **Accessing `$applicant->program` on a plain `new Applicant()` without `setRelation()`:** This triggers lazy loading, which throws `LazyLoadingViolationException` (since `preventLazyLoading()` is active globally).
- **Returning `Collection` instead of `array`:** `getElectiveSubjects()` must return `array<int, SubjectName>`, not a Collection. Call `->all()` at the end of the chain.
- **PHPDoc missing on interface array return:** `@return array<int, SubjectName>` is required on `getElectiveSubjects()` or PHPStan level 7 will infer `mixed[]`.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Subject type filtering | Manual foreach with if-checks | `Collection::firstWhere()` / `Collection::where()` | Eloquent Collection methods handle nullable comparison and are already available |
| "No DB in unit test" | RefreshDatabase + factory | `new Model(); $m->setRelation(...)` | setRelation() bypasses the ORM retrieval path entirely; zero DB dependency |
| Return type from collection | Custom DTO/array build | `->map(fn($s) => $s->subject_name)->all()` | Eloquent Collection map + all() is idiomatic and PHPStan-friendly |

**Key insight:** Eloquent's `setRelation()` method is the standard Laravel approach for injecting pre-loaded relationships in tests without touching the database. It is not a hack — it is the mechanism that the ORM itself uses internally when eager-loading results are merged onto model instances.

---

## Common Pitfalls

### Pitfall 1: Mockery Cannot Mock Final Classes

**What goes wrong:** `Mockery::mock(Program::class)` throws `RuntimeException: class Program is declared final and cannot be mocked`.

**Why it happens:** All five Eloquent models are declared `final` per the Pint `final_class` rule. Mockery's default approach requires overriding class methods, which requires either non-final classes or proxy generation.

**How to avoid:** Use real model instances with `new Program()` + `setRelation()`. No Mockery needed for the models being passed in.

**Warning signs:** Test output starts with `RuntimeException: class App\Models\Program is declared final`.

### Pitfall 2: preventLazyLoading Fires in Unit Tests

**What goes wrong:** Accessing `$applicant->program` (or `$program->subjects`) on a plain model instance in a unit test triggers `LazyLoadingViolationException`.

**Why it happens:** `Model::preventLazyLoading()` is called unconditionally in `AppServiceProvider::register()`. However, **unit tests in `tests/Unit/` do not boot the Laravel application** (they use base PHPUnit TestCase, not `Tests\TestCase`). So `preventLazyLoading()` is NOT active in pure unit tests.

**Verification:** The `tests/Pest.php` file binds `Tests\TestCase` only for `Feature` tests. `Unit` tests use PHPUnit's base. This means the AppServiceProvider is NOT executed in unit tests — `preventLazyLoading()`, `unguard()`, and `preventAccessingMissingAttributes()` are NOT active.

**Impact on test strategy:**
- `Model::unguard()` is NOT called → mass assignment via `fill([...])` will use model's `$guarded`/`$fillable` rules. Since models have no `$fillable` and no `$guarded`, the default is `$guarded = ['*']` (everything protected). Use `setAttribute()` instead of `fill()` or `create([...])`.
- `preventLazyLoading()` is NOT active → relationship access on un-set relations returns null quietly (or triggers a DB query which would fail due to no DB connection). Use `setRelation()` explicitly to avoid any DB access attempt.
- `preventAccessingMissingAttributes()` is NOT active → accessing undefined attributes returns null rather than throwing.

**How to avoid:** Always use `$model->setAttribute('column', value)` and `$model->setRelation('relation', $value)` to construct test fixtures in unit tests. Never rely on mass assignment or factory `make()` without verifying the test class extends `Tests\TestCase`.

**Warning signs:** Test accesses a relationship and either throws `LazyLoadingViolationException` (if somehow the service provider ran) or silently queries a non-existent in-memory SQLite.

### Pitfall 3: Collection::firstWhere Comparison with Enum Values

**What goes wrong:** `$program->subjects->firstWhere('requirement_type', RequirementType::Mandatory)` returns `null` even when a mandatory subject exists.

**Why it happens:** `Collection::firstWhere()` uses loose equality. The `ProgramSubject::$requirement_type` attribute is cast to `RequirementType` enum when retrieved from a real DB. But when creating test instances with `setAttribute('requirement_type', RequirementType::Mandatory)`, the cast is applied correctly. If the attribute is set as a string (`'mandatory'`) then comparison with the enum object may not match depending on the equality check.

**How to avoid:** When building test `ProgramSubject` instances, set attributes using the enum object directly: `$subject->setAttribute('requirement_type', RequirementType::Mandatory)`. The model's `casts()` method will handle serialization. During unit tests without DB, the cast is applied on read by Eloquent's attribute accessor — ensure the model has casts() defined (it does, per Phase 3).

**Alternative:** Compare using `RequirementType::Mandatory->value` string if enum comparison proves inconsistent, but this is a last resort.

**Warning signs:** `getMandatorySubject()` returns null; `getElectiveSubjects()` returns an empty array when subjects exist.

### Pitfall 4: PHPStan Level 7 on Interface Return Types

**What goes wrong:** PHPStan reports `method returns mixed` or `method has no return type hint` on interface methods.

**Why it happens:** Without explicit `@return` PHPDoc on array-returning methods, PHPStan level 7 infers `mixed[]` for the array.

**How to avoid:** Add `/** @return array<int, SubjectName> */` PHPDoc before `getElectiveSubjects()` on both the interface and the implementation.

**Warning signs:** `./vendor/bin/phpstan analyse app/` outputs errors about `getElectiveSubjects()` return type.

### Pitfall 5: Null Safety on getMandatorySubject()

**What goes wrong:** `Collection::firstWhere()` returns `null` when no mandatory subject matches. Accessing `->subject_name` on null throws a TypeError.

**Why it happens:** A Program is expected to always have exactly one mandatory subject, but this invariant is not enforced at the model level — it is assumed by the domain.

**How to avoid:** Either: (a) add a null guard that throws `UnknownProgramException` if no mandatory subject is found, making the return type `SubjectName` safe; or (b) use null-safe operator `?->subject_name` but then return type becomes `?SubjectName`, breaking the interface contract.

**Recommendation:** Throw `UnknownProgramException` when `firstWhere` returns null for mandatory. This aligns with the existing exception in `app/Exceptions/UnknownProgramException.php` (already exists from Phase 1). PHPStan level 7 will require this guard to consider the return type non-nullable.

**Warning signs:** PHPStan reports `Cannot access property ... on App\Models\ProgramSubject|null`.

---

## Code Examples

Verified patterns from project codebase and official sources:

### ProgramRequirementsInterface

```php
<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;

interface ProgramRequirementsInterface
{
    public function getMandatorySubject(): SubjectName;

    /** @return array<int, SubjectName> */
    public function getElectiveSubjects(): array;

    public function getMandatorySubjectLevel(): ?ExamLevel;
}
```

### DatabaseProgramRequirements

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProgramRequirementsInterface;
use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use App\Exceptions\UnknownProgramException;
use App\Models\Program;

final readonly class DatabaseProgramRequirements implements ProgramRequirementsInterface
{
    public function __construct(private Program $program) {}

    public function getMandatorySubject(): SubjectName
    {
        $mandatory = $this->program->subjects
            ->firstWhere('requirement_type', RequirementType::Mandatory);

        if ($mandatory === null) {
            throw new UnknownProgramException();
        }

        return $mandatory->subject_name;
    }

    /** @return array<int, SubjectName> */
    public function getElectiveSubjects(): array
    {
        return $this->program->subjects
            ->where('requirement_type', RequirementType::Elective)
            ->values()
            ->map(fn ($subject) => $subject->subject_name)
            ->all();
    }

    public function getMandatorySubjectLevel(): ?ExamLevel
    {
        return $this->program->subjects
            ->firstWhere('requirement_type', RequirementType::Mandatory)
            ?->required_level;
    }
}
```

### ProgramRegistry

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProgramRequirementsInterface;
use App\Models\Applicant;

final class ProgramRegistry
{
    public function findByApplicant(Applicant $applicant): ProgramRequirementsInterface
    {
        return new DatabaseProgramRequirements($applicant->program);
    }
}
```

### DatabaseProgramRequirementsTest (unit, no DB)

```php
<?php

declare(strict_types=1);

use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use App\Models\Program;
use App\Models\ProgramSubject;
use App\Services\DatabaseProgramRequirements;
use Illuminate\Database\Eloquent\Collection;

function makeMandatorySubject(SubjectName $name, ?ExamLevel $level = null): ProgramSubject
{
    $subject = new ProgramSubject();
    $subject->setAttribute('subject_name', $name);
    $subject->setAttribute('requirement_type', RequirementType::Mandatory);
    $subject->setAttribute('required_level', $level);
    return $subject;
}

function makeElectiveSubject(SubjectName $name): ProgramSubject
{
    $subject = new ProgramSubject();
    $subject->setAttribute('subject_name', $name);
    $subject->setAttribute('requirement_type', RequirementType::Elective);
    $subject->setAttribute('required_level', null);
    return $subject;
}

function makeProgramWithSubjects(array $subjects): Program
{
    $program = new Program();
    $program->setRelation('subjects', Collection::make($subjects));
    return $program;
}

it('returns the mandatory subject name', function (): void {
    $program = makeProgramWithSubjects([
        makeMandatorySubject(SubjectName::Mathematics),
        makeElectiveSubject(SubjectName::Physics),
    ]);

    $requirements = new DatabaseProgramRequirements($program);
    expect($requirements->getMandatorySubject())->toBe(SubjectName::Mathematics);
});

it('returns elective subject names as array', function (): void {
    $program = makeProgramWithSubjects([
        makeMandatorySubject(SubjectName::Mathematics),
        makeElectiveSubject(SubjectName::Physics),
        makeElectiveSubject(SubjectName::Biology),
    ]);

    $requirements = new DatabaseProgramRequirements($program);
    expect($requirements->getElectiveSubjects())->toBe([SubjectName::Physics, SubjectName::Biology]);
});

it('returns null when mandatory subject has no required level', function (): void {
    $program = makeProgramWithSubjects([
        makeMandatorySubject(SubjectName::Mathematics, null),
    ]);

    $requirements = new DatabaseProgramRequirements($program);
    expect($requirements->getMandatorySubjectLevel())->toBeNull();
});

it('returns the required level when mandatory subject specifies one', function (): void {
    $program = makeProgramWithSubjects([
        makeMandatorySubject(SubjectName::EnglishLanguage, ExamLevel::Advanced),
    ]);

    $requirements = new DatabaseProgramRequirements($program);
    expect($requirements->getMandatorySubjectLevel())->toBe(ExamLevel::Advanced);
});
```

### ProgramRegistryTest (unit, no DB)

```php
<?php

declare(strict_types=1);

use App\Contracts\ProgramRequirementsInterface;
use App\Models\Applicant;
use App\Models\Program;
use App\Services\DatabaseProgramRequirements;
use App\Services\ProgramRegistry;
use Illuminate\Database\Eloquent\Collection;

it('returns DatabaseProgramRequirements for an applicant', function (): void {
    $program = new Program();
    $program->setRelation('subjects', Collection::make([]));

    $applicant = new Applicant();
    $applicant->setRelation('program', $program);

    $registry = new ProgramRegistry();
    $result = $registry->findByApplicant($applicant);

    expect($result)->toBeInstanceOf(DatabaseProgramRequirements::class)
        ->and($result)->toBeInstanceOf(ProgramRequirementsInterface::class);
});
```

### Artisan Commands for File Creation

```bash
php artisan make:interface --no-interaction Contracts/ProgramRequirementsInterface
# OR if make:interface isn't available:
php artisan make:class --no-interaction Contracts/ProgramRequirementsInterface

php artisan make:class --no-interaction Services/DatabaseProgramRequirements
php artisan make:class --no-interaction Services/ProgramRegistry

php artisan make:test --pest --unit --no-interaction Services/DatabaseProgramRequirementsTest
php artisan make:test --pest --unit --no-interaction Services/ProgramRegistryTest
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `Mockery::mock(FinalClass::class)` fails | Real instances + `setRelation()` / `setAttribute()` | Design decision when `final_class` Pint rule was set | Tests are more realistic; no Mockery proxy overhead |
| `$model->fill([...])` with unguard() | `$model->setAttribute(...)` in pure unit tests | Unit tests don't boot Laravel; `unguard()` not called | Need to use `setAttribute()` directly |
| `$with` property for auto-eager-loading | Explicit `->with('relation')` at call site | Project decision (Phase 3 CONTEXT.md) | ProgramRegistry must explicitly handle eager loading in production; tests use `setRelation()` |

**Deprecated/outdated:**
- `Mockery::mock()` on final models: not viable with `final_class` Pint rule. Use real instances.

---

## Open Questions

1. **`app/Contracts/` vs `app/Services/` for the interface**
   - What we know: The project has no existing `app/Contracts/` or `app/Services/` directories. Both are valid Laravel conventions.
   - What's unclear: Which the project author prefers.
   - Recommendation: Create `app/Contracts/ProgramRequirementsInterface.php` (interface) and `app/Services/DatabaseProgramRequirements.php` + `app/Services/ProgramRegistry.php` (implementations). This is the most standard Laravel separation.

2. **Collection::where() enum comparison behavior**
   - What we know: `Collection::where()` uses strict comparison when the value is an object. Eloquent's `casts()` returns enum instances on attribute access. When `setAttribute()` is used in tests, the cast is applied on read.
   - What's unclear: Whether `Collection::firstWhere('requirement_type', RequirementType::Mandatory)` will match when `setAttribute` was called with the enum object.
   - Recommendation: Test empirically by running the unit tests. If it fails, filter with a closure: `$this->program->subjects->first(fn($s) => $s->requirement_type === RequirementType::Mandatory)`.

3. **PHPStan inference on Collection->map()->all()**
   - What we know: `Collection::map()` returns `Collection`. `->all()` returns `array`. PHPStan with Larastan 3 has generics for Collection.
   - What's unclear: Whether Larastan 3 will infer the array type from the closure's return type annotation.
   - Recommendation: Add `/** @return array<int, SubjectName> */` on `getElectiveSubjects()` to ensure PHPStan level 7 does not complain about return type.

---

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest 4.4 (pestphp/pest ^4.4) |
| Config file | `phpunit.xml.dist` + `tests/Pest.php` |
| Quick run command | `php artisan test --compact --filter=Services` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| DOM-08 | Interface contract exists with correct method signatures | unit (arch) | `php artisan test --compact --filter=DatabaseProgramRequirementsTest` | Wave 0 |
| BIZ-01 | DatabaseProgramRequirements returns correct SubjectName, SubjectName[], ExamLevel/null | unit | `php artisan test --compact --filter=DatabaseProgramRequirementsTest` | Wave 0 |
| BIZ-02 | ProgramRegistry.findByApplicant() returns DatabaseProgramRequirements | unit | `php artisan test --compact --filter=ProgramRegistryTest` | Wave 0 |
| TEST-04 | Unit tests for DatabaseProgramRequirements with mock models | unit | `php artisan test --compact --filter=DatabaseProgramRequirementsTest` | Wave 0 |
| TEST-05 | Unit tests for ProgramRegistry | unit | `php artisan test --compact --filter=ProgramRegistryTest` | Wave 0 |

### Sampling Rate

- **Per task commit:** `php artisan test --compact --filter=Services`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green + `vendor/bin/phpstan analyse` clean before `/gsd:verify-work`

### Wave 0 Gaps

- [ ] `app/Contracts/ProgramRequirementsInterface.php` — DOM-08 contract
- [ ] `app/Services/DatabaseProgramRequirements.php` — BIZ-01 implementation
- [ ] `app/Services/ProgramRegistry.php` — BIZ-02 registry
- [ ] `tests/Unit/Services/DatabaseProgramRequirementsTest.php` — TEST-04
- [ ] `tests/Unit/Services/ProgramRegistryTest.php` — TEST-05

---

## Sources

### Primary (HIGH confidence)

- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Models/Program.php` — confirms `final class`, `HasUuids`, `subjects()` HasMany relationship
- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Models/ProgramSubject.php` — confirms `final class`, `casts()` with SubjectName/RequirementType/ExamLevel
- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Models/Applicant.php` — confirms `final class`, `program()` BelongsTo relationship
- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Providers/AppServiceProvider.php` — confirms `Model::preventLazyLoading()` active unconditionally
- `/Users/otisz/Projects/oktatasi-hivatal/server/tests/Pest.php` — confirms `Tests\TestCase` only bound to `Feature`; Unit tests use PHPUnit base
- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Exceptions/UnknownProgramException.php` — confirms exception exists for unknown program case
- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Enums/RequirementType.php` — confirms `Mandatory` and `Elective` case names
- Pest 4 docs (via laravel-boost search-docs) — Mockery integration, `toBeInstanceOf()`, test helpers
- Laravel 12 docs (via laravel-boost search-docs mocking.md) — `Mockery::mock()`, final class limitations, `setRelation()` approach
- `.planning/codebase/CONVENTIONS.md` — confirms final_class rule, declare_strict_types, no fillable on models
- `.planning/STATE.md` — confirms key decisions: `Model::unguard()`, no `$with`, cascade delete, UUID PKs

### Secondary (MEDIUM confidence)

- `.planning/REQUIREMENTS.md` — authoritative method signatures for DOM-08, BIZ-01, BIZ-02
- `.planning/ROADMAP.md` — success criteria for each requirement in Phase 5

### Tertiary (LOW confidence)

- Collection::firstWhere() with enum comparison — behavior with `setAttribute()` in unit tests not empirically verified. May need `first(fn($s) => ...)` closure pattern if equality fails.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Mockery ^1.6 confirmed in composer.json; PHP interface is stable; no new dependencies
- Architecture (interface, service classes, directory structure): HIGH — Standard Laravel pattern; confirmed by existing project structure and ARCHITECTURE.md
- Pitfalls (final class mocking, preventLazyLoading in unit tests, Collection enum comparison): HIGH for first two (verified from code); MEDIUM for Collection enum comparison (needs empirical check)
- Test patterns (setRelation/setAttribute approach): HIGH — verified Laravel Eloquent API; used in Laravel's own test suite

**Research date:** 2026-02-26
**Valid until:** 2026-03-27 (stable domain — PHP interfaces, Mockery 1.6, Laravel 12 Eloquent internals are stable)
