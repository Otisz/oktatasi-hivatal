# Phase 4: Score Detail View - Research

**Researched:** 2026-02-28
**Domain:** Vue 3 SFC + TanStack Query v5 (Vue) + Tailwind CSS v4 — score detail rendering, skeleton loading, 422 domain error card, generic error with retry, back navigation, programme context from cache
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **Score presentation**: Large hero number for total score (`összpontszám`) — prominent focal point at the top. Base points (`alappont`) and bonus points (`többletpont`) shown as two side-by-side cards below the hero. All labels in Hungarian: "Összpontszám", "Alappont", "Többletpont".
- **Error card styling**: 422 domain errors displayed in an amber/warning-colored card (distinct from generic errors). Heading "Pontozás nem lehetséges" above the verbatim Hungarian error message from the API. Generic (non-422) errors reuse the gray icon pattern from ApplicantsView but add a "Próbálja újra" (Try again) retry button. Back link ("← Vissza") always visible above all states (loading, success, domain error, generic error).
- **Loading state**: Skeleton placeholders matching the score layout (hero number area + two breakdown cards) using `animate-pulse` — consistent with ApplicantsView. Back link and page heading shown immediately; skeleton only for the score content area. Skeleton appears while `useApplicantScore` is loading.
- **Applicant context**: Programme info (university, faculty, programme name) displayed above the score card as a header area. Clear hierarchy: context first (programme), then data (score).

### Claude's Discretion

- Visual emphasis on the hero score (color accent, border treatment, or plain)
- Data source for programme info (TanStack Query cache from list vs separate query)
- Page title treatment (generic "Pontozás" vs dynamic with programme name)
- Progress bar integration (whether to use `useProgress` alongside skeleton)

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| SCORE-01 | User can view score breakdown (total, base points, bonus points) | `useApplicantScore` returns `ScoreResult` with `osszpontszam`, `alappont`, `tobbletpont`; template renders all three when `isSuccess` is true |
| SCORE-02 | Total score (`osszpontszam`) displayed prominently above breakdown | Hero section with large text at top of score content; `alappont` and `tobbletpont` in two side-by-side cards below |
| SCORE-03 | Styled error card displaying verbatim Hungarian error message on 422 | `useApplicantScore` discriminates 422 → `ScoreError { kind: 'domain', message: string }`; amber card with "Pontozás nem lehetséges" heading and verbatim `error.message` body |
| SCORE-04 | Loading state displayed while score is being fetched | `isLoading` from `useApplicantScore` gates the skeleton; skeleton mirrors hero + two-card layout; always shown before score data or error appears |
</phase_requirements>

---

## Summary

Phase 4 is a pure UI composition phase — identical in structure to Phase 3 but with a more complex four-state render pattern and two distinct error paths. All infrastructure is already complete: `useApplicantScore` composable with discriminated `ScoreError`, `ApplicantDetailView.vue` placeholder with back link and route param, typed `ScoreResult` interface, and Tailwind CSS v4. No new libraries, composables, or routes are needed.

The key implementation challenge is correctly branching on the discriminated `ScoreError` union. TanStack Query surfaces the typed error as `error` (a reactive `Ref<ScoreError | null>`) and `isError` as the boolean gate. After `isError` is true, the template must check `error.value?.kind === 'domain'` to decide between the amber 422 card and the gray generic error with retry. The retry button calls `refetch()`, which is returned by `useApplicantScore` — `domain` errors are non-retryable by query configuration (`retry: false` when `error.kind === 'domain'`) but the user can still manually trigger a refetch.

Programme info (university, faculty, programme name) can be read from the TanStack Query cache using `useQueryClient().getQueryData<Applicant[]>(['applicants'])` without triggering a new network request. If the user navigated from the list, the `['applicants']` query is cached with 30-minute staleTime — the find by `route.params.id` will return the programme data synchronously. If the cache is cold (direct URL load), the programme info is undefined and the heading area should degrade gracefully (show "Pontozás" without programme name, or omit the header area).

**Primary recommendation:** Implement `ApplicantDetailView.vue` with `useApplicantScore(route.params.id)`, four `v-if`/`v-else-if`/`v-else` rendering branches (loading skeleton | domain error | generic error | score success), and programme info sourced from `useQueryClient().getQueryData` with graceful cold-cache fallback.

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `@tanstack/vue-query` | 5.92.9 (installed) | `useApplicantScore` composable + `useQueryClient` for cache read | Already wired; returns reactive `isLoading`, `isError`, `error`, `data`, `refetch` |
| `vue-router` | 5.0.3 (installed) | `useRoute` for `route.params.id`; `RouterLink` for back nav | Route `/applicants/:id` already configured; back link already exists in placeholder |
| `tailwindcss` | 4.2.1 (installed) | All styling — hero card, breakdown cards, amber error card, skeleton | Project standard; no configuration changes needed |
| `vue` | 3.5.29 (installed) | `<script setup>`, `computed`, reactive template | Project framework |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `useQueryClient` (from `@tanstack/vue-query`) | 5.92.9 (installed) | Read `['applicants']` cache for programme info without network call | When user navigated from list; cache is warm; avoids redundant API call for data already in memory |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `useQueryClient().getQueryData` for programme info | Separate `useApplicants()` call in detail view | Second approach always fetches (or reads from cache identically) but sets up an additional query observer — unnecessary overhead; `getQueryData` is a synchronous one-time read |
| `useQueryClient().getQueryData` for programme info | Pass programme info as route state (`router.push({ state: { programme } })`) | State approach works but is fragile on direct URL load (state is lost); cache read is more robust |

**Installation:**
```bash
# No new packages — all dependencies already installed
```

---

## Architecture Patterns

### Recommended Project Structure

No new files or directories needed — Phase 4 modifies exactly one file:

```
src/
├── views/
│   └── ApplicantDetailView.vue    # EDIT THIS — replace placeholder with score content
├── composables/
│   └── useApplicantScore.ts       # Already complete — no changes needed
├── types/
│   └── api.ts                     # Already complete — ScoreResult interface confirmed
└── router/
    └── index.ts                   # Already complete — route already configured
```

### Pattern 1: Four-State Rendering with Discriminated Error

**What:** Mutually exclusive `v-if` / `v-else-if` / `v-else` chain on the query state, with a nested conditional inside the error branch to distinguish `kind: 'domain'` from `kind: 'generic'`.

**When to use:** Whenever a TanStack Query result has typed discriminated errors. This is the idiomatic Vue + TanStack Query v5 pattern for error-type-specific UI.

```vue
<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useQueryClient } from '@tanstack/vue-query'
import { useApplicantScore } from '@/composables/useApplicantScore'
import type { Applicant } from '@/types/api'

const route = useRoute()
const queryClient = useQueryClient()

// Read programme info from cache (synchronous — no network call)
const applicant = computed(() => {
  const applicants = queryClient.getQueryData<Applicant[]>(['applicants'])
  return applicants?.find(a => a.id === route.params.id)
})

const { isLoading, isError, error, data, refetch } = useApplicantScore(
  () => route.params.id as string
)
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">

    <!-- Back link: always visible (all states) -->
    <RouterLink to="/applicants" class="text-sm text-blue-600 hover:underline mb-4 inline-block">
      &larr; Vissza
    </RouterLink>

    <!-- Programme context header: always visible when cache is warm -->
    <div v-if="applicant" class="mb-6">
      <p class="text-xs text-gray-500">
        {{ applicant.program.university }} — {{ applicant.program.faculty }}
      </p>
      <h2 class="text-lg font-semibold text-gray-900">{{ applicant.program.name }}</h2>
    </div>
    <h2 v-else class="text-lg font-semibold text-gray-900 mb-6">Pontozás</h2>

    <!-- SCORE-04: Loading skeleton — hero + two breakdown cards -->
    <div v-if="isLoading">
      <!-- skeleton content (see Pattern 2) -->
    </div>

    <!-- SCORE-03: 422 domain error — amber warning card -->
    <div v-else-if="isError && error?.kind === 'domain'" class="...">
      <h3>Pontozás nem lehetséges</h3>
      <p>{{ error.message }}</p>
    </div>

    <!-- Generic error — gray icon + retry button -->
    <div v-else-if="isError" class="text-center py-12">
      <!-- icon + Hungarian text + Próbálja újra button -->
      <button @click="refetch()">Próbálja újra</button>
    </div>

    <!-- SCORE-01 + SCORE-02: Score breakdown -->
    <div v-else-if="data">
      <!-- hero score + two breakdown cards (see Pattern 3) -->
    </div>

  </div>
</template>
```

**Key detail:** `error` in TanStack Query v5 is typed as `ScoreError | null`. After `isError` is true, `error.value` is a `ScoreError` object. Narrowing on `error?.kind === 'domain'` is safe because `kind` is the discriminant of the union.

### Pattern 2: Skeleton Matching Score Layout

**What:** Skeleton placeholders mirror the hero + two-card structure. The hero placeholder is a wide tall rectangle; the two breakdown card placeholders are side-by-side (CSS grid or flex) narrower rectangles.

**When to use:** While `isLoading` is true. `isLoading` = `isPending && isFetching` — same semantics as Phase 3.

```vue
<!-- SCORE-04: Loading skeleton -->
<div v-if="isLoading" class="animate-pulse">
  <!-- Hero score placeholder -->
  <div class="bg-white border border-gray-200 rounded-lg p-8 mb-4 flex flex-col items-center">
    <div class="h-4 bg-gray-200 rounded w-32 mb-4"></div>  <!-- "Összpontszám" label -->
    <div class="h-16 bg-gray-200 rounded w-24"></div>       <!-- hero number -->
  </div>
  <!-- Two breakdown card placeholders side by side -->
  <div class="grid grid-cols-2 gap-4">
    <div class="bg-white border border-gray-200 rounded-lg p-4">
      <div class="h-3 bg-gray-200 rounded w-20 mb-2"></div>
      <div class="h-8 bg-gray-200 rounded w-16"></div>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4">
      <div class="h-3 bg-gray-200 rounded w-20 mb-2"></div>
      <div class="h-8 bg-gray-200 rounded w-16"></div>
    </div>
  </div>
</div>
```

**Note:** The `animate-pulse` class can be placed on a single wrapper `<div>` to pulse all children together. This is the established project pattern (ApplicantsView does `animate-pulse` per card; detail view can do it on the whole section wrapper).

### Pattern 3: Hero Score + Breakdown Cards

**What:** Total score (`osszpontszam`) is the primary focal point — large text in a centered card at the top. `alappont` and `tobbletpont` are secondary — smaller cards side by side below.

**When to use:** When `data` is defined and not null/undefined (success state).

```vue
<!-- SCORE-01 + SCORE-02: Score breakdown -->
<div v-else-if="data">
  <!-- Hero: total score -->
  <div class="bg-white border border-gray-200 rounded-lg p-8 mb-4 text-center">
    <p class="text-sm text-gray-500 mb-2">Összpontszám</p>
    <p class="text-5xl font-bold text-gray-900">{{ data.osszpontszam }}</p>
  </div>

  <!-- Breakdown: side by side -->
  <div class="grid grid-cols-2 gap-4">
    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
      <p class="text-xs text-gray-500 mb-1">Alappont</p>
      <p class="text-2xl font-semibold text-gray-900">{{ data.alappont }}</p>
    </div>
    <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
      <p class="text-xs text-gray-500 mb-1">Többletpont</p>
      <p class="text-2xl font-semibold text-gray-900">{{ data.tobbletpont }}</p>
    </div>
  </div>
</div>
```

**Claude's discretion:** The hero score visual emphasis (color accent, border treatment, or plain) is open. Options: `text-blue-600` for the number, a thicker border `border-2 border-blue-200`, or a colored background `bg-blue-50`. The plain white card with large font is the minimal correct implementation.

### Pattern 4: Amber Domain Error Card (422)

**What:** Amber/warning-colored card with a heading "Pontozás nem lehetséges" and the verbatim API error string as body text. Amber distinguishes it from generic gray errors.

**Why amber:** `bg-amber-50 border-amber-200 text-amber-800` is a conventional Tailwind warning palette. The CONTEXT.md specifies "amber/warning-colored card" — this maps directly to Tailwind's amber scale in v4.

```vue
<!-- SCORE-03: 422 domain error -->
<div
  v-else-if="isError && error?.kind === 'domain'"
  class="bg-amber-50 border border-amber-200 rounded-lg p-6"
>
  <h3 class="text-base font-semibold text-amber-900 mb-2">Pontozás nem lehetséges</h3>
  <p class="text-sm text-amber-800">{{ error.message }}</p>
</div>
```

**Critical:** `error.message` is the verbatim string from the API response `body.error` field — set in `useApplicantScore`'s catch block as `throw { kind: 'domain', message: body.error }`. The view must display it unchanged (no translation, no reformatting).

### Pattern 5: Generic Error with Retry Button

**What:** Gray centered icon (reusing ApplicantsView pattern) + Hungarian error text + a "Próbálja újra" button that calls `refetch()`. The retry button is the key addition over the Phase 3 error state.

**`refetch()` note:** TanStack Query's `retry` option in `useApplicantScore` is set to `(_, error) => error.kind !== 'domain'` — automatic retries are suppressed for domain errors. `refetch()` is a manual user-initiated retry, which bypasses the `retry` config and always triggers a new fetch. This is the correct behavior for the generic error case.

```vue
<!-- Generic error (non-422) -->
<div v-else-if="isError" class="text-center py-12">
  <svg
    class="h-12 w-12 text-gray-300 mx-auto"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
    aria-hidden="true"
  >
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      stroke-width="1.5"
      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
    />
  </svg>
  <p class="text-lg font-medium text-gray-900 mt-4">Hiba történt</p>
  <p class="text-sm text-gray-500 mt-1">
    A pontozás betöltése sikertelen. Kérjük, próbálja újra.
  </p>
  <button
    class="mt-4 text-sm text-blue-600 hover:underline"
    @click="refetch()"
  >
    Próbálja újra
  </button>
</div>
```

### Pattern 6: Reading TanStack Query Cache for Programme Info

**What:** `useQueryClient().getQueryData<Applicant[]>(['applicants'])` returns the cached list without triggering a network request. This is wrapped in a `computed` so it is reactive to cache updates.

**When to use:** When the user navigated from the applicant list (cache is warm). The `['applicants']` query has 30-minute staleTime (set in Phase 2), so data will be present for the entire user session.

**Cold cache fallback:** When the user loads `/applicants/:id` directly (bookmark, share), the cache is empty and `getQueryData` returns `undefined`. The `computed` returns `undefined`; the template shows the generic "Pontozás" heading. This is the correct graceful degradation.

```typescript
// In <script setup>
import { computed } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import type { Applicant } from '@/types/api'

const route = useRoute()
const queryClient = useQueryClient()

const applicant = computed(() => {
  const cached = queryClient.getQueryData<Applicant[]>(['applicants'])
  return cached?.find(a => a.id === (route.params.id as string))
})
```

**Type note:** `getQueryData<Applicant[]>(['applicants'])` returns `Applicant[] | undefined`. The explicit type parameter `<Applicant[]>` is required because the queryKey `['applicants']` is not tagged with a type. This is confirmed from the installed `@tanstack/query-core` type definition:
```typescript
getQueryData<TQueryFnData = unknown, TTaggedQueryKey extends QueryKey = QueryKey>(
  queryKey: TTaggedQueryKey
): TInferredQueryFnData | undefined;
```

### Pattern 7: Passing Route Param to useApplicantScore

**What:** `useApplicantScore` accepts `MaybeRefOrGetter<string>`. The correct pattern is to pass a getter function `() => route.params.id as string` so the query key reactively updates if the param changes.

```typescript
const { isLoading, isError, error, data, refetch } = useApplicantScore(
  () => route.params.id as string
)
```

**Why getter, not `route.params.id` directly:** `route.params.id` is `string | string[]`. Passing it directly would lose reactivity and TypeScript would complain about the `string[]` case. A getter function extracts the value at call time, and `toValue()` inside `useApplicantScore` handles the getter correctly. The `as string` cast is safe because the route is defined with a single `:id` segment.

### Anti-Patterns to Avoid

- **Checking `error.value?.kind` without first checking `isError`**: If `isError` is false, `error.value` is null and `null?.kind` is `undefined` — the domain error card would never show. Always gate on `isError` first.
- **Using `isPending` for the skeleton instead of `isLoading`**: Same pitfall as Phase 3. `isLoading` is `isPending && isFetching`; use `isLoading` to avoid showing skeleton on back-navigation when cached score data is available.
- **Displaying a translated or reformatted 422 error message**: The locked decision is "verbatim Hungarian error message from the API". Do not strip, translate, or reformat `error.message`. Display `{{ error.message }}` directly.
- **Calling a separate `useApplicants()` in the detail view to get programme info**: This creates an unnecessary query observer and triggers a network call (or cache hydration overhead). Use `queryClient.getQueryData(['applicants'])` for a one-time synchronous read.
- **Placing the amber error branch after the generic error branch**: The `v-else-if` chain must check `error?.kind === 'domain'` before the generic fallback. Reversed order would never render the amber card (the generic branch would always catch first).
- **Forgetting `refetch` in the destructured return**: `useApplicantScore` returns TanStack Query's full `UseQueryReturnType`, which includes `refetch`. It must be destructured for the retry button.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Score loading / error / data state | Manual `ref(false)` loading, `ref(null)` error | `useApplicantScore()` (already built) | Handles deduplication, 422 discrimination, retry suppression, background refetch |
| 422 vs generic error discrimination | Custom status code check in the view | `ScoreError.kind` discriminant in composable | Already discriminated in `useApplicantScore`'s catch block; view only reads the kind |
| Programme info fetching | Another `useApplicants()` call | `useQueryClient().getQueryData(['applicants'])` | Synchronous cache read; no network call; no extra observer |
| CSS pulse animation | Custom `@keyframes` in `<style scoped>` | Tailwind `animate-pulse` | Already available; consistent with Phase 3 skeleton pattern |

**Key insight:** Phase 4 requires zero new dependencies, zero new composables, and zero new routes. The entire implementation is a single-file template composition exercise.

---

## Common Pitfalls

### Pitfall 1: Error Branch Order — Domain Before Generic

**What goes wrong:** Generic error branch `v-else-if="isError"` catches all errors before the domain-specific branch `v-else-if="isError && error?.kind === 'domain'"`. The amber 422 card never renders.

**Why it happens:** Developers write `isError` generically first because it's simpler to think about, then add the specific case after. In `v-if`/`v-else-if` chains, the first matching condition wins.

**How to avoid:** Always put the MORE SPECIFIC condition first:
```html
<!-- Correct order -->
<div v-else-if="isError && error?.kind === 'domain'">amber card</div>
<div v-else-if="isError">generic error</div>
```

**Warning signs:** Amber card never appears even with a 422 response; generic error card shows for 422 responses.

### Pitfall 2: `route.params.id` Type is `string | string[]`

**What goes wrong:** `route.params.id` has type `string | string[]` in Vue Router 5. Passing it directly to `useApplicantScore` would fail TypeScript since the function expects `MaybeRefOrGetter<string>`.

**Why it happens:** Vue Router allows params to match multiple segments (e.g., `/applicants/a/b/c`), making the type union `string | string[]`.

**How to avoid:** Use a getter with a cast: `() => route.params.id as string`. The `as string` is safe for the `/applicants/:id` route definition (single `:id` segment). Alternatively, validate with `Array.isArray(route.params.id) ? route.params.id[0] : route.params.id`.

**Warning signs:** TypeScript error on `useApplicantScore(route.params.id)` — type mismatch.

### Pitfall 3: Amber Color Classes Not Scanned in Production

**What goes wrong:** Amber Tailwind classes (`bg-amber-50`, `border-amber-200`, `text-amber-800`) are absent from other files in the project. Tailwind v4's scanner detects classes only in source files — if classes are not written as complete strings or not present in scanned files, they may be absent from the production bundle.

**Why it happens:** Tailwind v4 scans source at build time. Classes must appear as complete strings in scanned files. Since amber classes are new to this project (not used in Phase 1-3), there is no prior reference.

**How to avoid:** Write the amber classes as complete static strings in the template:
```html
class="bg-amber-50 border border-amber-200 rounded-lg p-6"
```
Do NOT construct them dynamically (e.g., `` `bg-${color}-50` ``). Static complete strings are always detected by Tailwind v4's scanner.

**Warning signs:** Amber error card appears with correct structure but white/transparent background in production build; styles look correct in development (Vite dev server doesn't purge).

### Pitfall 4: `isLoading` vs `isPending` (Same as Phase 3)

**What goes wrong:** Using `isPending` for the skeleton condition shows skeleton on back-navigation from list → detail → back → detail.

**Why it happens:** Same v4→v5 terminology confusion documented in Phase 3 research.

**How to avoid:** Use `isLoading` (= `isPending && isFetching`) — same as Phase 3.

**Warning signs:** Skeleton flashes when navigating back to the detail view of a recently-viewed applicant.

### Pitfall 5: Cold Cache Hardening — Programme Info Missing

**What goes wrong:** If `getQueryData(['applicants'])` returns `undefined` (direct URL load), `applicant.value` is `undefined` and `applicant.program.name` throws a template error.

**Why it happens:** `computed` returns `undefined` when cache is empty. Template using `{{ applicant.program.name }}` without a null check crashes the view.

**How to avoid:** Use `v-if="applicant"` to gate programme info display:
```html
<div v-if="applicant">
  <p>{{ applicant.program.university }} — {{ applicant.program.faculty }}</p>
  <h2>{{ applicant.program.name }}</h2>
</div>
<h2 v-else>Pontozás</h2>
```

**Warning signs:** Vue template error "Cannot read properties of undefined (reading 'name')" when loading the detail page directly via URL.

---

## Code Examples

Verified patterns from installed libraries and existing project code:

### Complete `useApplicantScore` Return Shape

```typescript
// src/composables/useApplicantScore.ts (existing — no changes needed)
// Return value from useQuery includes:
const {
  isLoading,   // boolean — true only on first fetch with no cache
  isError,     // boolean — true when queryFn threw
  error,       // Ref<ScoreError | null> — typed discriminated union
  data,        // Ref<ScoreResult | undefined>
  refetch,     // () => Promise<QueryObserverResult> — manual retry
} = useApplicantScore(() => route.params.id as string)
```

### ScoreResult Field Names

```typescript
// src/types/api.ts (existing — confirmed Hungarian field names)
interface ScoreResult {
  osszpontszam: number   // total score — hero display
  alappont: number       // base points — left breakdown card
  tobbletpont: number    // bonus points — right breakdown card
}
```

### ScoreError Discriminated Union Access

```typescript
// After isError is true, error.value is ScoreError (not null)
// error.value.kind === 'domain' → { kind: 'domain', message: string }
// error.value.kind === 'generic' → { kind: 'generic' }

// In template (error is Ref<ScoreError | null>):
// error?.kind === 'domain'  → true for 422 responses
// error?.kind === 'generic' → true for network/server errors
```

### TanStack Query Cache Read (getQueryData)

```typescript
// Confirmed from node_modules/@tanstack/query-core type definition:
// getQueryData<TQueryFnData>(queryKey: QueryKey): TQueryFnData | undefined

const queryClient = useQueryClient()
const cached = queryClient.getQueryData<Applicant[]>(['applicants'])
// Returns Applicant[] | undefined (undefined if cache is cold)
```

### Full View Structure

```vue
<!-- src/views/ApplicantDetailView.vue — complete implementation -->
<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useQueryClient } from '@tanstack/vue-query'
import { useApplicantScore } from '@/composables/useApplicantScore'
import type { Applicant } from '@/types/api'

const route = useRoute()
const queryClient = useQueryClient()

const applicant = computed(() => {
  const cached = queryClient.getQueryData<Applicant[]>(['applicants'])
  return cached?.find(a => a.id === (route.params.id as string))
})

const { isLoading, isError, error, data, refetch } = useApplicantScore(
  () => route.params.id as string
)
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">

    <!-- Back link: always visible -->
    <RouterLink to="/applicants" class="text-sm text-blue-600 hover:underline mb-4 inline-block">
      &larr; Vissza
    </RouterLink>

    <!-- Programme context header (warm cache only) -->
    <div v-if="applicant" class="mb-6">
      <p class="text-xs text-gray-500">
        {{ applicant.program.university }} — {{ applicant.program.faculty }}
      </p>
      <h2 class="text-lg font-semibold text-gray-900">{{ applicant.program.name }}</h2>
    </div>
    <h2 v-else class="text-lg font-semibold text-gray-900 mb-6">Pontozás</h2>

    <!-- SCORE-04: Loading skeleton -->
    <div v-if="isLoading" class="animate-pulse">
      <div class="bg-white border border-gray-200 rounded-lg p-8 mb-4 flex flex-col items-center">
        <div class="h-4 bg-gray-200 rounded w-32 mb-4"></div>
        <div class="h-16 bg-gray-200 rounded w-24"></div>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="h-3 bg-gray-200 rounded w-20 mb-2"></div>
          <div class="h-8 bg-gray-200 rounded w-16"></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="h-3 bg-gray-200 rounded w-20 mb-2"></div>
          <div class="h-8 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- SCORE-03: 422 domain error — amber card (MUST be before generic error branch) -->
    <div
      v-else-if="isError && error?.kind === 'domain'"
      class="bg-amber-50 border border-amber-200 rounded-lg p-6"
    >
      <h3 class="text-base font-semibold text-amber-900 mb-2">Pontozás nem lehetséges</h3>
      <p class="text-sm text-amber-800">{{ error.message }}</p>
    </div>

    <!-- Generic error (non-422) — gray icon + retry -->
    <div v-else-if="isError" class="text-center py-12">
      <svg
        class="h-12 w-12 text-gray-300 mx-auto"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1.5"
          d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"
        />
      </svg>
      <p class="text-lg font-medium text-gray-900 mt-4">Hiba történt</p>
      <p class="text-sm text-gray-500 mt-1">
        A pontozás betöltése sikertelen. Kérjük, próbálja újra.
      </p>
      <button
        class="mt-4 text-sm text-blue-600 hover:underline"
        type="button"
        @click="refetch()"
      >
        Próbálja újra
      </button>
    </div>

    <!-- SCORE-01 + SCORE-02: Score breakdown -->
    <div v-else-if="data">
      <div class="bg-white border border-gray-200 rounded-lg p-8 mb-4 text-center">
        <p class="text-sm text-gray-500 mb-2">Összpontszám</p>
        <p class="text-5xl font-bold text-gray-900">{{ data.osszpontszam }}</p>
      </div>
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">Alappont</p>
          <p class="text-2xl font-semibold text-gray-900">{{ data.alappont }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-4 text-center">
          <p class="text-xs text-gray-500 mb-1">Többletpont</p>
          <p class="text-2xl font-semibold text-gray-900">{{ data.tobbletpont }}</p>
        </div>
      </div>
    </div>

  </div>
</template>
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| TanStack Query v4 `isLoading` (= no data yet) | TanStack Query v5 `isLoading` = `isPending && isFetching` | v5 (2023) | Use `isLoading` for skeleton; `isPending` would show skeleton when cache is warm |
| `onError` callback to read error type | `error` ref read in template after `isError` guard | TanStack Query v5 | `onError`/`onSuccess` removed in v5; use reactive `error` ref directly |
| `useQuery` generic `<TData, TError = Error>` | `useQuery<ScoreResult, ScoreError>` — typed discriminated error | TanStack Query v5 | Full type narrowing on `error.value.kind` without any casts |
| `this.$route.params.id` Options API | `useRoute().params.id` Composition API | Vue 3 | `useRoute()` in `<script setup>` is the standard |

**Deprecated/outdated:**
- `onError` / `onSuccess` callbacks on `useQuery()`: Removed in TanStack Query v5 — read `.error` and `.data` reactive refs in template or `watch`
- `isInitialLoading`: Deprecated alias — use `isLoading` directly
- `status === 'loading'`: Renamed to `status === 'pending'` in TanStack Query v5

---

## Open Questions

1. **Hero score visual emphasis — color accent or plain white**
   - What we know: CONTEXT.md marks this as Claude's discretion. The locked decision is "prominent focal point at the top" but does not specify color.
   - What's unclear: Whether to use `text-blue-600` for the number, a colored border, or a colored background — all would satisfy "prominent".
   - Recommendation: Plain white card with very large bold text (`text-5xl font-bold`) is sufficient for prominence without introducing a new color that might clash. If accent is desired, `text-blue-600` for the hero number is the most conservative choice.

2. **Programme info for cold cache — show loading skeleton or heading fallback**
   - What we know: CONTEXT.md says "Programme info (university, faculty, programme name) displayed above the score card as a header area" but does not specify what to show on cold cache.
   - What's unclear: Should the header area show a skeleton too, or just a fallback heading?
   - Recommendation: Show generic "Pontozás" heading (`<h2 v-else>`) on cold cache. Adding a separate `useApplicants()` query just to populate the header is disproportionate; the `getQueryData` pattern with a graceful fallback is correct.

3. **Progress bar (`useProgress`) integration alongside skeleton**
   - What we know: CONTEXT.md marks this as Claude's discretion. `isNavigating` from `useProgress` is already shown globally in `App.vue` during route transitions.
   - What's unclear: Whether `useApplicantScore`'s `isFetching` state should also pulse the global top bar (separate from navigation transitions).
   - Recommendation: Skip progress bar integration for the score fetch. The skeleton provides sufficient loading feedback. The global progress bar is for navigation transitions (already handled by router guards in `index.ts`). Mixing data-fetch progress into the navigation bar would require adding `isFetching` awareness to `App.vue` — out of scope for this phase.

---

## Validation Architecture

> Testing is deferred to v1.1 per REQUIREMENTS.md Out of Scope table ("Unit/E2E testing: Deferred to v1.1"). No `config.json` found in `.planning/` — skipping Validation Architecture section.

---

## Sources

### Primary (HIGH confidence)

- `src/composables/useApplicantScore.ts` — return shape confirmed: `useQuery<ScoreResult, ScoreError>` with `retry` config; `refetch` available in return; `ScoreError` discriminated union with `kind: 'domain' | 'generic'`
- `src/types/api.ts` — `ScoreResult` interface confirmed: `osszpontszam`, `alappont`, `tobbletpont` as `number`; `Applicant` interface confirmed with `id` and `program` fields
- `src/views/ApplicantDetailView.vue` — existing placeholder confirmed: `RouterLink to="/applicants"` back link already present; `route.params.id` already accessed; `max-w-4xl mx-auto px-4 py-6` container already present
- `src/views/ApplicantsView.vue` — error state SVG path confirmed for reuse in generic error; `animate-pulse` pattern confirmed for skeleton
- `node_modules/@tanstack/vue-query/build/legacy/useQueryClient.d.ts` — `useQueryClient()` export confirmed
- `node_modules/@tanstack/query-core/build/legacy/hydration-BlEVG2Lp.d.ts` — `getQueryData<TQueryFnData>(queryKey: TTaggedQueryKey): TInferredQueryFnData | undefined` signature confirmed; explicit type param required
- `package.json` — `@tanstack/vue-query@^5.92.9`, `vue-router@^5.0.3`, `tailwindcss@^4.2.1` confirmed installed; no icon library present
- `.planning/STATE.md` — `['applicants']` queryKey confirmed; 30-minute staleTime confirmed

### Secondary (MEDIUM confidence)

- Phase 3 RESEARCH.md — `isLoading` vs `isPending` semantics cross-verified and confirmed for Phase 4 (same TanStack Query v5 version)
- Phase 3 RESEARCH.md — Tailwind v4 static class scanning requirement cross-verified; applies equally to amber classes in Phase 4

### Tertiary (LOW confidence)

- None — all findings verified against installed packages or project source files

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — no new dependencies; all verified from installed node_modules and existing source
- Architecture: HIGH — single file change; all patterns verified from installed type definitions and existing project code
- Pitfalls: HIGH — error branch ordering and type narrowing verified from installed `@tanstack/query-core` types; amber class scanning applies documented Tailwind v4 behavior

**Research date:** 2026-02-28
**Valid until:** 2026-03-30 (stable installed dependencies; no updates expected to affect this phase's patterns)
