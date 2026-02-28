# Phase 4: Seed Data - Research

**Researched:** 2026-02-26
**Domain:** Laravel database seeding with UUID primary keys and enum-cast Eloquent models
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **Applicant ID strategy:** Set explicit IDs for all entities — programmes (ELTE IK = UUID constant, PPKE BTK = UUID constant) and applicants (use the four constants already defined on Applicant model). Use named constants at the top of each seeder class for FK references — no magic numbers. Each applicant block gets a PHPDoc comment documenting expected outcome.
- **Seeder idempotency:** Assume `migrate:fresh --seed` workflow — seeders just insert, no upsert logic. No transaction wrapping. No `WithoutModelEvents` — models have no observers. Silent operation — no progress messages.
- **Enum usage in seeders:** Reference Phase 1 enums throughout: `SubjectName`, `ExamLevel`, `LanguageCertificateType`. Pass enum instances directly to Eloquent (rely on model casts for serialization) — do NOT call `->value` on enum arguments.
- **New enums discovered:** `BonusCategory` and `RequirementType` enums belong to Phase 1. `RequirementType` already exists in `app/Enums/RequirementType.php`. `BonusCategory` does NOT exist yet and is out of scope for Phase 4 — the `category` column is a plain varchar with no model cast, so seed it as the string `'Nyelvvizsga'`.

### Claude's Discretion

- Internal seeder method organization (single `run()` vs helper methods)
- Whether to use Eloquent `create()` or `insert()` for bulk data
- ProgramSubject seeding approach (inline with programme or separate)

### Deferred Ideas (OUT OF SCOPE)

- BonusCategory and RequirementType enums need to be added to Phase 1 (Domain Primitives) before this phase executes — note for roadmap update. (RequirementType is already present; BonusCategory is missing but seeding `'Nyelvvizsga'` as a string is the workaround.)
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| SEED-01 | ProgramSeeder creates ELTE IK Programtervezo informatikus (mandatory: matematika, electives: biologia/fizika/informatika/kemia) | Program::create() with UUID constant; ProgramSubject::create() using SubjectName and RequirementType enums |
| SEED-02 | ProgramSeeder creates PPKE BTK Anglisztika (mandatory: angol nyelv emelt, electives: francia/nemet/olasz/orosz/spanyol/tortenelem) | Same pattern; required_level uses ExamLevel::Advanced for the mandatory subject |
| SEED-03 | ApplicantSeeder creates Applicant 1 (ELTE IK, expected score: 470) | Applicant::create() with Applicant::CASE_1_UUID; then exam results and bonus points using child model create() |
| SEED-04 | ApplicantSeeder creates Applicant 2 (ELTE IK + fizika, expected score: 476) | Same as SEED-03 pattern with Applicant::CASE_2_UUID; adds fizika 98% kozep exam |
| SEED-05 | ApplicantSeeder creates Applicant 3 (missing magyar + tortenelem, expected: error) | Applicant::CASE_3_UUID; only 3 exam results, intentionally missing global mandatory subjects |
| SEED-06 | ApplicantSeeder creates Applicant 4 (magyar 15%, expected: FailedExam error) | Applicant::CASE_4_UUID; magyar at 15% — below 20% threshold |
| SEED-07 | DatabaseSeeder calls ProgramSeeder then ApplicantSeeder in correct FK order | $this->call([ProgramSeeder::class, ApplicantSeeder::class]) |
</phase_requirements>

## Summary

Phase 4 seeds two programmes and four applicants into a fully-migrated SQLite schema. All tables use UUID primary keys (`varchar` in SQLite via Laravel `HasUuids`). The four applicant UUID constants are already defined on the `Applicant` model; program UUIDs need constants defined in `ProgramSeeder`. FK insertion order is: `programs` → `program_subjects` → `applicants` → `applicant_exam_results` / `applicant_bonus_points`.

The seeding approach is plain `Model::create()` with enum instances passed directly — model casts on `ProgramSubject`, `ApplicantExamResult`, and `ApplicantBonusPoint` handle serialization. The `ApplicantBonusPoint.category` column is an uncasted plain string; seed it as `'Nyelvvizsga'` until a `BonusCategory` enum is added in a later Phase 1 patch. The `DatabaseSeeder` already exists but uses `WithoutModelEvents` — the context decision says to remove that trait since models have no observers; the seeder should be rewritten to call `ProgramSeeder` then `ApplicantSeeder` via `$this->call()`.

**Primary recommendation:** Two seeder classes (`ProgramSeeder` and `ApplicantSeeder`) created via `php artisan make:seeder`; `DatabaseSeeder` updated to call them in FK order; all data values transcribed exactly from `homework_input.php`.

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Seeder | 12.x | Seeder base class + `$this->call()` | Framework built-in; FK order via call array order |
| Eloquent `HasUuids` | 12.x | Auto-generates UUIDs; allows overriding with explicit id | Already on all 5 models |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `php artisan make:seeder` | 12.x | Scaffolds seeder class | All seeder creation |
| `Model::create()` | 12.x | Inserts a single row via Eloquent, fires model events | Small, readable datasets with enum casting |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `Model::create()` | `DB::table()->insert()` | `insert()` bypasses enum casts and UUID auto-generation; not appropriate here |
| `Model::create()` | Factory states | Factories are for random test data; seeders need exact values — use `create()` with explicit data |

## Architecture Patterns

### Recommended Project Structure

```
database/
├── seeders/
│   ├── DatabaseSeeder.php      # orchestrator — calls ProgramSeeder then ApplicantSeeder
│   ├── ProgramSeeder.php       # SEED-01, SEED-02 — programs + program_subjects
│   └── ApplicantSeeder.php     # SEED-03..06 — applicants + exam_results + bonus_points
```

### Pattern 1: UUID Constants at Top of Seeder

**What:** Define programme UUIDs as class constants, mirror the pattern already used in `Applicant` model.

**When to use:** Any seeder that another seeder's FK references depend on.

**Example:**

```php
// Source: Applicant model already has CASE_1_UUID pattern
final class ProgramSeeder extends Seeder
{
    public const string ELTE_IK_UUID = '0195a1b2-0000-7000-8000-000000000101';
    public const string PPKE_BTK_UUID = '0195a1b2-0000-7000-8000-000000000102';

    public function run(): void
    {
        $elteIk = Program::create([
            'id' => self::ELTE_IK_UUID,
            'university' => 'ELTE',
            'faculty' => 'IK',
            'name' => 'Programtervező informatikus',
        ]);
        // ...
    }
}
```

### Pattern 2: Enum Instances Passed Directly (Rely on Model Casts)

**What:** Pass PHP enum instances as attribute values; the model's `casts()` method serializes them to their string backing value automatically.

**When to use:** Any column with a PHP enum cast defined in `casts()` on the model.

**Example:**

```php
// ProgramSubject has subject_name => SubjectName::class cast
$elteIk->subjects()->create([
    'subject_name' => SubjectName::Mathematics,        // enum instance, not ->value
    'requirement_type' => RequirementType::Mandatory,  // enum instance
    'required_level' => null,
]);

$elteIk->subjects()->create([
    'subject_name' => SubjectName::Biology,
    'requirement_type' => RequirementType::Elective,
    'required_level' => null,
]);
```

### Pattern 3: Child Records via Relationship Methods

**What:** Create child records by calling `$parent->relationship()->create([...])` so the FK is automatically populated.

**When to use:** All child inserts — `program_subjects`, `applicant_exam_results`, `applicant_bonus_points`.

**Example:**

```php
$applicant1 = Applicant::create([
    'id' => Applicant::CASE_1_UUID,
    'program_id' => ProgramSeeder::ELTE_IK_UUID,
]);

$applicant1->examResults()->create([
    'subject_name' => SubjectName::HungarianLanguageAndLiterature,
    'level' => ExamLevel::Intermediate,
    'percentage' => 70,
]);
```

### Pattern 4: DatabaseSeeder Calls Sub-Seeders in FK Order

**What:** `DatabaseSeeder::run()` uses `$this->call([])` with programmes first.

**Example:**

```php
// Source: Laravel docs seeding.md — Calling Additional Seeders
public function run(): void
{
    $this->call([
        ProgramSeeder::class,
        ApplicantSeeder::class,
    ]);
}
```

### Anti-Patterns to Avoid

- **Using `->value` on enum arguments:** The model casts handle serialization. Passing `SubjectName::Mathematics->value` bypasses the intent and creates a future inconsistency risk if casts change.
- **Using `WithoutModelEvents` in DatabaseSeeder:** The current stub has it. The context decision says remove it since models have no observers, and it would suppress any future observer wiring.
- **Hardcoding UUID strings in ApplicantSeeder without referencing constants:** Always reference `Applicant::CASE_1_UUID` and `ProgramSeeder::ELTE_IK_UUID` — never inline UUID strings.
- **Inserting applicants before programmes:** FK constraint will fail in SQLite even without explicit FK enforcement if order is wrong in FK-enforced mode. Keep call order strict.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Enum serialization | `->value` calls everywhere | Model `casts()` + pass enum instances | Casts already defined; uniform handling |
| FK value references | String UUIDs inline | Class constants | Constants survive search-and-replace; string literals don't |
| Sub-seeder orchestration | Manual `new ProgramSeeder()->run()` | `$this->call([...])` | Laravel handles container resolution and output |

## Common Pitfalls

### Pitfall 1: UUID Auto-Generation Overwritten by Explicit ID

**What goes wrong:** `HasUuids` generates a UUID on model creation. Passing an explicit `id` in the `create()` array should override it — but only if the column is in the UUID-generating list AND you pass the `id` key explicitly.

**Why it happens:** `HasUuids::uniqueIds()` returns `['id']` by default; Laravel checks if `id` was provided and skips generation if it was. This is the correct mechanism and does work — verified by `Applicant` model already having `CASE_1_UUID` constants intended for this use.

**How to avoid:** Pass `'id' => self::ELTE_IK_UUID` explicitly in the `create()` call. The explicit value wins.

**Warning signs:** If IDs in the DB don't match constants, HasUuids generated its own UUID instead of using the provided one — verify the key name matches exactly `'id'`.

### Pitfall 2: category Column Has No Enum Cast

**What goes wrong:** The `ApplicantBonusPoint` model only casts `type` to `LanguageCertificateType`. The `category` column is a plain `varchar`. Attempting to pass `BonusCategory::Nyelvvizsga` (which doesn't exist yet) will cause a fatal error.

**How to avoid:** Seed `category` as the string `'Nyelvvizsga'` directly. This matches the factory default already using `'Nyelvvizsga'` as a string literal.

### Pitfall 3: PPKE BTK Electives Must Include tortenelem

**What goes wrong:** The PPKE BTK Anglisztika programme has 7 subject entries: 1 mandatory (angol emelt) + 6 electives (francia, nemet, olasz, orosz, spanyol, tortenelem). Forgetting `tortenelem` would cause a test failure later in Phase 8.

**How to avoid:** Reference the exact list from REQUIREMENTS.md: `SEED-02` says electives are `francia/nemet/olasz/orosz/spanyol/tortenelem`.

### Pitfall 4: Applicant 3 Is Missing magyar AND tortenelem (Not Just One)

**What goes wrong:** Only creating one missing subject row won't reproduce the correct error case. Applicant 3 has only 3 exam results: matematika emelt 90%, angol kozep 94%, informatika kozep 95%.

**How to avoid:** Do not add `HungarianLanguageAndLiterature` or `History` exam results to Applicant 3. The scoring service detects missing globally mandatory subjects in Step 2.

### Pitfall 5: Applicant 4 Uses 15% for magyar (Below the 20% Threshold)

**What goes wrong:** The factory default for percentage is 20-100 to avoid triggering `FailedExamException`. Applicant 4 needs magyar at exactly 15% — which IS the failing case.

**How to avoid:** Explicitly set `'percentage' => 15` for Applicant 4's magyar exam result. This tests `FailedExamException` being thrown during VO mapping (Step 1 of validation).

## Code Examples

### Exact Data Reference — All Four Applicants

Derived from `homework_input.php`. This is the ground truth for seeder values.

**Applicant 1 (CASE_1_UUID) — expected 470 (370 base + 100 bonus)**
```
Exams:
  HungarianLanguageAndLiterature, Intermediate, 70%
  History,                         Intermediate, 80%
  Mathematics,                     Advanced,     90%
  EnglishLanguage,                 Intermediate, 94%
  Informatics,                     Intermediate, 95%
Bonus Points:
  category='Nyelvvizsga', type=UpperIntermediate (B2), language='angol'
  category='Nyelvvizsga', type=Advanced (C1),           language='német'
```

**Applicant 2 (CASE_2_UUID) — expected 476 (376 base + 100 bonus)**
```
Exams: (same as Applicant 1 plus:)
  Physics, Intermediate, 98%
Bonus Points: (identical to Applicant 1)
```

**Applicant 3 (CASE_3_UUID) — expected MissingGlobalMandatorySubjectException**
```
Exams:
  Mathematics,    Advanced,     90%
  EnglishLanguage, Intermediate, 94%
  Informatics,    Intermediate, 95%
  (NO magyar, NO tortenelem)
Bonus Points:
  category='Nyelvvizsga', type=UpperIntermediate (B2), language='angol'
  category='Nyelvvizsga', type=Advanced (C1),           language='német'
```

**Applicant 4 (CASE_4_UUID) — expected FailedExamException (magyar < 20%)**
```
Exams:
  HungarianLanguageAndLiterature, Intermediate, 15%   ← triggers FailedExam
  History,                         Intermediate, 80%
  Mathematics,                     Advanced,     90%
  EnglishLanguage,                 Intermediate, 94%
  Informatics,                     Intermediate, 95%
Bonus Points:
  category='Nyelvvizsga', type=UpperIntermediate (B2), language='angol'
  category='Nyelvvizsga', type=Advanced (C1),           language='német'
```

### ELTE IK Programme Subjects

```
Mandatory:
  Mathematics (matematika), no required level
Electives:
  Biology (biológia)
  Physics (fizika)
  Informatics (informatika)
  Chemistry (kémia)
```

### PPKE BTK Anglisztika Programme Subjects

```
Mandatory:
  EnglishLanguage (angol nyelv), required_level = Advanced (emelt)
Electives:
  FrenchLanguage  (francia nyelv)
  GermanLanguage  (német nyelv)
  ItalianLanguage (olasz nyelv)
  RussianLanguage (orosz nyelv)
  SpanishLanguage (spanyol nyelv)
  History         (történelem)
```

### DatabaseSeeder Rewrite

```php
// Source: Laravel seeding.md — Calling Additional Seeders
final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProgramSeeder::class,
            ApplicantSeeder::class,
        ]);
    }
}
```

Note: Remove `use WithoutModelEvents;` from DatabaseSeeder per the context decision.

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `$casts` property array | `casts()` method | Laravel 10+ | Method is the convention for this project (all 3 cast-bearing models use it) |
| Auto-increment integer PKs | UUID via `HasUuids` | Phase 3 decision | Explicit UUID must be passed in `create()` array to override auto-generation |

## Open Questions

1. **Should ProgramSeeder UUID constants live in ProgramSeeder or in Program model?**
   - What we know: `Applicant` model carries the UUID constants for applicants; it is natural to mirror this pattern.
   - What's unclear: The roadmap says IDs 1-4 for applicants — the Applicant model already defines UUIDs for those. For programmes, CONTEXT says "ELTE IK = 1, PPKE BTK = 2" which was written before the UUID pivot in Phase 3. The actual values should be stable UUID strings.
   - Recommendation: Define programme UUID constants in `ProgramSeeder` (not the model) since they are seeder-specific references, and cross-reference them as `ProgramSeeder::ELTE_IK_UUID` from `ApplicantSeeder`. If Phase 5+ needs them, promote to the model at that point.

2. **How many seeder files?**
   - What we know: CONTEXT mentions `ProgramSeeder` and `ApplicantSeeder` as separate classes.
   - Recommendation: Two seeder files plus updating `DatabaseSeeder`. This is clear from CONTEXT and SEED-07.

## Sources

### Primary (HIGH confidence)

- Laravel 12.x seeding.md — seeder scaffolding, `$this->call()`, `WithoutModelEvents`, `migrate:fresh --seed`
- Project codebase — models, enums, factories, migrations all inspected directly
- `homework_input.php` — ground truth for all seed data values
- `app/Models/Applicant.php` — UUID constants pattern (`CASE_1_UUID` through `CASE_4_UUID`)
- `app/Enums/*.php` — all four enum files confirmed present; `BonusCategory` confirmed absent

### Secondary (MEDIUM confidence)

- CONTEXT.md locked decisions — directly sourced from prior `/gsd:discuss-phase` session

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Laravel seeder API is stable and verified against docs
- Architecture: HIGH — pattern derived directly from existing codebase conventions
- Pitfalls: HIGH — derived from actual schema inspection, factory code, and homework_input.php

**Research date:** 2026-02-26
**Valid until:** 2026-03-28 (stable Laravel seeding API, 30-day window)
