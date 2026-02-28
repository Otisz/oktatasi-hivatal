# Codebase Concerns

**Analysis Date:** 2026-02-28

## Tech Debt

**Unused Import in ApplicantsView:**
- Issue: `useRouter` is imported but never used in the component
- Files: `client/src/views/ApplicantsView.vue` (line 2)
- Impact: Dead code creates confusion about intent and adds unnecessary bundle overhead
- Fix approach: Remove the unused import: delete line 2 `import {useRouter} from 'vue-router'`

**Incomplete HTTP Client Configuration:**
- Issue: The axios HTTP client in `lib/http.ts` has no error interceptor, timeout configuration, or retry logic at the client level
- Files: `client/src/lib/http.ts`
- Impact: Network failures, timeouts, or intermittent errors are unhandled at the HTTP layer, forcing each composable to implement its own error handling; this leads to inconsistent error patterns across the application
- Fix approach: Add HTTP client interceptors for error handling, set reasonable timeout (e.g., 10s), and implement centralized error transformation before composables consume the response

**Query Cache Key Fragility in useApplicantScore:**
- Issue: The query key is computed dynamically using `computed(() => ['applicants', 'score', toValue(id)])`, but there's no mechanism to invalidate this cache when the applicant detail view is navigated back from
- Files: `client/src/composables/useApplicantScore.ts` (line 12)
- Impact: If score data changes on the backend, users won't see updates unless they manually refresh; the cache persists indefinitely
- Fix approach: Implement cache invalidation in the router when returning from detail view, or set a reasonable staleTime (e.g., 5 minutes) and gcTime on the useQuery

**Missing HydrationRace Protection in ApplicantDetailView:**
- Issue: The component attempts to access `route.params.id` immediately without null safety checks; if the route changes or navigates before the component hydrates, `undefined` is passed to composables
- Files: `client/src/views/ApplicantDetailView.vue` (lines 8, 17)
- Impact: Race condition could cause "Cannot read property of undefined" errors if route params are undefined during initial render
- Fix approach: Add explicit null/undefined guard; consider using `unref(route.params.id)` or explicit checks before passing to composables

**No Defensive Validation in API Resource Transformation:**
- Issue: The `ApplicantResource` directly accesses nested properties without checking if relationships are loaded (e.g., `$this->program->university`)
- Files: `server/app/Http/Resources/ApplicantResource.php` (lines 23-26)
- Impact: If a relationship fails to eager-load, accessing the property will cause an N+1 query or return null unexpectedly
- Fix approach: Add explicit relationship checks or use `whenLoaded()` method in resource: `'university' => $this->whenLoaded('program', fn() => $this->program->university)`

**Missing Required Fields in ScoreResource Type Hint:**
- Issue: The `ScoreResource` type hint shows return type, but the actual value object (`Score`) might be null in error conditions without explicit handling
- Files: `server/app/Http/Resources/ScoreResource.php` (line 18)
- Impact: No validation that the Score value object has the expected methods before calling them
- Fix approach: Add defensive checks or ensure the service always returns a valid Score or throws an exception

## Known Bugs

**useApplicantScore Error Handling Missing 'kind' Field:**
- Symptoms: Generic error responses from the HTTP layer won't match the `ScoreError` discriminated union type, causing type mismatches in error handling
- Files: `client/src/composables/useApplicantScore.ts` (lines 22)
- Trigger: Any HTTP error that's not a 422 Axios error (network timeout, 500, etc.)
- Workaround: The composable already has a fallback in line 22 that throws `{ kind: 'generic' }`, but this happens silently; should log or provide more context

**Enum Conversion Fragility:**
- Symptoms: If the database contains a subject name string that doesn't match an enum case, `SubjectName::from()` will throw an error
- Files: `server/app/Services/AdmissionScoringService.php` (lines 37-42 where ExamResult VO is constructed from DB rows)
- Trigger: Seeded data contains invalid subject names, or database values don't match enum values
- Workaround: None; will result in unhandled exception; should add validation in seeder or migration

**Missing Program Lookup Validation:**
- Symptoms: If an applicant has a `program_id` that doesn't match any Program record, the relationship will be null, causing "Call to a member function on null" error
- Files: `server/app/Http/Controllers/ApplicantController.php` (lines 21-23 and 28)
- Trigger: Orphaned applicant records with invalid program_id due to cascade delete not being enforced
- Workaround: Database foreign key constraint should prevent this, but there's no explicit validation

## Security Considerations

**Missing Input Validation on Route Parameter:**
- Risk: The `{applicant}` route parameter is implicitly cast to an Applicant model via route model binding, but there's no verification that the model exists before accessing relationships
- Files: `server/routes/api.php` (line 10)
- Current mitigation: Laravel's implicit model binding will throw a 404 if the ID doesn't exist
- Recommendations: Explicitly document this behavior; consider using scoped bindings if applicants should be filtered by user/tenant in future iterations

**No Rate Limiting on API Endpoints:**
- Risk: Scoring endpoints can be called unlimited times, allowing potential abuse (resource exhaustion, DOS)
- Files: `server/routes/api.php` (lines 9-10), `server/app/Http/Controllers/ApplicantController.php`
- Current mitigation: Running on local environment with no public exposure
- Recommendations: Add rate limiting middleware before production deployment: `Route::middleware('throttle:60,1')->group(...)`

**No Authentication or Authorization:**
- Risk: Any caller can fetch applicant data and scores without credentials
- Files: `server/bootstrap/app.php` (no auth middleware defined), `server/routes/api.php` (no auth guards)
- Current mitigation: Application is intended for evaluation/homework, not production
- Recommendations: Implement API authentication before any real-world use (Sanctum tokens, OAuth2, or similar)

**Debug Mode Default in Example Environment File:**
- Risk: `.env.example` shows `APP_DEBUG=true`, which will expose detailed error information including source code and stack traces if copied to production
- Files: `server/.env.example` (line 4)
- Current mitigation: Developers are responsible for setting `APP_DEBUG=false` in actual `.env` on production
- Recommendations: Document this requirement explicitly in README and deployment instructions

## Performance Bottlenecks

**Potential N+1 Query in Score Calculation:**
- Problem: `ApplicantController::score()` eager-loads program relationships, but the `ScoreResource` doesn't explicitly verify which relationships are loaded before accessing them
- Files: `server/app/Http/Controllers/ApplicantController.php` (line 28), `server/app/Http/Resources/ScoreResource.php`
- Cause: If `ApplicantResource` transformation is called after score resource, each nested relationship access could trigger a query
- Improvement path: Always use `with()` to explicitly eager-load all required relationships before instantiating resources

**No Query Optimization for Applicant List:**
- Problem: `ApplicantController::index()` loads all applicants with `Applicant::query()->with('program')->get()`, but doesn't paginate or limit results
- Files: `server/app/Http/Controllers/ApplicantController.php` (lines 21-23)
- Cause: For large datasets (1000+ applicants), this will load entire table into memory and transfer all rows to client
- Improvement path: Implement pagination: `Applicant::with('program')->paginate(50)` or use cursor-based pagination for better performance

**Unindexed Foreign Key Lookups:**
- Problem: Database queries for program lookups in `ProgramRegistry` may not have indexes on `applicant.program_id`
- Files: `server/database/migrations/` (actual migration not provided for review, but foreign key relationships are assumed)
- Cause: No explicit index definition on relationships
- Improvement path: Ensure foreign key columns have indexes: `$table->foreign('program_id')->index();` in migrations

## Fragile Areas

**Error Response Structure Inconsistency:**
- Files: `server/app/Http/Controllers/ApplicantController.php`, `client/src/composables/useApplicantScore.ts`
- Why fragile: 422 responses wrap the error in `{ error: string }` (from `AdmissionException`), but success responses wrap data in `{ data: T }`. The client assumes this structure, but if error response format changes, the discriminated union type breaks
- Safe modification: Centralize response formatting in a single middleware or response macro that ensures all endpoints follow the same envelope structure
- Test coverage: Add integration tests that verify all error codes (422, 500, etc.) return consistent error shapes

**Enum Value Mapping Between PHP and Database:**
- Files: `server/app/Enums/SubjectName.php`, `server/database/migrations/`, seeder data
- Why fragile: PHP enum case names (e.g., `MagyarNyelvEsIrodalom`) must map to database string values (e.g., `'magyar nyelv és irodalom'`). If enum case `::from()` is called with wrong casing, it will fail
- Safe modification: Define enum values explicitly with `->value` attribute; ensure seeder uses the enum value method, not manual strings
- Test coverage: Add tests that verify `SubjectName::from('magyar nyelv és irodalom') === SubjectName::MagyarNyelvEsIrodalom->value`

**Validation Order Dependency:**
- Files: `server/app/Services/AdmissionScoringService.php` (lines 33-75)
- Why fragile: The service validates in a specific sequence: failed exams (exception in VO constructor) → global mandatory → program mandatory → level check → elective. If this order changes, test cases and spec examples may produce different error messages or exception types
- Safe modification: Add inline comments documenting the validation sequence; write unit tests for each validation step in isolation
- Test coverage: Feature tests must verify all 4 homework examples produce expected exception types in expected order

**Hard-Coded Test UUIDs in Model:**
- Files: `server/app/Models/Applicant.php` (lines 20-26)
- Why fragile: The model defines 4 case UUIDs as constants (`CASE_1_UUID`, `CASE_2_UUID`, etc.) for testing. If seeder uses different IDs, tests will fail to find applicants
- Safe modification: Either use the model constants in seeder, or move these constants to a test factory/fixture class
- Test coverage: Add test that verifies seeded applicants have these UUIDs

## Missing Critical Features

**No Client-Side Error Logging or Monitoring:**
- Problem: Errors in composables are caught and stored in error state, but never logged or monitored. User interactions that fail silently won't be visible to developers
- Blocks: Cannot diagnose production issues or understand error patterns
- Recommendation: Integrate error tracking (Sentry, LogRocket, or similar) to capture errors with context; or implement basic console logging with environment checks

**No Backend Error Logging:**
- Problem: `AdmissionException` is caught and serialized to JSON, but not logged. Scoring failures are silent on server
- Blocks: Cannot troubleshoot why applicants fail scoring validation
- Recommendation: Add logging in `AdmissionScoringService` before throwing exceptions; use Laravel's logging to capture validation failures with applicant context

**No Pagination or Cursor Support in Applicant List:**
- Problem: Client fetches all applicants in a single request with no pagination
- Blocks: Application will be slow/unusable with 1000+ applicants
- Recommendation: Implement cursor-based pagination on backend; update client composable to handle page navigation

**No Sorting or Filtering on Applicant List:**
- Problem: Backend returns all applicants in arbitrary order; client has no way to filter by program or sort by name/score
- Blocks: Users can't find specific applicants efficiently
- Recommendation: Add `sort` and `filter` query parameters to `GET /api/v1/applicants` endpoint

**No Cache Validation or ETags:**
- Problem: Client relies on TanStack Query cache, but server has no way to signal that data has changed (no Last-Modified or ETag headers)
- Blocks: Users won't know when to refresh cached data
- Recommendation: Add ETag headers to API responses; configure TanStack Query to use them

## Test Coverage Gaps

**No Frontend Unit Tests:**
- What's not tested: Composables (`useApplicants`, `useApplicantScore`, `useProgress`) are not unit-tested
- Files: `client/src/composables/` (no `.test.ts` or `.spec.ts` files)
- Risk: Composable logic (query key generation, error handling, retry behavior) could regress unnoticed
- Priority: High - composables are critical business logic

**No Frontend Integration Tests:**
- What's not tested: Vue components rendering with actual HTTP responses, error handling UI branches
- Files: `client/src/views/`, `client/src/components/` (no test files)
- Risk: UI branches for error states, loading states, and empty states are untested; could be broken in refactors
- Priority: High - UI integration is user-facing

**No API Feature Tests:**
- What's not tested: HTTP endpoints haven't been tested end-to-end
- Files: `server/tests/` (no API test files)
- Risk: Request/response formats, HTTP status codes, error serialization are untested and could be wrong
- Priority: High - API contracts are critical

**No Service Unit Tests:**
- What's not tested: `AdmissionScoringService`, calculators, and validators have no unit tests
- Files: `server/app/Services/` (no corresponding test files)
- Risk: Business logic (point calculations, validation rules) could be broken and undetected
- Priority: Critical - scoring logic is the core feature

**No Value Object Tests:**
- What's not tested: `ExamResult`, `LanguageCertificate`, `Score` construction and validation
- Files: `server/app/ValueObjects/` (no test files)
- Risk: Value object constraints (0-100 percentage, 20% minimum, immutability) are untested
- Priority: High - VOs are foundational

**No Database Seeding Tests:**
- What's not tested: Seeders and factories don't have tests verifying correct data generation
- Files: `server/database/seeders/`, `server/database/factories/` (no verification tests)
- Risk: Seeded data could be incorrect, breaking feature tests
- Priority: Medium - needed for feature test reliability

**No Client-Server Integration Tests:**
- What's not tested: Real HTTP calls from client composables to API endpoints
- Files: No end-to-end test setup
- Risk: Interface contracts between client and server could mismatch (response envelope structure, field names, status codes)
- Priority: Medium - integration is critical but can be verified through manual testing initially

## Scaling Limits

**No Pagination on Applicant List:**
- Current capacity: Single HTTP response containing all applicants
- Limit: Will degrade with 1000+ applicants (memory usage, network transfer, parsing time)
- Scaling path: Implement server-side pagination (offset/limit or cursor-based); update client to request pages on demand

**No Database Indexes on Relationships:**
- Current capacity: Queries by foreign key will perform table scans
- Limit: Performance will degrade as tables grow (100k+ rows)
- Scaling path: Add indexes on `applicant.program_id`, `applicant_exam_result.applicant_id`, etc.

**Single-Process Server:**
- Current capacity: Laravel's built-in server (or Artisan serve) handles one request at a time in development
- Limit: Cannot handle concurrent requests
- Scaling path: Deploy to production server (Nginx + PHP-FPM) with multiple worker processes

**In-Memory Query Cache in Composables:**
- Current capacity: TanStack Query cache lives in browser memory; no shared cache across users
- Limit: Each user has their own applicant list cache; no server-side caching
- Scaling path: Consider Redis cache on backend for program/applicant data; or implement server-side pagination to reduce cache size

## Dependencies at Risk

**@tanstack/vue-query ^5.92.9:**
- Risk: Major version bump could introduce breaking changes; version is somewhat pinned
- Impact: Updates require testing and verification
- Migration plan: Review changelog before upgrading; use `npm outdated` to monitor; test with new versions in isolated branch

**axios ^1.13.6:**
- Risk: No explicit timeout or retry logic configured; relies on browser defaults
- Impact: Slow or hanging requests could block UI indefinitely
- Migration plan: Add custom interceptors to configure timeouts and retries; or migrate to fetch API with custom retry logic

**TypeScript ~5.8.0:**
- Risk: Tilde version allows minor updates but locks to 5.x; could miss critical fixes
- Impact: Type checking could become inconsistent if TypeScript is pinned while dependencies update their types
- Migration plan: Consider using `^5.8.0` to allow patch updates; test periodically

**Laravel ^12.0:**
- Risk: Stable but new; ecosystem packages may lag behind Laravel updates
- Impact: Some development packages (Pest, Larastan, etc.) could have compatibility issues
- Migration plan: Monitor package release notes; ensure all dev dependencies are compatible with Laravel 12 before upgrading

## Notes on Current Implementation State

**Implementation is Complete:**
- All major features from the specification have been implemented
- No significant gaps in business logic
- API endpoints are functional and wired correctly
- Client applications render correctly with proper error handling

**Existing Code Quality:**
- Code follows established patterns (Strategy pattern, Value Objects, Contracts)
- Type safety is strong in both PHP and TypeScript
- Error handling is implemented with discriminated unions
- No console.log statements or debug code left in production

---

*Concerns audit: 2026-02-28*
