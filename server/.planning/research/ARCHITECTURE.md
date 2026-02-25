# Architecture Patterns

**Domain:** Hungarian university admission score calculator API
**Researched:** 2026-02-25
**Sources:** IMPLEMENTATION.md (authoritative spec), PRD.md, codebase analysis files

---

## Recommended Architecture

A layered DDD-lite architecture with four distinct horizontal layers and clean unidirectional dependencies. The scoring engine is isolated from persistence and HTTP concerns by a Service layer and Value Object boundary.

```
┌────────────────────────────────────────────────────────┐
│  HTTP Layer (Controller + API Resource)                 │
│  app/Http/Controllers/Api/V1/ApplicantController        │
│  app/Http/Resources/Api/V1/ApplicantScoreResource       │
└───────────────────────┬────────────────────────────────┘
                        │ injects / calls
┌───────────────────────▼────────────────────────────────┐
│  Service Layer (Orchestration)                          │
│  app/Services/AdmissionScoringService                   │
│  app/Services/ProgramRegistry                           │
│  app/Services/BasePointCalculator                       │
│  app/Services/BonusPointCalculator                      │
│  app/Services/DatabaseProgramRequirements               │
└───────┬──────────────────────────┬─────────────────────┘
        │ maps to                  │ uses
┌───────▼──────────┐   ┌───────────▼─────────────────────┐
│  Domain Layer    │   │  Contracts                       │
│  (Value Objects) │   │  app/Contracts/                  │
│  ExamResult      │   │  ProgramRequirementsInterface    │
│  LanguageCert    │   └─────────────────────────────────-┘
│  Score           │
│  + Enums         │
│  + Exceptions    │
└──────────────────┘
┌────────────────────────────────────────────────────────┐
│  Persistence Layer (Eloquent Models + DB Schema)        │
│  app/Models/{Program, ProgramSubject, Applicant,        │
│              ApplicantExamResult, ApplicantBonusPoint}  │
│  database/migrations/, database/seeders/                │
└────────────────────────────────────────────────────────┘
```

---

## Component Boundaries

| Component | Responsibility | Input | Output | Communicates With |
|-----------|---------------|-------|--------|-------------------|
| `ApplicantController` | Route handling, model binding, response shaping | HTTP request + route param | JSON response (200/404/422) | `AdmissionScoringService`, `ApplicantScoreResource` |
| `ApplicantScoreResource` | Transform `Score` VO to API JSON shape | `Score` value object | `{ osszpontszam, alappont, tobbletpont }` | Controller only |
| `AdmissionScoringService` | Orchestrate the full scoring pipeline | `Applicant` Eloquent model | `Score` VO or throws `AdmissionException` | `ProgramRegistry`, `BasePointCalculator`, `BonusPointCalculator`, Enums, Value Objects |
| `ProgramRegistry` | Resolve programme requirements for an applicant | `Applicant` model | `ProgramRequirementsInterface` instance | `Applicant` model (eager-loads `program.subjects`) |
| `DatabaseProgramRequirements` | Provide programme rules from DB data | `Program` model (eager-loaded) | Mandatory subject, elective list, level requirement | `Program` model, `ProgramSubject` model, Enums |
| `BasePointCalculator` | Compute base points from two exam results | `ExamResult $mandatory`, `ExamResult $bestElective` | `int` (0–400) | `ExamResult` VO only |
| `BonusPointCalculator` | Compute capped bonus points | `ExamResult[]`, `LanguageCertificate[]` | `int` (0–100) | `ExamResult`, `LanguageCertificate` VOs only |
| `ExamResult` | Represent one exam result; enforce 20% minimum | `SubjectName`, `ExamLevel`, `int $percentage` | `points(): int`, `isAdvancedLevel(): bool` | Throws `FailedExamException` on construction if < 20% |
| `LanguageCertificate` | Represent one language certificate | `LanguageCertificateType`, `string $language` | `points(): int`, `language(): string` | `LanguageCertificateType` enum only |
| `Score` | Immutable score result VO | `int $basePoints`, `int $bonusPoints` | `total(): int`, `basePoints(): int`, `bonusPoints(): int` | Nothing |
| `AdmissionException` (abstract) + subclasses | Typed domain errors | Constructed with rule-violation context | Human-readable Hungarian error message | Rendered by `bootstrap/app.php` exception handler |
| `ProgramRequirementsInterface` | Contract between registry and scoring service | — | `getMandatorySubject()`, `getElectiveSubjects()`, `getMandatorySubjectLevel()` | Implemented by `DatabaseProgramRequirements` |
| Enums (`SubjectName`, `ExamLevel`, `LanguageCertificateType`) | Type-safe domain constants | — | Enum cases with typed values | Used across all domain and service classes |
| Eloquent Models (5) | DB persistence and relationships | — | Hydrated model instances | Used only by `ProgramRegistry` and `AdmissionScoringService` for initial data loading |

**Key boundary rule:** Eloquent models are consumed at the service layer boundary. Calculators and validators receive only Value Objects — they have zero knowledge of persistence.

---

## Data Flow

### Score Calculation Request (`GET /api/v1/applicants/{applicant}/score`)

```
HTTP Request
  → ApplicantController::score(Applicant $applicant)
      │ (route model binding resolves Applicant or 404)
      │
      ▼
  AdmissionScoringService::calculateForApplicant(Applicant $applicant)
      │
      ├── ProgramRegistry::findByApplicant(Applicant)
      │     └── eager-loads applicant->program->subjects
      │         → returns DatabaseProgramRequirements(Program)
      │
      ├── Map Eloquent rows → Value Objects
      │     ApplicantExamResult[] → ExamResult[]
      │       (constructor throws FailedExamException if percentage < 20%)
      │     ApplicantBonusPoint[] → LanguageCertificate[]
      │
      ├── Validation chain (in strict order, first failure throws and stops):
      │   1. FailedExamException          ← thrown during VO mapping above
      │   2. MissingGlobalMandatorySubjectException
      │   3. MissingProgramMandatorySubjectException
      │   4. ProgramMandatorySubjectLevelException
      │   5. MissingElectiveSubjectException
      │
      ├── BasePointCalculator::calculate(mandatory ExamResult, best elective ExamResult)
      │     → int basePoints (max 400)
      │
      ├── BonusPointCalculator::calculate(ExamResult[], LanguageCertificate[])
      │     → int bonusPoints (max 100, with dedup)
      │
      └── returns Score(basePoints, bonusPoints)
            │
            ▼
  ApplicantController receives Score VO
      → ApplicantScoreResource::make(Score)
          → JSON { data: { osszpontszam, alappont, tobbletpont } }
              → 200 OK

  On any AdmissionException:
      bootstrap/app.php exception renderer
          → JSON { error: "<Hungarian message>" } → 422
```

### Applicant List Request (`GET /api/v1/applicants`)

```
HTTP Request
  → ApplicantController::index()
      → Applicant::query()->with('program')->get()
      → ApplicantResource::collection(...)
      → JSON { data: [ { id, program: { university, faculty, name } } ] }
      → 200 OK
```

---

## Patterns to Follow

### Pattern 1: Value Object as Validation Gate

**What:** `ExamResult` throws `FailedExamException` during construction if `$percentage < 20`. The service maps Eloquent rows to VOs before running any other validation — so rule #1 (failed exam) is enforced automatically by the type system.

**When:** Any rule that applies to every individual domain object, not to collections or cross-object relationships.

**Example:**
```php
// In AdmissionScoringService — mapping triggers validation:
$examResults = $applicant->examResults
    ->map(fn (ApplicantExamResult $r) => new ExamResult(
        SubjectName::from($r->subject_name),
        ExamLevel::from($r->level),
        $r->percentage, // throws FailedExamException if < 20
    ))
    ->all();
```

### Pattern 2: Strategy via Interface + Registry

**What:** `ProgramRequirementsInterface` defines the contract. `DatabaseProgramRequirements` is the single concrete strategy, instantiated by `ProgramRegistry` after eager-loading programme data from the DB.

**When:** Programme requirements vary per programme but the retrieval mechanism is uniform (always DB-backed). New programmes are added via seeder/migration, not code.

**Why one concrete strategy:** The requirement to support multiple programmes comes from DB rows, not from needing different algorithms. A single DB-backed strategy handles all programmes.

**Example:**
```php
final class DatabaseProgramRequirements implements ProgramRequirementsInterface
{
    public function __construct(private readonly Program $program) {}

    public function getMandatorySubject(): SubjectName
    {
        return SubjectName::from(
            $this->program->subjects
                ->first(fn ($s) => $s->requirement_type === 'mandatory')
                ->subject_name
        );
    }
}
```

### Pattern 3: Ordered Validation Chain (Fail-Fast)

**What:** Each validation step runs in a fixed sequence. The first failure throws an exception and stops the pipeline. No partial results are accumulated.

**When:** Domain rules are mutually exclusive in priority — checking for an elective before confirming mandatory subjects are present would produce misleading errors.

**Implementation note:** Validation steps 2–5 are explicit guard clauses in `AdmissionScoringService::calculateForApplicant()`. Step 1 is implicit in VO construction.

### Pattern 4: Typed Exception Hierarchy

**What:** `AdmissionException` (abstract) is the base for all domain error types. Each exception encodes exactly one rule violation. `bootstrap/app.php` renders any `AdmissionException` as `422 + { "error": $e->getMessage() }`.

**When:** Multiple domain rules can fail in ways that need distinct error messages, but all map to the same HTTP status.

**Example hierarchy:**
```
AdmissionException (abstract)
├── FailedExamException
├── MissingGlobalMandatorySubjectException
├── MissingProgramMandatorySubjectException
├── ProgramMandatorySubjectLevelException
├── MissingElectiveSubjectException
└── UnknownProgramException
```

### Pattern 5: Eager Loading at the Registry Boundary

**What:** `ProgramRegistry` eager-loads `program.subjects` when resolving requirements for an applicant. Controllers eager-load `program` when listing applicants.

**When:** Every time you cross from persistence to domain logic. No lazy loading is allowed (the `AppServiceProvider` enforces `Model::preventLazyLoading()`).

**Example:**
```php
// ProgramRegistry
$applicant->program()->with('subjects')->firstOrFail();

// ApplicantController::index()
Applicant::query()->with('program')->get();
```

---

## Anti-Patterns to Avoid

### Anti-Pattern 1: Passing Eloquent Models into Calculators

**What:** Giving `BasePointCalculator` or `BonusPointCalculator` direct access to `ApplicantExamResult` models.

**Why bad:** Calculators become implicitly coupled to the DB schema. Deduplication and level checks must be reimplemented if the schema changes. Unit tests require database setup.

**Instead:** The service maps Eloquent rows to `ExamResult[]` and `LanguageCertificate[]` VOs before calling any calculator. Calculators receive only VOs.

### Anti-Pattern 2: Validation Inside Calculators

**What:** Checking for missing mandatory subjects or minimum percentage inside `BasePointCalculator`.

**Why bad:** Validation order is a business rule, not a calculation rule. Moving it into calculators makes the order implicit and untestable in isolation.

**Instead:** All validation is in `AdmissionScoringService` (or triggered by VO construction). Calculators receive already-validated VOs and only perform arithmetic.

### Anti-Pattern 3: Hardcoding Programme Requirements

**What:** Switch statements or per-programme strategy classes that encode subject lists in PHP.

**Why bad:** Adding a programme requires a code change and deployment. Violates the DB-backed requirement contract.

**Instead:** `DatabaseProgramRequirements` reads all requirements from `program_subjects` rows. New programmes are seedable without code changes.

### Anti-Pattern 4: Lazy Loading Across Request Boundary

**What:** Accessing `$applicant->examResults` inside a loop or inside `BonusPointCalculator` without prior eager loading.

**Why bad:** N+1 queries. The `preventLazyLoading()` constraint in `AppServiceProvider` will throw an exception in development.

**Instead:** `AdmissionScoringService` receives an `Applicant` model that must already have `examResults` and `bonusPoints` eager-loaded, or it loads them explicitly before the mapping step.

### Anti-Pattern 5: Mutable Score Aggregation

**What:** Accumulating base and bonus points in mutable variables inside `AdmissionScoringService` and returning an array.

**Why bad:** Loses type safety, makes the return contract implicit, harder to test.

**Instead:** Return an immutable `Score` value object from the service. The controller transforms it via `ApplicantScoreResource`.

---

## Component Build Order

The dependency graph dictates a strict bottom-up build order:

```
Layer 0 (no dependencies):
  Enums → SubjectName, ExamLevel, LanguageCertificateType

Layer 1 (depends on Enums):
  Exceptions → AdmissionException + all subclasses
  Value Objects → ExamResult, LanguageCertificate, Score

Layer 2 (depends on Enums):
  Contracts → ProgramRequirementsInterface

Layer 3 (depends on nothing application-specific):
  Migrations → 5 tables (programs, program_subjects, applicants,
               applicant_exam_results, applicant_bonus_points)
  Eloquent Models → Program, ProgramSubject, Applicant,
                    ApplicantExamResult, ApplicantBonusPoint
  Factories → ApplicantFactory, ApplicantExamResultFactory,
              ApplicantBonusPointFactory

Layer 4 (depends on Layer 3):
  Seeders → ProgramSeeder, ApplicantSeeder → DatabaseSeeder

Layer 5 (depends on Contracts + Models + Enums):
  DatabaseProgramRequirements → implements ProgramRequirementsInterface
  ProgramRegistry → wraps DatabaseProgramRequirements

Layer 6 (depends on Value Objects only):
  BasePointCalculator
  BonusPointCalculator

Layer 7 (depends on all of the above):
  AdmissionScoringService → orchestrates everything

Layer 8 (depends on Layer 7 + Models):
  ApplicantScoreResource
  ApplicantController
  routes/api/v1.php
  bootstrap/app.php exception renderer
  AppServiceProvider ProgramRegistry singleton
```

This matches the TDD sequence in IMPLEMENTATION.md exactly: tests for each layer can be written once the layer below is implemented.

---

## Scalability Considerations

This is a small, read-only API with seeded data. Scalability concerns are minimal and explicitly out of scope (no pagination, no auth, no CRUD). The following observations apply if the domain were to grow:

| Concern | Current approach | If scope grew |
|---------|-----------------|---------------|
| Multiple programmes | DB rows + single strategy class | Still works; no code changes needed |
| Large applicant dataset | No pagination | Add cursor pagination to `index` endpoint |
| Programme rules diversity | Single concrete strategy | Add per-type strategy classes only if DB structure becomes insufficient |
| Calculation accuracy | Pure PHP arithmetic | No change needed; deterministic and stateless |
| Test isolation | SQLite in-memory via `RefreshDatabase` | Stays fast regardless of programme count |

---

## Infrastructure Touchpoints

Two existing framework files require modification (not creation):

**`bootstrap/app.php`**
- Add `api` routing to point at `routes/api/v1.php` with `apiPrefix: 'api/v1'`
- Register `AdmissionException` renderer in `withExceptions()` callback

**`app/Providers/AppServiceProvider.php`**
- Bind `ProgramRegistry::class` as a singleton in `register()`

---

## Sources

- `IMPLEMENTATION.md` — Authoritative architecture specification (HIGH confidence; project-specific)
- `PRD.md` — Scoring rules and API contract (HIGH confidence; project-specific)
- `.planning/codebase/ARCHITECTURE.md` — Existing Laravel 12 skeleton patterns (HIGH confidence; codebase analysis)
- `.planning/codebase/STRUCTURE.md` — Directory layout and naming conventions (HIGH confidence; codebase analysis)
