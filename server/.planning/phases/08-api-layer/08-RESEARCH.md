# Phase 8: API Layer - Research

**Researched:** 2026-02-28
**Domain:** Laravel API routing, Eloquent Resources, exception rendering, Pest feature tests
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

User confirmed the spec is tight enough — Claude handles all remaining implementation decisions:

- **Applicant list response shape**: Applicant ID + nested programme details (university, faculty, name). Use Laravel Eloquent API Resource for consistent formatting.
- **Response envelope**: Both endpoints wrap in `{ data: ... }` — standard Laravel Resource convention. Score endpoint uses Hungarian keys as spec'd: `osszpontszam`, `alappont`, `tobbletpont`.
- **Error response format**: `{ error: "<Hungarian message>" }` with 422 status for all AdmissionException subclasses. No extra metadata — keep it simple per spec.
- **Exception rendering**: Wire in `bootstrap/app.php` via `withExceptions()` callback — render AdmissionException as 422 JSON.
- **Route model binding**: Implicit binding on `{applicant}` (UUID-based). Laravel's default 404 handling for missing models.
- **API versioning**: `routes/api.php` with `/v1` prefix group.
- **Feature test strategy**: RefreshDatabase + seeder for acceptance test data. One test class covering all 5 cases (4 applicants + 404).

### Claude's Discretion

All implementation decisions are locked — no discretion areas.

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| API-01 | GET /api/v1/applicants returns list of applicants with programme details (university, faculty, name) | ApplicantResource with nested ProgramResource; `withRouting(api:)` in bootstrap/app.php; eager-load `program` |
| API-02 | GET /api/v1/applicants/{applicant}/score returns `{ data: { osszpontszam, alappont, tobbletpont } }` on success (200) | ScoreResource mapping Score VO fields; implicit route model binding on UUID key; AdmissionScoringService injection |
| API-03 | AdmissionException subclasses render as 422 JSON `{ error: "<Hungarian message>" }` via bootstrap/app.php | `$exceptions->render(function (AdmissionException $e) { ... })` pattern in withExceptions() |
| API-04 | Unknown applicant returns 404 (Laravel default model binding) | HasUuids + implicit binding automatically 404s on missing model — no custom code needed |
| API-05 | ProgramRegistry bound as singleton in AppServiceProvider | Already done in Phase 7 — AppServiceProvider already binds all three interfaces; no new work |
| TEST-09 | Feature test Case 1 — Applicant 1 scores 470 (370 base + 100 bonus) | RefreshDatabase + $this->seed(); getJson('/api/v1/applicants/…/score')->assertSuccessful()->assertJson() |
| TEST-10 | Feature test Case 2 — Applicant 2 scores 476 (376 base + 100 bonus) | Same pattern as TEST-09 |
| TEST-11 | Feature test Case 3 — Applicant 3 returns 422 (missing global mandatory subjects) | getJson(…)->assertStatus(422)->assertJson(['error' => '…']) |
| TEST-12 | Feature test Case 4 — Applicant 4 returns 422 (magyar 15% below 20%) | getJson(…)->assertStatus(422)->assertJson(['error' => '…']) |
| TEST-13 | Feature test — Unknown applicant returns 404 | getJson('/api/v1/applicants/00000000-0000-0000-0000-000000000000/score')->assertNotFound() |
</phase_requirements>

## Summary

Phase 8 is a pure wiring phase — all business logic is complete. The work is: (1) create `routes/api.php`, register it in `bootstrap/app.php`; (2) create `ApplicantController` with `index()` and `score()`; (3) create `ApplicantResource` and a `ScoreResource` (or inline array); (4) wire `AdmissionException` → 422 JSON in `withExceptions()`; (5) write one feature test class covering all five acceptance cases.

The biggest subtlety is the `routes/api.php` registration. Since `install:api` was not run (no Sanctum), the api route file must be registered manually in `bootstrap/app.php` using the `api:` parameter in `withRouting()`. This gives the `api` middleware group automatically (stateless, no CSRF, `/api` prefix). The `/v1` prefix is then declared as a nested `Route::prefix('v1')->group(...)` inside `routes/api.php`.

The `Model::preventLazyLoading()` global in `AppServiceProvider` means the controller **must** eager-load `program` when calling `Applicant::all()` for the index endpoint, and must eager-load `program.subjects`, `examResults`, and `bonusPoints` before passing an `Applicant` to `AdmissionScoringService`.

**Primary recommendation:** Register `api:` route file natively in `withRouting()`, use implicit model binding with `HasUuids` for automatic 404, wrap the single `$exceptions->render()` call on `AdmissionException`, and use `RefreshDatabase` + `$this->seed()` per-test in the feature test file.

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| laravel/framework | v12 | Routing, controllers, API resources, exception handling | Already installed |
| pestphp/pest | v4 | Feature tests with HTTP test helpers | Project standard |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `Illuminate\Http\Resources\Json\JsonResource` | v12 | Transforms Eloquent model to JSON array | For ApplicantResource and ScoreResource |
| `Illuminate\Foundation\Testing\RefreshDatabase` | v12 | Resets DB between tests | All feature tests touching the database |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `JsonResource` for score | Inline `response()->json([...])` | Resource adds consistency but inline is simpler for one-off VOs; inline is fine here |
| `RefreshDatabase` per-test | `beforeEach($this->seed())` only | RefreshDatabase guarantees isolation; required since tests share the DB |

## Architecture Patterns

### Recommended Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── ApplicantController.php   # index() and score()
│   └── Resources/
│       ├── ApplicantResource.php     # { id, program: { university, faculty, name } }
│       └── ScoreResource.php         # { osszpontszam, alappont, tobbletpont }
routes/
└── api.php                           # Route::prefix('v1')->group(...)
bootstrap/
└── app.php                           # add api: param, add withExceptions render()
tests/
└── Feature/
    └── Api/
        └── ApplicantScoreTest.php    # 5 test cases
```

### Pattern 1: Register api.php in bootstrap/app.php

**What:** Add the `api:` key to `withRouting()` — Laravel automatically wraps these routes in the `api` middleware group and `/api` URI prefix.

**When to use:** Any time a `routes/api.php` file is introduced without running `install:api`.

**Example:**
```php
// Source: Laravel 12 routing docs
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
)
```

### Pattern 2: /v1 prefix group inside api.php

**What:** The `/api` prefix comes from the middleware group. Add `/v1` explicitly inside the file.

**Example:**
```php
use App\Http\Controllers\ApplicantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/applicants', [ApplicantController::class, 'index']);
    Route::get('/applicants/{applicant}/score', [ApplicantController::class, 'score']);
});
```

Final URLs: `GET /api/v1/applicants` and `GET /api/v1/applicants/{applicant}/score`.

### Pattern 3: Implicit Route Model Binding with HasUuids

**What:** Laravel matches `{applicant}` to `Applicant` model by its primary key. `HasUuids` models resolve by UUID. Missing model → automatic 404.

**Critical:** No extra configuration needed. The model uses `HasUuids` and its PK is `id`. Laravel's implicit binding resolves on `id` by default.

**Example:**
```php
// Controller method signature — Applicant is injected automatically
public function score(Applicant $applicant): ScoreResource
```

### Pattern 4: Exception Rendering in bootstrap/app.php

**What:** The `render` method on `Exceptions` accepts a typed closure. Type-hint `AdmissionException` to catch all 6 subclasses at once.

**Example:**
```php
// Source: Laravel 12 errors docs
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->render(function (\App\Exceptions\AdmissionException $e): \Illuminate\Http\JsonResponse {
        return response()->json(['error' => $e->getMessage()], 422);
    });
})
```

### Pattern 5: ApplicantResource with nested Program data

**What:** `JsonResource::toArray()` returns an array. Access relationship via `$this->program` (eager-loaded).

**Example:**
```php
// Source: Laravel 12 eloquent-resources docs
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'program' => [
            'university' => $this->program->university,
            'faculty' => $this->program->faculty,
            'name' => $this->program->name,
        ],
    ];
}
```

### Pattern 6: ScoreResource mapping Score VO

**What:** The Score VO is not an Eloquent model, so we use an anonymous resource or a plain `JsonResource` wrapping the VO, **or** simply return `response()->json(['data' => [...]])` directly from the controller. Given the spec's exact Hungarian key names, a dedicated `ScoreResource` keeps it clean.

**Example:**
```php
// ScoreResource wraps the Score VO
public function toArray(Request $request): array
{
    return [
        'osszpontszam' => $this->resource->total(),
        'alappont'     => $this->resource->basePoints(),
        'tobbletpont'  => $this->resource->bonusPoints(),
    ];
}
```

To wrap a non-Model in a `JsonResource`, pass the VO as the constructor argument: `new ScoreResource($score)`.

### Pattern 7: Controller eager-loading to avoid lazy load exception

**What:** `Model::preventLazyLoading()` is active globally. The controller **must** eager-load all relationships before accessing them.

**Index endpoint:**
```php
Applicant::query()->with('program')->get()
```

**Score endpoint:** The `AdmissionScoringService` accesses `$applicant->examResults` and `$applicant->bonusPoints`. The controller must eager-load these before calling the service. However, the service also accesses `$applicant->program->subjects` via `ProgramRegistry`. Load all at once:
```php
$applicant->load('program.subjects', 'examResults', 'bonusPoints');
```

Note: Route model binding resolves the `Applicant` instance **without** eager loading (plain `find`). Therefore, `.load()` must be called in the controller after binding resolves.

### Pattern 8: Feature test with RefreshDatabase + seed

**What:** `RefreshDatabase` resets the DB before each test. Call `$this->seed()` to run `DatabaseSeeder` and get the 4 seeded applicants.

Pest 4 with Laravel: `pest()->use(RefreshDatabase::class)->in('Feature')` is in `tests/Pest.php` but commented out. The feature test class must either use the trait directly or activate it per-file.

**Correct Pest 4 approach:**
```php
// Source: Laravel 12 database-testing docs
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});
```

### Pattern 9: HTTP assertions

Per the pest-testing skill, prefer named assertion methods over `assertStatus()`:

| Scenario | Assertion |
|----------|-----------|
| 200 success | `->assertSuccessful()` |
| 422 error | `->assertStatus(422)` (no named alias for 422) |
| 404 not found | `->assertNotFound()` |
| JSON shape | `->assertJson(['data' => [...]])` |

**Example test:**
```php
it('returns score for applicant 1', function (): void {
    $response = $this->getJson('/api/v1/applicants/' . Applicant::CASE_1_UUID . '/score');

    $response
        ->assertSuccessful()
        ->assertJson([
            'data' => [
                'osszpontszam' => 470,
                'alappont'     => 370,
                'tobbletpont'  => 100,
            ],
        ]);
});
```

### Anti-Patterns to Avoid

- **Lazy-loading in controller:** Accessing `$applicant->examResults` without `load()` or `with()` will throw `LazyLoadingViolationException` — `preventLazyLoading()` is active globally.
- **Missing `api:` param in `withRouting()`:** Without this, `routes/api.php` is not loaded, giving 404 for all `/api/*` routes.
- **Catching `\Exception` instead of `AdmissionException`:** Will catch too broadly; the render closure must be typed to `AdmissionException`.
- **Using `assertStatus(200)` in tests:** Project skill specifies `assertSuccessful()`.
- **Forgetting `declare(strict_types=1)`:** Every PHP file in this project opens with this.
- **Non-final controller class:** `final class` is enforced by Pint.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| 404 for missing model | Custom try/catch + ModelNotFoundException | Implicit route model binding | Laravel does this automatically |
| JSON wrapping `{ data: ... }` | Manual `response()->json(['data' => ...])` | `JsonResource` return | Resources add `data` wrapper automatically; consistent with Laravel convention |
| Catching all AdmissionException subclasses | Six separate `render()` calls | One `render()` typed to abstract `AdmissionException` | PHP type-hint resolves to all subclasses |

**Key insight:** Laravel's exception handler `render()` callback matches by type-hint including subclasses — one catch-all on the abstract parent handles all 6 exception types.

## Common Pitfalls

### Pitfall 1: Route model binding does not eager-load

**What goes wrong:** Route model binding resolves `Applicant` via a simple `Applicant::find($value)`. When the controller accesses `$applicant->examResults`, `bonusPoints`, or `program.subjects`, the lazy loading guard throws.

**Why it happens:** `Model::preventLazyLoading()` is active. Implicit binding does not accept `with()` customization by default in Laravel 12 without explicit binding override.

**How to avoid:** Call `$applicant->load('program.subjects', 'examResults', 'bonusPoints')` at the top of the `score()` controller method, before passing `$applicant` to the service.

**Warning signs:** `Illuminate\Database\LazyLoadingViolationException` in test output.

### Pitfall 2: api.php not registered → all API routes return 404

**What goes wrong:** If `api:` key is missing from `withRouting()`, no API routes exist and all tests fail with 404.

**Why it happens:** Laravel 12 does not load `routes/api.php` automatically — it must be registered.

**How to avoid:** Add `api: __DIR__.'/../routes/api.php'` to `withRouting()` in `bootstrap/app.php`.

### Pitfall 3: RefreshDatabase not activated in feature test

**What goes wrong:** Tests run against a real (empty or stale) database and fail because seeded data is absent.

**Why it happens:** `tests/Pest.php` has `RefreshDatabase` commented out: `// ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)`. New feature test files must opt in explicitly.

**How to avoid:** Add `uses(RefreshDatabase::class)` at the top of the new feature test file.

### Pitfall 4: Score VO passed to JsonResource without adaption

**What goes wrong:** `JsonResource` normally wraps an Eloquent model. Passing a plain PHP object (Score VO) still works — `$this->resource` holds whatever was passed to the constructor — but if the `toArray()` method tries to use magic property access (e.g. `$this->total()`), it fails.

**Why it happens:** `JsonResource` proxies property access to the underlying model, not method calls.

**How to avoid:** In `ScoreResource::toArray()`, call methods on `$this->resource` explicitly: `$this->resource->total()`.

### Pitfall 5: Incorrect UUID for the 404 test

**What goes wrong:** Using a UUID that accidentally matches a seeded applicant or is invalid.

**How to avoid:** Use a well-formed but unused UUID like `'00000000-0000-0000-0000-000000000000'` or `Str::uuid()`.

## Code Examples

Verified patterns from official sources:

### bootstrap/app.php — full wiring

```php
// Source: Laravel 12 routing docs + errors docs
use App\Exceptions\AdmissionException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AdmissionException $e): \Illuminate\Http\JsonResponse {
            return response()->json(['error' => $e->getMessage()], 422);
        });
    })->create();
```

### routes/api.php — versioned group

```php
declare(strict_types=1);

use App\Http\Controllers\ApplicantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/applicants', [ApplicantController::class, 'index']);
    Route::get('/applicants/{applicant}/score', [ApplicantController::class, 'score']);
});
```

### ApplicantController — index + score

```php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ApplicantResource;
use App\Http\Resources\ScoreResource;
use App\Models\Applicant;
use App\Services\AdmissionScoringService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ApplicantController extends Controller
{
    public function __construct(
        private AdmissionScoringService $scoringService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ApplicantResource::collection(
            Applicant::query()->with('program')->get()
        );
    }

    public function score(Applicant $applicant): ScoreResource
    {
        $applicant->load('program.subjects', 'examResults', 'bonusPoints');

        $score = $this->scoringService->calculateForApplicant($applicant);

        return new ScoreResource($score);
    }
}
```

### ApplicantResource

```php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApplicantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'program' => [
                'university' => $this->program->university,
                'faculty'    => $this->program->faculty,
                'name'       => $this->program->name,
            ],
        ];
    }
}
```

### ScoreResource (wraps Score VO)

```php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ScoreResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'osszpontszam' => $this->resource->total(),
            'alappont'     => $this->resource->basePoints(),
            'tobbletpont'  => $this->resource->bonusPoints(),
        ];
    }
}
```

### Feature test skeleton

```php
declare(strict_types=1);

use App\Models\Applicant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed();
});

it('returns score 470 for applicant 1', function (): void {
    $this->getJson('/api/v1/applicants/' . Applicant::CASE_1_UUID . '/score')
        ->assertSuccessful()
        ->assertJson([
            'data' => [
                'osszpontszam' => 470,
                'alappont'     => 370,
                'tobbletpont'  => 100,
            ],
        ]);
});

it('returns 422 for applicant 3 (missing global mandatory subjects)', function (): void {
    $this->getJson('/api/v1/applicants/' . Applicant::CASE_3_UUID . '/score')
        ->assertStatus(422)
        ->assertJson([
            'error' => 'nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt',
        ]);
});

it('returns 404 for unknown applicant', function (): void {
    $this->getJson('/api/v1/applicants/00000000-0000-0000-0000-000000000000/score')
        ->assertNotFound();
});
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `app/Http/Kernel.php` middleware registration | `bootstrap/app.php` `withMiddleware()` | Laravel 11 | Kernel.php no longer exists in this project |
| `app/Exceptions/Handler.php` `render()` | `bootstrap/app.php` `withExceptions()` callback | Laravel 11 | Handler.php no longer exists; use `withExceptions()` |
| `php artisan install:api` for api routes | Manual `api:` param in `withRouting()` | Laravel 11+ | `install:api` installs Sanctum; not needed here |

**Deprecated/outdated:**
- `app/Exceptions/Handler.php`: Does not exist in Laravel 12 streamlined structure — do not create it.
- `$exceptions->render(function (\Exception $e) {...})`: Overly broad; always type-hint the specific exception class.

## Open Questions

1. **API-05 already done?**
   - What we know: `AppServiceProvider` already binds `ProgramRegistryInterface`, `BasePointCalculatorInterface`, and `BonusPointCalculatorInterface` as singletons.
   - What's unclear: The requirement says "ProgramRegistry bound as singleton" — this is complete from Phase 7.
   - Recommendation: Mark API-05 as satisfied by existing code; no new work needed. Include in PLAN as a verification step only.

2. **`final` on JsonResource subclasses**
   - What we know: Pint enforces `final_class` rule; all existing classes are `final`.
   - What's unclear: `JsonResource` uses `$this->resource` magic internally — `final` class is safe because we're not extending further.
   - Recommendation: Use `final class ApplicantResource extends JsonResource` and `final class ScoreResource extends JsonResource` — consistent with project convention.

## Validation Architecture

> nyquist_validation is not set in config.json — skipping this section.

## Sources

### Primary (HIGH confidence)

- Laravel 12 routing docs (via laravel-boost search-docs) — `withRouting(api:)` pattern, route groups, implicit binding
- Laravel 12 errors docs (via laravel-boost search-docs) — `withExceptions()->render()` typed closure pattern
- Laravel 12 eloquent-resources docs (via laravel-boost search-docs) — `JsonResource::toArray()`, collection methods, `AnonymousResourceCollection`
- Laravel 12 database-testing docs (via laravel-boost search-docs) — `RefreshDatabase`, `$this->seed()` patterns
- Existing codebase (Read tool) — `AppServiceProvider`, `AdmissionScoringService`, `Score` VO accessors, `Applicant` model UUID constants, `MissingGlobalMandatorySubjectException` message text

### Secondary (MEDIUM confidence)

- pest-testing SKILL.md — `assertSuccessful()` preference over `assertStatus(200)`, `assertNotFound()` alias

### Tertiary (LOW confidence)

- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all Laravel 12 patterns verified via search-docs
- Architecture: HIGH — direct code inspection of existing files confirms integration points
- Pitfalls: HIGH — `preventLazyLoading()` active in AppServiceProvider confirmed by code read; route registration pattern confirmed by docs

**Research date:** 2026-02-28
**Valid until:** 2026-03-28 (Laravel 12 stable, no fast-moving changes expected)
