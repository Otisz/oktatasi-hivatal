# Coding Conventions

**Analysis Date:** 2026-02-25

## Naming Patterns

**Files:**
- Controllers: `Controller.php` (singular nouns, PascalCase)
- Models: `User.php` (singular nouns, PascalCase)
- Factories: `UserFactory.php` (Model name + "Factory")
- Seeders: `DatabaseSeeder.php` (PascalCase)
- Tests: `ExampleTest.php` (feature/unit tests follow PascalCase with Test suffix)

**Functions:**
- Private/Protected methods: `camelCase()` - e.g., `register()`, `boot()`
- Public methods: `camelCase()` - e.g., `definition()`, `unverified()`

**Variables:**
- camelCase for all variables - e.g., `$password`, `$email`, `$fillable`
- Plural form for collections/arrays - e.g., `$fillable`, `$hidden`

**Types & Classes:**
- PascalCase for all class names - e.g., `UserFactory`, `DatabaseSeeder`, `UserFactory`
- Enum keys: TitleCase - e.g., `FavoritePerson`, `Monthly`

## Code Style

**Formatting:**
- Indentation: 4 spaces
- Line endings: LF
- Charset: UTF-8
- Final newline: Always insert
- Trim trailing whitespace: Enabled

**Linting & Formatting:**
- Tool: Laravel Pint (PHP code formatter)
- Configuration: `pint.json`
- Preset: `laravel` (Laravel's opinionated defaults)
- Cache: Stored in `storage/pint.cache.json`

**Pint Rules (customized):**
- `declare_strict_types`: true - All files must declare strict types
- `final_class`: true - Classes are final by default (prevents unintended extension)
- `yoda_style`: true - Use Yoda comparisons (literal on left side)
- `fully_qualified_strict_types`: true - Use fully qualified type names
- `strict_comparison`: true - Use `===` and `!==` instead of `==` and `!=`
- `ternary_to_null_coalescing`: true - Convert `?:` to `??` operator
- `void_return`: true - Add `void` return type to void methods
- `not_operator_with_successor_space`: false - No space after `!` operator
- `phpdoc_separation`: false - No blank line between short description and long description in PHPDoc

**EditorConfig:**
- All files: charset=utf-8, LF line endings, 4-space indent
- YAML files: 2-space indent
- Markdown files: No trailing whitespace trimming

## Import Organization

**Order:**
1. Built-in PHP namespace declarations and use statements
2. External dependencies (Laravel core, vendor packages)
3. Application-specific imports (App\*)
4. Database imports (Database\*)

**Example from codebase:**
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
```

**Path Aliases:**
None configured; uses PSR-4 autoloading with namespaces.

## Type Declarations

**Return Types:**
- Always include explicit return type declarations
- Example: `public function register(): void`, `public function casts(): array`

**Method Parameters:**
- Use appropriate type hints
- Optional parameters use `?Type` syntax
- Example from codebase: `public function definition(): array`

## Comments & Documentation

**When to Comment:**
- Avoid inline comments unless logic is exceptionally complex
- Prefer PHPDoc blocks for documentation

**PHPDoc Blocks:**
- Required on all public methods and classes
- Use `@var` for property documentation
- Use `@return` for return types with descriptions
- Use `@extends` for factory classes and trait extensions
- Include array shape type definitions when appropriate

**Example from UserFactory:**
```php
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
```

**Example from User Model:**
```php
/**
 * The attributes that are mass assignable.
 *
 * @var list<string>
 */
protected $fillable = [
    'name',
    'email',
    'password',
];
```

## Function Design

**Size:** Compact, single responsibility functions preferred

**Parameters:**
- Use constructor property promotion in `__construct()` where appropriate
- Example: `public function __construct(public ServiceClass $service) { }`
- No empty constructors with zero parameters unless private

**Return Values:**
- Always declare return types
- Use proper type hints (array, void, bool, etc.)

## Eloquent & Database

**Casts:**
- Use `casts()` method on models rather than `$casts` property
- Example from `User.php`:
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
```

**Relationships:**
- Use full return type hints on relationship methods
- Always use Eloquent relationships, never raw queries

**Model Protection:**
- Models prevent lazy loading: `Model::preventLazyLoading()`
- Models prevent accessing missing attributes: `Model::preventAccessingMissingAttributes()`
- Configured in `AppServiceProvider::register()` - `app/Providers/AppServiceProvider.php`

## Error Handling

**Strategy:**
- Exceptions are configured in `bootstrap/app.php` via `withExceptions()`
- Currently uses default Laravel exception handling

**Patterns:**
- Use try-catch for external service calls
- Let Laravel's exception handler manage HTTP responses
- No custom error handling methods in base Controller

## Module Design

**Exports:**
- Classes export via namespaces and PSR-4 autoloading
- No barrel files used (no index.php re-exports)

**Class Structure:**
- Abstract base classes for shared functionality (e.g., `Controller`)
- Traits for shared behavior (`HasFactory`, `Notifiable`)

## Strict Typing & Quality

**Type Safety:**
- All files must declare `declare(strict_types=1);`
- Strict comparison enforced (`===` and `!==`)
- PHPStan Level 7 type checking required

**Static Analysis:**
- PHPStan: Level 7 (high strictness)
- Configuration: `phpstan.neon`
- Includes Larastan extension for Laravel-specific checks
- Excludes tests from static analysis

**Code Refactoring:**
- Rector automated refactoring enabled
- Configuration: `rector.php`
- Targets Laravel 12.0+
- Paths: `app/`, `database/`, `routes/`, `tests/`

---

*Convention analysis: 2026-02-25*
