---
phase: 03-applicant-list-view
verified: 2026-02-28T20:30:00Z
status: human_needed
score: 6/6 must-haves verified
re_verification: false
human_verification:
  - test: "Loading skeleton visible during first fetch"
    expected: "Three pulsing gray-bar cards appear briefly while useApplicants fetches data from the API"
    why_human: "Cannot trigger a network delay programmatically during static code analysis to observe skeleton timing"
  - test: "Hover state visible on applicant cards"
    expected: "Card background changes to a subtle gray (bg-gray-50) when the cursor hovers over it"
    why_human: "CSS hover transitions require a running browser to observe"
  - test: "Back navigation skips skeleton"
    expected: "Navigating back from ApplicantDetailView to /applicants shows the list immediately without skeleton, because TanStack Query serves cached data (isLoading = isPending && isFetching = false)"
    why_human: "Requires live browser session with API data cached from a prior navigation"
  - test: "Responsive layout on mobile viewport"
    expected: "Cards remain readable at ~375px width with no horizontal scrolling; all text wraps cleanly"
    why_human: "Viewport resize behavior requires a browser environment"
  - test: "Empty state renders correctly when API returns []"
    expected: "Centered 'Nincsenek jelentkezők' message with users SVG icon appears; no blank or broken layout"
    why_human: "Requires mocking the API to return an empty array in a live browser session"
---

# Phase 3: Applicant List View Verification Report

**Phase Goal:** Users can see all applicants, get feedback during loading, and navigate to a score detail view by clicking any row
**Verified:** 2026-02-28T20:30:00Z
**Status:** human_needed
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | Visiting /applicants shows a list of applicant cards, each displaying university, faculty, and programme name | VERIFIED | `v-for="applicant in data"` at line 75 renders `applicant.program.university`, `applicant.program.faculty`, `applicant.program.name` in correct hierarchy |
| 2 | A loading skeleton of 3 pulsing cards is visible while applicants are being fetched | VERIFIED | `v-if="isLoading"` at line 16 renders `v-for="n in 3"` skeleton divs with `animate-pulse` class; uses `isLoading` (not `isPending`) per plan |
| 3 | Clicking any applicant card navigates to /applicants/:id for that applicant | VERIFIED | `@click="navigateTo(applicant.id)"` at line 78 calls `router.push({ name: 'applicant-detail', params: { id } })` at line 9; `applicant-detail` route exists in `src/router/index.ts` line 20 |
| 4 | When the API returns an empty array, a centered Hungarian empty state message is shown | VERIFIED | `v-else-if="data?.length === 0"` at line 51 renders "Nincsenek jelentkezők" with users SVG icon in `text-center py-12` container |
| 5 | The layout is readable on mobile (single column) and desktop without horizontal scrolling | VERIFIED (automated) | `max-w-4xl mx-auto px-4 py-6` container with `space-y-3` card list; no fixed-width elements present; responsive classes confirmed |
| 6 | The page has consistent structure with the existing header and content pattern | VERIFIED | Container `max-w-4xl mx-auto px-4 py-6` matches App.vue's `max-w-4xl mx-auto px-4 py-4` pattern exactly; view rendered inside `<main><RouterView /></main>` in App.vue |

**Score:** 6/6 truths verified (automated checks pass; 5 items require human browser confirmation for live behavior)

---

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `src/views/ApplicantsView.vue` | Applicant list view with loading, empty, error, and list states | VERIFIED | 91 lines (exceeds min_lines: 40); four v-if/v-else-if/v-else rendering branches confirmed; no stubs or placeholders |

---

### Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `src/views/ApplicantsView.vue` | `src/composables/useApplicants.ts` | `useApplicants()` composable import | WIRED | Line 3: `import { useApplicants } from '@/composables/useApplicants'`; line 6: destructured and actively used (`isLoading`, `isError`, `data` all consumed in template) |
| `src/views/ApplicantsView.vue` | `src/router/index.ts` | `router.push` to `applicant-detail` named route | WIRED | Line 9: `router.push({ name: 'applicant-detail', params: { id } })`; confirmed `applicant-detail` exists in router at `src/router/index.ts` line 20 with path `/applicants/:id` |

---

### Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|------------|-------------|--------|----------|
| LIST-01 | 03-01-PLAN.md | User can view all applicants with programme info (university, faculty, name) | SATISFIED | `v-for="applicant in data"` renders `applicant.program.university`, `applicant.program.faculty`, `applicant.program.name` (lines 75-86) |
| LIST-02 | 03-01-PLAN.md | User can click an applicant row to navigate to their score view | SATISFIED | `@click="navigateTo(applicant.id)"` calls `router.push({ name: 'applicant-detail', params: { id } })` |
| LIST-03 | 03-01-PLAN.md | Loading skeleton displayed while applicants are being fetched | SATISFIED | `v-if="isLoading"` with `animate-pulse` 3-card skeleton using correct `isLoading` guard |
| LIST-04 | 03-01-PLAN.md | Empty state displayed when no applicants exist | SATISFIED | `v-else-if="data?.length === 0"` branch with Hungarian "Nincsenek jelentkezők" message and users SVG icon |
| LAYOUT-01 | 03-01-PLAN.md | Responsive Tailwind CSS layout (single-column mobile, comfortable desktop) | SATISFIED (automated) | `max-w-4xl mx-auto px-4 py-6` wrapper; no fixed widths; `shrink-0` on chevron prevents compression; requires human browser confirmation for live behavior |
| LAYOUT-02 | 03-01-PLAN.md | Consistent page structure with header and content areas | SATISFIED | Container matches App.vue header container (`max-w-4xl mx-auto px-4`); view renders inside `<main><RouterView />` |

All 6 requirements claimed by 03-01-PLAN.md are accounted for. No orphaned requirements for this phase.

---

### Anti-Patterns Found

No anti-patterns detected.

| File | Pattern Checked | Result |
|------|----------------|--------|
| `src/views/ApplicantsView.vue` | TODO/FIXME/PLACEHOLDER comments | None found |
| `src/views/ApplicantsView.vue` | Empty returns (`return null`, `return {}`) | None found |
| `src/views/ApplicantsView.vue` | Console.log statements | None found |
| `src/views/ApplicantsView.vue` | Stub handlers (only `preventDefault`) | None found |

---

### Tooling Checks

| Check | Result |
|-------|--------|
| `npx biome check src/views/ApplicantsView.vue` | Passed — "Checked 1 file in 7ms. No fixes applied." |
| `npx vue-tsc --noEmit` | Passed — no output, exit 0 |
| Commit `fea3c13` documented in SUMMARY | Verified — exists in git log with correct diff (86 insertions to ApplicantsView.vue) |

---

### Human Verification Required

All automated checks pass. The following items require live browser confirmation:

#### 1. Loading Skeleton Visibility

**Test:** Start `npm run dev`, open `http://localhost:5173/applicants` (or hard-refresh to bypass browser cache), observe the initial render before data arrives.
**Expected:** Three vertically-stacked pulsing gray-bar skeleton cards appear briefly, then transition to the applicant card list once the API responds.
**Why human:** Cannot simulate network latency or observe CSS animation during static analysis.

#### 2. Card Hover State

**Test:** Move the cursor over any applicant card in the list.
**Expected:** Card background changes to a subtle light gray (`bg-gray-50`). The transition should be smooth (`transition-colors`).
**Why human:** CSS hover pseudo-class behavior requires a live browser.

#### 3. Back Navigation Skips Skeleton

**Test:** Click any applicant card to navigate to `/applicants/:id`, then click the browser back button.
**Expected:** The applicant list reappears instantly without showing the skeleton — TanStack Query serves cached data, so `isLoading` (= `isPending && isFetching`) is `false`.
**Why human:** Requires a live browser session with a populated TanStack Query cache.

#### 4. Responsive Layout at Mobile Width

**Test:** Resize the browser to approximately 375px width (or use DevTools mobile simulation).
**Expected:** Cards remain in a single column, all text wraps cleanly, no horizontal scrollbar appears, and the chevron character (›) remains visible without being cut off.
**Why human:** Viewport resize behavior requires a browser rendering engine.

#### 5. Empty State Rendering

**Test:** Temporarily modify `useApplicants` to return an empty array (or use DevTools to intercept the API response), then visit `/applicants`.
**Expected:** A centered layout with the users SVG icon (gray outline), "Nincsenek jelentkezők" in bold, and "A rendszerben még nem szerepel egyetlen jelentkező sem." in smaller gray text.
**Why human:** Requires API mocking or a controlled environment to produce an empty response.

---

### Gaps Summary

No gaps found. All six must-have truths are satisfied by substantive, wired implementation. The view is not a stub — it contains 91 lines with four real rendering branches, proper imports, active composable usage, and live router navigation. Biome and TypeScript checks pass cleanly.

The phase goal is achieved programmatically. The five human verification items are standard browser-behavior checks (animation timing, hover states, caching, responsive layout) that cannot be asserted via static analysis but are strongly implied by the correct implementation of `animate-pulse`, `transition-colors`, `isLoading` guard, `max-w-4xl` constraint, and `data?.length === 0` branch.

---

_Verified: 2026-02-28T20:30:00Z_
_Verifier: Claude (gsd-verifier)_
