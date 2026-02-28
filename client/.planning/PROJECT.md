# Hungarian Admission Score Calculator — Vue Client

## What This Is

A Vue 3 single-page application that provides a user interface for the Hungarian university admission score calculator API. Users browse a list of pre-seeded applicants, select one, and view their calculated admission score breakdown — or a styled error explaining why the score cannot be calculated.

## Core Value

A clean, responsive UI that lets users quickly view any applicant's admission score breakdown without needing to interact with the API directly.

## Current Milestone: v1.0 MVP

**Goal:** Deliver a working Vue 3 frontend that consumes both API endpoints and presents score results (or errors) in a polished, responsive layout.

**Target features:**
- Applicant list view with programme details
- Score calculation result view (base points, bonus points, total)
- Styled error display for validation failures (Hungarian text from API)
- Responsive layout with Tailwind CSS

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Applicant list view consuming GET /api/v1/applicants
- [ ] Score view consuming GET /api/v1/applicants/{id}/score
- [ ] Error state display for 422 responses
- [ ] Responsive Tailwind CSS layout
- [ ] TypeScript types matching API response shapes

### Out of Scope

- Authentication / authorization — API is public, no auth needed
- CRUD operations — API is read-only, data is seeded
- Internationalisation — UI follows API's Hungarian-language domain errors
- Server-side rendering — SPA is sufficient for this use case
- State management library (Pinia) — app state is minimal (current view + API response)
- Unit/E2E testing — deferred to v1.1

## Context

**API Base URL:** Configurable via environment variable (e.g., `VITE_API_BASE_URL`).

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

**Possible 422 error messages (all Hungarian):**
- Failed exam (below 20%): `"nem lehetséges a pontszámítás a {subject} tárgyból elért 20% alatti eredmény miatt"`
- Missing global mandatory subject: `"nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt"`
- Missing programme mandatory subject: `"nem lehetséges a pontszámítás a {subject} tárgy hiánya miatt"`
- Wrong level for mandatory subject: `"nem lehetséges a pontszámítás a {subject} tárgy szintje miatt"`
- Missing elective subject: `"nem lehetséges a pontszámítás az elektív tárgy hiánya miatt"`
- Unknown programme: `"nem lehetséges a pontszámítás az ismeretlen program miatt"`

**Server project:** Sibling directory at `<git-root>/server/` — Laravel 12 REST API with SQLite, Pest 4 tests.

## Constraints

- **Tech stack**: Vue 3, Vite, TypeScript, Tailwind CSS
- **API style**: Composition API with `<script setup>` syntax
- **Structure**: Fully standalone project in `<git-root>/client/` with own `package.json`
- **Routing**: Vue Router for applicant list and score views
- **HTTP client**: Fetch API or Axios for API calls
- **No backend**: Pure SPA, all data comes from the sibling server API

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Standalone project (not Laravel Vite integration) | Client and server are independently deployable | — Pending |
| Tailwind CSS over component library | Lightweight, full control over design | — Pending |
| Hungarian error messages displayed as-is | Matches API domain language, no translation layer needed | — Pending |

---
*Last updated: 2026-02-28 after milestone v1.0 started*
