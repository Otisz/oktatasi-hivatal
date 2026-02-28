# Phase 2: Routing and Data Layer - Research

**Researched:** 2026-02-28
**Domain:** Vue Router 4 + TanStack Query v5 (Vue) — routing setup, typed composables, 422 error discrimination
**Confidence:** HIGH

---

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

- **422 Error Handling**: Two error categories: domain errors (422 with verbatim Hungarian message) vs generic errors (network/server — generic Hungarian message). Network/server errors show a Hungarian error message with a retry button. Domain 422 errors display the API's Hungarian message as-is. Retry button invalidates the cached error and refetches fresh.
- **Unknown Route Behavior**: All unknown URLs redirect silently to `/applicants` — no 404 page. Invalid applicant IDs are NOT validated client-side — the API response handles the error. HTML5 history mode for clean URLs. Subtle top progress bar visible during route transitions (like YouTube/GitHub style).
- **Data Freshness**: Long staleTime (30min+) since API data is seeded and static. No refetch on back-navigation from detail to list — show cached list instantly. Score results cached per applicant ID — revisiting the same applicant shows cached result. Retry action invalidates cache and refetches (clean attempt).
- **Placeholder Views**: Structural shells with layout wrapper, heading, and content area. Persistent app header across all views: "Oktatasi Hivatal" as main title, "Felveteli pontszamolo" as subtitle. Detail view placeholder includes "← Vissza" back link to `/applicants` (satisfies NAV-02 early).

### Claude's Discretion

- Technical pattern for 422 discrimination (interceptor vs per-query vs custom error class)
- Query composable structure (naming, file organization)
- Top progress bar implementation approach
- Exact header styling and layout spacing
- Placeholder view internal structure and Tailwind classes

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope
</user_constraints>

---

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| NAV-01 | Vue Router with history mode and two named routes (`/applicants`, `/applicants/:id`) | Vue Router 4 `createRouter` + `createWebHistory` pattern documented; named route with dynamic param `:id` confirmed |
| NAV-02 | Back navigation from score detail view to applicant list | `<RouterLink to="/applicants">` or `router.push` in placeholder view; covered by Decisions |
| NAV-03 | Default route (`/`) redirects to `/applicants` | Vue Router 4 redirect syntax `{ path: '/', redirect: '/applicants' }` confirmed; catch-all to named route documented |
| DATA-02 | Query for listing applicants consuming GET /api/v1/applicants | TanStack Query `useQuery` composable pattern confirmed; existing `http` Axios instance ready |
| DATA-03 | Query for fetching applicant score consuming GET /api/v1/applicants/{id}/score | Dynamic query key with reactive param; `toRef`/`toValue` pattern for prop/ref safety confirmed |
| DATA-04 | 422 domain errors distinguished from network errors in score query | `axios.isAxiosError(e) && e.response?.status === 422` narrows to `ApiError`; per-query approach recommended over global interceptor |
</phase_requirements>

---

## Summary

Phase 2 wires Vue Router 4 into the existing Vite/Vue 3 app and builds two TanStack Query composables. Both technologies are already present in the project's dependencies as Vue Router needs to be installed (`vue-router` not yet in `package.json`). The core patterns are stable, well-documented, and fit exactly the existing code structure (`src/lib/http.ts`, `src/lib/query.ts`, `src/types/api.ts` are all ready to consume).

The dominant risk is 422 error discrimination: TanStack Query treats HTTP 4xx as errors automatically when using Axios (since Axios throws on non-2xx), but distinguishing a domain 422 (`ApiError`) from a network/server failure requires explicit status-code narrowing inside the `queryFn` or a per-query `retry` guard. The recommended approach is to check `axios.isAxiosError(e) && e.response?.status === 422` directly in the score composable's `queryFn`, throwing a typed object that the component can discriminate. This keeps the logic co-located with the query that needs it and avoids a global interceptor that would affect all queries.

Vue Router 5 is available (released February 2025) but is merely "boring" — it merges `unplugin-vue-router` with no breaking API changes. Installing `vue-router` without a version pin gives v5; `vue-router@4` pins to v4. Either works identically for this phase's patterns. For the progress bar, `nprogress` (npm: 0.2.0, last published 11 years ago) is the de-facto standard and works fine with `@types/nprogress`; however, given the project already uses Tailwind CSS v4, a minimal custom implementation (a fixed `<div>` driven by a Vue `ref`) is a viable zero-dependency alternative — both are valid for Claude's discretion.

**Primary recommendation:** Install `vue-router`, create `src/router/index.ts` with two named routes + redirect + catch-all, register router in `main.ts`, update `App.vue` with persistent header + `<RouterView>`, then create two composables in `src/composables/` wrapping `useQuery` for applicants list and score.

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| vue-router | ^4.6.x or ^5.0.x | Client-side routing | Official Vue router; createWebHistory for HTML5 mode |
| @tanstack/vue-query | ^5.92.9 (already installed) | Data fetching/caching | Already wired in main.ts via VueQueryPlugin |
| axios | ^1.13.6 (already installed) | HTTP client | Already in src/lib/http.ts; isAxiosError for narrowing |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| nprogress | ^0.2.0 | Slim top progress bar | If using third-party progress bar (Claude's discretion) |
| @types/nprogress | ^0.2.x | TypeScript types for nprogress | Required if nprogress chosen |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| nprogress | Custom Tailwind `<div>` reactive component | Zero dep, fits Tailwind v4 project perfectly; slightly more code |
| nprogress | VueUse `useNProgress` (@vueuse/integrations) | Adds @vueuse dependency just for this; not worth it for Phase 2 |
| Per-query 422 discrimination | Axios request interceptor | Interceptor is global — bleeds 422 handling into list query where it's irrelevant |

**Installation (vue-router only — others already in package.json):**
```bash
npm install vue-router
```

If nprogress chosen:
```bash
npm install nprogress
npm install -D @types/nprogress
```

---

## Architecture Patterns

### Recommended Project Structure

```
src/
├── router/
│   └── index.ts          # createRouter, routes array, navigation guards
├── composables/
│   ├── useApplicants.ts   # wraps useQuery for GET /api/v1/applicants
│   └── useApplicantScore.ts  # wraps useQuery for GET /api/v1/applicants/{id}/score
├── views/
│   ├── ApplicantsView.vue     # placeholder — layout shell, heading
│   └── ApplicantDetailView.vue  # placeholder — layout shell, ← Vissza link
├── lib/
│   ├── http.ts           # (existing) Axios instance
│   └── query.ts          # (existing) QueryClient — staleTime needs increase to 30min
├── types/
│   └── api.ts            # (existing) Applicant, ScoreResult, ApiError
└── main.ts               # register router + VueQueryPlugin
```

### Pattern 1: Vue Router with History Mode, Named Routes, Redirects

**What:** Single router file exports the router instance. Routes use `name` for programmatic navigation. Redirect from `/` to `/applicants`. Catch-all redirects unknown paths to `/applicants`.

**When to use:** All routing in this phase.

```typescript
// src/router/index.ts
// Source: https://router.vuejs.org/guide/essentials/redirect-and-alias.html
import { createRouter, createWebHistory } from 'vue-router'
import ApplicantsView from '@/views/ApplicantsView.vue'
import ApplicantDetailView from '@/views/ApplicantDetailView.vue'

const routes = [
  {
    path: '/applicants',
    name: 'applicants',
    component: ApplicantsView,
  },
  {
    path: '/applicants/:id',
    name: 'applicant-detail',
    component: ApplicantDetailView,
  },
  // NAV-03: redirect root to applicants
  {
    path: '/',
    redirect: { name: 'applicants' },
  },
  // Catch-all: redirect unknown paths silently to applicants (no 404 page)
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'applicants' },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
```

**Note on Vite dev server:** Vite's dev server automatically handles HTML5 history fallback for single-entry SPAs — no extra configuration needed in `vite.config.ts` for development. Production deployment (nginx/apache) requires a catch-all rule to serve `index.html`.

### Pattern 2: Registering Router in main.ts Alongside VueQueryPlugin

**What:** Chain `.use()` calls — order matters: router before VueQueryPlugin is conventional but either order works.

```typescript
// src/main.ts
// Source: https://router.vuejs.org/guide/#javascript
import { VueQueryPlugin } from '@tanstack/vue-query'
import { createApp } from 'vue'
import { queryClient } from '@/lib/query'
import { router } from '@/router'
import App from './App.vue'
import './assets/main.css'

createApp(App)
  .use(router)
  .use(VueQueryPlugin, { queryClient })
  .mount('#app')
```

### Pattern 3: App.vue with Persistent Header and RouterView

**What:** App.vue becomes the layout shell. Persistent header renders above `<RouterView>`. No conditional logic — header is always visible.

```vue
<!-- src/App.vue -->
<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white border-b border-gray-200">
      <div class="max-w-4xl mx-auto px-4 py-4">
        <h1 class="text-xl font-bold text-gray-900">Oktatasi Hivatal</h1>
        <p class="text-sm text-gray-500">Felveteli pontszamolo</p>
      </div>
    </header>
    <!-- Optional: progress bar component here -->
    <main>
      <RouterView />
    </main>
  </div>
</template>
```

### Pattern 4: Navigation Guards for Progress Bar

**What:** `router.beforeEach` starts progress; `router.afterEach` completes it. Guards live in router/index.ts or in a separate setup function called from main.ts.

```typescript
// In router/index.ts or called from main.ts after router creation
// Source: https://router.vuejs.org/guide/advanced/navigation-guards.html
import NProgress from 'nprogress'
import 'nprogress/nprogress.css'

router.beforeEach(() => {
  NProgress.start()
})

router.afterEach(() => {
  NProgress.done()
})
```

**Custom Tailwind alternative (no nprogress dep):**
```typescript
// src/composables/useProgress.ts
import { ref } from 'vue'

export const isNavigating = ref(false)

// In router/index.ts:
// router.beforeEach(() => { isNavigating.value = true })
// router.afterEach(() => { isNavigating.value = false })
```
```vue
<!-- In App.vue template -->
<div
  v-if="isNavigating"
  class="fixed top-0 left-0 h-0.5 bg-blue-500 animate-pulse w-full z-50"
/>
```

### Pattern 5: TanStack Query Composable for Applicants List

**What:** Wraps `useQuery` in a named composable. Returns the full query result. No dynamic parameters.

```typescript
// src/composables/useApplicants.ts
import { useQuery } from '@tanstack/vue-query'
import type { Applicant, ApiResponse } from '@/types/api'
import { http } from '@/lib/http'

export function useApplicants() {
  return useQuery<Applicant[], Error>({
    queryKey: ['applicants'],
    queryFn: async () => {
      const { data } = await http.get<ApiResponse<Applicant[]>>('/api/v1/applicants')
      return data.data
    },
  })
}
```

### Pattern 6: TanStack Query Composable for Score with 422 Discrimination

**What:** Score composable accepts a reactive applicant ID. The `queryFn` discriminates 422 from other errors by catching Axios errors and re-throwing a typed domain error vs generic error. `retry` is disabled for 4xx responses.

**Key insight:** Axios throws automatically on non-2xx status. The `queryFn` should catch the AxiosError, check `error.response?.status === 422`, then re-throw a typed error object that components can discriminate. This keeps error type information in the query result's `error` field.

```typescript
// src/composables/useApplicantScore.ts
import { useQuery } from '@tanstack/vue-query'
import axios from 'axios'
import type { MaybeRefOrGetter } from 'vue'
import { toValue } from 'vue'
import type { ApiError, ScoreResult } from '@/types/api'
import { http } from '@/lib/http'

// Discriminated union: what the component inspects
export type ScoreError =
  | { kind: 'domain'; message: string }   // 422 — show verbatim API message
  | { kind: 'generic' }                   // network/5xx — show generic Hungarian text

export function useApplicantScore(id: MaybeRefOrGetter<string>) {
  return useQuery<ScoreResult, ScoreError>({
    queryKey: ['applicants', 'score', { id: toValue(id) }],
    queryFn: async () => {
      try {
        const { data } = await http.get<ScoreResult>(`/api/v1/applicants/${toValue(id)}/score`)
        return data
      } catch (e) {
        if (axios.isAxiosError(e) && e.response?.status === 422) {
          const body = e.response.data as ApiError
          throw { kind: 'domain', message: body.error } satisfies ScoreError
        }
        throw { kind: 'generic' } satisfies ScoreError
      }
    },
    // Do NOT retry domain errors (422) — they are deterministic
    retry: (_, error) => error.kind !== 'domain',
  })
}
```

**Reactivity note:** When the `id` prop/ref is passed to the composable, wrap it with `toRef(() => props.id)` at the call site or accept `MaybeRefOrGetter<string>` and call `toValue()` inside — failure to do so breaks reactive re-fetching when the route changes.

**Query key note:** Using `{ id: toValue(id) }` inside the key object is safe for static renders but will NOT be reactive if the outer key array doesn't change. Prefer the flat array form `['applicants', 'score', toValue(id)]` or ensure the entire `queryKey` array is wrapped in `computed()` when the id is reactive.

```typescript
// Safer reactive query key pattern:
queryKey: computed(() => ['applicants', 'score', toValue(id)]),
```

### Pattern 7: Retry Button Using useQueryClient + invalidateQueries

**What:** When user clicks retry, the cached error entry is invalidated, forcing a fresh fetch. Use `useQueryClient()` in the component — NOT in the composable.

```typescript
// In ApplicantDetailView.vue <script setup>
import { useQueryClient } from '@tanstack/vue-query'
import { useRoute } from 'vue-router'

const route = useRoute()
const queryClient = useQueryClient()

function handleRetry() {
  queryClient.invalidateQueries({
    queryKey: ['applicants', 'score', route.params.id],
  })
}
```

**Known issue:** There is a documented Vue Query v5 bug where `invalidateQueries` may not invalidate "immediately" in some scenarios (GitHub issue #7694, reported July 2024). Mitigation: use `refetchType: 'all'` option or call `refetch()` from the query result directly as a fallback.

### Pattern 8: QueryClient staleTime Update

The existing `src/lib/query.ts` has `staleTime: 1000 * 60 * 5` (5 min). Per user decision, increase to 30min+:

```typescript
// src/lib/query.ts
import { QueryClient } from '@tanstack/vue-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 30, // 30 minutes — static seeded data
    },
  },
})
```

### Anti-Patterns to Avoid

- **Importing `axios` directly in views or query files**: Always import `http` from `@/lib/http` — project rule from STATE.md.
- **Using `onError` callback in useQuery**: Removed in TanStack Query v5. Use the `error` return value in the component template or `watch(error, ...)` instead.
- **Extracting `props.id` to a plain variable before passing to composable**: Breaks Vue reactivity. Use `toRef(() => props.id)` or pass the route param ref.
- **Placing 422 handling in an Axios interceptor**: Interceptors are global and affect all queries. 422 on the applicant score endpoint has domain meaning; intercepting it globally prevents per-query error discrimination.
- **Catch-all route before named routes**: Vue Router matches routes in array order — put catch-all last.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Route matching & history management | Custom history pushState wrapper | vue-router | Edge cases: popstate, scroll restoration, navigation guards, route params normalization |
| Stale-while-revalidate caching | Manual `ref`/`reactive` with fetch | @tanstack/vue-query | Race conditions, deduplication, background refetch, devtools — already installed |
| Error retries with backoff | Manual `setTimeout` retry loop | TanStack Query `retry` option | Handles concurrent deduplication; exponential backoff built in |
| Progress bar CSS animation | Custom keyframes from scratch | nprogress OR 4-line Tailwind component | nprogress handles edge cases (min %, easing, done fade); simple enough to hand-roll with Tailwind only |

**Key insight:** TanStack Query's cache invalidation handles the retry-button requirement without mutations — `invalidateQueries` marks the score stale and triggers an immediate refetch if the query is active.

---

## Common Pitfalls

### Pitfall 1: Reactive Query Key Loss Across Component Boundary

**What goes wrong:** Passing `route.params.id` (string) instead of a reactive ref to the score composable. The query key becomes static — navigating to a different applicant ID does not trigger a new fetch.

**Why it happens:** Vue's reactivity is reference-based. A destructured string value loses the reactive binding.

**How to avoid:** In `ApplicantDetailView.vue`, use:
```typescript
const route = useRoute()
// Correct: toRef preserves reactivity
const { data, error } = useApplicantScore(toRef(() => route.params.id as string))
```

**Warning signs:** Navigating from `/applicants/uuid-1` to `/applicants/uuid-2` shows data from uuid-1.

### Pitfall 2: Vite Dev Server 404 on Direct Navigation

**What goes wrong:** Navigating directly to `/applicants/some-id` in a fresh browser tab returns 404 — Vite dev server serves `index.html` only for `/`.

**Why it happens:** HTML5 history mode requires the server to serve `index.html` for all paths. Vite does handle this for single-entry SPAs by default through its internal HTML fallback middleware.

**How to avoid:** Vite's SPA mode works without configuration for a single `index.html` entry — this should work out of the box. If 404s appear, check that `vite.config.ts` does NOT have `appType: 'mpa'`.

**Warning signs:** Direct URL navigation returns a blank page or 404 in Vite dev.

### Pitfall 3: 422 Not Caught as Error (Axios Auto-Throw)

**What goes wrong:** Assuming Axios 422 responses arrive in the `data` field — but Axios throws on all non-2xx status codes. The `queryFn` never receives the response body directly for 422.

**Why it happens:** Unlike `fetch`, Axios rejects the promise on HTTP error status. The response body is on `error.response.data`, not `data`.

**How to avoid:** Use the `try/catch` pattern in `queryFn` shown in Pattern 6. Access the 422 body via `e.response.data as ApiError`.

**Warning signs:** `data` is always `undefined` when score returns 422; error is caught as a generic Error object.

### Pitfall 4: onError/onSuccess Callbacks (Removed in v5)

**What goes wrong:** Writing `useQuery({ ..., onError: (e) => ... })` causes a TypeScript error — these callbacks were removed in TanStack Query v5.

**Why it happens:** Breaking change from v4 → v5 (documented in migration guide).

**How to avoid:** Use `watch(error, ...)` for side effects (toasts, logs). Read `error.value` directly in the template for rendering.

### Pitfall 5: router.push() Outside of Component / Composition API

**What goes wrong:** Calling `router.push()` in a module-level context (e.g., inside a composable that is not inside a component setup) causes a "No active component instance" warning.

**Why it happens:** `useRouter()` requires the Vue injection context.

**How to avoid:** For router guards, import the `router` instance directly from `@/router`. For components, use `useRouter()`.

### Pitfall 6: 422 Retry Loop

**What goes wrong:** TanStack Query default retry count is 3. Without configuring `retry`, a 422 error will be retried 3 times before surface — wasting API calls on a deterministic domain error.

**Why it happens:** Default retry config doesn't know a 422 is deterministic.

**How to avoid:** Set `retry: (_, error) => error.kind !== 'domain'` in the score composable — Pattern 6 above. This allows retries for generic (network/5xx) errors but not for 422s.

---

## Code Examples

Verified patterns from official and cross-verified sources:

### Complete router/index.ts

```typescript
// Source: https://router.vuejs.org/guide/essentials/redirect-and-alias.html
import { createRouter, createWebHistory } from 'vue-router'
import ApplicantsView from '@/views/ApplicantsView.vue'
import ApplicantDetailView from '@/views/ApplicantDetailView.vue'

const routes = [
  {
    path: '/applicants',
    name: 'applicants',
    component: ApplicantsView,
  },
  {
    path: '/applicants/:id',
    name: 'applicant-detail',
    component: ApplicantDetailView,
  },
  { path: '/', redirect: { name: 'applicants' } },
  { path: '/:pathMatch(.*)*', redirect: { name: 'applicants' } },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
```

### Dynamic param access in component

```typescript
// Source: https://router.vuejs.org/guide/essentials/dynamic-matching.html
import { useRoute } from 'vue-router'
import { toRef } from 'vue'

const route = useRoute()
// route.params.id is a string | string[] — cast to string
const applicantId = toRef(() => route.params.id as string)
```

### Query key arrays (stable, TanStack Query v5)

```typescript
// Flat array — preferred for dynamic params
queryKey: ['applicants']                              // list
queryKey: ['applicants', 'score', applicantId]        // score for one applicant
```

### useQueryClient invalidateQueries (retry button)

```typescript
// Source: https://tanstack.com/query/latest/docs/reference/QueryClient
import { useQueryClient } from '@tanstack/vue-query'

const queryClient = useQueryClient()

function retry() {
  queryClient.invalidateQueries({
    queryKey: ['applicants', 'score', applicantId],
  })
}
```

### Placeholder view structure

```vue
<!-- src/views/ApplicantDetailView.vue -->
<script setup lang="ts">
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
</script>

<template>
  <div class="max-w-4xl mx-auto px-4 py-6">
    <RouterLink to="/applicants" class="text-sm text-blue-600 hover:underline mb-4 inline-block">
      ← Vissza
    </RouterLink>
    <h2 class="text-xl font-semibold text-gray-900">Pontozas</h2>
    <p class="text-gray-500 text-sm mt-1">{{ route.params.id }}</p>
    <!-- Phase 4 fills content here -->
  </div>
</template>
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Vue Router 3 (Vue 2) | Vue Router 4/5 (Vue 3) | Vue 3 release | `createRouter` replaces `new VueRouter()`; named exports replace default |
| `onError`/`onSuccess` callbacks in useQuery | `watch(error, ...)` or read `.error` in template | TanStack Query v5 (2023) | Callbacks silently do nothing in v5 — runtime-silent TypeScript error |
| `vue-router@4` | `vue-router@5` (released Feb 2025) | Feb 2025 | No breaking changes — v5 adds file-based routing; v4 API identical |
| `queryKey: computed(() => ...)` required for reactive keys | `queryKey` as array with `toValue()` refs | TanStack Query v5.x | Both work; flat array pattern is simpler for this project's needs |

**Deprecated/outdated:**
- `new VueRouter()`: Vue Router 3 syntax — does not exist in vue-router 4/5.
- `this.$route`: Options API pattern — use `useRoute()` in Composition API / `<script setup>`.
- `onSuccess`/`onError` on `useQuery`: Removed in TanStack Query v5.

---

## Open Questions

1. **vue-router v4 vs v5 for installation**
   - What we know: `npm install vue-router` installs v5.0.3 (as of Feb 2025). v5 has no breaking API changes vs v4. All code examples in this document work on both.
   - What's unclear: Whether the project has a preference for pinning to v4. v5's experimental "data loaders" feature is irrelevant to this phase.
   - Recommendation: Install without version pin (`npm install vue-router`) to get v5.0.x. If v4 is preferred for stability, `npm install vue-router@4`.

2. **Progress bar approach (Claude's discretion)**
   - What we know: `nprogress` is the standard; it works with `@types/nprogress`. Custom Tailwind approach is simpler for this project.
   - What's unclear: Project's appetite for a third-party dependency just for a progress bar.
   - Recommendation: Use a custom minimal Tailwind component (a fixed `h-0.5 bg-blue-500` div driven by a Vue `ref`) — zero dependency, fits Tailwind v4, ~10 lines total. Avoids importing nprogress CSS alongside Tailwind's cascade.

3. **CORS in development (from STATE.md blocker)**
   - What we know: CORS must be verified before Phase 2 feature work can be validated in the browser.
   - What's unclear: Whether the backend is running locally or needs a Vite proxy config.
   - Recommendation: If CORS is blocked, add a Vite dev server proxy in `vite.config.ts`: `server: { proxy: { '/api': 'http://localhost:<port>' } }`. This is separate from routing but must be unblocked for DATA-02/DATA-03 success criteria verification.

---

## Validation Architecture

> No `.planning/config.json` found — `workflow.nyquist_validation` cannot be read. Defaulting to: **skip Validation Architecture section** as the REQUIREMENTS.md explicitly marks "Unit/E2E testing: Deferred to v1.1" in the Out of Scope table.

---

## Sources

### Primary (HIGH confidence)

- https://router.vuejs.org/guide/ — Official Vue Router 4/5 getting started, App.vue setup
- https://router.vuejs.org/guide/essentials/history-mode.html — createWebHistory, server config, catch-all route
- https://router.vuejs.org/guide/essentials/redirect-and-alias.html — Redirect syntax, named route redirects
- https://router.vuejs.org/guide/advanced/navigation-guards.html — beforeEach/afterEach guard syntax
- https://github.com/vuejs/router/releases — vue-router v5.0.3 release confirmation, backward compat statement
- https://jasonwatmore.com/vue-3-catch-all-redirect-default-route-with-vue-router — Verified against official docs, catch-all with redirect to named route

### Secondary (MEDIUM confidence)

- https://paulau.dev/blog/common-mistakes-in-tanstack-query-and-vuejs-composition-api/ — Reactivity loss pitfalls, toRef/toValue patterns (cross-verified with TanStack docs)
- https://tkdodo.eu/blog/react-query-error-handling — throwOnError pattern, 4xx vs 5xx discrimination (TkDodo is TanStack Query core team — HIGH credibility)
- https://tanstack.com/query/v5/docs/framework/vue/guides/query-retries — Retry function signature (confirmed via WebSearch cross-ref)
- https://www.npmjs.com/package/vue-router — Version confirmation (5.0.3, Feb 2025)
- https://www.npmjs.com/package/nprogress — Version confirmation (0.2.0)

### Tertiary (LOW confidence)

- GitHub issue #7694 (TanStack/query) — `invalidateQueries` not invalidating immediately in Vue — single report, unverified reproduction in project context; mitigation documented

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — both vue-router and @tanstack/vue-query are official, stable, version-confirmed
- Architecture: HIGH — patterns from official docs and verified secondary sources
- 422 discrimination pattern: HIGH — axios.isAxiosError + status check is well-documented; discriminated union is TypeScript-idiomatic
- Progress bar: MEDIUM — nprogress is de-facto standard but 11 years old; custom Tailwind approach is research-derived reasoning
- Pitfalls: HIGH for reactivity loss (multiple sources agree); MEDIUM for invalidateQueries timing bug (single report)

**Research date:** 2026-02-28
**Valid until:** 2026-03-30 (stable libraries; vue-router and TanStack Query APIs are not in flux)
