# Architecture

**Analysis Date:** 2026-02-28

## Pattern Overview

**Overall:** Monorepo with decoupled client-server architecture. Backend uses layered architecture with domain-driven design patterns; frontend uses component-based reactive pattern with data query caching.

**Key Characteristics:**
- Monorepo structure with `client/` (Vue 3) and `server/` (Laravel 12)
- Backend implements explicit separation of concerns via Services, Models, Resources, and Value Objects
- Frontend uses composable pattern for data fetching with TanStack Vue Query
- API-first communication via REST with JSON envelopes
- Admission scoring is core domain with sophisticated validation rules

## Layers

**API Layer (Backend):**
- Purpose: HTTP request/response boundary, request routing, resource transformation
- Location: `server/app/Http/` containing `Controllers/`, `Resources/`
- Contains: `ApplicantController` coordinating admission logic, `ApplicantResource` and `ScoreResource` transforming Eloquent models to API responses
- Depends on: Service layer, Models, Eloquent Resources
- Used by: Frontend client via REST calls

**Service Layer (Backend):**
- Purpose: Coordinate business logic, orchestrate domain calculations, coordinate between Models and Value Objects
- Location: `server/app/Services/`
- Contains: `AdmissionScoringService` orchestrates exam result validation and scoring; calculator abstractions (`BasePointCalculatorInterface`, `BonusPointCalculatorInterface`); `ProgramRegistry` resolves program requirements
- Depends on: Models, Value Objects, Exceptions, Contracts
- Used by: Controllers

**Model Layer (Backend):**
- Purpose: Database abstraction and Eloquent relationships
- Location: `server/app/Models/`
- Contains: `Applicant` (UUID keyed, has many exam results and bonus points), `Program` (university/faculty), `ProgramSubject` (program electives), `ApplicantExamResult`, `ApplicantBonusPoint`
- Depends on: Database migrations, factories, seeders
- Used by: Controllers, Services

**Value Object Layer (Backend):**
- Purpose: Encapsulate domain logic and validation, immutable data representations
- Location: `server/app/ValueObjects/`
- Contains: `Score` (base + bonus points with validation), `ExamResult` (subject, level, percentage with failure threshold), `LanguageCertificate`
- Depends on: Custom exceptions for validation
- Used by: AdmissionScoringService for calculations and validation

**Exception Layer (Backend):**
- Purpose: Domain-specific error handling with semantic meaning
- Location: `server/app/Exceptions/`
- Contains: Base `AdmissionException`, specific subclasses: `FailedExamException`, `MissingGlobalMandatorySubjectException`, `MissingProgramMandatorySubjectException`, `MissingElectiveSubjectException`, `ProgramMandatorySubjectLevelException`, `UnknownProgramException`
- Depends on: Configured in `bootstrap/app.php` for rendering
- Used by: Service layer throws; controller error handling via exception rendering

**View Layer (Frontend):**
- Purpose: User interface rendering and interactivity
- Location: `client/src/views/`
- Contains: `ApplicantsView.vue` (list with loading/error states), `ApplicantDetailView.vue` (score detail)
- Depends on: Composables for data, router, Tailwind CSS
- Used by: Router for page rendering

**Composable Layer (Frontend):**
- Purpose: Reusable logic for data fetching, state management, and side effects
- Location: `client/src/composables/`
- Contains: `useApplicants()` queries list, `useApplicantScore(id)` queries scoring with error discrimination, `useProgress` tracks navigation state
- Depends on: HTTP client, TanStack Vue Query, router
- Used by: Views for data access

**HTTP Client Layer (Frontend):**
- Purpose: HTTP abstraction and request configuration
- Location: `client/src/lib/`
- Contains: `http` (Axios instance with base URL), `queryClient` (TanStack Vue Query with 30-min stale time)
- Depends on: Axios, TanStack Query
- Used by: Composables for API calls

## Data Flow

**Applicant List Fetch:**
1. `ApplicantsView` component mounts
2. Calls `useApplicants()` composable
3. Composable invokes `http.get('/api/v1/applicants')` via TanStack Query
4. Backend `ApplicantController.index()` queries `Applicant::with('program')`
5. Returns `ApplicantResource::collection()` with JSON envelope
6. Frontend unwraps `data.data` array, Vue reactivity binds to template
7. User navigates to detail via `RouterLink`

**Score Calculation Flow:**
1. `ApplicantDetailView` mounts with applicant ID from route param
2. Calls `useApplicantScore(id)` composable
3. Composable fires GET `/api/v1/applicants/{id}/score`
4. Backend `ApplicantController.score()` loads relationships: `program.subjects`, `examResults`, `bonusPoints`
5. Passes to `AdmissionScoringService.calculateForApplicant()`:
   - Maps Eloquent rows to Value Objects (ExamResult, LanguageCertificate)
   - Validates global mandatory subjects (throws if missing)
   - Resolves program requirements via `ProgramRegistry`
   - Validates program mandatory subject present and level correct
   - Finds best-scoring elective
   - Calculates base points (mandatory + best elective via `BasePointCalculator`)
   - Calculates bonus points via `BonusPointCalculator`
   - Returns `Score` value object
6. `ScoreResource.toArray()` transforms to JSON envelope
7. Frontend receives, unwraps `data.data`, displays osszpontszam/alappont/tobbletpont
8. On domain error (422 status), composable transforms to `{ kind: 'domain', message: string }` error
9. On generic error, composable transforms to `{ kind: 'generic' }` and component shows fallback UI

**State Management:**
- No centralized store; TanStack Vue Query manages server state with caching
- Navigation state managed via `useProgress` ref (boolean)
- Reactive parameters passed as ref/getter to composables for dynamic queries
- Query keys include applicant ID to isolate score caches

## Key Abstractions

**AdmissionScoringService:**
- Purpose: Orchestrate the multi-step validation and scoring algorithm
- Examples: `server/app/Services/AdmissionScoringService.php`
- Pattern: Uses composition of calculator interfaces and registry; throws early on validation failure; maps domain data to VOs for type safety

**Program Requirements Interface:**
- Purpose: Abstract program-specific rules (mandatory subject, level, electives)
- Examples: `server/app/Contracts/ProgramRequirementsInterface.php`, implemented by `DatabaseProgramRequirements`
- Pattern: Strategy pattern; injected into service; decouples scoring from program resolution

**Calculator Interfaces:**
- Purpose: Abstract point calculation algorithms
- Examples: `BasePointCalculatorInterface`, `BonusPointCalculatorInterface` in `server/app/Contracts/`
- Pattern: Strategy pattern; separate concerns of base vs bonus scoring; injectable for testing/extension

**Value Objects:**
- Purpose: Enforce validation and domain invariants at construction
- Examples: `ExamResult` (throws if percentage < 20% = FailedExamException), `Score` (throws if negative)
- Pattern: Immutable, throw on invalid state, provide semantic methods (e.g., `isAdvancedLevel()`)

## Entry Points

**Backend Entry:**
- Location: `server/public/index.php`
- Triggers: HTTP requests to any path
- Responsibilities: Bootstrap Laravel, capture request, dispatch to router

**API Routes:**
- Location: `server/routes/api.php`
- Triggers: Requests to `/api/v1/*`
- Responsibilities: Route to controllers (`ApplicantController` index and score actions)

**Frontend Entry:**
- Location: `client/src/main.ts`
- Triggers: Page load
- Responsibilities: Mount Vue app, register router, TanStack Query plugin, load CSS

**Router Entry:**
- Location: `client/src/router/index.ts`
- Triggers: Navigation events
- Responsibilities: Define routes, attach navigation guards (progress tracking), render components

## Error Handling

**Strategy:**
- Backend: Domain exceptions thrown in service layer, caught and rendered as 422 JSON in `bootstrap/app.php`
- Frontend: HTTP 422 errors detected in composable, transformed to domain error type; generic errors treated as `kind: 'generic'`
- Composables disable retry on domain errors (validation failures are terminal)

**Patterns:**
- Backend throws domain-specific exceptions with messages suitable for UI display
- Frontend composables distinguish error types to provide semantic error handling
- Views show loading skeleton, error UI, empty state, or success states based on query status
- Navigation guard sets progress flag to show top-bar loading indicator

## Cross-Cutting Concerns

**Logging:** Not explicitly configured; Laravel defaults to stack driver in config; no client-side logging observed

**Validation:**
- Backend: Eloquent model relationships with UUID keys; Value Object constructors enforce invariants
- Frontend: TypeScript interfaces (`api.ts`) provide type checking; composables handle API errors

**Authentication:** No authentication observed; system assumes open access (no auth middleware in `bootstrap/app.php`)

**CORS:** Not configured in visible code; assumes server and client share origin or CORS headers already set

---

*Architecture analysis: 2026-02-28*
