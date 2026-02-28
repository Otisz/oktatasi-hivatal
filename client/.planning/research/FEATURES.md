# Feature Research

**Domain:** Data-display SPA — list-to-detail navigation with score breakdown and error states
**Researched:** 2026-02-28
**Confidence:** HIGH

---

## Feature Landscape

### Table Stakes (Users Expect These)

Features users assume exist. Missing these = product feels incomplete.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Applicant list view | Core navigation entry point; a SPA without its primary list is broken | LOW | Consumes GET /api/v1/applicants; renders university, faculty, programme name per row |
| Clickable list rows that navigate to detail | Master-detail navigation is a universal SPA convention; non-clickable rows feel broken | LOW | Route to `/applicants/:id`; highlight hover state with cursor-pointer |
| Score breakdown detail view | The purpose of the app; failing to show alappont / tobbletpont / osszpontszam breakdown is a show-stopper | LOW | Three-field display; total prominently above breakdown |
| Styled error state for 422 responses | API returns 6 different Hungarian domain errors; treating them as generic crashes destroys trust | MEDIUM | Distinguish error from loading; display the Hungarian string verbatim with clear visual framing |
| Loading indicator during API calls | Users expect visual feedback while the network request resolves; absence suggests the app has frozen | LOW | Skeleton screen preferred over spinner for full-page loads (Nielsen Norman Group research) |
| Back navigation from detail to list | Users expect to return to the list without using browser back button; missing this causes disorientation | LOW | Vue Router `router.back()` or explicit link to `/applicants` |
| Responsive layout | Users may access via mobile or tablet; fixed-width-only layout is a quality signal failure | LOW | Tailwind responsive utility classes; single-column stacking on mobile |
| TypeScript types for API responses | Type safety across API boundary prevents runtime property access errors | LOW | `Applicant`, `ScoreResult`, `ScoreError` interfaces matching API shapes |

### Differentiators (Competitive Advantage)

Features that set the product apart. Not required for MVP, but raise perceived quality.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Meaningful error categorisation display | Six distinct 422 messages convey different problems; visually grouping them by category (missing subject vs failed exam) helps users understand the issue faster | MEDIUM | Detect message pattern via regex on Hungarian string; display contextual icon or colour coding |
| Skeleton loading matching list structure | Skeleton rows that mirror the real list structure reduce cognitive load versus a generic spinner (NNG: skeleton best for full-page loads under 10s) | LOW | Three skeleton rows with animated pulse; column widths matching real data |
| Score visual hierarchy (total vs breakdown) | Displaying `osszpontszam` larger and above `alappont`/`tobbletpont` lets users read the bottom line first and drill down | LOW | Single card component; total in large text, breakdown in subdued detail row |
| Empty list state | If the API returns an empty applicants array, the user sees a meaningful explanation rather than a blank page | LOW | "Nincs megjelenítendő jelölt" or equivalent; unlikely but necessary for completeness |
| Network error state (non-422) | Distinguishes API domain errors (422) from connectivity or server failures (5xx, timeout); users see different messaging for each | LOW | Separate error condition branch in the fetch composable |

### Anti-Features (Commonly Requested, Often Problematic)

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Client-side search/filter on applicant list | Lists with many items feel navigable with filtering | API returns full seeded list; adding filter logic couples UI to data assumptions and adds complexity without validating need | Ship without filter; add only if user feedback confirms list length is a pain point |
| Pagination of applicant list | Large lists conventionally paginate | API returns all applicants in one call; adding client-side pagination creates a false navigation layer over a small, static dataset | Render the flat list; add pagination only if the dataset grows to the point of performance impact |
| Translation layer for Hungarian error messages | Non-Hungarian speakers cannot read the 422 messages | The API is the authoritative domain source; translating introduces desynchronisation risk as new error variants are added | Display verbatim as designed; document that the UI is Hungarian-language by design |
| State management library (Pinia) | Complex apps need centralised state | App state is minimal: current route params + active API response; Pinia adds boilerplate for no gain at this scope | Component-level `ref`/`reactive` or a thin `useApplicant` composable; revisit if state genuinely grows |
| Real-time score polling | Score data could change if API is live | The API computes scores from seeded, static data; polling wastes requests and creates UI flicker | Fetch once on route entry; no polling needed |
| Offline / PWA support | Progressive enhancement is good practice | This is a data-display tool dependent entirely on the API; offline mode would show stale data with no recalculation path | Out of scope; revisit only if the tool is deployed as a standalone installer |

---

## Feature Dependencies

```
Applicant list view
    └──requires──> API client composable (useApplicants)
                       └──requires──> VITE_API_BASE_URL env config
                       └──requires──> TypeScript Applicant interface

Score detail view
    └──requires──> Vue Router with /applicants/:id route
    └──requires──> API client composable (useApplicantScore)
                       └──requires──> VITE_API_BASE_URL env config
                       └──requires──> TypeScript ScoreResult + ScoreError interfaces

Error state display
    └──requires──> Score detail view (error occurs on score fetch, not list fetch)
    └──requires──> 422 response detection in useApplicantScore

Loading skeletons
    └──enhances──> Applicant list view
    └──enhances──> Score detail view

Back navigation
    └──requires──> Vue Router
    └──enhances──> Score detail view

Score visual hierarchy
    └──enhances──> Score detail view

Meaningful error categorisation
    └──enhances──> Error state display
    └──conflicts with──> Translation layer (both attempt to interpret Hungarian strings)
```

### Dependency Notes

- **Score detail view requires Vue Router:** The `:id` param is how the detail view knows which applicant to fetch; routing must be in place before the detail view is built.
- **Both composables require env config:** `VITE_API_BASE_URL` must be established as the first infrastructure decision; all fetching depends on it.
- **Error state requires score detail view:** The 422 path only occurs on the score endpoint, not the list endpoint; error display is a branch within the detail view, not a separate route.
- **Meaningful error categorisation conflicts with translation layer:** If error strings are translated, pattern-matching on the Hungarian originals breaks; choose one approach and stay consistent.

---

## MVP Definition

### Launch With (v1.0)

Minimum viable product — what's needed to demonstrate the working end-to-end flow.

- [ ] Applicant list view consuming GET /api/v1/applicants — without this, nothing else can be reached
- [ ] Vue Router with `/applicants` and `/applicants/:id` routes — prerequisite for all navigation
- [ ] TypeScript interfaces: `Applicant`, `ScoreResult`, `ScoreError` — prevents runtime errors at the API boundary
- [ ] `useApplicants` composable (loading + error + data) — clean fetch abstraction for the list
- [ ] `useApplicantScore` composable (loading + error + data, distinguishes 422 from other errors) — clean fetch abstraction for the score endpoint
- [ ] Score breakdown detail view (total prominently, base + bonus in breakdown) — core product value
- [ ] Styled 422 error state with verbatim Hungarian message — the six domain errors must be legible and visually distinct from loading
- [ ] Back navigation link on detail view — without this, users are stranded
- [ ] Loading indicators (skeleton or spinner) on both list and detail — without feedback, the app feels broken during network calls
- [ ] Responsive Tailwind layout (single-column on mobile, comfortable on desktop) — non-negotiable quality baseline

### Add After Validation (v1.x)

Features to add once core is confirmed working.

- [ ] Skeleton screens matching list/detail structure — upgrade from spinner once layout is stable
- [ ] Network error state distinct from 422 — distinguish domain errors from connectivity failures
- [ ] Empty list state ("no applicants") — defensive completeness; add when API returns zero results in a real scenario
- [ ] Meaningful error categorisation by message pattern — add if user testing reveals the raw Hungarian strings cause confusion

### Future Consideration (v2+)

Features to defer until product-market fit is established.

- [ ] Client-side search/filter — defer until list length proves to be a usability problem
- [ ] Score history / comparison across applicants — requires API support that does not currently exist
- [ ] Accessibility audit and ARIA improvements — important but deferred to avoid scope creep in MVP

---

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Applicant list view | HIGH | LOW | P1 |
| Vue Router setup | HIGH | LOW | P1 |
| TypeScript API interfaces | HIGH | LOW | P1 |
| Score detail view | HIGH | LOW | P1 |
| 422 error state display | HIGH | LOW | P1 |
| Loading indicator | HIGH | LOW | P1 |
| Back navigation | HIGH | LOW | P1 |
| Responsive layout | HIGH | LOW | P1 |
| Skeleton loading screens | MEDIUM | LOW | P2 |
| Network vs domain error distinction | MEDIUM | LOW | P2 |
| Empty list state | LOW | LOW | P2 |
| Error message categorisation | MEDIUM | MEDIUM | P2 |
| Client-side search/filter | LOW | MEDIUM | P3 |
| Pagination | LOW | MEDIUM | P3 |

**Priority key:**
- P1: Must have for launch
- P2: Should have, add when core is proven
- P3: Nice to have, future consideration

---

## UX Patterns Reference

Patterns specific to this class of data-display SPA (not generic advice):

### List View Pattern
- **Row-based table** preferred over cards for structured, comparable data with 3+ fields per item (university, faculty, programme). Cards suit image-rich or asymmetric data; uniform text records scan faster as rows.
- **Full-row click target** (not a button column) is the convention for list-to-detail navigation; it maximises tap target on mobile.
- **University/faculty as secondary text** below programme name reduces visual noise; hierarchy mirrors how users conceptualise the data.

### Detail View Pattern
- **Total score (`osszpontszam`) at the top** in large text — users scan for the bottom line first; breakdown (`alappont`, `tobbletpont`) in a supporting section below.
- **Single-card layout** for a 3-field result is appropriate; a full table would be disproportionate for this data density.
- **Label and value pairs** (e.g., "Alappont: 60") are sufficient; no chart or visualisation is warranted for 3 numerical values.

### Error State Pattern
- **Error must not look like loading** — distinct visual: red/warning colour, icon (X or warning triangle), message text.
- **Display the Hungarian string verbatim** — do not paraphrase; domain errors are exact and paraphrasing introduces inaccuracy.
- **Offer a retry action** — a "Próbálja újra" button re-triggers the score fetch without full page reload.
- **Keep the applicant context visible** — show the applicant's programme name above the error; users need to know which applicant triggered the error.

### Loading State Pattern
- **Skeleton screen over spinner** for full-page list loads (NNG research: skeleton reduces cognitive load when structure is known).
- **Spinner acceptable** for the score detail load (single module, smaller surface area) if skeleton adds build complexity.
- **Never show stale data** while loading — clear the previous result before fetching the next applicant.

---

## API Dependency Map

| Feature | API Dependency | What Breaks If API Changes |
|---------|---------------|---------------------------|
| Applicant list | GET /api/v1/applicants → `data[].id`, `data[].program.{university,faculty,name}` | List renders blank or throws if shape changes |
| Score display | GET /api/v1/applicants/{id}/score → `data.{osszpontszam,alappont,tobbletpont}` | Score view shows undefined values |
| Error display | Same endpoint → 422 with `error` string key | If key changes to `message`, error is swallowed silently |

TypeScript interfaces provide the only guard against API shape drift. They must be kept in sync with the server's Eloquent Resource outputs.

---

## Sources

- [Nielsen Norman Group — Skeleton Screens 101](https://www.nngroup.com/articles/skeleton-screens/) — skeleton vs spinner guidance, HIGH confidence
- [Agriculture Design System — Loading, Empty and Error States](https://design-system.agriculture.gov.au/patterns/loading-error-empty-states) — three-state design pattern, MEDIUM confidence
- [Vue.js Official — Composables](https://vuejs.org/guide/reusability/composables.html) — composable pattern for async fetch, HIGH confidence
- [UX Collective — Skeleton Screens](https://uxdesign.cc/what-you-should-know-about-skeleton-screens-a820c45a571a) — loading pattern tradeoffs, MEDIUM confidence
- [Smashing Magazine — UI Patterns for Mobile: Search, Sort, Filter](https://www.smashingmagazine.com/2012/04/ui-patterns-for-mobile-apps-search-sort-filter/) — filter/sort pattern analysis, MEDIUM confidence
- [Vue Mastery — Coding Better Composables: Async Without Await](https://www.vuemastery.com/blog/coding-better-composables-5-of-5/) — async composable patterns, MEDIUM confidence

---
*Feature research for: Hungarian Admission Score Calculator — Vue 3 SPA*
*Researched: 2026-02-28*
