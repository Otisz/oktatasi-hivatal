---
phase: 02-routing-and-data-layer
plan: "01"
subsystem: ui
tags: [vue-router, vue3, tailwindcss, tanstack-query]

# Dependency graph
requires:
  - phase: 01-foundation
    provides: Vite + Vue 3 project scaffold, TanStack Query + Axios setup, TypeScript config, Tailwind CSS
provides:
  - Vue Router with createWebHistory, two named routes (/applicants, /applicants/:id), root redirect, catch-all
  - ApplicantsView.vue placeholder shell (Phase 3 will fill)
  - ApplicantDetailView.vue placeholder with RouterLink back navigation to /applicants
  - useProgress.ts composable with exported isNavigating ref driven by navigation guards
  - App.vue persistent layout shell with header, progress bar wired to isNavigating, and RouterView
  - queryClient staleTime updated to 30 minutes
affects: [03-applicants-list, 04-score-detail, any phase that adds routes or navigates programmatically]

# Tech tracking
tech-stack:
  added: [vue-router@5]
  patterns: [history-mode routing, navigation guards for progress state, isNavigating reactive ref, persistent layout shell in App.vue]

key-files:
  created:
    - src/router/index.ts
    - src/views/ApplicantsView.vue
    - src/views/ApplicantDetailView.vue
    - src/composables/useProgress.ts
  modified:
    - src/App.vue
    - src/main.ts
    - src/lib/query.ts
    - package.json

key-decisions:
  - "vue-router uses createWebHistory (HTML5 history mode) for clean URLs — no hash routing"
  - "Catch-all route redirects to /applicants silently — no 404 page per user decision"
  - "Progress bar implemented as a simple isNavigating ref + Tailwind classes (no nprogress) — zero dependency approach"
  - "staleTime updated from 5 min to 30 min — seeded static dataset does not change frequently"
  - "Router registered with .use(router) before VueQueryPlugin in main.ts"

patterns-established:
  - "View components live in src/views/ and are imported statically in the router (no lazy loading for small app)"
  - "Navigation state (isNavigating) lives in a dedicated composable, not in App.vue or the router directly"
  - "App.vue is pure layout shell — no data fetching or business logic, only persistent header/progress/RouterView"

requirements-completed: [NAV-01, NAV-02, NAV-03]

# Metrics
duration: 2min
completed: 2026-02-28
---

# Phase 02 Plan 01: Vue Router Setup and App Shell Summary

**Vue Router 5 with history mode, two named routes, catch-all redirect, and persistent header/progress bar layout shell in App.vue**

## Performance

- **Duration:** 2 min
- **Started:** 2026-02-28T18:01:21Z
- **Completed:** 2026-02-28T18:03:24Z
- **Tasks:** 2
- **Files modified:** 8

## Accomplishments
- Installed vue-router and configured createRouter with createWebHistory, two named routes (applicants, applicant-detail), root redirect from /, and catch-all redirect for unknown URLs
- Created placeholder ApplicantsView.vue and ApplicantDetailView.vue (with RouterLink back nav); navigation guards drive the isNavigating ref in useProgress.ts
- Replaced App.vue with persistent layout shell containing the dual-line header ("Oktatasi Hivatal" / "Felveteli pontszamolo"), thin animated progress bar wired to isNavigating, and RouterView

## Task Commits

Each task was committed atomically:

1. **Task 1: Install Vue Router, create router config, register in main.ts, update staleTime** - `ffde896` (feat) — committed by a prior agent run that also included useApplicants.ts
2. **Task 2: Build App.vue layout shell with persistent header, progress bar, and RouterView** - `a9cc167` (feat)

## Files Created/Modified
- `src/router/index.ts` - Vue Router instance with createWebHistory, routes array (root redirect, applicants, applicant-detail, catch-all), navigation guards setting isNavigating
- `src/views/ApplicantsView.vue` - Placeholder list view with max-w-4xl layout container
- `src/views/ApplicantDetailView.vue` - Placeholder detail view with RouterLink back navigation to /applicants displaying "Vissza"
- `src/composables/useProgress.ts` - Exported isNavigating ref (false by default), toggled by router navigation guards
- `src/App.vue` - Persistent layout shell: fixed progress bar, header with title + subtitle, RouterView in main
- `src/main.ts` - Router registered with .use(router) before VueQueryPlugin
- `src/lib/query.ts` - staleTime updated from 1000*60*5 (5 min) to 1000*60*30 (30 min)
- `package.json` + `package-lock.json` - vue-router@5 added as dependency

## Decisions Made
- Vue Router uses HTML5 history mode (createWebHistory) for clean URLs without hash fragments
- No 404 page — catch-all silently redirects to /applicants per user decision
- Progress bar implemented with Tailwind classes only (h-0.5 bg-blue-500 animate-pulse) — zero additional dependencies
- staleTime increased to 30 minutes because dataset is seeded/static and does not change at runtime

## Deviations from Plan

None — plan executed exactly as written. Task 1 had already been committed by a prior agent invocation (commit ffde896 also included useApplicants.ts from plan 02-02). Task 2 (App.vue) was the only work remaining and was completed cleanly.

## Issues Encountered
- Initial commit attempt for Task 1 failed with "cannot lock ref HEAD" because the prior agent had already committed those files. Recognized the situation from git log, verified all Task 1 artifacts were correct, and proceeded directly to Task 2.

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Router fully configured — Phase 3 can import `useRoute` to read route params and add real list content to ApplicantsView.vue
- Phase 4 can add score content to ApplicantDetailView.vue, reading `route.params.id`
- The useApplicants.ts composable (from commit ffde896) is ready for use by Phase 3
- CORS must still be verified from a browser before any API calls are made in Phase 3

---
*Phase: 02-routing-and-data-layer*
*Completed: 2026-02-28*
