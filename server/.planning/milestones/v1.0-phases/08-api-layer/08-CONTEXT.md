# Phase 8: API Layer - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Two REST endpoints (applicant list + score calculation), exception rendering for AdmissionException subclasses as 422 JSON, 404 for unknown applicants, and feature tests covering all four acceptance cases plus the 404 case. No authentication, no CRUD, no pagination — API is read-only against seeded data.

</domain>

<decisions>
## Implementation Decisions

### Claude's Discretion

User confirmed the spec is tight enough — Claude handles all remaining implementation decisions:

- **Applicant list response shape**: Applicant ID + nested programme details (university, faculty, name). Use Laravel Eloquent API Resource for consistent formatting.
- **Response envelope**: Both endpoints wrap in `{ data: ... }` — standard Laravel Resource convention. Score endpoint uses Hungarian keys as spec'd: `osszpontszam`, `alappont`, `tobbletpont`.
- **Error response format**: `{ error: "<Hungarian message>" }` with 422 status for all AdmissionException subclasses. No extra metadata — keep it simple per spec.
- **Exception rendering**: Wire in `bootstrap/app.php` via `withExceptions()` callback — render AdmissionException as 422 JSON.
- **Route model binding**: Implicit binding on `{applicant}` (UUID-based). Laravel's default 404 handling for missing models.
- **API versioning**: `routes/api.php` with `/v1` prefix group.
- **Feature test strategy**: RefreshDatabase + seeder for acceptance test data. One test class covering all 5 cases (4 applicants + 404).

</decisions>

<specifics>
## Specific Ideas

No specific requirements — spec defines exact JSON shapes, HTTP status codes, and Hungarian field names. Open to standard Laravel approaches.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `AdmissionScoringService`: Fully built, accepts `Applicant` model, returns `Score` VO — controller calls this directly
- `Score` VO: Exposes `total()`, `basePoints()`, `bonusPoints()` — maps to `osszpontszam`, `alappont`, `tobbletpont`
- `Applicant` model: Uses `HasUuids`, has `program()`, `examResults()`, `bonusPoints()` relationships
- `AppServiceProvider`: Already binds `ProgramRegistryInterface`, `BasePointCalculatorInterface`, `BonusPointCalculatorInterface` as singletons
- 6 AdmissionException subclasses: Each carries Hungarian error messages, need 422 rendering

### Established Patterns
- `declare(strict_types=1)` on all files
- `final class` convention (Pint enforced)
- Constructor property promotion for DI
- `Model::preventLazyLoading()` — must eager-load relationships in controller
- Pest 4 for testing with `--compact` flag

### Integration Points
- `bootstrap/app.php`: Add `api:` route file in `withRouting()`, add AdmissionException rendering in `withExceptions()`
- `routes/api.php`: New file with v1 prefix group
- Controller: New `ApplicantController` with `index()` and `score()` methods
- Resources: New `ApplicantResource` and `ScoreResource` (or inline in controller)

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 08-api-layer*
*Context gathered: 2026-02-28*
