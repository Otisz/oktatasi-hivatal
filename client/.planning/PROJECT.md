# Hungarian Admission Score Calculator — Vue Client

## What This Is

A Vue 3 single-page application that provides a user interface for the Hungarian university admission score calculator API. Users browse a list of pre-seeded applicants, select one, and view their calculated admission score breakdown — or a styled Hungarian error message explaining why the score cannot be calculated.

## Core Value

A clean, responsive UI that lets users quickly view any applicant's admission score breakdown without needing to interact with the API directly.

## Requirements

### Validated

- ✓ Applicant list view consuming GET /api/v1/applicants — v1.1
- ✓ Score view consuming GET /api/v1/applicants/{id}/score — v1.1
- ✓ Error state display for 422 responses — v1.1
- ✓ Responsive Tailwind CSS layout — v1.1
- ✓ TypeScript types matching API response shapes — v1.1
- ✓ TanStack Query for data fetching with Axios HTTP client — v1.1
- ✓ Biome for linting and formatting — v1.1

### Active

(None — planning next milestone)

### Out of Scope

- Authentication / authorization — API is public, no auth needed
- CRUD operations — API is read-only, data is seeded
- Internationalisation — UI follows API's Hungarian-language domain errors
- Server-side rendering — SPA is sufficient for this use case
- State management library (Pinia) — TanStack Query handles server state, app state is minimal
- ESLint/Prettier — replaced by Biome
- Client-side search/filter — small seeded dataset, not needed until list grows
- Pagination — API returns all applicants in one call, dataset is small
- Real-time polling / WebSocket — static seeded data, no live updates needed
- Offline / PWA support — data-display tool fully dependent on API

## Context

Shipped v1.1 MVP with 354 LOC (TypeScript/Vue/CSS).
Tech stack: Vue 3.5, Vite 7.3, TypeScript 5.8, Tailwind CSS v4, TanStack Query (Vue), Axios, Biome 2.4, Vue Router 5.
All 22 requirements validated. 3 minor tech debt items from audit (dead import, stale docs).

**API Base URL:** Configurable via environment variable (`VITE_API_BASE_URL`).

**API Endpoints:**

1. **GET /api/v1/applicants** → 200
```json
{
  "data": [
    {
      "id": "uuid-string",
      "program": {
        "university": "string",
        "faculty": "string",
        "name": "string"
      }
    }
  ]
}
```

2. **GET /api/v1/applicants/{applicant}/score** → 200 (success)
```json
{
  "data": {
    "osszpontszam": 85,
    "alappont": 60,
    "tobbletpont": 25
  }
}
```

3. **GET /api/v1/applicants/{applicant}/score** → 422 (error)
```json
{
  "error": "Hungarian error message describing why score calculation failed"
}
```

**Server project:** Sibling directory at `<git-root>/server/` — Laravel 12 REST API with SQLite, Pest 4 tests.

## Constraints

- **Tech stack**: Vue 3, Vite 7, TypeScript, Tailwind CSS v4
- **API style**: Composition API with `<script setup>` syntax
- **Structure**: Fully standalone project in `<git-root>/client/` with own `package.json`
- **Routing**: Vue Router 5 for applicant list and score views
- **HTTP client**: Axios wrapped by TanStack Query (Vue) for data fetching/caching
- **Linting/formatting**: Biome (replaces ESLint/Prettier)
- **No backend**: Pure SPA, all data comes from the sibling server API

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Standalone project (not Laravel Vite integration) | Client and server are independently deployable | ✓ Good — clean separation |
| Tailwind CSS over component library | Lightweight, full control over design | ✓ Good — 354 LOC with polished UI |
| Hungarian error messages displayed as-is | Matches API domain language, no translation layer needed | ✓ Good — amber card renders verbatim |
| TanStack Query over hand-rolled composables | Built-in caching, loading/error states, retry logic | ✓ Good — cached back-nav, no skeleton flash |
| Axios over native Fetch | Auto-throws on 4xx/5xx, cleaner 422 handling | ✓ Good — isAxiosError enables clean discrimination |
| Biome over ESLint/Prettier | Single tool for linting + formatting, faster | ✓ Good — zero violations throughout |
| isLoading over isPending for skeleton guard | Prevents skeleton flash on cached back-navigation | ✓ Good — smooth UX on back-nav |
| ScoreError discriminated union (domain/generic) | Exhaustive error handling in components | ✓ Good — clean v-else-if branching |
| Programme context from TanStack Query cache | No extra network call for data already loaded | ✓ Good — synchronous read from cache |
| 30-min staleTime for QueryClient | Seeded dataset is static, doesn't change | ✓ Good — minimal re-fetching |

---
*Last updated: 2026-02-28 after v1.1 milestone*
