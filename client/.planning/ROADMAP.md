# Roadmap: Hungarian Admission Score Calculator — Vue Client

## Overview

Four phases deliver a working Vue 3 SPA from nothing to a fully functional score calculator UI. Phase 1 establishes the project skeleton and tooling so every subsequent phase builds on a verified foundation. Phase 2 wires up Vue Router and the TanStack Query data layer before any view touches the API. Phase 3 delivers the applicant list — the app's entry point — with all states handled. Phase 4 delivers the score detail view and error states, completing the end-to-end user flow.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Foundation** - Project scaffolded with Vue 3 + Vite + TypeScript + Tailwind CSS v4 + Biome + typed API contracts (completed 2026-02-28)
- [ ] **Phase 2: Routing and Data Layer** - Vue Router + TanStack Query + Axios configured with typed queries and 422 discrimination
- [ ] **Phase 3: Applicant List View** - Full list view with loading, empty state, click navigation, and responsive layout
- [ ] **Phase 4: Score Detail View** - Score breakdown and styled Hungarian error display completing the end-to-end flow

## Phase Details

### Phase 1: Foundation
**Goal**: The project skeleton exists, builds without errors, and all tooling is verified before any feature code is written
**Depends on**: Nothing (first phase)
**Requirements**: INFRA-01, INFRA-02, INFRA-03, INFRA-04, INFRA-05, DATA-01
**Success Criteria** (what must be TRUE):
  1. Running `npm run dev` serves the app at a local URL with no build errors
  2. Biome runs (`npx biome check .`) and reports no lint or format violations on the initial scaffold
  3. `import.meta.env.VITE_API_BASE_URL` resolves to the configured API URL in a browser console check
  4. All four TypeScript interfaces (`Applicant`, `Program`, `ScoreResult`, `ApiError`) exist in `src/types/api.ts` and the project compiles with no type errors
  5. TanStack Query (`VueQueryPlugin`) is registered in `main.ts` and the Axios instance reads `VITE_API_BASE_URL` as its base URL
**Plans**: 2 plans
  - [x] 01-01-PLAN.md — Scaffold Vue 3 + Vite + TypeScript + Tailwind CSS v4 + Biome
  - [x] 01-02-PLAN.md — TypeScript API interfaces + Axios + TanStack Query + environment config

### Phase 2: Routing and Data Layer
**Goal**: Vue Router routes are reachable in the browser and TanStack Query delivers typed applicant and score data (including 422 errors) before any view renders them
**Depends on**: Phase 1
**Requirements**: NAV-01, NAV-02, NAV-03, DATA-02, DATA-03, DATA-04
**Success Criteria** (what must be TRUE):
  1. Navigating to `/` in the browser redirects to `/applicants` without a 404 or blank screen
  2. Navigating directly to `/applicants/some-uuid` renders the correct route component (even if the view is a placeholder)
  3. The applicants query returns a typed `Applicant[]` array from the real API when called in a browser devtools snippet or mounted component
  4. The score query for a valid applicant ID returns a typed `ScoreResult`; for an applicant with a 422 response, the error is captured as a typed `ApiError` and not treated as a network failure
**Plans**: 2 plans
  - [ ] 02-01-PLAN.md — Vue Router + App shell + placeholder views + progress bar
  - [ ] 02-02-PLAN.md — TanStack Query composables (useApplicants + useApplicantScore with 422 discrimination)

### Phase 3: Applicant List View
**Goal**: Users can see all applicants, get feedback during loading, and navigate to a score detail view by clicking any row
**Depends on**: Phase 2
**Requirements**: LIST-01, LIST-02, LIST-03, LIST-04, LAYOUT-01, LAYOUT-02
**Success Criteria** (what must be TRUE):
  1. Visiting `/applicants` shows a list of applicants, each displaying university, faculty, and programme name
  2. A loading skeleton is visible between page load and the first applicant appearing in the list
  3. Clicking any applicant row navigates to `/applicants/:id` for that applicant
  4. When the API returns an empty array, an empty state message is shown instead of a blank list
  5. The layout is readable on a mobile viewport (single column) and a desktop viewport without horizontal scrolling
**Plans**: TBD

### Phase 4: Score Detail View
**Goal**: Users can view an applicant's full score breakdown or a clear error message explaining why the score cannot be calculated, and return to the list
**Depends on**: Phase 3
**Requirements**: SCORE-01, SCORE-02, SCORE-03, SCORE-04
**Success Criteria** (what must be TRUE):
  1. Visiting `/applicants/:id` for a scorable applicant displays the total score (`osszpontszam`) prominently above the base points (`alappont`) and bonus points (`tobbletpont`) breakdown
  2. A loading indicator is visible while the score is being fetched before the breakdown or error appears
  3. When the API returns a 422, the verbatim Hungarian error message is displayed in a styled error card (not a generic "something went wrong" message)
  4. A back navigation link on the detail view returns the user to `/applicants`
**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation | 2/2 | Complete   | 2026-02-28 |
| 2. Routing and Data Layer | 1/2 | In Progress|  |
| 3. Applicant List View | TBD | Not started | - |
| 4. Score Detail View | TBD | Not started | - |
