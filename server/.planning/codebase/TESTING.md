# Testing Patterns

**Analysis Date:** 2026-02-25

## Test Framework

**Runner:**
- Pest v4.4 (PHP testing framework)
- Configuration: `tests/Pest.php` (global configuration)
- Config file: `phpunit.xml.dist` (PHPUnit configuration)

**Assertion Library:**
- Pest expectations API (built on PHPUnit assertions)
- Extended with custom expectations defined in `tests/Pest.php`

**Run Commands:**
```bash
php artisan test --compact              # Run all tests
php artisan test --compact --filter=testName  # Run specific test
composer test                           # Run tests via composer script
```

**Test Output:**
- Compact mode enabled (minimal output)
- Colors enabled in console output

## Test File Organization

**Location:**
- Feature tests: `tests/Feature/` (test HTTP endpoints and application behavior)
- Unit tests: `tests/Unit/` (test individual classes and functions)

**Naming Convention:**
- Files: `ExampleTest.php` (PascalCase with "Test" suffix)
- Test directories mirror application structure when possible

**Structure:**
```
tests/
├── Feature/
│   ├── ExampleTest.php
│   └── [Feature tests for endpoints/controllers]
├── Unit/
│   ├── ExampleTest.php
│   └── [Unit tests for models/services]
├── Pest.php                 # Global Pest configuration
└── TestCase.php             # Base test case class
```

## Test Case Setup

**Base Test Class:**
- Location: `tests/TestCase.php`
- Extends: `Illuminate\Foundation\Testing\TestCase`
- Used by: All feature tests (configured in `Pest.php`)

**Configuration (Pest.php):**
```php
pest()->extend(Tests\TestCase::class)
    ->in('Feature');
```

Feature tests automatically extend `TestCase`. Unit tests are independent.

## PHPUnit Configuration

**Environment Variables for Testing:**
- `APP_ENV`: testing
- `APP_MAINTENANCE_DRIVER`: file
- `BCRYPT_ROUNDS`: 4 (lower rounds for speed)
- `BROADCAST_CONNECTION`: null
- `CACHE_STORE`: array (in-memory)
- `DB_CONNECTION`: sqlite
- `DB_DATABASE`: :memory: (in-memory SQLite for isolation)
- `MAIL_MAILER`: array (no actual email sending)
- `QUEUE_CONNECTION`: sync (synchronous job execution)
- `SESSION_DRIVER`: array (in-memory sessions)
- `PULSE_ENABLED`: false
- `TELESCOPE_ENABLED`: false
- `NIGHTWATCH_ENABLED`: false

**Test Suites:**
```xml
<testsuite name="Unit">
    <directory>tests/Unit</directory>
</testsuite>
<testsuite name="Feature">
    <directory>tests/Feature</directory>
</testsuite>
```

**Coverage:**
- Coverage reports include: `app/` directory only
- Tests excluded from coverage

## Test Structure

**Feature Test Example (from codebase):**
```php
<?php

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
```

**Unit Test Example (from codebase):**
```php
<?php

test('that true is true', function () {
    expect(true)->toBeTrue();
});
```

**Suite Organization Pattern:**
- One test function per test method
- Test names should describe what is being tested
- Closure-based syntax using Pest's `test()` function

## Pest Expectations API

**Available Expectations:**
- `expect($value)->toBeTrue()` - Assert value is true
- `expect($value)->toBe($expected)` - Assert equality
- Custom extensions can be added in `Pest.php`

**Custom Expectations (from Pest.php):**
```php
expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});
```

## HTTP Testing (Feature Tests)

**Available Methods:**
- `$this->get('/path')` - Perform GET request
- `$response->assertStatus(200)` - Assert HTTP status code
- `$this->post()`, `$this->put()`, `$this->patch()`, `$this->delete()` - Other HTTP methods

**Test Case Access:**
- Feature tests have access to `$this` with HTTP testing methods
- Automatically provided by extending `TestCase` and Laravel's testing traits

## Database Testing

**Isolation:**
- In-memory SQLite database (`:memory:`)
- Fresh database per test suite run
- No data persists between test runs

**Migrations:**
- Not explicitly configured in `Pest.php`
- Comment suggests `RefreshDatabase` trait could be enabled:
```php
// ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
```

**Factories:**
- Pest recognizes Laravel factories via Pest plugin
- Factory location: `database/factories/UserFactory.php`
- Usage in tests: `User::factory()->create([...])`

## Mocking

**Framework:**
- Mockery v1.6 (configured in composer.json)

**Patterns:**
- Mockery is available but no examples in codebase yet
- Standard Mockery usage: `\Mockery::mock()` for creating mock objects

**What to Mock:**
- External API calls
- Database queries (when testing service layer in isolation)
- File system operations

**What NOT to Mock:**
- Eloquent models (use factories instead)
- Laravel core services (test with real instances)

## Test Data & Fixtures

**Factories:**
- Location: `database/factories/`
- Example: `UserFactory.php`

**Factory Pattern (from UserFactory.php):**
```php
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
```

**Factory States:**
- Define variations using `state()` method
- Example: `unverified()` method returns modified state
- Usage in tests: `User::factory()->unverified()->create()`

**Faker:**
- Provider: `fakerphp/faker` v1.23
- Usage: `fake()->methodName()` or `$this->faker->methodName()`
- Examples: `fake()->name()`, `fake()->unique()->safeEmail()`

## Database Seeding

**Location:** `database/seeders/DatabaseSeeder.php`

**Pattern:**
```php
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
```

## Test Helpers & Utilities

**Global Functions (from Pest.php):**
```php
function something()
{
    // Custom test helpers can be defined here
}
```

**Available via TestCase:**
- HTTP testing methods (`$this->get()`, `$this->post()`)
- Database assertions
- Authentication helpers

## Coverage

**Requirements:**
- No explicit coverage target enforced
- PHPUnit configured to track coverage on `app/` only

**View Coverage:**
```bash
php artisan test --coverage
```

## Test Types & Strategies

**Feature Tests:**
- Scope: Full request lifecycle through controllers
- Approach: Test HTTP endpoints and application workflows
- Location: `tests/Feature/`
- Use `TestCase` base class with HTTP testing methods

**Unit Tests:**
- Scope: Individual classes and functions in isolation
- Approach: Test specific logic without dependencies
- Location: `tests/Unit/`
- Use Pest closure-based syntax with expectations

**Integration Tests:**
- Not explicitly configured but can use feature tests
- Can test interactions between multiple components

**E2E Tests:**
- Not configured (would require browser testing framework)

## Async Testing

**Pattern:**
- Not demonstrated in codebase
- For async code, use Pest's support or PHPUnit's async capabilities

## Error Testing

**Pattern:**
```php
test('condition handling', function () {
    $response = $this->get('/path');
    $response->assertStatus(404);
});
```

- Assert HTTP status codes for error conditions
- Use Pest expectations for value assertions

## Pest Configuration Details

**File:** `tests/Pest.php`

**Features:**
- Extends `TestCase` for Feature tests only
- Custom expectation `toBeOne` defined for demonstration
- Helper function `something()` available globally in tests
- Fully commented with documentation on configuration options

---

*Testing analysis: 2026-02-25*
