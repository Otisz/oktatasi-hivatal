---
phase: 04-score-detail-view
verified: 2026-02-28T19:10:00Z
status: human_needed
score: 6/6 must-haves verified
re_verification: false
human_verification:
  - test: "Navigate to /applicants, click any applicant card, verify university + faculty + programme name appear above the score area"
    expected: "Programme context header is visible above the score breakdown — sourced from TanStack Query cache without an extra network call"
    why_human: "Cache warm state (navigating from list vs cold direct URL) cannot be confirmed programmatically — only browser navigation sequence can verify this"
  - test: "Hard-refresh (Cmd+Shift+R) the detail page URL directly and observe the initial render"
    expected: "Animate-pulse skeleton (hero rectangle + two breakdown rectangles) briefly visible before data loads"
    why_human: "Skeleton timing is a live browser observation — programmatic checks confirm the skeleton branch exists but not that it renders visibly"
  - test: "Navigate to an applicant that triggers a 422 response from the API"
    expected: "Amber card (bg-amber-50 border-amber-200) appears with heading 'Pontozas nem lehetsege' and the verbatim Hungarian error message from the API body"
    why_human: "Requires a real API endpoint returning 422; cannot verify domain error rendering from static file analysis alone"
  - test: "After viewing a score, click 'Vissza', then click the same applicant again"
    expected: "Score appears immediately with no skeleton flash (TanStack Query cache hit)"
    why_human: "Cache hit vs miss behavior requires live browser interaction"
---

# Phase 4: Score Detail View Verification Report

**Phase Goal:** Users can view an applicant's full score breakdown or a clear error message explaining why the score cannot be calculated, and return to the list
**Verified:** 2026-02-28T19:10:00Z
**Status:** human_needed (all automated checks pass — 4 items require browser verification)
**Re-verification:** No — initial verification

---

## Goal Achievement

### Observable Truths

| #  | Truth | Status | Evidence |
|----|-------|--------|----------|
| 1  | Visiting `/applicants/:id` for a scorable applicant displays `osszpontszam` prominently above `alappont` and `tobbletpont` breakdown | VERIFIED | Line 93: `text-5xl font-bold` hero card renders `data.osszpontszam`; lines 98-103: breakdown `grid grid-cols-2 gap-4` with `data.alappont` and `data.tobbletpont` |
| 2  | A loading skeleton with hero number area and two breakdown card placeholders is visible while the score is being fetched | VERIFIED | Lines 39-54: `v-if="isLoading"` branch with `animate-pulse`, hero placeholder div (h-4 + h-16), two card placeholders in `grid grid-cols-2 gap-4` |
| 3  | When the API returns a 422, a styled amber error card displays `Pontozas nem lehetsege` heading and the verbatim Hungarian error message from the API | VERIFIED | Lines 57-63: `v-else-if="isError && error?.kind === 'domain'"` branch; `bg-amber-50 border border-amber-200`; heading "Pontozas nem lehetsege"; `{{ error.message }}` (verbatim) |
| 4  | When a generic (non-422) error occurs, a gray error state with a `Probaja ujra` retry button is displayed | VERIFIED | Lines 66-88: `v-else-if="isError"` branch; exclamation-triangle SVG; "Hiba tortent" paragraph; `<button type="button" @click="refetch()">Probaja ujra</button>` |
| 5  | A back link (`Vissza`) is always visible above all states (loading, success, domain error, generic error) | VERIFIED | Lines 24-26: `<RouterLink to="/applicants">` is rendered outside all `v-if`/`v-else-if` branches — unconditional |
| 6  | Programme info (university, faculty, programme name) is shown above the score area when navigating from the applicant list | VERIFIED* | Lines 29-36: `v-if="applicant"` reads from `useQueryClient().getQueryData(['applicants'])` synchronously; renders `applicant.program.university`, `.faculty`, `.name`; fallback `<h2>Pontozas</h2>` when cold *(requires browser verification for cache-warm path — see Human Verification section)* |

**Score:** 6/6 truths verified (automated); 4 items flagged for human confirmation

---

## Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| `src/views/ApplicantDetailView.vue` | Score detail view with all four branches, back nav, programme header | VERIFIED | 108 lines (min_lines: 80 — passed); substantive implementation with four real `v-if`/`v-else-if` branches; imported and wired in router |

---

## Key Link Verification

| From | To | Via | Status | Details |
|------|----|-----|--------|---------|
| `src/views/ApplicantDetailView.vue` | `src/composables/useApplicantScore.ts` | `useApplicantScore()` import | WIRED | Line 5: `import { useApplicantScore } from '@/composables/useApplicantScore'`; line 16: destructured and used with getter `() => route.params.id as string` |
| `src/views/ApplicantDetailView.vue` | `src/types/api.ts` | `Applicant` type for cache lookup | WIRED | Line 6: `import type { Applicant } from '@/types/api'`; line 12: `queryClient.getQueryData<Applicant[]>(['applicants'])` |
| `src/views/ApplicantDetailView.vue` | `@tanstack/vue-query` | `useQueryClient` for reading applicants cache | WIRED | Line 2: `import { useQueryClient } from '@tanstack/vue-query'`; line 9: `const queryClient = useQueryClient()`; line 12: `queryClient.getQueryData<Applicant[]>(['applicants'])` |
| `src/router/index.ts` | `src/views/ApplicantDetailView.vue` | Route component registration | WIRED | `router/index.ts` line 3: imports `ApplicantDetailView`; line 19-22: registered at `/applicants/:id` named `applicant-detail` |

---

## Requirements Coverage

| Requirement | Source Plan | Description | Status | Evidence |
|-------------|-------------|-------------|--------|----------|
| SCORE-01 | 04-01-PLAN.md | User can view score breakdown (total, base points, bonus points) | SATISFIED | Branch 4 (`v-else-if="data"`): hero card + two breakdown cards render `osszpontszam`, `alappont`, `tobbletpont` |
| SCORE-02 | 04-01-PLAN.md | Total score (`osszpontszam`) displayed prominently above breakdown | SATISFIED | `text-5xl font-bold` hero card above `grid grid-cols-2 gap-4` breakdown; DOM ordering enforces visual hierarchy |
| SCORE-03 | 04-01-PLAN.md | Styled error card displaying verbatim Hungarian error message on 422 | SATISFIED | Amber card branch (`v-else-if="isError && error?.kind === 'domain'"`); `{{ error.message }}` displays verbatim API response; `useApplicantScore` extracts `body.error` from 422 response |
| SCORE-04 | 04-01-PLAN.md | Loading state displayed while score is being fetched | SATISFIED | `v-if="isLoading"` skeleton branch with `animate-pulse`; `isLoading` (not `isPending`) prevents flash on cached back-navigation |

**REQUIREMENTS.md traceability note:** `NAV-02` ("Back navigation from score detail view to applicant list") is assigned to Phase 4 in the traceability table. The PLAN frontmatter for 04-01 does not declare NAV-02 in its `requirements` field, but the implementation satisfies it: `RouterLink to="/applicants"` is always rendered unconditionally (line 24-26). No orphaned gap — the requirement is implemented; it was omitted from the plan's `requirements` array.

---

## Anti-Patterns Found

| File | Line | Pattern | Severity | Impact |
|------|------|---------|----------|--------|
| — | — | None | — | — |

No TODO/FIXME/placeholder comments, no empty return values, no console.log statements, no stub handlers found.

---

## Technical Quality Checks

| Check | Result | Details |
|-------|--------|---------|
| Biome lint/format | PASS | "Checked 1 file in 51ms. No fixes applied." |
| TypeScript (`vue-tsc --noEmit`) | PASS | No output — zero type errors |
| Commit documented | VERIFIED | `0b98afe` (`feat(04-01): implement ApplicantDetailView with score breakdown and error states`) exists in git log |
| Branch ordering (domain before generic) | CORRECT | Line 58: `v-else-if="isError && error?.kind === 'domain'"` appears before line 66: `v-else-if="isError"` |
| `type="button"` on retry button | PRESENT | Line 85: `<button type="button" ...>` — Biome a11y compliance |
| `isLoading` (not `isPending`) | CORRECT | Line 16: destructures `isLoading`; used in line 39 |
| Getter passed to composable | CORRECT | Line 17: `() => route.params.id as string` — preserves reactivity |

---

## Human Verification Required

### 1. Programme context header from warm cache

**Test:** Navigate to `/applicants`, wait for the list to load, click any applicant card.
**Expected:** University, faculty, and programme name appear above the score area — no extra network request for this data.
**Why human:** Cache warm state requires live browser navigation from the list; static analysis confirms the code reads `['applicants']` cache but cannot confirm it is populated at the moment of navigation.

### 2. Loading skeleton visible on cold load

**Test:** Hard-refresh (Cmd+Shift+R) the detail URL directly (e.g. `http://localhost:5173/applicants/some-uuid`).
**Expected:** Animate-pulse skeleton briefly visible — one tall hero rectangle + two side-by-side card rectangles — before the score or error appears.
**Why human:** Skeleton rendering duration depends on network timing; programmatic checks confirm the branch exists but cannot confirm the skeleton is perceptible.

### 3. 422 domain error amber card

**Test:** Navigate to an applicant whose score endpoint returns a 422 response.
**Expected:** Amber card with heading "Pontozas nem lehetsege" and the verbatim Hungarian error message from the API response body.
**Why human:** Requires a live API endpoint returning 422; cannot simulate from static file analysis.

### 4. Cached back-navigation — no skeleton flash

**Test:** View a score, click "Vissza", then click the same applicant again.
**Expected:** Score appears immediately without the skeleton flash (TanStack Query cache hit via `isLoading` guard).
**Why human:** Cache hit behavior requires live browser interaction to observe the timing.

---

## Gaps Summary

No gaps found. All six observable truths are verified through static code analysis. All artifacts exist, are substantive (108 lines of real implementation), and are correctly wired. All four SCORE requirements are satisfied. Key links are all present and connected. No anti-patterns detected. Biome and TypeScript checks pass clean.

Four human verification items remain — these are runtime/behavioral confirmations that cannot be verified from source alone. All have corresponding implementation evidence in the code.

---

_Verified: 2026-02-28T19:10:00Z_
_Verifier: Claude (gsd-verifier)_
