# Coding Conventions

**Analysis Date:** 2026-02-28

## Naming Patterns

**Files:**
- PHP classes: PascalCase - `ApplicantController.php`, `AdmissionScoringService.php`, `ExamResult.php`
- PHP files for enums: PascalCase - `SubjectName.php`, `ExamLevel.php`
- Vue files: PascalCase - `ApplicantsView.vue`, `ApplicantDetailView.vue`, `App.vue`
- TypeScript files: camelCase - `useApplicants.ts`, `useProgress.ts`, `useApplicantScore.ts`
- Configuration files: snake_case or camelCase - `tsconfig.json`, `biome.json`

**Functions/Methods:**
- PHP: camelCase - `calculateForApplicant()`, `validateGlobalMandatorySubjects()`, `isAdvancedLevel()`
- TypeScript: camelCase - `makeExamResultRow()`, `makeApplicantWithExams()`
- Vue composables: prefix with `use` - `useApplicants()`, `useApplicantScore()`, `useProgress()`

**Variables:**
- PHP: camelCase - `$examResults`, `$mandatoryResult`, `$basePoints`
- TypeScript: camelCase - `router`, `applicant`, `isLoading`, `isError`
- Vue template data: camelCase - `isNavigating`, `data`, `isLoading`

**Types/Interfaces (TypeScript):**
- PascalCase - `ApiResponse<T>`, `ScoreResult`, `Applicant`, `Program`, `ScoreError`
- Enums: TitleCase keys - `SubjectName.HungarianLanguageAndLiterature`, `ExamLevel.Advanced`, `LanguageCertificateType.UpperIntermediate`

## Code Style

**Formatting:**
- Tool: Biome for client-side (TypeScript/Vue)
- Tool: Laravel Pint for server-side (PHP)
- Indentation: 2 spaces (both client and server)
- Line width: 100 characters max (Biome)
- Trailing commas: all (JavaScript/Vue)
- Semicolons: asNeeded (Biome) — omit when not required
- Quotes: single quotes preferred in JavaScript/Vue

**Linting:**
- Client: `biome check .` — enforces recommended rules
- Server: `php ./vendor/bin/rector` and `php ./vendor/bin/pint --parallel`

**Biome Rules (Client):**
```json
{
  "formatter": {
    "indentStyle": "space",
    "indentWidth": 2,
    "lineWidth": 100
  },
  "javascript": {
    "quoteStyle": "single",
    "trailingCommas": "all",
    "semicolons": "asNeeded"
  },
  "linter": {
    "rules": {
      "recommended": true
    }
  }
}
```

**Pint Rules (Server):**
```json
{
  "preset": "laravel",
  "rules": {
    "declare_strict_types": true,
    "final_class": true,
    "yoda_style": true,
    "fully_qualified_strict_types": true,
    "strict_comparison": true,
    "ternary_to_null_coalescing": true,
    "void_return": true
  }
}
```

## Import Organization

**TypeScript (Client):**
Order imports:
1. External dependencies - `import { useQuery } from '@tanstack/vue-query'`
2. Vue/framework imports - `import { createApp } from 'vue'`
3. Internal path alias imports - `import { router } from '@/router'`
4. Relative imports - `import App from './App.vue'`
5. Assets - `import './assets/main.css'`

Biome's `organizeImports` action automatically sorts imports on save.

Path aliases configured in `tsconfig.app.json`:
```json
{
  "paths": {
    "@/*": ["./src/*"]
  }
}
```

**PHP (Server):**
Order imports:
1. `declare(strict_types=1);` at the very top
2. `namespace` declaration
3. `use` statements (standard library first, then Laravel/packages, then application classes)

Example from `AdmissionScoringService.php`:
```php
<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\BasePointCalculatorInterface;
use App\Contracts\BonusPointCalculatorInterface;
use App\Models\Applicant;
// ... more imports
```

## Error Handling

**PHP Patterns:**
- Exceptions extend from custom base classes (`AdmissionException` for domain errors)
- Exceptions carry context as public readonly properties:
  ```php
  final class FailedExamException extends AdmissionException
  {
      public function __construct(
          public readonly SubjectName $subject,
          public readonly int $percentage,
      ) { ... }
  }
  ```
- Validation errors thrown in Value Object constructors (fail fast pattern)
- Laravel's `throw_if()` and `throw_unless()` helpers used for early validation:
  ```php
  throw_if($percentage < 20, FailedExamException::class, $subject, $percentage);
  throw_unless(in_array($required, $subjectNames, true), MissingGlobalMandatorySubjectException::class);
  ```

**TypeScript Patterns:**
- Composables return error state via Vue Query's `error` property
- Custom error type discriminator used to categorize errors:
  ```typescript
  export type ScoreError = { kind: 'domain'; message: string } | { kind: 'generic' }
  ```
- Catch blocks differentiate Axios errors:
  ```typescript
  if (axios.isAxiosError(e) && e.response?.status === 422) {
    const body = e.response.data as ApiError
    throw { kind: 'domain', message: body.error } satisfies ScoreError
  }
  ```

## Logging

**Framework:** Console methods (no dedicated logging framework configured)

**Patterns:**
- No logging observed in production code
- Tests and development use standard output for debugging

## Comments

**When to Comment:**
- Prefer self-documenting code with clear names
- PHPDoc blocks on public methods and classes (not inline comments)
- TSDoc/JSDoc only when type inference is insufficient

**PHPDoc Pattern (PHP):**
```php
/**
 * Find the best-scoring elective exam result. First-encountered wins on ties (strict > comparison).
 *
 * @param  array<int, ExamResult>  $examResults
 */
private function findBestElective(
    array $examResults,
    ProgramRequirementsInterface $requirements,
): ExamResult
```

**Complex Logic Comments:**
From `AdmissionScoringService::calculateForApplicant()`:
```php
// Step 1: Map Eloquent rows to VOs — ExamResult constructor throws FailedExamException if < 20%
// Step 2: Global mandatory check
// Resolve programme requirements (after step 1+2 to preserve exception priority)
```

## Function Design

**Size:**
- Most functions < 50 lines
- Private helpers under 30 lines typically
- Public methods 15-35 lines

**Parameters:**
- Max 3-4 parameters before considering refactor
- Use dependency injection for services (constructor injection)
- Type hints required on all parameters

**Return Values:**
- Always declare return type explicitly
- Use specific types, not `mixed` or void when possible
- Value Objects preferred for complex returns:
  ```php
  public function calculateForApplicant(Applicant $applicant): Score
  ```

**PHP Parameter Promotion (Constructor Property):**
Used throughout for concise dependency injection:
```php
public function __construct(
    private readonly AdmissionScoringService $scoringService,
) {}
```

## Module Design

**Exports:**
- Composables export single function with `use` prefix:
  ```typescript
  export function useApplicants() { ... }
  export function useApplicantScore(id: MaybeRefOrGetter<string>) { ... }
  ```
- Services export as instances or classes:
  ```typescript
  export const http = axios.create({ ... })
  export const queryClient = new QueryClient({ ... })
  ```

**Barrel Files:**
- Not heavily used
- Direct imports preferred: `import { useApplicants } from '@/composables/useApplicants'`

**Value Objects (PHP):**
Immutable objects with validation in constructor:
```php
final readonly class ExamResult
{
    public function __construct(
        public SubjectName $subject,
        public ExamLevel $level,
        public int $percentage,
    ) {
        throw_if($percentage < 0 || $percentage > 100, \InvalidArgumentException::class, ...);
        throw_if($percentage < 20, FailedExamException::class, ...);
    }
}
```

**Enum Pattern (PHP):**
String-backed enums with methods:
```php
enum SubjectName: string
{
    case HungarianLanguageAndLiterature = 'magyar nyelv és irodalom';

    public static function globallyMandatory(): array { ... }
    public function isLanguage(): bool { ... }
}
```

---

*Convention analysis: 2026-02-28*
