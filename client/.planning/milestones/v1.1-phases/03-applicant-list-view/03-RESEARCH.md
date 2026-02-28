# Phase 3: Applicant List View - Research

**Researched:** 2026-02-28
**Domain:** Vue 3 SFC + TanStack Query v5 (Vue) + Tailwind CSS v4 — list view rendering, loading skeleton, empty state, click-to-navigate
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **List item design**: Card-based layout — one card per applicant, stacked vertically. Programme name (`program.name`) is the most prominent text (largest/boldest). University and faculty displayed smaller above or below the programme name. Cards have subtle border (gray-200), white background — no shadow. Right-aligned chevron (`›`) always visible to signal navigation. Hover state: background color change to reinforce clickability. Clicking anywhere on the card navigates to `/applicants/:id`.
- **Empty state**: Icon + text centered on the page. Subtle icon (list or users icon) above the message. Hungarian text: "Nincsenek jelentkezők" with a secondary line explaining no applicants are in the system. Consistent with the app's Hungarian language (header, API errors).
- **Loading skeleton**: 3 skeleton cards shown while `useApplicants()` is fetching. Skeleton cards match the real card layout — 3 animated bars representing programme name, university, and faculty. Pulsing animation (Tailwind `animate-pulse`).
- **Language**: All UI text in Hungarian — matches header ("Oktatási Hivatal", "Felvételi pontszámoló") and API error messages.

### Claude's Discretion

- Exact spacing, gap, and typography sizes within cards
- Hover effect color and transition timing
- Skeleton bar widths and exact positioning
- Icon choice for empty state
- Page-level layout adjustments (padding, margins beyond existing `max-w-4xl` container)
- Row interaction micro-animations
- Error state for failed API fetch (network error, not empty response)

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| LIST-01 | User can view all applicants with programme info (university, faculty, name) | `useApplicants()` already returns typed `Applicant[]`; template iterates with `v-for`; all three fields from `program.*` are in the `Applicant` type |
| LIST-02 | User can click an applicant row to navigate to `/applicants/:id` | Named route `applicant-detail` with `:id` param is configured; use `useRouter().push()` or `<RouterLink>` wrapping the card; full-card click via click handler on outer `<div>` is simpler for a card layout |
| LIST-03 | Loading skeleton displayed while applicants are being fetched | TanStack Query v5 `isLoading` ref (= `isFetching && isPending`) is `true` only on first load with no cached data; `v-if="isLoading"` shows 3 skeleton card placeholders |
| LIST-04 | Empty state displayed when no applicants exist | `data` is `[]` (not `undefined`) after a successful empty response; `data?.length === 0` after `isLoading` and `!isError` guards shows the empty state |
| LAYOUT-01 | Responsive Tailwind CSS layout (single-column mobile, comfortable desktop) | Container `max-w-4xl mx-auto px-4` already established; stacked card list is inherently single-column; no responsive grid needed — just `w-full` cards |
| LAYOUT-02 | Consistent page structure with header and content areas | `App.vue` provides header + `<RouterView>`; view only needs its internal `max-w-4xl mx-auto px-4 py-6` wrapper matching `ApplicantDetailView.vue` established pattern |
</phase_requirements>

---

## Summary

Phase 3 is purely a UI composition phase — no new libraries, no new composables, no routing changes. All infrastructure is already in place: `useApplicants()` returns `{ isLoading, isError, data }` refs, the router has `applicant-detail` named route with `:id`, and Tailwind CSS v4 with `animate-pulse` is wired. The task is to implement `ApplicantsView.vue` with three mutually-exclusive rendering branches: (1) loading skeleton when `isLoading.value` is true, (2) empty state when `data.value` is an empty array, and (3) the list of cards when data exists.

The key implementation detail is correct branching logic. In TanStack Query v5, `isLoading` is `isFetching && isPending` — it is only `true` on the very first fetch when no cache exists. This is exactly the right flag for the skeleton: after the first successful fetch, back-navigation shows cached data instantly with `isLoading` staying `false`. The empty state check must come after the loading guard: `!isLoading.value && data.value?.length === 0`. The list renders when `data.value` has items.

Navigation from cards uses `useRouter().push({ name: 'applicant-detail', params: { id: applicant.id } })` on a click handler attached to the outer card `<div>`. Using a `<RouterLink>` wrapping the entire card is also valid but produces a block-level `<a>` tag — the click handler approach on a `<div role="button">` is simpler for the card-shaped layout and avoids nested interactive element concerns.

**Primary recommendation:** Implement `ApplicantsView.vue` in a single file with `useApplicants()`, three `v-if`/`v-else-if`/`v-else` branches (loading skeleton | empty state | list), and inline Tailwind classes following the established project color scheme.

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `@tanstack/vue-query` | 5.92.9 (installed) | Data fetching — `useApplicants()` composable | Already wired; returns reactive `isLoading`, `data`, `isError` refs |
| `vue-router` | 5.0.3 (installed) | Navigation — push to `applicant-detail` route | Named route already configured in `src/router/index.ts` |
| `tailwindcss` | 4.2.1 (installed) | All styling — cards, skeleton, empty state, hover | Project standard; v4 with `@tailwindcss/vite` already configured |
| `vue` | 3.5.29 (installed) | `<script setup>`, `v-for`, `v-if`, reactive template | Project framework |

### Supporting

None — no new packages required. This phase installs nothing.

**Installation:**
```bash
# No new packages — all dependencies already installed
```

---

## Architecture Patterns

### Recommended Project Structure

No new files needed beyond updating the existing placeholder:

```
src/
├── views/
│   └── ApplicantsView.vue    # EDIT THIS — replace placeholder comment with list implementation
├── composables/
│   └── useApplicants.ts      # Already complete — no changes needed
├── router/
│   └── index.ts              # Already complete — no changes needed
└── App.vue                   # Already complete — no changes needed
```

The only file changed in Phase 3 is `src/views/ApplicantsView.vue`.

### Pattern 1: Three-Branch Rendering with TanStack Query v5 States

**What:** Mutually exclusive `v-if` / `v-else-if` / `v-else` on the query state refs returned by `useApplicants()`.

**When to use:** Whenever a view depends on a TanStack Query result. This exact pattern is idiomatic for Vue + TanStack Query v5.

**Key distinction — `isLoading` vs `isPending` in TanStack Query v5:**
- `isPending`: `true` when there is no cached data yet (status = 'pending'), regardless of whether a fetch is in flight
- `isLoading`: `true` when `isPending && isFetching` — a fetch is actively in flight AND there is no data yet
- Use `isLoading` for the skeleton: it is `false` on back-navigation (cached data shows instantly) and `true` only on first load

```vue
<!-- src/views/ApplicantsView.vue -->
<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useApplicants } from '@/composables/useApplicants'

const router = useRouter()
const { isLoading, isError, data } = useApplicants()

function navigateTo(id: string) {
  router.push({ name: 'applicant-detail', params: { id } })
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">

    <!-- Loading skeleton: only on first fetch, not on back-navigation -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="n in 3" :key="n" class="bg-white border border-gray-200 rounded-lg p-4 animate-pulse">
        <div class="h-3 bg-gray-200 rounded w-1/3 mb-3"></div>
        <div class="h-5 bg-gray-200 rounded w-2/3 mb-2"></div>
        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Error state (Claude's discretion — generic network error) -->
    <div v-else-if="isError" class="text-center py-12 text-gray-500">
      <p>Hiba történt az adatok betöltése során.</p>
    </div>

    <!-- Empty state: successful fetch, zero results -->
    <div v-else-if="data?.length === 0" class="text-center py-12">
      <!-- Icon (Claude's discretion: users or list icon as inline SVG) -->
      <p class="text-lg font-medium text-gray-900 mt-4">Nincsenek jelentkezők</p>
      <p class="text-sm text-gray-500 mt-1">A rendszerben még nem szerepel egyetlen jelentkező sem.</p>
    </div>

    <!-- List: data exists and is non-empty -->
    <div v-else class="space-y-3">
      <div
        v-for="applicant in data"
        :key="applicant.id"
        class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors"
        @click="navigateTo(applicant.id)"
      >
        <div>
          <p class="text-xs text-gray-500">{{ applicant.program.university }} — {{ applicant.program.faculty }}</p>
          <p class="text-base font-semibold text-gray-900 mt-0.5">{{ applicant.program.name }}</p>
        </div>
        <span class="text-gray-400 text-xl ml-4" aria-hidden="true">›</span>
      </div>
    </div>

  </div>
</template>
```

**Why click handler on `<div>` instead of `<RouterLink>` wrapping card:**
- `<RouterLink>` renders as an `<a>` element. Wrapping an entire card-sized block with `<a>` is valid HTML but produces a block-level interactive element. The click handler approach on a `<div>` is simpler and keeps the Tailwind classes self-contained. Both work identically for this use case.
- If using `<div>` with click handler, add `role="button"` and `tabindex="0"` for keyboard accessibility (deferred to v2 per A11Y-01/A11Y-02, but easy to add later).

### Pattern 2: Skeleton Cards Matching Real Card Layout

**What:** Skeleton placeholder uses identical structure and spacing as real cards. Animated with Tailwind `animate-pulse`. Gray bars (`bg-gray-200`) represent text placeholders.

**Design rationale from locked decisions:**
- 3 skeleton cards (not dynamic based on expected data — always exactly 3)
- 3 bars per skeleton card: one narrow top bar (university/faculty), one wide bold bar (programme name), one medium bar (secondary info)
- Bar widths vary (`w-1/3`, `w-2/3`, `w-1/2`) to feel organic, not mechanical

**Tailwind `animate-pulse` availability:** Confirmed present in Tailwind CSS v4.2.1 (the `animate-pulse` utility generates a CSS animation keyframe). No additional configuration needed.

```vue
<!-- Skeleton card — repeat 3x via v-for="n in 3" -->
<div class="bg-white border border-gray-200 rounded-lg p-4 animate-pulse">
  <div class="h-3 bg-gray-200 rounded w-1/3 mb-3"></div>   <!-- university — faculty line -->
  <div class="h-5 bg-gray-200 rounded w-2/3 mb-2"></div>   <!-- programme name (taller) -->
  <div class="h-3 bg-gray-200 rounded w-1/2"></div>         <!-- optional secondary bar -->
</div>
```

### Pattern 3: Empty State

**What:** Centered content with icon + two lines of Hungarian text. Icon is a simple inline SVG (no icon library — project uses no icon packages).

**Why no icon library:** The project has no icon library installed. Adding one for a single icon would be disproportionate. An inline SVG or a Unicode symbol is the correct approach for this phase.

**Tailwind classes:** `text-center py-12` for vertical centering in the page flow.

```vue
<!-- Empty state -->
<div class="text-center py-12">
  <!-- Inline SVG: users or list outline icon (Claude's discretion on exact icon) -->
  <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
    <!-- users outline path — Claude chooses specific icon -->
  </svg>
  <p class="text-lg font-medium text-gray-900 mt-4">Nincsenek jelentkezők</p>
  <p class="text-sm text-gray-500 mt-1">A rendszerben még nem szerepel egyetlen jelentkező sem.</p>
</div>
```

### Pattern 4: Hover State in Tailwind CSS v4

**What:** `hover:bg-gray-50` changes the card background from white to gray-50 on hover. `transition-colors` adds a smooth color transition.

**Tailwind v4 behavior:** `hover:` prefix works identically in v4 to v3. `transition-colors` utility is present and generates `transition-property: color, background-color, ...` with a default duration. No configuration changes needed.

**Note on Tailwind v4 dynamic classes:** Tailwind v4 uses content-based scanning. Static class strings like `hover:bg-gray-50` are detected automatically. No safelist entry required when classes are written as complete strings (not concatenated dynamically).

### Pattern 5: Navigation from Card Click

**What:** `useRouter()` in `<script setup>`, call `router.push()` in the click handler.

```typescript
// In <script setup>
import { useRouter } from 'vue-router'
const router = useRouter()

function navigateTo(id: string) {
  router.push({ name: 'applicant-detail', params: { id } })
}
```

```html
<!-- In template -->
<div @click="navigateTo(applicant.id)">...</div>
```

**Why named route instead of string path:** Using `{ name: 'applicant-detail', params: { id } }` is more robust than `/applicants/${id}` — it is refactoring-safe if the path ever changes, and it is the established pattern from `ApplicantDetailView.vue`.

### Anti-Patterns to Avoid

- **Using `isPending` for the skeleton instead of `isLoading`**: `isPending` is `true` even when cached data exists but is stale. Using it for the skeleton would show the skeleton on back-navigation when data is already cached. Use `isLoading` (`isPending && isFetching`).
- **Using `v-show` instead of `v-if` for branches**: `v-show` renders all branches and hides with CSS. For skeleton/empty/list, `v-if`/`v-else-if`/`v-else` is correct — it avoids rendering skeleton DOM in the success case.
- **Dynamic class string concatenation for hover colors**: e.g., `` `hover:bg-${color}-50` `` will be purged in production by Tailwind's scanner. Always write complete class strings.
- **Wrapping the entire `<main>` in `ApplicantDetailView.vue` extra containers**: `App.vue` already provides `<main><RouterView /></main>`. Views must NOT add an extra `<main>` wrapper — just a `<div>` with container classes.
- **Checking `!data` for empty state**: After a successful fetch, `data` is always defined (either `[]` or an array with items). Check `data?.length === 0`, not `!data`, to distinguish empty from loading.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Loading/error/data state management | Manual `ref(false)` for loading, `ref(null)` for error | `useApplicants()` composable (already built) | Race conditions, deduplication, background refetch — TanStack Query handles all of this |
| Client-side routing | `window.location.href` or `history.pushState` | `useRouter().push()` | Vue Router handles scroll restoration, navigation guards, history stack correctly |
| CSS skeleton animation | Custom `@keyframes` in `<style>` | Tailwind `animate-pulse` | Already available, consistent with project's Tailwind-only styling approach |
| Icon component library | Install `heroicons` or `lucide-vue-next` | Inline SVG for the single empty-state icon | Disproportionate dependency for one icon; inline SVG is ~5 lines |

**Key insight:** This phase requires zero new dependencies and zero new composables. Every building block is already in place.

---

## Common Pitfalls

### Pitfall 1: isLoading vs isPending — Wrong Flag for Skeleton

**What goes wrong:** Using `isPending` for the skeleton condition. On back-navigation from the detail view, `isPending` is `false` (correct) BUT if the query is configured with `enabled: false` scenarios or the query key changes, `isPending` can be true even with cached data visible. More critically: `isPending` does not require an active fetch — a query with no fetcher running is also "pending". The skeleton should only appear when a fetch is actually in flight with no prior data.

**Why it happens:** Developers confuse TanStack Query v4's `isLoading` (renamed to `isPending` in v5) with v5's new `isLoading` definition.

**TanStack Query v5 terminology change:**
- v4: `isLoading` = no data yet (equivalent to v5's `isPending`)
- v5: `isLoading` = `isPending && isFetching` = actively fetching with no data
- v5: `isPending` = no data, regardless of fetch status

**How to avoid:** Use `isLoading` (not `isPending`) for the skeleton visibility condition. `isLoading` is `true` only when the query is actively fetching AND has no cached data — exactly the first-load scenario.

**Warning signs:** Skeleton appears on back-navigation from detail view; skeleton stays visible indefinitely when network is slow but data was previously cached.

### Pitfall 2: Empty State Check Before Data is Ready

**What goes wrong:** Rendering the empty state while the query is still loading. If `data` is `undefined` during loading and the template checks `data?.length === 0`, the check passes (`undefined?.length === undefined`, which is falsy, not `=== 0`), so the empty state does not show — but if the check order is wrong, it might.

**How to avoid:** Always use the mutually exclusive `v-if` / `v-else-if` / `v-else` chain:
```html
<div v-if="isLoading">...skeleton...</div>
<div v-else-if="isError">...error...</div>
<div v-else-if="data?.length === 0">...empty state...</div>
<div v-else>...list...</div>
```
This order ensures empty state is only evaluated after loading and error are confirmed false.

### Pitfall 3: Dynamic Tailwind Classes Purged in Production

**What goes wrong:** Building hover color or conditional class via string interpolation. e.g., `` `hover:bg-${hovered ? 'gray' : 'white'}-50` `` — Tailwind's v4 scanner only detects complete class strings. The generated string is not in source and gets purged in the production build.

**Why it happens:** Developers coming from CSS-in-JS where interpolation is normal.

**How to avoid:** Write complete Tailwind class strings. For conditional classes, use an object syntax or computed string of complete class names:
```html
<!-- Correct: complete class strings -->
<div class="bg-white hover:bg-gray-50 transition-colors">
```

**Warning signs:** Cards show white background on hover in development but not in production build.

### Pitfall 4: Cursor Not Showing Click Intent on Card

**What goes wrong:** The card `<div>` is not styled with `cursor-pointer`, so users do not get visual feedback that the card is clickable. The hover background change alone is not sufficient for discoverability.

**How to avoid:** Add `cursor-pointer` to the card element that has the `@click` handler.

### Pitfall 5: v-for Key on Skeleton Cards

**What goes wrong:** Using `v-for="n in 3"` without a `:key` — Vue warns in development. Using `:key="n"` is correct since `n` is 1, 2, 3 (unique within the loop).

**How to avoid:** Always include `:key` on `v-for`. For skeleton: `:key="n"` where `n` is the loop index from `v-for="n in 3"`.

---

## Code Examples

Verified patterns from the installed libraries and existing project code:

### Complete ApplicantsView.vue Implementation

```vue
<!-- src/views/ApplicantsView.vue -->
<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useApplicants } from '@/composables/useApplicants'

const router = useRouter()
const { isLoading, isError, data } = useApplicants()

function navigateTo(id: string) {
  router.push({ name: 'applicant-detail', params: { id } })
}
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">

    <!-- LIST-03: Loading skeleton — 3 animated placeholder cards -->
    <div v-if="isLoading" class="space-y-3">
      <div
        v-for="n in 3"
        :key="n"
        class="bg-white border border-gray-200 rounded-lg p-4 animate-pulse"
      >
        <div class="h-3 bg-gray-200 rounded w-1/3 mb-3"></div>
        <div class="h-5 bg-gray-200 rounded w-2/3 mb-2"></div>
        <div class="h-3 bg-gray-200 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Error state (Claude's discretion) -->
    <div v-else-if="isError" class="text-center py-12 text-gray-500">
      <p>Hiba történt az adatok betöltése során.</p>
    </div>

    <!-- LIST-04: Empty state -->
    <div v-else-if="data?.length === 0" class="text-center py-12">
      <svg
        class="mx-auto h-12 w-12 text-gray-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <!-- Claude chooses icon — users or list outline -->
      </svg>
      <p class="text-lg font-medium text-gray-900 mt-4">Nincsenek jelentkezők</p>
      <p class="text-sm text-gray-500 mt-1">A rendszerben még nem szerepel egyetlen jelentkező sem.</p>
    </div>

    <!-- LIST-01 + LIST-02: Applicant cards -->
    <div v-else class="space-y-3">
      <div
        v-for="applicant in data"
        :key="applicant.id"
        class="bg-white border border-gray-200 rounded-lg p-4 flex items-center justify-between cursor-pointer hover:bg-gray-50 transition-colors"
        @click="navigateTo(applicant.id)"
      >
        <div>
          <p class="text-xs text-gray-500">
            {{ applicant.program.university }} — {{ applicant.program.faculty }}
          </p>
          <p class="text-base font-semibold text-gray-900 mt-0.5">
            {{ applicant.program.name }}
          </p>
        </div>
        <span class="text-gray-400 text-xl ml-4" aria-hidden="true">›</span>
      </div>
    </div>

  </div>
</template>
```

### TanStack Query v5 State Flags Reference

```typescript
// Confirmed from installed @tanstack/query-core 5.92.x type definitions
// QueryObserverBaseResult interface:
//   isLoading: boolean   — true when isFetching && isPending (first load, no cache)
//   isPending: boolean   — true when no data in cache (status = 'pending')
//   isError: boolean     — true when last fetch failed
//   isSuccess: boolean   — true when data is available

// For skeleton: use isLoading (not isPending)
// For error branch: use isError
// For empty check: data?.length === 0 (data is [] not undefined on success)
```

### Accessing Typed Applicant Fields

```typescript
// Applicant type from src/types/api.ts (no changes needed):
// interface Applicant {
//   id: string
//   program: {
//     university: string
//     faculty: string
//     name: string
//   }
// }

// In template:
// applicant.id          → navigation param
// applicant.program.name       → primary text (bold, larger)
// applicant.program.university → secondary text
// applicant.program.faculty    → secondary text
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| TanStack Query v4 `isLoading` (= no data yet) | TanStack Query v5 `isPending` (= no data yet); `isLoading` = first active fetch | v5 (2023) | Use `isLoading` for skeleton — more precise; `isPending` would show skeleton even when data is cached |
| Tailwind CSS v3 `hover:` with `transition` plugin | Tailwind CSS v4 `hover:` + `transition-colors` — same API, different internals | Tailwind v4 (2024) | No API change; same class names work |
| `@tailwindcss/vite` + PostCSS | `@tailwindcss/vite` only — no PostCSS config | Tailwind v4 | No `tailwind.config.js` needed; content detection is automatic |
| Options API `this.$router.push()` | Composition API `useRouter().push()` | Vue 3 / Composition API | `useRouter()` is required in `<script setup>` |

**Deprecated/outdated:**
- `isInitialLoading`: Deprecated alias for `isLoading` in TanStack Query v5 — use `isLoading` directly
- `onError`/`onSuccess` callbacks on `useQuery()`: Removed in TanStack Query v5 — read `.error` and `.data` refs in template

---

## Open Questions

1. **Error state design for failed API fetch (network error)**
   - What we know: CONTEXT.md marks "Error state for failed API fetch" as Claude's discretion. `isError` is available from `useApplicants()`.
   - What's unclear: No explicit design decision for the error card in Phase 3 (the error card design is a Phase 4 concern for 422 scores).
   - Recommendation: Simple centered text message in Hungarian — "Hiba történt az adatok betöltése során." with no retry button (retry button is explicitly mentioned only for Phase 4 score errors). This is the minimal correct implementation.

2. **Empty state icon choice**
   - What we know: CONTEXT.md says "list or users icon", Claude's discretion.
   - What's unclear: Project has no icon library — must be inline SVG.
   - Recommendation: Use a simple inline SVG for a "users" icon (3 person shapes) or a "list" icon (3 horizontal lines). Both are simple enough to write as a 5-6 line SVG path without a library. Heroicons SVG paths are MIT licensed and can be copied directly.

---

## Validation Architecture

> Testing is deferred to v1.1 per REQUIREMENTS.md Out of Scope table ("Unit/E2E testing: Deferred to v1.1"). No `config.json` found to confirm `nyquist_validation` setting — skipping this section per the default established in Phase 2 research.

---

## Sources

### Primary (HIGH confidence)

- Installed `@tanstack/query-core` v5.92.x type definitions at `node_modules/@tanstack/query-core/build/legacy/hydration-BlEVG2Lp.d.ts` — `isLoading`, `isPending`, `isError`, `isSuccess` field definitions confirmed directly from installed package
- Installed `@tanstack/vue-query` v5.92.9 `UseBaseQueryReturnType` — confirms all `QueryObserverResult` fields are returned as `Ref<>` types
- `src/composables/useApplicants.ts` — returns `useQuery<Applicant[]>` result; confirmed `isLoading`, `data`, `isError` are accessible
- `src/types/api.ts` — `Applicant` interface with `id: string` and `program: { university, faculty, name }` confirmed
- `src/router/index.ts` — `applicant-detail` named route with `:id` param confirmed
- `src/App.vue` — `animate-pulse` utility usage confirmed working in project
- `src/views/ApplicantsView.vue` — existing `max-w-4xl mx-auto px-4 py-6` container pattern confirmed
- Tailwind CSS v4.2.1 installed — `animate-pulse`, `hover:`, `transition-colors`, `space-y-*` utilities confirmed available
- `package.json` — no icon library present; confirms inline SVG approach

### Secondary (MEDIUM confidence)

- TanStack Query v5 migration guide — `isLoading` vs `isPending` semantic change from v4 to v5 (cross-verified with installed type definitions — HIGH)

### Tertiary (LOW confidence)

- None — all findings verified against installed packages or project source files

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — no new dependencies; all verified from installed node_modules
- Architecture: HIGH — single file change; all patterns verified from installed type definitions and existing project source
- Pitfalls: HIGH — `isLoading` vs `isPending` verified directly from installed `@tanstack/query-core` types; Tailwind dynamic class purging is a documented v4 behavior

**Research date:** 2026-02-28
**Valid until:** 2026-03-30 (stable installed dependencies; no updates expected to affect this phase's patterns)
