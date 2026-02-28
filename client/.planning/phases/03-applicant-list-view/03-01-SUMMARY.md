---
phase: 03-applicant-list-view
plan: 01
subsystem: ui
tags: [vue3, tailwind, tanstack-query, vue-router, skeleton, responsive]

# Dependency graph
requires:
  - phase: 02-routing-and-data-layer
    provides: useApplicants composable returning typed Applicant[] via TanStack Query; named applicant-detail route for navigation
provides:
  - Fully functional ApplicantsView.vue with four rendering branches: loading skeleton, error, empty state, and applicant card list
  - Click-to-navigate pattern from list to detail view via router.push({ name: 'applicant-detail', params: { id } })
affects:
  - 04-score-detail-view (detail view receives navigation from this list; back-button returns here; cached data avoids skeleton on re-entry)

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "isLoading (not isPending) for skeleton guard — isPending && isFetching is false on cached back-navigation, preventing unnecessary skeleton flash"
    - "Four-branch v-if/v-else-if/v-else template pattern for async state: loading → error → empty → data"
    - "Inline SVG icons in template — no icon library dependency"
    - "max-w-4xl mx-auto px-4 py-6 container matches App.vue and ApplicantDetailView.vue layout pattern"

key-files:
  created: []
  modified:
    - src/views/ApplicantsView.vue

key-decisions:
  - "isLoading used instead of isPending for skeleton — isPending stays true when cached data exists, causing unwanted flash on back-navigation"
  - "No heading above card list — cards are self-explanatory and the app header already identifies the app"
  - "Inline SVG for users and exclamation-triangle icons avoids adding an icon library dependency"
  - "Right single guillemet (›) used as chevron character for simplicity over an SVG chevron icon"

patterns-established:
  - "Card list pattern: bg-white border rounded-lg with hover:bg-gray-50 transition-colors and cursor-pointer"
  - "Skeleton pattern: animate-pulse with h-3/h-5 bars at w-1/3, w-2/3, w-1/2 widths for visual hierarchy"
  - "Empty state pattern: centered py-12 with SVG icon (text-gray-300), bold title, muted subtitle"

requirements-completed: [LIST-01, LIST-02, LIST-03, LIST-04, LAYOUT-01, LAYOUT-02]

# Metrics
duration: ~15min
completed: 2026-02-28
---

# Phase 3 Plan 01: Applicant List View Summary

**Card-based applicant list with animate-pulse loading skeleton, Hungarian empty/error states, and click-to-navigate to score detail — all rendered via four v-if branches consuming useApplicants composable**

## Performance

- **Duration:** ~15 min
- **Started:** 2026-02-28T18:09:00Z
- **Completed:** 2026-02-28T19:45:00Z
- **Tasks:** 2 (1 auto + 1 human-verify checkpoint)
- **Files modified:** 1

## Accomplishments

- Replaced placeholder ApplicantsView.vue with complete four-state list view (loading, error, empty, list)
- Loading skeleton shows 3 animate-pulse cards only on first load (isLoading guards against cached back-navigation flash)
- Each applicant card displays programme name (bold), university and faculty (small gray), right chevron; click navigates to applicant-detail route
- Hungarian UI strings throughout: "Nincsenek jelentkezők", "Hiba történt", error explanations
- Browser verification confirmed: loading skeleton, card list rendering, hover state, click navigation, back navigation (no skeleton), responsive layout

## Task Commits

Each task was committed atomically:

1. **Task 1: Implement ApplicantsView.vue with all rendering states** - `fea3c13` (feat)
2. **Task 2: Verify applicant list view in browser** - checkpoint approved by user (no code commit)

## Files Created/Modified

- `src/views/ApplicantsView.vue` - Full card-based list view replacing placeholder; four rendering branches using useApplicants and useRouter

## Decisions Made

- Used `isLoading` instead of `isPending` for the skeleton guard. TanStack Query v5 defines `isLoading` as `isPending && isFetching`, which resolves to false when cached data exists. Using `isPending` would cause the skeleton to flash on every back-navigation since `isPending` stays true while a query is not in a success state, even with cached data present.
- No page heading above the card list. The app header already identifies the context and the cards themselves are self-explanatory, so an extra "Jelentkezők" heading adds visual noise without benefit.
- Inline SVG icons for users and exclamation-triangle. Avoids introducing an icon library dependency for two icons used in low-frequency states.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Phase 3 complete. ApplicantsView.vue fully functional with all states verified in browser.
- Phase 4 (Score Detail View) can begin: `ApplicantDetailView.vue` is currently a placeholder; it receives navigation from this list via the `applicant-detail` named route.
- Back navigation from detail view will return to `/applicants` with cached data — no skeleton flash expected.

---
*Phase: 03-applicant-list-view*
*Completed: 2026-02-28*
