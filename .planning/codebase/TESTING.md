# Testing Patterns

**Analysis Date:** 2026-02-28

## Test Framework

**Backend Runner:**
- Framework: Pest 4 with PHPUnit 12 backend
- Config: `pint.json` (code style, not test config)
- Run Commands:
  ```bash
  php artisan test                           # Run all tests
  php artisan test --compact                 # Condensed output
  php artisan test --filter=testName         # Run specific test
  ```

**Frontend Runner:**
- Not detected — no test framework configured (Vitest, Jest, etc.)
- Vue components not currently tested

**Assertion Library:**
- Backend: Pest's `expect()` function with fluent assertions

## Test File Organization

**Location (Backend):**
- Tests stored under `tests/` directory (separate from application code)
- Structure mirrors application code:
  - `tests/Unit/` — unit tests for services, value objects, enums
  - `tests/Feature/` — integration tests for API endpoints
  - `tests/TestCase.php` — base test class extending Laravel's TestCase

**Naming:**
- File names: `{ServiceName}Test.php` or `{FeatureName}Test.php`
- Examples:
  - `tests/Unit/Services/AdmissionScoringServiceTest.php`
  - `tests/Unit/ValueObjects/ExamResultTest.php`
  - `tests/Feature/Api/ApplicantScoreTest.php`

**Structure:**
```
tests/
├── Unit/
│   ├── Services/
│   │   ├── AdmissionScoringServiceTest.php
│   │   ├── BasePointCalculatorTest.php
│   │   └── BonusPointCalculatorTest.php
│   └── ValueObjects/
│       ├── ExamResultTest.php
│       ├── ScoreTest.php
│       └── LanguageCertificateTest.php
├── Feature/
│   └── Api/
│       └── ApplicantScoreTest.php
├── Pest.php
└── TestCase.php
```

## Test Structure

**Pest Test Suite Pattern:**

Global test configuration in `tests/Pest.php`:
```php
<?php
declare(strict_types=1);

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

function something(): void { ... }
```

**Unit Test Example** (`tests/Unit/Services/BasePointCalculatorTest.php`):
```php
<?php
declare(strict_types=1);

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Services\BasePointCalculator;
use App\ValueObjects\ExamResult;

it('calculates base points for typical inputs', function (): void {
    $calculator = new BasePointCalculator;
    $mandatory = new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, 90);
    $bestElective = new ExamResult(SubjectName::Informatics, ExamLevel::Intermediate, 95);

    expect($calculator->calculate($mandatory, $bestElective))->toBe(370);
});

it('calculates base points for mixed percentages', function (int $mandatory, int $elective, int $expected): void {
    // ... parametrized test with data
})->with([
    '50+50=200' => [50, 50, 200],
    '75+80=310' => [75, 80, 310],
    '100+90=380' => [100, 90, 380],
]);
```

**Feature Test Example** (`tests/Feature/Api/ApplicantScoreTest.php`):
```php
<?php
declare(strict_types=1);

use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

it('returns score 470 for applicant 1', function (): void {
    $this->getJson('/api/v1/applicants/'.Applicant::CASE_1_UUID.'/score')
        ->assertSuccessful()
        ->assertJson([
            'data' => [
                'osszpontszam' => 470,
                'alappont' => 370,
                'tobbletpont' => 100,
            ],
        ]);
});
```

**Patterns:**

1. **Test Function Naming:**
   - Use `it('description', function () { ... })` — Pest convention
   - Description should read naturally: `it('throws FailedExamException when percentage < 20')`

2. **Setup/Teardown:**
   - `beforeEach()` runs before each test
   - `RefreshDatabase` trait rolls back database for Feature tests
   - `$this->seed()` runs seeders to populate test data

3. **Assertion Pattern:**
   - Fluent chaining: `$response->assertSuccessful()->assertJson([...])`
   - Inline expectations: `expect($value)->toBe($expected)`

## Mocking

**Framework:** Mockery (via `mockery/mockery` package)

**Patterns:**

Creation:
```php
$registry = Mockery::mock(ProgramRegistryInterface::class);
```

Stubbing methods:
```php
$registry->shouldReceive('findByApplicant')
    ->once()
    ->andReturn($requirements);
```

Asserting expectations:
```php
$registry->shouldNotReceive('someMethod');  // Method must not be called
$registry->shouldReceive('getValue')
    ->once()                               // Expect exactly once
    ->andReturn(100);
```

**What to Mock:**
- External dependencies (interfaces): `ProgramRegistryInterface`, `BasePointCalculatorInterface`
- Database queries in Unit tests (Feature tests use real DB with `RefreshDatabase`)
- Services that are being tested in isolation

**What NOT to Mock:**
- Value Objects — construct real instances
- Enums — use directly: `SubjectName::Mathematics`
- Models in Feature tests — use with `RefreshDatabase`

**Example from `AdmissionScoringServiceTest.php`:**
```php
function makeExamResultRow(SubjectName $subject, ExamLevel $level, int $percentage): ApplicantExamResult
{
    $row = new ApplicantExamResult;
    $row->setAttribute('subject_name', $subject);
    $row->setAttribute('level', $level);
    $row->setAttribute('percentage', $percentage);

    return $row;
}

$applicant = makeApplicantWithExams([
    makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 50),
    makeExamResultRow(SubjectName::Mathematics, ExamLevel::Intermediate, 70),
]);
```

## Fixtures and Factories

**Test Data Pattern:**

Test helpers defined at top of test file as functions:
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

**Location:**
- Helpers defined inline in test file (not in separate factory directory currently)
- Can be reused across multiple tests in same file

**Database Seeders:**
- Feature tests use Laravel's seeding via `$this->seed()`
- Seeder populates test applicants with known UUIDs for assertion (e.g., `Applicant::CASE_1_UUID`)

## Coverage

**Requirements:** Not enforced — no coverage threshold configured

**View Coverage:**
```bash
php artisan test --coverage              # Generate and display coverage
php artisan test --coverage-report=html  # Generate HTML report
```

## Test Types

**Unit Tests:**
- Scope: Single class in isolation
- Approach: Mock all dependencies; test pure logic
- Examples:
  - `tests/Unit/Services/BasePointCalculatorTest.php` — calculator logic
  - `tests/Unit/ValueObjects/ExamResultTest.php` — validation in constructor
- No database, no HTTP

**Integration/Feature Tests:**
- Scope: Full request-response cycle; database interaction
- Approach: Real database with `RefreshDatabase`; real models; no mocking of application logic
- Examples:
  - `tests/Feature/Api/ApplicantScoreTest.php` — tests `/api/v1/applicants/{id}/score` endpoint
- Makes HTTP requests via `$this->getJson()`

**E2E Tests:**
- Not detected — no browser/E2E framework configured

## Common Patterns

**Parametrized Tests:**
```php
it('calculates base points for mixed percentages', function (int $mandatory, int $elective, int $expected): void {
    // ...
})->with([
    '50+50=200' => [50, 50, 200],
    '75+80=310' => [75, 80, 310],
]);
```

Test function receives parameters; `->with()` provides data sets.

**Exception Testing:**
```php
it('throws FailedExamException when an exam has percentage below 20', function (): void {
    $service = new AdmissionScoringService(...);
    $applicant = makeApplicantWithExams([
        makeExamResultRow(SubjectName::HungarianLanguageAndLiterature, ExamLevel::Intermediate, 15),
    ]);

    expect(fn () => $service->calculateForApplicant($applicant))
        ->toThrow(FailedExamException::class);
});
```

Pass callable to `expect()` to defer execution; `toThrow()` asserts exception type.

**Invalid Constructor Arguments:**
```php
it('throws InvalidArgumentException for out-of-range percentage', function (int $percentage): void {
    expect(fn () => new ExamResult(SubjectName::Mathematics, ExamLevel::Intermediate, $percentage))
        ->toThrow(InvalidArgumentException::class);
})->with([-1, 101]);
```

Value Objects validate in constructor; tests verify validation via `toThrow()`.

**HTTP Response Assertions:**
```php
$this->getJson('/api/v1/applicants/'.$id.'/score')
    ->assertSuccessful()
    ->assertJson([
        'data' => [
            'osszpontszam' => 470,
            'alappont' => 370,
            'tobbletpont' => 100,
        ],
    ]);

$this->getJson('/api/v1/applicants/'.$id.'/score')
    ->assertStatus(422)
    ->assertJson([
        'error' => 'nem lehetséges a pontszámítás...',
    ]);
```

Feature tests verify response status and JSON structure.

**Database Testing with Known UUIDs:**
Applicant model defines constants:
```php
final class Applicant extends Model
{
    public const string CASE_1_UUID = '0195a1b2-0000-7000-8000-000000000001';
    public const string CASE_2_UUID = '0195a1b2-0000-7000-8000-000000000002';
}
```

Tests reference: `Applicant::CASE_1_UUID` — provides stable IDs for assertions.

---

*Testing analysis: 2026-02-28*
