---
phase: 01-foundation
plan: "02"
subsystem: api
tags: [axios, tanstack-query, typescript, vue-query, api-types]

requires:
  - phase: 01-01
    provides: Vue 3 + Vite + TypeScript scaffold with @ path alias, src/lib and src/types directories

provides:
  - src/types/api.ts with Program, Applicant, ScoreResult, ApiError, ApiResponse<T> interfaces
  - src/lib/http.ts: single Axios instance reading VITE_API_BASE_URL as baseURL
  - src/lib/query.ts: QueryClient with 5-minute staleTime
  - src/main.ts: VueQueryPlugin registered with queryClient before mount
  - .env.development, .env.production, .env.example with VITE_API_BASE_URL

affects:
  - 01-03 (any subsequent foundation plans)
  - Phase 2 composables (all API composables import http from @/lib/http and queryClient from @/lib/query)

tech-stack:
  added:
    - "@tanstack/vue-query"
    - axios
  patterns:
    - Single Axios instance from @/lib/http — never import axios directly in feature code
    - VueQueryPlugin registered before mount with explicit queryClient
    - ApiResponse<T> generic wrapper matches Laravel API Resources { data: ... } envelope
    - VITE_ prefix required on all env vars for Vite browser exposure

key-files:
  created:
    - src/types/api.ts
    - src/lib/http.ts
    - src/lib/query.ts
    - .env.development
    - .env.production
    - .env.example
  modified:
    - src/main.ts (added VueQueryPlugin + queryClient registration)

key-decisions:
  - "Hungarian field names (osszpontszam, alappont, tobbletpont) used verbatim in ScoreResult — must match Laravel API Resources exactly"
  - "ApiResponse<T> generic wrapper matches server JSON:API-style { data: ... } envelope from Laravel"
  - "5-minute staleTime on QueryClient — appropriate for read-only seeded dataset"

patterns-established:
  - "Pattern 5 - API client: Single Axios instance from @/lib/http with VITE_API_BASE_URL baseURL; never use axios directly"
  - "Pattern 6 - Data fetching: TanStack Query (VueQueryPlugin) registered before mount; composables use useQuery/useMutation"
  - "Pattern 7 - API types: ApiResponse<T> generic wraps Laravel Resource responses; interfaces in src/types/api.ts"

requirements-completed: [INFRA-03, INFRA-04, INFRA-05, DATA-01]

duration: 1min
completed: "2026-02-28"
---

# Phase 1 Plan 02: API Data Layer Summary

**Axios HTTP client + TanStack Query registered as VueQueryPlugin with typed Laravel API interfaces (Program, Applicant, ScoreResult) and VITE_API_BASE_URL-driven env config**

## Performance

- **Duration:** 1 min
- **Started:** 2026-02-28T17:01:30Z
- **Completed:** 2026-02-28T17:02:48Z
- **Tasks:** 2
- **Files modified:** 6 created, 1 modified

## Accomplishments

- Typed API contracts established: 5 interfaces covering all API response shapes including generic ApiResponse<T> for Laravel Resource envelope
- Axios instance configured reading VITE_API_BASE_URL — single source of truth for all API calls in Phase 2+
- TanStack Query registered in main.ts via VueQueryPlugin with explicit QueryClient (5-min staleTime) before mount
- Environment files created for dev (localhost:8000), prod, and example template

## Task Commits

Each task was committed atomically:

1. **Task 1: Define TypeScript API interfaces and environment variable types** - `1452b24` (feat)
2. **Task 2: Configure Axios HTTP client and TanStack Query with VueQueryPlugin** - `7e39a84` (feat)

**Plan metadata:** (pending final commit)

## Files Created/Modified

- `src/types/api.ts` — Program, Applicant, ScoreResult, ApiError, ApiResponse<T> interfaces
- `src/lib/http.ts` — Axios instance with VITE_API_BASE_URL baseURL and JSON Accept/Content-Type headers
- `src/lib/query.ts` — QueryClient with 5-minute default staleTime
- `src/main.ts` — Updated: VueQueryPlugin registered with queryClient before .mount('#app')
- `.env.development` — VITE_API_BASE_URL=http://localhost:8000
- `.env.production` — VITE_API_BASE_URL=https://api.oktatasi-hivatal.example.com
- `.env.example` — Documented template with VITE_ prefix explanation

## Decisions Made

- Hungarian field names (`osszpontszam`, `alappont`, `tobbletpont`) used verbatim in ScoreResult — these are server-defined names from Laravel API Resources and must match exactly to avoid silent undefined at runtime
- ApiResponse<T> generic wrapper reflects Laravel's JSON:API-style `{ data: ... }` envelope pattern
- 5-minute staleTime chosen as reasonable default for a read-only seeded dataset

## Deviations from Plan

None — plan executed exactly as written. The `vite-env.d.ts` file with `VITE_API_BASE_URL` type was already in place from plan 01-01, which is correct behavior (no re-write needed).

## Issues Encountered

None.

## User Setup Required

None — no external service configuration required. Development uses `http://localhost:8000` as the API base URL (configure in `.env.development` if the Laravel backend runs on a different port).

## Next Phase Readiness

- API data layer complete: typed interfaces, Axios client, TanStack Query all wired and verified
- Phase 2 composables can immediately import `http` from `@/lib/http` and use `useQuery`/`useMutation` hooks
- CORS still needs browser verification when the Laravel backend is running (pre-existing Phase 2 dependency, not a blocker for this plan)

## Self-Check: PASSED

- `src/types/api.ts` exists and exports 5 interfaces
- `src/lib/http.ts` exists and exports `http` Axios instance
- `src/lib/query.ts` exists and exports `queryClient`
- `.env.development` exists with VITE_API_BASE_URL
- Task 1 commit `1452b24` verified in git log
- Task 2 commit `7e39a84` verified in git log
- `vue-tsc --noEmit` passes clean
- `npm run build` succeeds (87.28 kB JS bundle)
- `npx biome check .` reports zero violations
- VueQueryPlugin import on line 1, use+mount on line 7 (before mount confirmed)

---
*Phase: 01-foundation*
*Completed: 2026-02-28*
