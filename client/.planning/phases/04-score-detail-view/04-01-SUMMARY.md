---
phase: 04-score-detail-view
plan: 01
subsystem: ui
tags: [vue, tanstack-query, tailwind, typescript, hungarian]

requires:
  - phase: 02-routing-and-data-layer
    provides: useApplicantScore composable with ScoreError discriminated union, useApplicants composable with ['applicants'] cache
  - phase: 03-applicant-list-view
    provides: ApplicantsView navigation patterns, isLoading guard pattern, inline SVG icon pattern

provides:
  - ApplicantDetailView.vue with four rendering branches (skeleton, domain error, generic error, score breakdown)
  - Hero score display (osszpontszam in text-5xl font-bold)
  - Domain error amber card with verbatim Hungarian API message
  - Generic error state with exclamation-triangle SVG and retry button
  - Programme context header sourced from TanStack Query cache (synchronous, no network call)
  - Back navigation always visible above all states

affects: [05-integration-testing]

tech-stack:
  added: []
  patterns:
    - "Programme context from cache: useQueryClient().getQueryData(['applicants']) for synchronous read without network call"
    - "isLoading (not isPending) guard for skeleton — prevents flash on cached back-navigation"
    - "Domain error branch before generic error branch in v-else-if chain (ordering is critical)"
    - "type='button' on all non-form buttons for Biome a11y compliance"

key-files:
  created: []
  modified:
    - src/views/ApplicantDetailView.vue

key-decisions:
  - "Programme context sourced synchronously from ['applicants'] TanStack Query cache via useQueryClient().getQueryData() — no extra network call or composable needed"
  - "Domain error (422) branch MUST precede generic error branch in v-else-if chain — reversed order would prevent amber card from rendering"
  - "isLoading (not isPending) used for skeleton guard — consistent with Phase 03 decision, prevents skeleton flash on back-navigation with cached data"

patterns-established:
  - "Cache read pattern: useQueryClient().getQueryData<T>(key) for synchronous context without triggering a fetch"
  - "Error branch ordering: most-specific error condition first in v-else-if chain"

requirements-completed: [SCORE-01, SCORE-02, SCORE-03, SCORE-04]

duration: 7min
completed: 2026-02-28
---

# Phase 04 Plan 01: Score Detail View Summary

**Score detail view with hero total score, alappont/tobbletpont breakdown cards, 422 amber domain error card, loading skeleton, and back navigation — all text in Hungarian**

## Performance

- **Duration:** 7 min
- **Started:** 2026-02-28T18:50:06Z
- **Completed:** 2026-02-28T18:57:00Z
- **Tasks:** 1 of 2 (paused at human-verify checkpoint)
- **Files modified:** 1

## Accomplishments

- Replaced placeholder ApplicantDetailView.vue with full score detail implementation (95 lines added)
- Four rendering branches: loading skeleton, domain error (amber), generic error with retry, score breakdown
- Programme context header (university, faculty, programme name) sourced from TanStack Query cache synchronously
- Back link always visible above all rendering states
- Biome and vue-tsc checks pass with no violations

## Task Commits

1. **Task 1: Implement ApplicantDetailView.vue with score breakdown and error states** - `0b98afe` (feat)

_Task 2 (human-verify checkpoint) awaiting browser verification._

## Files Created/Modified

- `/Users/otisz/Projects/oktatasi-hivatal/client/src/views/ApplicantDetailView.vue` - Full score detail view replacing placeholder: loading skeleton, domain error amber card, generic error with retry, hero score + breakdown cards, programme context from cache

## Decisions Made

- Programme context sourced synchronously from `['applicants']` TanStack Query cache via `useQueryClient().getQueryData()` — no extra network call or composable needed for data already loaded by list view
- Domain error (422) branch placed BEFORE generic error branch in `v-else-if` chain — reversed order would swallow domain errors into generic handler
- `isLoading` (not `isPending`) used for skeleton guard — consistent with Phase 03 decision, prevents skeleton flash on back-navigation with cached data

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed Biome formatting violations in initial write**
- **Found during:** Task 1 (Biome check step)
- **Issue:** Three Biome errors: import order (vue-query before vue), formatter line-length (button tag multi-line), missing `type="button"` on retry button (a11y lint)
- **Fix:** Reordered imports per Biome's organizeImports rule; collapsed button to single line; added `type="button"` attribute
- **Files modified:** src/views/ApplicantDetailView.vue
- **Verification:** `npx biome check` exits 0 with no violations
- **Committed in:** 0b98afe (Task 1 commit)

---

**Total deviations:** 1 auto-fixed (formatting/lint violations caught during verification)
**Impact on plan:** Fix was necessary for plan verification criteria. No scope creep.

## Issues Encountered

None beyond the Biome formatting fix documented above.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- ApplicantDetailView.vue is feature-complete pending human browser verification (Task 2 checkpoint)
- Once verified, Phase 04 is complete and the end-to-end flow (list → detail → back) is fully implemented
- No blockers for the verification step

---
*Phase: 04-score-detail-view*
*Completed: 2026-02-28*
