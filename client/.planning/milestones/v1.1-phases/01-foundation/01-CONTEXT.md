# Phase 1: Foundation - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Scaffold a Vue 3 + Vite + TypeScript + Tailwind CSS v4 + Biome project with typed API contracts, TanStack Query, and Axios. Everything compiles and runs with zero errors before any feature code is written. No views, no routing, no data fetching — just the verified skeleton.

</domain>

<decisions>
## Implementation Decisions

### Package manager
- npm (ships with Node, no extra install)
- Semver ranges (default ^ prefixes) for dependency versions
- `.nvmrc` file pinning Node 22 LTS

### Project structure
- Standard Vue convention: `src/components/`, `src/views/`, `src/composables/`, `src/types/`, `src/lib/`
- API layer (Axios instance, TanStack Query client) lives in `src/lib/`
- Single-file components with `<script setup lang="ts">`
- Path alias: `@` maps to `src/` (configured in both tsconfig and Vite)

### Claude's Discretion
- Biome configuration and rule strictness
- Initial app shell content (what App.vue renders)
- Tailwind CSS v4 configuration approach
- Exact tsconfig compiler options
- .env file structure beyond VITE_API_BASE_URL

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- None — greenfield project, client directory is empty

### Established Patterns
- Server uses Laravel API Resources with JSON:API-style `{ data: ... }` wrapping
- API versioned at `/api/v1/`

### Integration Points
- `GET /api/v1/applicants` → `{ data: [{ id: string, program: { university, faculty, name } }] }`
- `GET /api/v1/applicants/{id}/score` → `{ data: { osszpontszam: int, alappont: int, tobbletpont: int } }`
- 422 responses on score endpoint for applicants whose score can't be calculated
- TypeScript interfaces must match these exact shapes: `Applicant`, `Program`, `ScoreResult`, `ApiError`

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 01-foundation*
*Context gathered: 2026-02-28*
