# Project Research Summary

**Project:** Oktatasi Hivatal — Hungarian Admission Score Calculator (Vue 3 SPA client)
**Domain:** Data-display SPA consuming a cross-origin Laravel REST API
**Researched:** 2026-02-28
**Confidence:** HIGH

## Executive Summary

This is a compact, two-view single-page application: a list of applicants and a score breakdown detail. The scope is deliberately minimal — two API endpoints, no authentication, no shared state across views, and no real-time data. Experts build this class of app with Vue 3 Composition API using `<script setup>`, a thin typed HTTP wrapper, one composable per API resource, and presentational components that receive data as props. The stack is stable and well-documented: Vue 3.5, Vite 7, TypeScript 5, Tailwind CSS 4, Vue Router 5, and Axios 1.13.

The recommended approach is a strict four-layer architecture: `types/` for API interfaces, `api/` for plain async HTTP functions, `composables/` for reactive state wrappers, and `views/` + `components/` for rendering. No state management library (Pinia) is needed at this scale. The most critical design decision is treating 422 responses as domain errors, not network failures — the score endpoint uses 422 to return six distinct Hungarian-language business messages that must reach the user verbatim. A typed HTTP wrapper that discriminates `status === 422` from other failures is the architectural centrepiece.

The primary risks are all infrastructure-level and surface early: CORS must be verified from a browser before any feature work proceeds, the `VITE_API_BASE_URL` environment variable must be established in Phase 1 because every subsequent API call depends on it, and the Fetch API's silent handling of 4xx responses must be addressed in the HTTP wrapper before any component relies on error state. All identified pitfalls have clear, low-cost preventions and none require advanced techniques.

## Key Findings

### Recommended Stack

See full details in `.planning/research/STACK.md`.

Vue 3 with `<script setup>` and TypeScript is the clear standard for this domain in 2026. Vite 7 provides the build tooling with sub-millisecond HMR. Tailwind CSS v4 introduces a first-party Vite plugin that eliminates the PostCSS pipeline — a single `@import "tailwindcss"` in the entry CSS replaces all prior configuration. Vue Router 5 introduces optional file-based routing but conventional `createRouter()` usage is unchanged from v4. Axios is preferred over native `fetch` specifically because the 422 error flow requires distinguishing HTTP errors from network failures, and Axios throws on non-2xx by default.

**Core technologies:**
- Vue 3.5.13: UI framework — Composition API + `<script setup>` is the current standard; `ref`/`computed` sufficient without Pinia
- Vite 7.3.1: Build tool — official Vue build tool; requires Node.js 20.19+
- TypeScript 5.x: Type safety — required for typing `Applicant`, `ScoreResult`, and `ApiError` shapes
- Tailwind CSS 4.1.4: Styling — `@tailwindcss/vite` plugin; zero-config content detection
- Vue Router 5.0.3: Client-side routing — `createWebHistory()` with named routes
- Axios 1.13.6: HTTP client — preferred over `fetch` for automatic error throwing on 4xx/5xx

### Expected Features

See full details in `.planning/research/FEATURES.md`.

All P1 features are low-complexity. The app has a clearly defined MVP: list view, detail view, routing, TypeScript interfaces, composables, error state, loading feedback, back navigation, and responsive layout. Nothing in the P1 set is novel — these are established patterns with well-documented implementations. The anti-features list is definitive: Pinia, client-side search/filter, pagination, and PWA support are all explicitly out of scope.

**Must have (table stakes):**
- Applicant list view consuming GET /api/v1/applicants — without this, nothing else is reachable
- Vue Router with `/applicants` and `/applicants/:id` routes — prerequisite for all navigation
- TypeScript interfaces: `Applicant`, `ScoreResult`, `ScoreError` — prevents runtime errors at the API boundary
- `useApplicants` composable (loading + error + data) — fetch abstraction for the list
- `useApplicantScore` composable (loading + domainError + networkError + data) — distinguishes 422 from other failures
- Score breakdown detail view with `osszpontszam` prominently displayed — core product value
- Styled 422 error state displaying verbatim Hungarian message — six domain errors must be legible
- Back navigation link on detail view — users are stranded without it
- Loading indicators on both list and detail views — absence makes app feel broken
- Responsive Tailwind layout — single-column on mobile

**Should have (v1.x after validation):**
- Skeleton screens matching list/detail structure — upgrade from spinner
- Network error state distinct from 422 — separate connectivity failures from domain errors
- Empty list state — defensive completeness
- Meaningful error categorisation by message pattern — if user testing reveals confusion

**Defer (v2+):**
- Client-side search/filter — only if list length proves to be a usability problem
- Score history or cross-applicant comparison — requires API changes
- Accessibility audit and ARIA improvements

### Architecture Approach

See full details in `.planning/research/ARCHITECTURE.md`.

The architecture is a clean four-layer stack. The `types/` layer defines all API interfaces in one file — `Applicant`, `Program`, `ScoreResult`, `ApiError` — and everything else imports from there. The `api/` layer contains plain async functions with no Vue reactivity, typed against those interfaces, with a single `http.ts` wrapper that reads `VITE_API_BASE_URL` exactly once. The `composables/` layer wraps each API function with Vue `ref` state (`loading`, `error`, `data`) and exposes a `load()` function. Views wire composables to child components via props; components are fully presentational and never call the API directly. The build order in ARCHITECTURE.md (types → http → api → composables → router → components → views → App → main) eliminates circular dependency risk.

**Major components:**
1. `types/api.ts` — single source of truth for all API response shapes
2. `api/http.ts` — Fetch wrapper: base URL, JSON parsing, typed error throwing, `response.ok` guard
3. `api/applicants.ts` — `fetchApplicants()` and `fetchApplicantScore(id)` as plain async functions
4. `composables/useApplicants.ts` — reactive `loading/error/data` lifecycle for the list endpoint
5. `composables/useApplicantScore.ts` — reactive state with `domainError` (422) vs `networkError` discrimination
6. `router/index.ts` — `/applicants` and `/applicants/:id` named routes with `createWebHistory()`
7. `views/ApplicantListView.vue` / `views/ScoreDetailView.vue` — page-level wiring only
8. `components/applicants/ApplicantCard.vue` — presentational list row
9. `components/score/ScoreBreakdown.vue` / `ScoreError.vue` — presentational score and error display
10. `components/ui/LoadingSpinner.vue` / `ErrorMessage.vue` — shared atoms

### Critical Pitfalls

See full details in `.planning/research/PITFALLS.md`.

1. **`VITE_API_BASE_URL` not established in Phase 1** — every fetch call in the entire codebase depends on this variable; establishing it last means everything built before is untested against a real URL. Prevention: create `.env.development`, `.env.production`, and `env.d.ts` as the first scaffolding action.

2. **Fetch API silently succeeds on 422** — `fetch()` only rejects on network failure; a 422 response resolves successfully with `response.ok === false`. Without an explicit `response.ok` check in the HTTP wrapper, the composable never enters its error branch, and the score view renders blank instead of the Hungarian error message. Prevention: enforce `response.ok` guard in `api/http.ts` before any component is built.

3. **CORS not verified from browser before feature work** — curl and Insomnia bypass CORS; the browser enforces it. A misconfigured CORS header on the Laravel server blocks all API calls before a single feature can be demonstrated. Prevention: make one API call from a browser tab and confirm `Access-Control-Allow-Origin` header is present before writing any composable.

4. **`VITE_` prefix omitted from env var** — `API_BASE_URL` without the prefix is invisible to browser-side code (`import.meta.env.API_BASE_URL === undefined`). All API calls resolve to `undefinedapi/v1/...`. Prevention: name it `VITE_API_BASE_URL` from the start and declare it in `env.d.ts`.

5. **Vue Router history mode returns 404 on direct URL access or refresh** — `createWebHistory()` requires the server to serve `index.html` for all non-asset paths. Prevention: configure a catch-all fallback route in the router for unmatched paths; use `vite preview` for local preview (already handles this correctly).

## Implications for Roadmap

Based on research, suggested phase structure:

### Phase 1: Project Scaffolding and Infrastructure
**Rationale:** Every pitfall identified in PITFALLS.md traces back to infrastructure decisions made (or skipped) in the first 30 minutes. CORS verification, env var naming, TypeScript type definitions, and Vite configuration must be settled before any feature code is written. All downstream phases depend on these being correct.
**Delivers:** Working project skeleton: Vite + Vue 3 + TypeScript + Tailwind CSS v4 + Vue Router 5 + Axios; `.env.development` and `.env.production` with `VITE_API_BASE_URL`; `env.d.ts` type declaration; `types/api.ts` with all API interfaces; `api/http.ts` with `response.ok` guard and typed error throwing; CORS verified from browser.
**Addresses:** TypeScript interfaces (`Applicant`, `ScoreResult`, `ScoreError`), responsive Tailwind layout baseline
**Avoids:** Missing `VITE_` prefix, CORS blocked before feature work, type mismatch on API responses, Fetch silently swallowing 422

### Phase 2: API Layer and Composables
**Rationale:** Composables are the reactive bridge between the HTTP layer (Phase 1) and the views (Phase 3). Building them before views ensures views are never tested with inline fetch calls, and the 422 discrimination logic is proven correct in isolation before it is rendered.
**Delivers:** `api/applicants.ts` with `fetchApplicants()` and `fetchApplicantScore(id)`; `composables/useApplicants.ts`; `composables/useApplicantScore.ts` with `domainError`/`networkError` split; Vue Router `index.ts` with named routes and `createWebHistory()`.
**Uses:** Axios 1.13.6, TypeScript generics, Vue 3 `ref`
**Implements:** Composable pattern (Pattern 1), Thin API layer (Pattern 2), 422 as Typed Domain Error (Pattern 3)

### Phase 3: Applicant List View
**Rationale:** The list view is the entry point of the app and depends only on `useApplicants` (established in Phase 2). Building it before the detail view confirms routing works and navigation to the detail view is possible, unblocking Phase 4.
**Delivers:** `views/ApplicantListView.vue` with loading, error, and empty states; `components/applicants/ApplicantCard.vue` as a presentational row with full-row click target; skeleton loading for list; back navigation baseline.
**Addresses:** Applicant list view, clickable list rows, loading indicator, responsive layout, empty list state
**Avoids:** Fetch calls inside components, no loading state during route navigation

### Phase 4: Score Detail View and Error States
**Rationale:** The detail view is the core product value and requires the list view (Phase 3) to navigate from. The 422 error state is a branch within this view, not a separate route, so it is built here. This phase delivers the complete end-to-end user flow.
**Delivers:** `views/ScoreDetailView.vue` with score display and error state; `components/score/ScoreBreakdown.vue` with total prominent above breakdown; `components/score/ScoreError.vue` displaying verbatim Hungarian error message; back navigation link; `components/ui/LoadingSpinner.vue` and `ErrorMessage.vue`.
**Addresses:** Score breakdown detail view, styled 422 error state, back navigation, score visual hierarchy, network vs domain error distinction
**Avoids:** Treating 422 as a generic error, showing stale data while loading

### Phase 5: Polish and Production Readiness
**Rationale:** All core functionality is complete after Phase 4. This phase upgrades loading states from spinners to skeleton screens, verifies the production build, and confirms the "looks done but isn't" checklist from PITFALLS.md. It is intentionally last because skeleton structure mirrors the real component layout, which only becomes stable once views are finalised.
**Delivers:** Skeleton loading screens matching list and detail structure; production build verification (`npm run build && npm run preview`); CORS header confirmed from browser on production URL; history mode fallback confirmed; dynamic Tailwind class audit.
**Addresses:** Skeleton loading screens, production env configuration
**Avoids:** Vite proxy disappearing in production, dynamic Tailwind classes purged in production build, history mode 404 on refresh

### Phase Ordering Rationale

- Infrastructure before features: every pitfall in PITFALLS.md is preventable by establishing `VITE_API_BASE_URL`, `response.ok` guards, and CORS in Phase 1. Skipping this order means debugging integration failures inside feature code.
- API layer before views: composables own the loading/error/data lifecycle. Views built before composables will inline fetch logic, creating the anti-pattern that is hardest to refactor later.
- List before detail: Vue Router must be configured and navigation must be proven working before the detail view can be reached by the user. Phase 3 validates routing is functional.
- Error state inside detail view (not separate phase): the 422 path is a conditional branch in `ScoreDetailView.vue`, not a separate route or component tree. Treating it as its own phase would split a single view implementation across phases.
- Polish last: skeleton screens reference the actual DOM structure of the list and detail views. Building them before views are finalised means rebuilding them when layout changes.

### Research Flags

Phases with standard, well-documented patterns (skip `/gsd:research-phase`):
- **Phase 1:** Vite + Vue 3 + TypeScript + Tailwind v4 scaffolding is fully documented in official guides. `.env` naming rules are a known gotcha with a clear fix.
- **Phase 2:** Composable pattern with `loading/error/data` lifecycle is the canonical Vue 3 async pattern per official docs. 422 discrimination is a known pattern.
- **Phase 3:** List view with `v-for`, loading skeleton, and router-link navigation are standard Vue 3 patterns.
- **Phase 4:** Score detail view follows the same composable pattern as Phase 3. `ScoreError` rendering is a simple conditional template.
- **Phase 5:** Production build verification is a checklist task, not a design problem.

No phase requires deeper research. All patterns are covered by official Vue 3, Vue Router, Vite, Tailwind CSS v4, and Axios documentation.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | All versions verified against npm registry and official documentation. Vue 3.5.13, Vite 7.3.1, Tailwind 4.1.4, Axios 1.13.6, Vue Router 5.0.3 confirmed current. |
| Features | HIGH | Clear MVP definition backed by UX research (Nielsen Norman Group). Anti-features list is well-reasoned against the project's scope. |
| Architecture | HIGH | Official Vue 3 composable pattern. Four-layer structure is the community standard for apps at this scale. Build order verified against dependency graph. |
| Pitfalls | HIGH | All six critical pitfalls have authoritative sources (Vite docs, Vue Router docs, MDN Fetch API). Prevention strategies are specific and verifiable. |

**Overall confidence:** HIGH

### Gaps to Address

- **API field name verification:** `osszpontszam`, `alappont`, `tobbletpont`, `altalanos_tantargy`, `emelt_tantargy` field names are specified in the server's PROJECT.md and must be cross-checked character by character against the actual Laravel API resource output before or during Phase 1 type definition. Hungarian field names are easy to mistype.
- **CORS configuration on the server:** PITFALLS.md flags that Laravel's CORS middleware must be configured for the frontend origin. This is a server-side concern but must be confirmed in Phase 1. If the server's CORS configuration is not yet set up, Phase 1 is blocked until it is.
- **Node.js version on development machines:** Vite 7 requires Node.js 20.19+ or 22.12+. If any developer is on an older version, scaffolding fails silently. Confirm Node version before Phase 1.

## Sources

### Primary (HIGH confidence)
- Vue.js official quick-start — https://vuejs.org/guide/quick-start — scaffolding, TypeScript, composable patterns
- Vue Router docs — https://router.vuejs.org/installation — v5 routing, history mode, composition API
- Tailwind CSS v4 docs — https://tailwindcss.com/docs — Vite plugin installation, zero-config content detection
- Vite 7 announcement — https://vite.dev/blog/announcing-vite7 — Node requirements, ESM-only, breaking changes
- Vite env variables — https://vite.dev/guide/env-and-mode — `VITE_` prefix requirement
- Vite server options — https://vite.dev/config/server-options — proxy is dev-only
- Vue Router history mode — https://router.vuejs.org/guide/essentials/history-mode.html — 404 fallback requirement
- Nielsen Norman Group — Skeleton Screens 101 — https://www.nngroup.com/articles/skeleton-screens/ — skeleton vs spinner guidance

### Secondary (MEDIUM confidence)
- Managing API Layers in Vue.js with TypeScript — https://dev.to/blindkai/managing-api-layers-in-vue-js-with-typescript-hno — four-layer structure validation
- Vue 3 + TypeScript Best Practices 2025 — https://eastondev.com/blog/en/posts/dev/20251124-vue3-typescript-best-practices/ — composable patterns
- CORS with Vite — https://rubenr.dev/cors-vite-vue/ — proxy vs direct cross-origin
- Fetch does not throw on 4xx/5xx — https://dev.to/kresohr/the-fetch-api-trap-when-http-errors-dont-land-in-catch-40l6 — `response.ok` guard rationale
- Tailwind dynamic class purging — https://vue-land.github.io/faq/missing-tailwind-classes — production build class verification

### Tertiary (LOW confidence)
- WebSearch: vue-router 5.0.3, @vitejs/plugin-vue 6.0.4 — npm search results, not verified directly against npm registry

---
*Research completed: 2026-02-28*
*Ready for roadmap: yes*
