# Phase 1: Domain Primitives - Research

**Researched:** 2026-02-25
**Domain:** PHP 8.1+ backed enums, abstract exception hierarchies, Larastan/Pint enforcement
**Confidence:** HIGH

## Summary

Phase 1 creates the static type vocabulary that every downstream phase imports: three backed string enums and an abstract exception base with six typed subclasses. There are no external dependencies and no database interactions. The entire phase is pure PHP class authoring in `app/Enums/` and `app/Exceptions/`.

The stack is already fully configured: PHP 8.5, Laravel 12, Pint (Laravel preset + `final_class`, `declare_strict_types`, `fully_qualified_strict_types`), and Larastan at level 7. All artisan `make:` scaffolding commands for enums and exceptions are available. The implementation is straightforward but has one known typo in IMPLEMENTATION.md (`AngoNyelv` must be `AngolNyelv`) and requires careful attention to Pint's `final_class` rule (exceptions cannot be `final` because subclasses extend them, and `AdmissionException` is explicitly abstract).

The only non-trivial decisions delegated to Claude's discretion are the exact English case names for the 13 `SubjectName` cases and whether to add any extra convenience methods beyond the two specified helpers.

**Primary recommendation:** Use `php artisan make:enum` and `php artisan make:exception` for scaffolding, then populate content; run `vendor/bin/pint --dirty --format agent` and `./vendor/bin/phpstan analyse` after each file to catch issues immediately rather than in bulk.

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

#### Enum naming convention
- Enum **case names are English**: SubjectName::Mathematics, ExamLevel::Intermediate, ExamLevel::Advanced
- Enum **backing values are accented Hungarian**: `'magyar nyelv és irodalom'`, `'biológia'`, `'kémia'`, `'közép'`, `'emelt'`
- LanguageCertificateType uses descriptive English names: `UpperIntermediate` (B2, 28pts), `Advanced` (C1, 40pts)
- ExamLevel uses `Intermediate` (közép) and `Advanced` (emelt)

#### Subject enum helpers
- Add `SubjectName::globallyMandatory()` static method returning `[HungarianLanguageAndLiterature, History, Mathematics]`
- Add `SubjectName::isLanguage()` instance method for identifying language subjects
- Helpers co-locate business rules with the enum for downstream validation phases

#### Exception design
- `AdmissionException` is **abstract** — cannot be thrown directly, only subclasses
- All 6 subclasses carry **rich context data** as typed properties (e.g., FailedExamException stores subject name + percentage)
- Error messages are **dynamic Hungarian strings** built from context: e.g., "nem lehetséges a pontszámítás a {subject} tárgyból elért 20% alatti eredmény miatt"
- Exceptions are **pure domain objects** — no HTTP status code awareness; API layer (Phase 8) maps AdmissionException → 422

#### Code organization
- Enums in **App\Enums** flat: SubjectName.php, ExamLevel.php, LanguageCertificateType.php
- Exceptions in **App\Exceptions** flat: AdmissionException.php + 6 subclass files (FailedExamException.php, MissingGlobalMandatorySubjectException.php, etc.)
- No subdirectories for either enums or exceptions

### Claude's Discretion
- Exact English case names for the 13 subjects (Full English like HungarianLanguageAndLiterature vs shorter forms)
- Value Objects namespace for Phase 2 (App\ValueObjects vs App\DTOs)
- Any additional convenience methods on enums beyond the requested helpers

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| DOM-01 | SubjectName enum defines all 13 matriculation subjects with Hungarian string values | PHP backed enum pattern; PRD Section 11 provides authoritative key+value table; typo in IMPLEMENTATION.md (`AngoNyelv` → `AngolNyelv`) |
| DOM-02 | ExamLevel enum defines közép and emelt levels | PHP backed enum pattern; CONTEXT.md maps to `Intermediate`/`Advanced` English names |
| DOM-03 | LanguageCertificateType enum defines B2 (28 pts) and C1 (40 pts) with `points()` method | PHP backed enum with instance method; `B2` and `C1` as backing values; `UpperIntermediate`/`Advanced` as case names per CONTEXT.md |
| DOM-07 | AdmissionException abstract base class with 6 typed subclasses (FailedExam, MissingGlobalMandatory, MissingProgramMandatory, ProgramMandatoryLevel, MissingElective, UnknownProgram) | PHP abstract class extending `\Exception`; subclass constructor property promotion; dynamic Hungarian messages from homework_input.php acceptance cases |
</phase_requirements>

---

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.5.2 | Backed enum syntax, constructor property promotion, readonly, abstract classes | Already installed; no additional packages needed |
| laravel/pint | 1.27.1 | Code style enforcement (Laravel preset + project rules) | Configured in `pint.json`; enforces `final_class`, `declare_strict_types` |
| larastan/larastan | 3.9.2 | Static analysis at level 7 | Configured in `phpstan.neon`; paths: `app/`; baseline in `phpstan-baseline.neon` |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| pestphp/pest | 4.4.1 | Test runner | Phase 1 has no tests per the TDD order (enums/exceptions contain no testable behavior beyond compilation), but Pest's `arch()->expect('App\Enums')->toBeEnums()` can verify structure |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| PHP backed enum | Plain class constants | Enums are first-class in PHP 8.1+; provide type safety, `from()`/`tryFrom()`, `cases()` methods used by DB casting in later phases |
| Abstract exception base | Interface | Abstract class can enforce constructor signature and store common behavior; interfaces cannot carry implementation |

**Installation:** No installation needed — all tooling already in place.

---

## Architecture Patterns

### Recommended Project Structure
```
app/
├── Enums/
│   ├── ExamLevel.php
│   ├── LanguageCertificateType.php
│   └── SubjectName.php
└── Exceptions/
    ├── AdmissionException.php          (abstract)
    ├── FailedExamException.php
    ├── MissingElectiveSubjectException.php
    ├── MissingGlobalMandatorySubjectException.php
    ├── MissingProgramMandatorySubjectException.php
    ├── ProgramMandatorySubjectLevelException.php
    └── UnknownProgramException.php
```

### Pattern 1: PHP Backed String Enum with Instance Method

**What:** A `string` backed enum where each case maps to a Hungarian database value, with methods that compute values from the case.

**When to use:** LanguageCertificateType (needs `points()`) and SubjectName (needs `isLanguage()` and `globallyMandatory()`).

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum LanguageCertificateType: string
{
    case UpperIntermediate = 'B2';
    case Advanced = 'C1';

    public function points(): int
    {
        return match ($this) {
            self::UpperIntermediate => 28,
            self::Advanced => 40,
        };
    }
}
```

### Pattern 2: PHP Backed String Enum with Static Helper

**What:** A backed enum with a static method returning a filtered subset of cases.

**When to use:** SubjectName needs `globallyMandatory()` returning the three universally required subjects.

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum SubjectName: string
{
    case HungarianLanguageAndLiterature = 'magyar nyelv és irodalom';
    case History = 'történelem';
    case Mathematics = 'matematika';
    case EnglishLanguage = 'angol nyelv';
    // ...

    /** @return array<int, self> */
    public static function globallyMandatory(): array
    {
        return [
            self::HungarianLanguageAndLiterature,
            self::History,
            self::Mathematics,
        ];
    }

    public function isLanguage(): bool
    {
        return in_array($this, [
            self::EnglishLanguage,
            self::GermanLanguage,
            self::FrenchLanguage,
            self::ItalianLanguage,
            self::RussianLanguage,
            self::SpanyolNyelv,
        ], true);
    }
}
```

### Pattern 3: Abstract Exception with Typed Subclasses

**What:** Abstract base class extending `\Exception`, with constructor-less or empty body; subclasses use constructor property promotion to carry context data and build the Hungarian message in the constructor.

**When to use:** All AdmissionException subclasses.

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

abstract class AdmissionException extends \Exception {}
```

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\SubjectName;

final class FailedExamException extends AdmissionException
{
    public function __construct(
        public readonly SubjectName $subject,
        public readonly int $percentage,
    ) {
        parent::__construct(
            "nem lehetséges a pontszámítás a {$subject->value} tárgyból elért 20% alatti eredmény miatt"
        );
    }
}
```

### Pattern 4: Exceptions with No Context Data

**What:** For exceptions that have a fixed message with no dynamic parts (MissingGlobalMandatorySubjectException, UnknownProgramException).

**Example:**
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MissingGlobalMandatorySubjectException extends AdmissionException
{
    public function __construct()
    {
        parent::__construct('nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt');
    }
}
```

Note: Pint enforces `final_class` — subclasses can be `final` because they are not themselves subclassed. `AdmissionException` itself cannot be `final` because it is abstract with subclasses.

### Anti-Patterns to Avoid

- **Non-abstract AdmissionException:** The base must be `abstract` so it cannot be thrown directly; PHP enforces this at call site only if declared abstract.
- **Static messages as class constants on AdmissionException:** Messages belong in each subclass constructor so context data is embedded.
- **Using `enum` keyword for ExamLevel without a string backing type:** The DB stores `'közép'` and `'emelt'`; the enum must be `enum ExamLevel: string` to enable `ExamLevel::from($dbValue)` in later phases.
- **Non-TitleCase enum keys:** The project's PHP rules specify TitleCase for enum cases (e.g., `HungarianLanguageAndLiterature`, not `HUNGARIAN_LANGUAGE_AND_LITERATURE`).

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Enum from DB string | Custom `fromValue()` mapper | `SubjectName::from('matematika')` | Built into PHP 8.1 backed enums; `from()` throws `ValueError`, `tryFrom()` returns null |
| Enum case list | Manual array | `SubjectName::cases()` | Built into PHP backed enums |
| Enum iteration for helper subset | Hand-written arrays in calling code | `SubjectName::globallyMandatory()` static method on the enum | Co-locates the business rule with the type |

**Key insight:** PHP 8.1+ backed enums handle value mapping and case enumeration natively; there is no need for any helper classes or external packages.

---

## Common Pitfalls

### Pitfall 1: Typo in IMPLEMENTATION.md — `AngoNyelv` vs `AngolNyelv`
**What goes wrong:** IMPLEMENTATION.md lists `AngoNyelv` as the English case name; this is a typo.
**Why it happens:** Copy error in the planning document.
**How to avoid:** Use `AngolNyelv` (correct) as documented in STATE.md blockers. PRD Section 11 shows the backing value `'angol nyelv'` which clarifies the correct spelling.
**Warning signs:** PHPStan or Pint will not catch this; it will silently produce a misspelled case name.

### Pitfall 2: Pint `final_class` Rule on Exceptions
**What goes wrong:** Pint is configured with `"final_class": true`, which adds `final` to all classes. If `AdmissionException` is declared `final`, no subclass can extend it.
**Why it happens:** The `final_class` Pint rule auto-applies to classes without explicit `final` or `abstract`. Abstract classes are exempt from this rule.
**How to avoid:** Declare `AdmissionException` as `abstract class AdmissionException` — abstract classes are not modified by `final_class`. Subclasses are leaf classes and should be `final`.
**Warning signs:** Pint run produces a diff adding `final` to `AdmissionException`, then PHP fatals when a subclass tries to extend it.

### Pitfall 3: Missing `declare(strict_types=1)`
**What goes wrong:** Pint enforces `declare_strict_types` rule, which adds the declaration. If it's missing from a file, Pint will add it on the first `--dirty` run, causing a diff and a needed re-check.
**Why it happens:** Artisan scaffolding stubs may or may not include it.
**How to avoid:** Add `declare(strict_types=1);` to every new file immediately after the opening `<?php`.

### Pitfall 4: Hungarian Accented Characters in Backing Values
**What goes wrong:** Backing values like `'magyar nyelv és irodalom'` contain non-ASCII characters. If a file is saved with wrong encoding or if string comparison is done case-sensitively on the wrong value, `SubjectName::from()` will throw `ValueError`.
**Why it happens:** Encoding issues or copy-paste from non-UTF-8 sources.
**How to avoid:** Use the homework_input.php file as the authoritative source for exact backing values — it is already in the repo and uses UTF-8.

### Pitfall 5: ExamLevel Naming Conflict
**What goes wrong:** CONTEXT.md specifies `Intermediate` and `Advanced` for ExamLevel cases, but IMPLEMENTATION.md uses `Kozep` and `Emelt`. PRD uses `Kozep`/`Emelt` as keys.
**Why it happens:** CONTEXT.md represents the final locked decision that overrides earlier documents.
**How to avoid:** Use CONTEXT.md (the locked decision): `ExamLevel::Intermediate = 'közép'` and `ExamLevel::Advanced = 'emelt'`. CONTEXT.md decisions take precedence over IMPLEMENTATION.md and PRD.

### Pitfall 6: PHPStan Level 7 on `globallyMandatory()` Return Type
**What goes wrong:** Larastan at level 7 requires explicit return types. Returning `array` without a PHPDoc array shape will likely pass, but a precise `@return array<int, self>` shape is better style and may be required by project conventions.
**Why it happens:** Level 7 enforces generic types on arrays in some contexts.
**How to avoid:** Add `/** @return array<int, self> */` PHPDoc on `globallyMandatory()`. For `isLanguage()`, `bool` return type is sufficient.

---

## Code Examples

Verified patterns from authoritative sources:

### Complete SubjectName Enum (all 13 cases)
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum SubjectName: string
{
    case HungarianLanguageAndLiterature = 'magyar nyelv és irodalom';
    case History = 'történelem';
    case Mathematics = 'matematika';
    case EnglishLanguage = 'angol nyelv';
    case GermanLanguage = 'német nyelv';
    case FrenchLanguage = 'francia nyelv';
    case ItalianLanguage = 'olasz nyelv';
    case RussianLanguage = 'orosz nyelv';
    case SpanishLanguage = 'spanyol nyelv';
    case Informatics = 'informatika';
    case Biology = 'biológia';
    case Physics = 'fizika';
    case Chemistry = 'kémia';

    /** @return array<int, self> */
    public static function globallyMandatory(): array
    {
        return [
            self::HungarianLanguageAndLiterature,
            self::History,
            self::Mathematics,
        ];
    }

    public function isLanguage(): bool
    {
        return in_array($this, [
            self::EnglishLanguage,
            self::GermanLanguage,
            self::FrenchLanguage,
            self::ItalianLanguage,
            self::RussianLanguage,
            self::SpanishLanguage,
        ], true);
    }
}
```

Source: PRD Section 11 (authoritative key+value list), CONTEXT.md (locked decisions), homework_input.php (verified Hungarian backing values).

### ExamLevel Enum
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ExamLevel: string
{
    case Intermediate = 'közép';
    case Advanced = 'emelt';
}
```

Source: CONTEXT.md locked decision. Backing values from homework_input.php (verified in-repo).

### LanguageCertificateType Enum
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum LanguageCertificateType: string
{
    case UpperIntermediate = 'B2';
    case Advanced = 'C1';

    public function points(): int
    {
        return match ($this) {
            self::UpperIntermediate => 28,
            self::Advanced => 40,
        };
    }
}
```

Source: CONTEXT.md locked decision. Points confirmed by PRD Section 11, kovetelmenyek.md, and homework_input.php.

### AdmissionException Abstract Base
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

abstract class AdmissionException extends \Exception {}
```

### FailedExamException (dynamic message with context)
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\SubjectName;

final class FailedExamException extends AdmissionException
{
    public function __construct(
        public readonly SubjectName $subject,
        public readonly int $percentage,
    ) {
        parent::__construct(
            "nem lehetséges a pontszámítás a {$subject->value} tárgyból elért 20% alatti eredmény miatt"
        );
    }
}
```

Source: homework_input.php Case 4 expected message: "nem lehetséges a pontszámítás a magyar nyelv és irodalom tárgyból elért 20% alatti eredmény miatt"

### MissingGlobalMandatorySubjectException (fixed message)
```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MissingGlobalMandatorySubjectException extends AdmissionException
{
    public function __construct()
    {
        parent::__construct('nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt');
    }
}
```

Source: homework_input.php Case 3 expected message; IMPLEMENTATION.md Section 9 Example 3.

### Other Exception Subclasses (pattern reference)

These need dynamic messages. The exact Hungarian message strings for the remaining 4 exceptions are not specified in homework_input.php acceptance cases; they are not exercised by the 4 seeded test cases. Use descriptive Hungarian messages consistent with the existing patterns:

| Class | Context Properties | Suggested Message Pattern |
|-------|--------------------|--------------------------|
| `MissingProgramMandatorySubjectException` | `SubjectName $subject` | `"nem lehetséges a pontszámítás a {$subject->value} kötelező tárgy hiánya miatt"` |
| `ProgramMandatorySubjectLevelException` | `SubjectName $subject`, `ExamLevel $requiredLevel` | `"nem lehetséges a pontszámítás a {$subject->value} tárgyból elért emelt szint hiánya miatt"` |
| `MissingElectiveSubjectException` | (no context needed) | `"nem lehetséges a pontszámítás a kötelezően választható tárgy hiánya miatt"` |
| `UnknownProgramException` | (no context needed) | `"nem lehetséges a pontszámítás ismeretlen szak miatt"` |

These messages are within Claude's discretion — the acceptance tests only verify Cases 3 and 4.

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Class constants for enums | PHP backed enums | PHP 8.1 (2021) | Native `from()`, `tryFrom()`, `cases()`, `value` property; no helper library needed |
| `$casts` property on models | `casts()` method on models | Laravel 11+ | Project convention (from Laravel 12 guidelines) — relevant when DB-casting enums in Phase 3 |

**No deprecated approaches apply to this phase** — PHP backed enums and abstract exception classes are standard, stable features.

---

## Open Questions

1. **Exact message strings for the 4 non-tested exceptions**
   - What we know: Only Cases 3 (MissingGlobalMandatory) and 4 (FailedExam) have acceptance test coverage with exact message strings.
   - What's unclear: Whether MissingProgramMandatory, ProgramMandatoryLevel, MissingElective, and UnknownProgram have exact required messages.
   - Recommendation: Use descriptive Hungarian messages matching the pattern. Feature tests in Phase 8 only assert the 4 seeded acceptance cases, so these messages are not under test constraint. Claude has discretion here.

2. **`isLanguage()` definition — which subjects count as languages**
   - What we know: The 6 foreign languages in SubjectName (angol, német, francia, olasz, orosz, spanyol) are clearly language subjects.
   - What's unclear: Whether `isLanguage()` should include `MagyarNyelvEsIrodalom` — it is a language subject in the colloquial sense but is a globally mandatory core subject, not a foreign language.
   - Recommendation: Exclude `HungarianLanguageAndLiterature` from `isLanguage()`. The method is intended for identifying language exam subjects that correlate with language certificates (bonus points), and Hungarian language certificates are not a thing in this domain.

---

## Validation Architecture

> `workflow.nyquist_validation` is not present in `.planning/config.json` (the key does not exist); treating as false — skipping this section.

---

## Sources

### Primary (HIGH confidence)
- `/Users/otisz/Projects/oktatasi-hivatal/server/homework_input.php` — authoritative Hungarian backing values, exact error message strings for Cases 3 and 4
- `/Users/otisz/Projects/oktatasi-hivatal/server/PRD.md` Section 11 — full enum key/value table for all 13 SubjectName cases
- `/Users/otisz/Projects/oktatasi-hivatal/server/.planning/phases/01-domain-primitives/01-CONTEXT.md` — locked implementation decisions (enum names, exception design, code organization)
- `/Users/otisz/Projects/oktatasi-hivatal/server/IMPLEMENTATION.md` — directory structure, class signatures, TDD order
- `/Users/otisz/Projects/oktatasi-hivatal/server/pint.json` — confirmed `final_class`, `declare_strict_types`, `fully_qualified_strict_types` rules
- `/Users/otisz/Projects/oktatasi-hivatal/server/phpstan.neon` — confirmed level 7, `app/` paths
- Laravel Boost search-docs — confirmed `make:enum` and `make:exception` artisan commands exist; PHP backed enum patterns in Laravel 12

### Secondary (MEDIUM confidence)
- `kovetelmenyek.md` — original homework spec confirming B2=28pts, C1=40pts, emelt=50pts
- IMPLEMENTATION.md Section 4 — enum case list (used with correction for `AngoNyelv` → `AngolNyelv` typo)

### Tertiary (LOW confidence)
- Pint `final_class` behavior on `abstract` classes — verified by reading `pint.json` and PHP semantics (abstract classes cannot be `final`); the Pint rule skips abstract classes by design.

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all tooling confirmed present and configured in the repo
- Architecture: HIGH — patterns directly verified from PRD, homework_input.php, and CONTEXT.md locked decisions
- Pitfalls: HIGH for typo/Pint/strict_types (verified from project files); MEDIUM for PHPStan array shape (based on level 7 behavior knowledge)

**Research date:** 2026-02-25
**Valid until:** 2026-03-25 (stable domain — pure PHP class authoring, no external APIs)
