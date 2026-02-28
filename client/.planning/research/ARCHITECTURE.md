# Architecture Research

**Domain:** Small Vue 3 + TypeScript SPA — list/detail with REST API
**Researched:** 2026-02-28
**Confidence:** HIGH (official Vue docs + Vue Router docs verified)

## Standard Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        Views (Pages)                         │
│  ┌─────────────────────┐  ┌─────────────────────────────┐   │
│  │  ApplicantListView  │  │      ScoreDetailView        │   │
│  └──────────┬──────────┘  └──────────────┬──────────────┘   │
│             │                            │                   │
│  ┌──────────▼──────────┐  ┌──────────────▼──────────────┐   │
│  │ ApplicantCard (list)│  │ ScoreBreakdown / ScoreError │   │
│  └──────────┬──────────┘  └──────────────┬──────────────┘   │
├─────────────┴────────────────────────────┴───────────────────┤
│                      Composables Layer                        │
│  ┌────────────────────┐  ┌──────────────────────────────┐    │
│  │  useApplicants()   │  │       useApplicantScore()    │    │
│  └────────────┬───────┘  └──────────────┬───────────────┘    │
├───────────────┴──────────────────────────┴────────────────────┤
│                        API Layer                               │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │                    api/applicants.ts                     │  │
│  │  fetchApplicants() | fetchApplicantScore(id)             │  │
│  └─────────────────────────────────────────────────────────┘  │
│  ┌─────────────────────────────────────────────────────────┐  │
│  │                    api/http.ts                           │  │
│  │  Fetch wrapper — base URL, JSON parsing, error shaping  │  │
│  └─────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────── ┤
│                        Types Layer                              │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  types/api.ts — Applicant, ScoreResult, ApiError        │   │
│  └─────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| `ApplicantListView` | Calls `useApplicants()`, renders list, handles loading/error | `<script setup>` view, delegates to ApplicantCard |
| `ScoreDetailView` | Reads route param, calls `useApplicantScore(id)`, renders result or error | `<script setup>` view, delegates to ScoreBreakdown or ScoreError |
| `ApplicantCard` | Renders a single applicant row with navigation link | Presentational — no API calls, receives applicant as prop |
| `ScoreBreakdown` | Renders `osszpontszam`, `alappont`, `tobbletpont` fields | Presentational — receives score as prop |
| `ScoreError` | Renders the Hungarian error string from 422 response | Presentational — receives error string as prop |
| `LoadingSpinner` | Shared loading indicator | Presentational atom |
| `ErrorMessage` | Shared generic error display for network failures | Presentational atom |

## Recommended Project Structure

```
src/
├── api/
│   ├── http.ts              # Fetch wrapper: base URL, JSON, error shaping
│   └── applicants.ts        # fetchApplicants(), fetchApplicantScore(id)
│
├── composables/
│   ├── useApplicants.ts     # Wraps fetchApplicants with loading/error/data refs
│   └── useApplicantScore.ts # Wraps fetchApplicantScore with loading/error/data refs
│
├── types/
│   └── api.ts               # Applicant, Program, ScoreResult, ApiError, ApiResponse<T>
│
├── views/
│   ├── ApplicantListView.vue
│   └── ScoreDetailView.vue
│
├── components/
│   ├── applicants/
│   │   └── ApplicantCard.vue
│   ├── score/
│   │   ├── ScoreBreakdown.vue
│   │   └── ScoreError.vue
│   └── ui/
│       ├── LoadingSpinner.vue
│       └── ErrorMessage.vue
│
├── router/
│   └── index.ts             # Route definitions: /applicants, /applicants/:id/score
│
├── App.vue                  # Root shell — router-view only
└── main.ts                  # App bootstrap
```

### Structure Rationale

- **`api/`:** Raw HTTP functions — no Vue reactivity, pure async/await. Easy to mock in future tests. Separates transport from state.
- **`composables/`:** Vue reactivity layer. Each composable owns `data`, `loading`, `error` refs for one API resource. Views destructure and use directly.
- **`types/`:** Single source of truth for API shapes. All API functions and composables import from here.
- **`views/`:** Page-level components. One per route. Own no logic beyond wiring composables to child components.
- **`components/`:** Grouped by domain (`applicants/`, `score/`) plus `ui/` for generic atoms. Never call API — receive data as props.
- **`router/`:** Single `index.ts`. No need for route modules at this scale.

## Architectural Patterns

### Pattern 1: Layered Composable with loading/error/data

**What:** Each API resource gets a composable that owns the three-state lifecycle (loading, error, data) as Vue refs. Views destructure and bind to template.

**When to use:** Every API-backed view. This is the standard Vue 3 pattern for async data.

**Trade-offs:** Straightforward but not shared across views (each view instance gets its own state). For this app that is correct — no shared state needed.

**Example:**
```typescript
// composables/useApplicants.ts
import { ref } from 'vue'
import { fetchApplicants } from '@/api/applicants'
import type { Applicant } from '@/types/api'

export function useApplicants() {
  const applicants = ref<Applicant[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  async function load() {
    loading.value = true
    error.value = null
    try {
      applicants.value = await fetchApplicants()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Hálózati hiba'
    } finally {
      loading.value = false
    }
  }

  return { applicants, loading, error, load }
}
```

### Pattern 2: Thin API Layer (plain async functions)

**What:** `api/` files export plain `async` functions that call the HTTP layer. No Vue reactivity here — just fetch, parse, and throw on failure.

**When to use:** Always. Keeps API logic testable outside Vue's reactivity system and decoupled from any state management decision.

**Trade-offs:** Slightly more files, but makes future test mocking straightforward.

**Example:**
```typescript
// api/http.ts
const BASE_URL = import.meta.env.VITE_API_BASE_URL

async function request<T>(path: string): Promise<T> {
  const res = await fetch(`${BASE_URL}${path}`, {
    headers: { Accept: 'application/json' },
  })
  const body = await res.json()
  if (!res.ok) {
    // Preserve the API's error shape for callers
    throw { status: res.status, error: body.error ?? 'Unknown error' }
  }
  return body.data as T
}

export { request }
```

```typescript
// api/applicants.ts
import { request } from './http'
import type { Applicant, ScoreResult } from '@/types/api'

export const fetchApplicants = () =>
  request<Applicant[]>('/api/v1/applicants')

export const fetchApplicantScore = (id: string) =>
  request<ScoreResult>(`/api/v1/applicants/${id}/score`)
```

### Pattern 3: 422 as Typed Domain Error (not generic Error)

**What:** The score endpoint returns 422 with `{ "error": "Hungarian string" }` when the score cannot be calculated. This is a valid domain response, not a network failure. The HTTP wrapper throws an object with `{ status, error }`. The score composable catches and discriminates: if `status === 422`, it populates a `domainError` ref; otherwise it populates a generic `error` ref.

**When to use:** Any time an API uses HTTP error codes to signal business-level outcomes distinct from network/server failures.

**Trade-offs:** Requires a typed error shape in the HTTP wrapper. Slightly more complex catch logic, but results in clean template logic — separate display for domain errors vs network failures.

**Example:**
```typescript
// composables/useApplicantScore.ts
import { ref } from 'vue'
import { fetchApplicantScore } from '@/api/applicants'
import type { ScoreResult } from '@/types/api'

export function useApplicantScore(id: string) {
  const score = ref<ScoreResult | null>(null)
  const domainError = ref<string | null>(null)  // Hungarian 422 message
  const networkError = ref<string | null>(null) // Network/server failures
  const loading = ref(false)

  async function load() {
    loading.value = true
    score.value = null
    domainError.value = null
    networkError.value = null
    try {
      score.value = await fetchApplicantScore(id)
    } catch (e: unknown) {
      const err = e as { status?: number; error?: string }
      if (err.status === 422 && err.error) {
        domainError.value = err.error
      } else {
        networkError.value = 'Nem sikerült betölteni az adatokat.'
      }
    } finally {
      loading.value = false
    }
  }

  return { score, domainError, networkError, loading, load }
}
```

## Data Flow

### Request Flow

```
User clicks applicant row
    ↓
Router.push({ name: 'score', params: { id } })
    ↓
ScoreDetailView.vue mounts
    ↓
useApplicantScore(route.params.id as string)
    ↓
load() called in onMounted()
    ↓
fetchApplicantScore(id)  [api/applicants.ts]
    ↓
request('/api/v1/applicants/{id}/score')  [api/http.ts]
    ↓
fetch() → JSON parse
    ├── 200 → score.value = data    → ScoreBreakdown rendered
    └── 422 → domainError.value     → ScoreError rendered
         (or network failure → networkError.value → ErrorMessage)
```

### Applicant List Flow

```
ApplicantListView.vue mounts
    ↓
useApplicants().load()
    ↓
fetchApplicants() → 200 → applicants.value = data
    ↓
v-for applicants → ApplicantCard rendered
    ↓
User clicks card → router.push to ScoreDetailView
```

### Key Data Flows

1. **List → Detail navigation:** Via named route with `id` param. ScoreDetailView reads `route.params.id` to call the score endpoint. No shared store required.
2. **422 domain error:** HTTP wrapper throws a plain object `{ status: 422, error: string }`. Composable catches, discriminates by status, sets `domainError`. Template renders `<ScoreError>` component.
3. **Type propagation:** `types/api.ts` defines `Applicant` and `ScoreResult`. API layer functions are typed to return these. Composables use them as ref generics. Components receive them as typed props.

## Scaling Considerations

This is a deliberately minimal app. Scaling notes are included for completeness, not as active recommendations.

| Scale | Architecture Adjustments |
|-------|--------------------------|
| Current (2 views, 2 endpoints) | Present structure is correct. No Pinia, no route modules, no shared state. |
| +5 views, +10 endpoints | Add `api/` modules per domain. Consider Pinia if state is shared across views. |
| +20 views, team of 3+ | Move to feature-sliced structure. Add route lazy loading. Pinia becomes mandatory. |

### Scaling Priorities

1. **First bottleneck:** Shared state across views. Fix by adding Pinia — composable state is currently per-instance and doesn't persist navigation.
2. **Second bottleneck:** Growing `api/` file. Fix by splitting into one file per domain area.

## Anti-Patterns

### Anti-Pattern 1: Fetch calls inside components

**What people do:** Call `fetch()` directly inside `<script setup>` of a view component.
**Why it's wrong:** Mixes transport logic with rendering logic. Loading/error state scattered. Impossible to reuse. Kills testability.
**Do this instead:** Always go through a composable that owns the state lifecycle. Components call `.load()` and bind to refs.

### Anti-Pattern 2: Treating 422 as a generic error

**What people do:** Surface 422 responses with the same "network error" message as connection failures.
**Why it's wrong:** This API uses 422 to return meaningful Hungarian domain messages. Hiding them behind a generic message loses information the user needs.
**Do this instead:** Discriminate by `status === 422` in the composable catch block. Render domain errors with `<ScoreError>`, network failures with `<ErrorMessage>`.

### Anti-Pattern 3: Importing `import.meta.env.VITE_API_BASE_URL` across the codebase

**What people do:** Reference the env var in every file that makes a fetch call.
**Why it's wrong:** Scatters configuration. Changes require touching multiple files.
**Do this instead:** Read `VITE_API_BASE_URL` exactly once in `api/http.ts`. All other API files import from `http.ts`.

### Anti-Pattern 4: Keeping types inside component files

**What people do:** Define `interface Applicant { ... }` inside the `.vue` file that uses it.
**Why it's wrong:** API response shapes are shared between the API layer, composables, and components. Local type definitions force duplication or imports from component files.
**Do this instead:** All API-derived types live in `types/api.ts`. Components import prop types from there.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| Laravel API (`/server`) | `fetch()` via `api/http.ts` wrapper, JSON, no auth | Base URL from `VITE_API_BASE_URL` env var. CORS must be enabled server-side. |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| `api/` → `composables/` | Composable imports and calls api functions directly | No intermediate bus or event system needed |
| `composables/` → `views/` | View destructures refs returned by composable | `{ score, domainError, networkError, loading, load }` |
| `views/` → `components/` | Props only, no emit back up except navigation | Components are fully presentational |
| `router/` → `views/` | Named routes, `route.params.id` in ScoreDetailView | No route-level guards needed for this app |

## TypeScript Types Reference

```typescript
// types/api.ts

export interface Program {
  university: string
  faculty: string
  name: string
}

export interface Applicant {
  id: string
  program: Program
}

export interface ScoreResult {
  osszpontszam: number
  alappont: number
  tobbletpont: number
}

// Shape thrown by api/http.ts on non-OK responses
export interface ApiError {
  status: number
  error: string
}

// Wrapper around paginated/enveloped responses
export interface ApiListResponse<T> {
  data: T[]
}

export interface ApiSingleResponse<T> {
  data: T
}
```

## Build Order (Dependency Sequence)

Build in this order — each step has all dependencies satisfied by previous steps:

1. **`types/api.ts`** — No dependencies. Everything else imports from here.
2. **`api/http.ts`** — Only depends on `VITE_API_BASE_URL` env var.
3. **`api/applicants.ts`** — Depends on `http.ts` and `types/api.ts`.
4. **`composables/useApplicants.ts`** — Depends on `api/applicants.ts` and `types/api.ts`.
5. **`composables/useApplicantScore.ts`** — Depends on `api/applicants.ts` and `types/api.ts`.
6. **`router/index.ts`** — Depends on view paths (forward references are fine in router config).
7. **`components/ui/`** (`LoadingSpinner`, `ErrorMessage`) — No dependencies.
8. **`components/applicants/ApplicantCard.vue`** — Depends on `Applicant` type.
9. **`components/score/ScoreBreakdown.vue`** — Depends on `ScoreResult` type.
10. **`components/score/ScoreError.vue`** — Depends on error string (plain prop, no type import needed).
11. **`views/ApplicantListView.vue`** — Depends on composable + ApplicantCard + ui components.
12. **`views/ScoreDetailView.vue`** — Depends on composable + ScoreBreakdown + ScoreError + ui components.
13. **`App.vue`** — Depends on router.
14. **`main.ts`** — Wires everything together.

## Sources

- [Vue 3 Composables — Official Guide](https://vuejs.org/guide/reusability/composables.html) — HIGH confidence
- [Vue Router Composition API](https://router.vuejs.org/guide/advanced/composition-api.html) — HIGH confidence
- [Managing API Layers in Vue.js with TypeScript](https://dev.to/blindkai/managing-api-layers-in-vue-js-with-typescript-hno) — MEDIUM confidence
- [Vue 3 + TypeScript Best Practices 2025](https://eastondev.com/blog/en/posts/dev/20251124-vue3-typescript-best-practices/) — MEDIUM confidence
- [Vue FAQ — Project Structure](https://vue-faq.org/en/development/project-structure.html) — MEDIUM confidence

---
*Architecture research for: Hungarian Admission Score Calculator — Vue 3 SPA*
*Researched: 2026-02-28*
