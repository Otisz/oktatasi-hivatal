# Phase 3: Database Schema and Models - Research

**Researched:** 2026-02-25
**Domain:** Laravel 12 migrations (UUID PKs), Eloquent models (enum casting, HasUuids, typed relationships), model factories with states
**Confidence:** HIGH

## Summary

Phase 3 creates the five-table persistence layer: migrations, Eloquent models with typed relationships and enum casts, and factories with named states. This phase has no external dependencies beyond what Laravel 12 already provides — everything needed (HasUuids, foreignUuid, enum casting via `casts()`, factory state pattern) is built into the framework.

The critical architectural divergence from IMPLEMENTATION.md is that CONTEXT.md overrides the PRD's `bigint` PKs with ordered UUIDs (`HasUuids` trait + `$table->uuid('id')->primary()`). Laravel 12's `HasUuids` now produces UUIDv7 (ordered, lexicographically sortable) by default, which replaces the old `Str::orderedUuid()` (UUIDv4 ordered) approach. Models must use the `HasUuids` trait; migrations use `$table->uuid('id')->primary()` and `$table->foreignUuid('...')`. Foreign key columns must use `->constrained()->cascadeOnDelete()` to enforce referential integrity at the DB level.

`preventLazyLoading()` is already active in `AppServiceProvider::register()` for all environments, not just non-production. This means relationship access without eager loading will throw `LazyLoadingViolationException` during tests — factories and tests must chain `->with()` explicitly or use `->load()` after retrieval.

**Primary recommendation:** Create all five migrations as separate files in dependency order (programs → program_subjects → applicants → applicant_exam_results → applicant_bonus_points), use `php artisan make:model --factory --migration` for each, add `HasUuids` trait, define `casts()` with Phase 1 enums, and declare all relationship methods with full return type hints.

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

#### Enum Casting Strategy
- Store all enum-backed columns as plain varchar (string) columns in migrations
- Cast to Phase 1 PHP enums at the Eloquent model layer using Laravel's enum casting
- subject_name columns cast to SubjectName enum, level columns cast to ExamLevel enum
- Create a new RequirementType enum (Mandatory, Elective) for program_subjects.requirement_type
- Cast applicant_bonus_points.type to LanguageCertificateType enum (B2/C1)
- Cast applicant_bonus_points.category as plain string (no enum needed)
- Cast program_subjects.required_level to ExamLevel enum (nullable)

#### Primary Key Strategy
- All five tables use ordered UUIDs (Laravel's HasUuids trait with Str::orderedUuid())
- Migrations use `$table->uuid('id')->primary()` instead of bigIncrements
- Foreign key columns use `$table->foreignUuid('program_id')` / `$table->foreignUuid('applicant_id')`
- All models include the `HasUuids` trait — route model binding resolves by UUID automatically
- Define named UUID constants on models for seeded test data (e.g., Applicant::CASE_1_UUID) — Phase 4 seeder and Phase 8 tests reference applicants by these constants
- API routes use UUID in URL: `/api/v1/applicants/{uuid}/score`

#### Schema Conventions
- Include Laravel timestamps (created_at, updated_at) on all tables
- Foreign keys only — no CHECK constraints or unique constraints; validation lives in Value Objects and service layer
- Default string(255) for all varchar columns (university, faculty, name, subject_name, etc.)
- Use unsignedTinyInteger for percentage column on applicant_exam_results (documents 0-100 range intent)

#### Factory Design
- Factories for all five models (Program, ProgramSubject, Applicant, ApplicantExamResult, ApplicantBonusPoint)
- Include named states for common testing scenarios (e.g., ->failingExam(), ->advancedLevel(), ->b2Certificate())
- Explicit relation creation — no afterCreating callbacks; tests chain ->has() for full control
- Factory defaults use enum values (e.g., SubjectName::cases() random element) rather than hardcoded strings

#### Relationship Loading
- No default $with on any model — all eager loading is explicit via ->with() in controllers/services
- Enable Model::preventLazyLoading() in AppServiceProvider to catch N+1 issues during development
- Standard Laravel naming conventions: examResults(), bonusPoints(), program(), subjects()
- Full return type hints on all relationship methods (BelongsTo, HasMany, etc.)

### Claude's Discretion
- Exact factory state names and what scenarios they cover beyond the obvious ones
- Migration ordering (single migration or one per table)
- Whether to use fillable or guarded on models
- PHPDoc blocks on model properties for IDE support

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DB-01 | Programs table migration (id, university, faculty, name) | uuid PK, string columns, timestamps; foreignUuid not needed on this table |
| DB-02 | ProgramSubjects table migration (id, program_id FK, subject_name, requirement_type, required_level nullable) | foreignUuid('program_id')->constrained()->cascadeOnDelete(); nullable string for required_level; string(255) for all varchar |
| DB-03 | Applicants table migration (id, program_id FK) | foreignUuid('program_id')->constrained()->cascadeOnDelete(); minimal table by design |
| DB-04 | ApplicantExamResults table migration (id, applicant_id FK, subject_name, level, percentage) | foreignUuid + constrained; unsignedTinyInteger for percentage |
| DB-05 | ApplicantBonusPoints table migration (id, applicant_id FK, category, type, language nullable) | foreignUuid + constrained; nullable string for language |
| DB-06 | Eloquent models for all 5 tables with typed relationships and eager loading support | HasUuids trait; casts() method with Phase 1 enums; typed BelongsTo/HasMany return hints; no $with property |
| DB-07 | Factories for Applicant, ApplicantExamResult, ApplicantBonusPoint | CONTEXT.md also requires Program and ProgramSubject factories; named states per locked decisions; enum-based defaults |
</phase_requirements>

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| laravel/framework | 12.53.0 | Migrations, Eloquent ORM, HasUuids, factory system | Already installed; everything needed is built in |
| PHP | 8.5.2 | Backed enum casting, typed properties, constructor promotion | Already installed |
| laravel/pint | 1.27.1 | Code style (final_class, declare_strict_types, void_return) | Already configured in pint.json |
| larastan/larastan | 3.9.2 | Static analysis level 7 on app/ | Already configured in phpstan.neon |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| pestphp/pest | 4.4.1 | Test runner | Phase 3 has no unit tests per TDD order, but `php artisan migrate:fresh` smoke-test validates all migrations |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| HasUuids (UUIDv7) | HasVersion4Uuids or ULID | UUIDs are locked by CONTEXT.md; UUIDv7 is the current Laravel 12 default — no reason to deviate |
| One migration per table | Single mega-migration | One per table aligns with existing project convention (3 separate migration files already exist) and allows cleaner rollback |
| fillable array | `Model::unguard()` | `Model::unguard()` is already called in AppServiceProvider — no fillable/$guarded needed on models |

**Installation:** No new packages needed.

---

## Architecture Patterns

### Recommended Project Structure

```
app/
├── Enums/
│   ├── RequirementType.php     (NEW — created in this phase)
│   ├── ExamLevel.php           (Phase 1)
│   ├── LanguageCertificateType.php  (Phase 1)
│   └── SubjectName.php         (Phase 1)
└── Models/
    ├── Applicant.php
    ├── ApplicantBonusPoint.php
    ├── ApplicantExamResult.php
    ├── Program.php
    └── ProgramSubject.php

database/
├── factories/
│   ├── ApplicantBonusPointFactory.php
│   ├── ApplicantExamResultFactory.php
│   ├── ApplicantFactory.php
│   ├── ProgramFactory.php
│   └── ProgramSubjectFactory.php
└── migrations/
    ├── xxxx_create_programs_table.php
    ├── xxxx_create_program_subjects_table.php
    ├── xxxx_create_applicants_table.php
    ├── xxxx_create_applicant_exam_results_table.php
    └── xxxx_create_applicant_bonus_points_table.php
```

### Pattern 1: UUID Primary Key Migration

**What:** All tables use `$table->uuid('id')->primary()` as PK instead of `$table->id()`. Foreign keys use `$table->foreignUuid('...')` with `->constrained()->cascadeOnDelete()`.

**When to use:** All five migrations in this phase.

**Example:**
```php
// Source: Laravel 12 docs — migrations.md "uuid()" and "foreignUuid()"
Schema::create('program_subjects', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('program_id')->constrained()->cascadeOnDelete();
    $table->string('subject_name');
    $table->string('requirement_type');
    $table->string('required_level')->nullable();
    $table->timestamps();
});
```

### Pattern 2: HasUuids Model with Enum Casts

**What:** Model uses `HasUuids` trait (generates UUIDv7 by default in Laravel 12). Casts are defined in the `casts()` method (project convention from existing User model). Relationship methods have full return type hints.

**When to use:** All five models.

**Example:**
```php
// Source: Laravel 12 docs — eloquent.md "UUID and ULID Keys", eloquent-mutators.md "Enum Casting"
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApplicantExamResult extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantExamResultFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'subject_name' => SubjectName::class,
            'level' => ExamLevel::class,
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
```

**Critical note on Laravel 12:** `HasUuids` now generates UUIDv7 (not UUIDv4 ordered). CONTEXT.md says "Str::orderedUuid()" but that is UUIDv4 ordered — the current `HasUuids` default achieves the same lexicographic-sorting goal with UUIDv7. No override needed; use `HasUuids` as-is.

### Pattern 3: Nullable Enum Cast

**What:** When a column is nullable (e.g., `required_level` on program_subjects), the enum cast still works — Eloquent passes `null` through without calling `from()`.

**When to use:** `program_subjects.required_level` (nullable ExamLevel), `applicant_bonus_points.language` (plain nullable string, no cast needed).

**Example:**
```php
protected function casts(): array
{
    return [
        'subject_name' => SubjectName::class,
        'requirement_type' => RequirementType::class,
        'required_level' => ExamLevel::class,  // nullable column — null stays null
    ];
}
```

### Pattern 4: Factory State Methods

**What:** Named state methods return `$this->state(fn(array $attributes) => [...])`. Factory defaults use `fake()->randomElement(EnumClass::cases())` to pick valid enum values.

**When to use:** All factories in this phase.

**Example:**
```php
// Source: Laravel 12 docs — eloquent-factories.md "Factory States"
public function definition(): array
{
    return [
        'applicant_id' => Applicant::factory(),
        'subject_name' => fake()->randomElement(SubjectName::cases())->value,
        'level' => fake()->randomElement(ExamLevel::cases())->value,
        'percentage' => fake()->numberBetween(20, 100),
    ];
}

public function failingExam(): static
{
    return $this->state(fn (array $attributes) => [
        'percentage' => fake()->numberBetween(0, 19),
    ]);
}

public function advancedLevel(): static
{
    return $this->state(fn (array $attributes) => [
        'level' => ExamLevel::Advanced->value,
    ]);
}
```

**Note:** Store enum `.value` (the string) in factory arrays since the DB column holds a string. The model's `casts()` will hydrate the enum when reading.

### Pattern 5: UUID Constants on Models for Seeded Data

**What:** Public const strings on the Applicant model that the Phase 4 seeder writes as `id` and Phase 8 tests reference by name.

**When to use:** Applicant model (Phase 4 seeder needs to write known UUIDs; Phase 8 tests need to call `/api/v1/applicants/{uuid}/score`).

**Example:**
```php
final class Applicant extends Model
{
    use HasFactory, HasUuids;

    public const string CASE_1_UUID = '0195a1b2-0000-7000-8000-000000000001';
    public const string CASE_2_UUID = '0195a1b2-0000-7000-8000-000000000002';
    public const string CASE_3_UUID = '0195a1b2-0000-7000-8000-000000000003';
    public const string CASE_4_UUID = '0195a1b2-0000-7000-8000-000000000004';

    // ...
}
```

**Note on UUID values:** Use any valid UUID format strings for the constants; they just need to be consistent between the seeder (Phase 4) and the tests (Phase 8). UUIDv7 format is `0XXXXXXX-XXXX-7XXX-XXXX-XXXXXXXXXXXX`.

### Pattern 6: RequirementType Enum (New in This Phase)

**What:** A new `App\Enums\RequirementType` backed enum with two cases: `Mandatory` and `Elective`. CONTEXT.md locked this as a new enum to create.

**When to use:** Cast on `ProgramSubject::$requirement_type`. Also used by Phase 5 `DatabaseProgramRequirements` to filter subjects.

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum RequirementType: string
{
    case Mandatory = 'mandatory';
    case Elective = 'elective';
}
```

### Anti-Patterns to Avoid

- **`$table->id()` instead of `$table->uuid('id')->primary()`:** Using the default auto-increment PK is incompatible with the HasUuids trait and will cause insert failures.
- **`$table->foreignId()` for FK columns:** Must use `$table->foreignUuid()` when the referenced table uses UUID PKs.
- **Accessing enum `.value` in the wrong place:** Factory `definition()` arrays should store the raw string value (e.g., `ExamLevel::Advanced->value`) since the database column is a string. The cast hydrates the enum on read.
- **Storing `fake()->randomElement(SubjectName::cases())` (the enum object) instead of `->value`:** Would store an object representation, not the string.
- **`$with` property on models:** CONTEXT.md locked no default eager loading; use explicit `->with()` in callers.
- **afterCreating callbacks in factories:** CONTEXT.md locked explicit `->has()` chaining in tests instead.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| UUID primary key generation | Custom `boot()` hook with `Str::orderedUuid()` | `HasUuids` trait | Built into Laravel; generates UUIDv7 automatically; route model binding resolves UUIDs transparently |
| Enum ↔ DB string mapping | Custom accessor/mutator | `casts()` with `EnumClass::class` | Native Laravel enum casting since Laravel 9; handles null, `from()`, `tryFrom()` internally |
| FK referential integrity at DB level | Application-level checks | `->constrained()->cascadeOnDelete()` | Database-level enforcement is more reliable; SQLite supports FK constraints when enabled |
| Factory relationships | Manual `create()` calls and manual ID assignment | `->for(ParentModel::factory())` or `Applicant::factory()` as FK value | Laravel factory `for()` / `has()` / field-as-factory pattern is cleaner and avoids ordering issues |

**Key insight:** Laravel 12's factory + HasUuids + enum casting stack handles all the boilerplate automatically. The only custom code needed is business-specific named state methods.

---

## Common Pitfalls

### Pitfall 1: `HasUuids` vs IMPLEMENTATION.md's `bigIncrements`

**What goes wrong:** IMPLEMENTATION.md Section 2 uses `bigIncrements` PKs and `foreignId()` FKs. CONTEXT.md overrides this with UUIDs. If migrations use `$table->id()` (bigIncrements), the HasUuids trait will still try to insert UUID strings into an integer column and fail.
**Why it happens:** IMPLEMENTATION.md was written before the CONTEXT.md discussion finalized UUIDs.
**How to avoid:** Always use `$table->uuid('id')->primary()` and `$table->foreignUuid()`. CONTEXT.md decisions take precedence over IMPLEMENTATION.md.
**Warning signs:** Eloquent insert fails with a database type mismatch or truncation error.

### Pitfall 2: Laravel 12 HasUuids Generates UUIDv7, Not UUIDv4 Ordered

**What goes wrong:** CONTEXT.md mentions "Str::orderedUuid()" (which is UUIDv4 with timestamp prefix). In Laravel 12, `HasUuids` was updated to generate UUIDv7 instead. The upgrade guide notes this change. If code explicitly overrides `newUniqueId()` to call `Str::orderedUuid()`, it uses the old algorithm; if it relies on the default `HasUuids`, it uses UUIDv7.
**Why it happens:** Laravel 12 changed the default UUID generation in `HasUuids` from ordered UUIDv4 to UUIDv7.
**How to avoid:** Use `HasUuids` as-is (no `newUniqueId()` override). Both achieve lexicographic ordering; UUIDv7 is the current standard. The CONTEXT.md note about `Str::orderedUuid()` is a reference to the ordered behavior goal, not a mandate to call that specific method.
**Warning signs:** None at runtime — both work. But overriding unnecessarily adds dead code.

### Pitfall 3: Enum Cast on Nullable Column with `from()` Behavior

**What goes wrong:** `program_subjects.required_level` is nullable. If Eloquent tries to cast `null` through `ExamLevel::from(null)`, it would throw a `ValueError`.
**Why it happens:** Standard concern when applying enum casts to nullable columns.
**How to avoid:** Laravel's enum cast implementation handles this correctly — when the column value is `null`, the cast returns `null` without calling `from()`. No special handling needed. Verified from Laravel 12 docs.
**Warning signs:** Not an issue with standard nullable string columns and enum casts in Laravel 12.

### Pitfall 4: preventLazyLoading() Is Already Enabled Unconditionally

**What goes wrong:** `AppServiceProvider::register()` already calls `Model::preventLazyLoading()` with no condition (not gated to non-production). Any test or code that accesses a relationship without eager-loading will throw `LazyLoadingViolationException`, including factories that call `$model->relationship->...` after creating.
**Why it happens:** The service provider was already configured this way; CONTEXT.md confirms it.
**How to avoid:** In tests, always eager-load needed relationships (`->load('examResults')` or pass `->with('examResults')` at query time). Factory `definition()` methods that assign FK as `Applicant::factory()` do NOT trigger lazy loading — lazy loading only triggers when accessing a relationship on an already-loaded model without having eager-loaded it.
**Warning signs:** Tests throw `Illuminate\Database\LazyLoadingViolationException` with message mentioning the relationship name.

### Pitfall 5: Pint's `final_class` Rule on Models

**What goes wrong:** Pint is configured with `"final_class": true`. It will add `final` to all model classes unless they already declare `final` or `abstract`. Models can be `final` in this project (no polymorphism or model inheritance), but confirm that no other phase extends these models.
**Why it happens:** Same Pint rule as Phase 1 for exceptions.
**How to avoid:** Declare models as `final` explicitly to avoid Pint modifying them after creation. All 5 domain models are leaf classes in this project.
**Warning signs:** Pint diff shows `final` being added to a model that a downstream phase tries to extend.

### Pitfall 6: Missing `declare(strict_types=1)` in Migration Files

**What goes wrong:** Migrations use `return new class extends Migration { ... }` — anonymous class syntax. Pint's `declare_strict_types` rule applies to migration files too.
**Why it happens:** `php artisan make:migration` stubs may not include `declare(strict_types=1)`.
**How to avoid:** Add `declare(strict_types=1);` after `<?php` in each generated migration file before running Pint.
**Warning signs:** `vendor/bin/pint --dirty` shows a diff adding the declaration to migration files.

### Pitfall 7: `Model::unguard()` Already Called — No fillable Needed

**What goes wrong:** Writing `protected $fillable = [...]` arrays on models when `Model::unguard()` is already called in `AppServiceProvider::register()`.
**Why it happens:** Default Laravel model stubs include fillable; developers add it by habit.
**How to avoid:** Omit `$fillable` and `$guarded` from all models in this project. `Model::unguard()` is a global setting. Adding `$fillable` would be redundant and conflict with the project's intentional choice.
**Warning signs:** None — it is silently redundant but not harmful.

### Pitfall 8: Factory Stores Enum Object Instead of String Value

**What goes wrong:** `fake()->randomElement(SubjectName::cases())` returns a `SubjectName` enum instance, not a string. Storing it in the `definition()` array without calling `->value` will fail when Eloquent tries to insert the object into a string column.
**Why it happens:** Easy mistake when building enum-based factory defaults.
**How to avoid:** Always call `->value` on the enum: `fake()->randomElement(SubjectName::cases())->value`.
**Warning signs:** PDO/SQLite error like "Object of class App\Enums\SubjectName could not be converted to string."

---

## Code Examples

Verified patterns from official sources:

### Migration: programs Table

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('university');
            $table->string('faculty');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
```

### Migration: applicant_exam_results Table

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_exam_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('subject_name');
            $table->string('level');
            $table->unsignedTinyInteger('percentage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_exam_results');
    }
};
```

### Model: Program

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
    use HasFactory, HasUuids;

    /** @return HasMany<ProgramSubject, $this> */
    public function subjects(): HasMany
    {
        return $this->hasMany(ProgramSubject::class);
    }

    /** @return HasMany<Applicant, $this> */
    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }
}
```

### Model: Applicant

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Applicant extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory, HasUuids;

    public const string CASE_1_UUID = '0195a1b2-0000-7000-8000-000000000001';
    public const string CASE_2_UUID = '0195a1b2-0000-7000-8000-000000000002';
    public const string CASE_3_UUID = '0195a1b2-0000-7000-8000-000000000003';
    public const string CASE_4_UUID = '0195a1b2-0000-7000-8000-000000000004';

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return HasMany<ApplicantExamResult, $this> */
    public function examResults(): HasMany
    {
        return $this->hasMany(ApplicantExamResult::class);
    }

    /** @return HasMany<ApplicantBonusPoint, $this> */
    public function bonusPoints(): HasMany
    {
        return $this->hasMany(ApplicantBonusPoint::class);
    }
}
```

### Model: ProgramSubject (with nullable enum cast)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProgramSubject extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramSubjectFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'subject_name' => SubjectName::class,
            'requirement_type' => RequirementType::class,
            'required_level' => ExamLevel::class,
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
```

### Model: ApplicantBonusPoint (type cast to LanguageCertificateType)

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LanguageCertificateType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ApplicantBonusPoint extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantBonusPointFactory> */
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'type' => LanguageCertificateType::class,
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }
}
```

### RequirementType Enum (new in this phase)

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum RequirementType: string
{
    case Mandatory = 'mandatory';
    case Elective = 'elective';
}
```

### Factory: ApplicantExamResultFactory with States

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ApplicantExamResult>
 */
final class ApplicantExamResultFactory extends Factory
{
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'subject_name' => fake()->randomElement(SubjectName::cases())->value,
            'level' => fake()->randomElement(ExamLevel::cases())->value,
            'percentage' => fake()->numberBetween(20, 100),
        ];
    }

    public function failingExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'percentage' => fake()->numberBetween(0, 19),
        ]);
    }

    public function advancedLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => ExamLevel::Advanced->value,
        ]);
    }

    public function forSubject(SubjectName $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'subject_name' => $subject->value,
        ]);
    }
}
```

### Factory: ApplicantBonusPointFactory with States

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LanguageCertificateType;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ApplicantBonusPoint>
 */
final class ApplicantBonusPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'category' => 'Nyelvvizsga',
            'type' => fake()->randomElement(LanguageCertificateType::cases())->value,
            'language' => fake()->randomElement(['angol', 'német', 'francia', 'olasz', 'orosz', 'spanyol']),
        ];
    }

    public function b2Certificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LanguageCertificateType::UpperIntermediate->value,
        ]);
    }

    public function c1Certificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => LanguageCertificateType::Advanced->value,
        ]);
    }
}
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `$table->id()` bigint PK | `$table->uuid('id')->primary()` + HasUuids | Project decision (CONTEXT.md) | Route model binding works automatically with UUIDs; no `$keyType = 'string'` needed in Laravel 12 |
| `HasUuids` generating ordered UUIDv4 (`Str::orderedUuid()`) | `HasUuids` generating UUIDv7 | Laravel 12 upgrade | UUIDv7 is the new standard for time-ordered UUIDs; same lexicographic ordering benefit |
| `$casts` property array | `casts()` method | Laravel 11+ | Project convention from existing User model; method-based casts is the current Laravel approach |
| `protected $fillable` array | `Model::unguard()` in AppServiceProvider | Project-level decision | Already configured; no per-model fillable arrays needed |
| `HasVersion7Uuids` trait | `HasUuids` | Laravel 12 | `HasVersion7Uuids` was removed; `HasUuids` now IS the UUIDv7 implementation |

**Deprecated/outdated:**
- `$table->foreignId()`: Use `$table->foreignUuid()` when referenced table has UUID PK
- `HasVersion7Uuids`: Removed in Laravel 12 — use `HasUuids`
- `HasVersion4Uuids`: Still available for old UUIDv4 behavior if needed, but not needed here

---

## Open Questions

1. **UUID constant values on Applicant model**
   - What we know: CONTEXT.md says define `Applicant::CASE_1_UUID` through `CASE_4_UUID` as constants for Phase 4/8 use.
   - What's unclear: The exact string values to use. They just need to be valid UUID format strings; the seeder will insert them as the `id` column value.
   - Recommendation: Use any valid UUIDv7-format strings or well-formatted UUID strings. The planner can pick simple memorable values (e.g., `'00000000-0000-7000-8000-000000000001'`). The only constraint is they must be valid UUIDs that `HasUuids` route binding will accept.

2. **Whether Program and ProgramSubject also need UUID constants for seeded data**
   - What we know: CONTEXT.md only mentions constants on Applicant.
   - What's unclear: Phase 4 seeder also needs to reference seeded programs; Phase 5 `DatabaseProgramRequirements` looks up by the program associated with an applicant (no direct program UUID needed in tests).
   - Recommendation: Only Applicant needs UUID constants for Phase 8 HTTP tests. Program seeder can use any UUIDs since tests reach programs through applicants.

3. **PHPStan level 7 generic return types for HasMany/BelongsTo**
   - What we know: Larastan 3.x adds generic type support for relationships. Laravel 12's type annotations ship with `HasMany<TRelatedModel, TDeclaringModel>`.
   - What's unclear: Whether `HasMany<ApplicantExamResult, $this>` is the correct Larastan 3 syntax or if `HasMany<ApplicantExamResult>` suffices.
   - Recommendation: Use `HasMany<ApplicantExamResult, $this>` (two type parameters) as shown in the existing codebase pattern and Larastan 3 docs. If PHPStan complains, fall back to `HasMany<ApplicantExamResult>`.

---

## Sources

### Primary (HIGH confidence)

- Laravel 12 docs (via search-docs) — `migrations.md` "uuid()", "foreignUuid()" column methods
- Laravel 12 docs (via search-docs) — `eloquent.md` "UUID and ULID Keys" — HasUuids trait usage
- Laravel 12 docs (via search-docs) — `upgrade.md` "Models and UUIDv7" — HasUuids now generates UUIDv7
- Laravel 12 docs (via search-docs) — `eloquent-mutators.md` "Enum Casting" — `casts()` with PHP enum classes
- Laravel 12 docs (via search-docs) — `eloquent-factories.md` "Factory States" — named state method pattern
- Laravel 12 docs (via search-docs) — `eloquent-relationships.md` "Preventing Lazy Loading" — preventLazyLoading() API
- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Providers/AppServiceProvider.php` — confirms preventLazyLoading() already active unconditionally; Model::unguard() already active
- `/Users/otisz/Projects/oktatasi-hivatal/server/pint.json` — confirms final_class, declare_strict_types, void_return rules
- `/Users/otisz/Projects/oktatasi-hivatal/server/phpstan.neon` — confirms level 7, paths: app/
- `/Users/otisz/Projects/oktatasi-hivatal/server/app/Models/User.php` — confirms casts() method convention, HasFactory PHPDoc style
- `/Users/otisz/Projects/oktatasi-hivatal/server/.planning/phases/03-database-schema-and-models/03-CONTEXT.md` — locked implementation decisions

### Secondary (MEDIUM confidence)

- `/Users/otisz/Projects/oktatasi-hivatal/server/IMPLEMENTATION.md` — used for directory structure, column list, factory file names (overridden by CONTEXT.md for PK strategy)
- `/Users/otisz/Projects/oktatasi-hivatal/server/PRD.md` Section 10 — authoritative column list per table

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all tooling confirmed in repo, no new dependencies
- Architecture (UUID, enum cast, factory states): HIGH — verified from Laravel 12 docs and existing project files
- Pitfalls: HIGH — most verified from actual project files (AppServiceProvider, pint.json, upgrade docs); UUIDv7 change verified from Laravel 12 upgrade guide

**Research date:** 2026-02-25
**Valid until:** 2026-03-25 (stable domain — Laravel 12 ORM patterns, no external APIs)
