---
phase: 02-routing-and-data-layer
plan: "02"
subsystem: api
tags: [tanstack-query, axios, vue-query, composables, typescript, error-handling]

# Dependency graph
requires:
  - phase: 01-foundation
    provides: Axios http client, TanStack Query setup, typed API interfaces (Applicant, ScoreResult, ApiError, ApiResponse)
provides:
  - useApplicants() composable: typed TanStack Query wrapper for GET /api/v1/applicants returning Applicant[]
  - useApplicantScore(id) composable: typed TanStack Query wrapper for GET /api/v1/applicants/{id}/score with 422 domain error discrimination
  - ScoreError discriminated union type for 422 vs generic error handling
affects: [03-list-view, 04-score-detail-view]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - TanStack Query v5 composable pattern with typed generics
    - MaybeRefOrGetter<string> + computed queryKey for reactive id parameters
    - 422 domain error discrimination via axios.isAxiosError in queryFn catch block
    - ScoreError discriminated union for exhaustive error handling in components
    - satisfies operator for compile-time union type narrowing on thrown errors
    - retry function guard: (_, error) => error.kind !== 'domain' to skip deterministic 422s

key-files:
  created:
    - src/composables/useApplicants.ts
    - src/composables/useApplicantScore.ts
  modified: []

key-decisions:
  - "useApplicants queryKey is flat ['applicants'] — simple and consistent with TanStack Query convention"
  - "useApplicantScore queryKey is computed() wrapping ['applicants', 'score', toValue(id)] for reactivity"
  - "Score endpoint returns ScoreResult directly (no ApiResponse<T> envelope) — differs from applicants list"
  - "axios imported in useApplicantScore only for isAxiosError static type guard — not for HTTP calls"
  - "ScoreError discriminated union uses kind: 'domain' | 'generic' — components switch on kind for display"
  - "422 domain errors are not retried (deterministic); generic errors use default TanStack Query retry"

patterns-established:
  - "Composable pattern: wrap useQuery, export function + error types as named exports"
  - "Error discrimination: catch AxiosError in queryFn, check status code, throw typed union"
  - "Reactive params: MaybeRefOrGetter<string> + computed queryKey ensures reactivity on id changes"
  - "Envelope unwrap: data.data for ApiResponse<T> endpoints, data directly for plain-type endpoints"

requirements-completed: [DATA-02, DATA-03, DATA-04]

# Metrics
duration: 2min
completed: 2026-02-28
---

# Phase 2 Plan 02: TanStack Query Data Composables Summary

**useApplicants and useApplicantScore composables with typed Applicant[] and ScoreResult returns, 422 domain error discrimination via ScoreError discriminated union, and retry guard skipping deterministic failures**

## Performance

- **Duration:** 2 min
- **Started:** 2026-02-28T18:01:39Z
- **Completed:** 2026-02-28T18:03:00Z
- **Tasks:** 2
- **Files modified:** 2

## Accomplishments
- `useApplicants()` composable wrapping GET /api/v1/applicants with typed Applicant[] return and ApiResponse<T> envelope unwrapping
- `useApplicantScore(id)` composable wrapping GET /api/v1/applicants/{id}/score with reactive MaybeRefOrGetter<string> id, computed queryKey, and full 422 error discrimination
- ScoreError discriminated union type exported for components to switch on kind ('domain' | 'generic')
- Retry guard preventing TanStack Query from retrying deterministic 422 domain errors while allowing retries for transient failures

## Task Commits

Each task was committed atomically:

1. **Task 1: Create useApplicants composable for applicant list query** - `ffde896` (feat)
2. **Task 2: Create useApplicantScore composable with 422 domain error discrimination** - `b1ac628` (feat)

**Plan metadata:** (docs: complete plan — pending)

## Files Created/Modified
- `src/composables/useApplicants.ts` - TanStack Query wrapper for GET /api/v1/applicants, returns typed Applicant[]
- `src/composables/useApplicantScore.ts` - TanStack Query wrapper for GET /api/v1/applicants/{id}/score with ScoreError discrimination

## Decisions Made
- Score endpoint does NOT use the Laravel ApiResponse<T> envelope (confirmed in plan spec) — http.get<ScoreResult> returns data directly, not wrapped
- axios imported only for `axios.isAxiosError` static utility in useApplicantScore — this is the one exception to the "never import axios directly" rule
- ScoreError union type uses inline-on-one-line format per Biome formatter preference (fits under 80 chars)

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 3 - Blocking] Fixed Biome import ordering in src/router/index.ts**
- **Found during:** Task 1 (useApplicants verification)
- **Issue:** Pre-existing import ordering violation in src/router/index.ts caused `npx biome check .` to fail, blocking Task 1 verification
- **Fix:** Ran `npx biome check --write src/router/index.ts` to auto-fix import order
- **Files modified:** src/router/index.ts
- **Verification:** `npx biome check .` passed with no errors
- **Committed in:** ffde896 (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (1 blocking)
**Impact on plan:** Necessary fix for pre-existing Biome violation introduced by 02-01 work that wasn't yet committed/verified. No scope creep.

## Issues Encountered
- Biome formatter enforces single-line union types when they fit within the line limit — the multi-line ScoreError union definition from the plan was reformatted to one line. Semantically identical.
- Biome formatter collapsed the multi-line `http.get<ScoreResult>(url)` call to one line. Semantically identical.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Both data composables are ready for Phase 3 (list view) to import `useApplicants`
- Phase 4 (score detail view) can import `useApplicantScore` and `ScoreError` type for error display
- Components should use `toRef(() => route.params.id as string)` when passing reactive route params to `useApplicantScore`

## Self-Check: PASSED

- FOUND: src/composables/useApplicants.ts
- FOUND: src/composables/useApplicantScore.ts
- FOUND: .planning/phases/02-routing-and-data-layer/02-02-SUMMARY.md
- FOUND: commit ffde896 (Task 1)
- FOUND: commit b1ac628 (Task 2)

---
*Phase: 02-routing-and-data-layer*
*Completed: 2026-02-28*
