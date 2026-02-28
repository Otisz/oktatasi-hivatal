# Phase 2: Routing and Data Layer - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Wire up Vue Router and TanStack Query data layer so routes are reachable in the browser and typed applicant/score data flows (including 422 error discrimination) before any real view renders them. Route components are structural placeholders — real views come in Phase 3 and 4.

</domain>

<decisions>
## Implementation Decisions

### 422 Error Handling
- Two error categories: domain errors (422 with verbatim Hungarian message) vs generic errors (network/server — generic Hungarian message)
- Network/server errors show a Hungarian error message with a retry button ("Hiba tortent. Probalja ujra." or similar)
- Domain 422 errors display the API's Hungarian message as-is — no translation or rewriting
- Retry button invalidates the cached error and refetches fresh

### Unknown Route Behavior
- All unknown URLs redirect silently to `/applicants` — no 404 page
- Invalid applicant IDs (e.g., `/applicants/not-a-uuid`) are NOT validated client-side; the API response handles the error and Phase 4's error display shows it
- HTML5 history mode for clean URLs (`/applicants`, not `/#/applicants`)
- Subtle top progress bar visible during route transitions (like YouTube/GitHub style)

### Data Freshness
- Long staleTime (30min+) since API data is seeded and static
- No refetch on back-navigation from detail to list — show cached list instantly
- Score results cached per applicant ID — revisiting the same applicant shows cached result
- Retry action invalidates cache and refetches (clean attempt)

### Placeholder Views
- Structural shells with layout wrapper, heading, and content area — Phase 3/4 fills in the content
- Persistent app header across all views: "Oktatasi Hivatal" as main title, "Felveteli pontszamolo" as subtitle
- Detail view placeholder includes "← Vissza" back link to `/applicants` (satisfies NAV-02 early)

### Claude's Discretion
- Technical pattern for 422 discrimination (interceptor vs per-query vs custom error class)
- Query composable structure (naming, file organization)
- Top progress bar implementation approach
- Exact header styling and layout spacing
- Placeholder view internal structure and Tailwind classes

</decisions>

<specifics>
## Specific Ideas

- All user-facing text in Hungarian — both domain error messages (from API) and generic error messages
- Header uses dual-line format: institutional name above, functional descriptor below
- Progress bar should be subtle — thin line at top of viewport, not a blocking overlay

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `src/lib/http.ts`: Axios instance with `VITE_API_BASE_URL` — ready for query functions to import
- `src/lib/query.ts`: QueryClient with 5min staleTime — needs staleTime increase to 30min+
- `src/types/api.ts`: All interfaces defined (`Applicant`, `Program`, `ScoreResult`, `ApiError`, `ApiResponse<T>`)

### Established Patterns
- Axios + TanStack Query stack already wired in `main.ts` via `VueQueryPlugin`
- TypeScript strict mode with typed interfaces for all API shapes
- Tailwind CSS v4 for styling (via `src/assets/main.css`)
- Biome for linting/formatting

### Integration Points
- `src/main.ts`: Needs Vue Router plugin registration alongside existing VueQueryPlugin
- `src/App.vue`: Currently a static placeholder — needs `<router-view>` and persistent header layout
- `src/views/`: Empty directory ready for route components
- `src/composables/`: Empty directory ready for query composables

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 02-routing-and-data-layer*
*Context gathered: 2026-02-28*
