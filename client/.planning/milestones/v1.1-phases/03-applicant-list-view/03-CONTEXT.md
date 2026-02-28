# Phase 3: Applicant List View - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Display all applicants in a scrollable list with loading skeleton, empty state, click-to-navigate, and responsive layout. This is the app's entry point — the first view users see after landing on `/applicants`. Creating, editing, filtering, or searching applicants are out of scope.

</domain>

<decisions>
## Implementation Decisions

### List item design
- Card-based layout — one card per applicant, stacked vertically
- Programme name (`program.name`) is the most prominent text (largest/boldest)
- University and faculty displayed smaller above or below the programme name
- Cards have subtle border (gray-200), white background — no shadow
- Right-aligned chevron (`›`) always visible to signal navigation
- Hover state: background color change to reinforce clickability
- Clicking anywhere on the card navigates to `/applicants/:id`

### Empty state
- Icon + text centered on the page
- Subtle icon (list or users icon) above the message
- Hungarian text: "Nincsenek jelentkezők" with a secondary line explaining no applicants are in the system
- Consistent with the app's Hungarian language (header, API errors)

### Loading skeleton
- 3 skeleton cards shown while `useApplicants()` is fetching
- Skeleton cards match the real card layout — 3 animated bars representing programme name, university, and faculty
- Pulsing animation (Tailwind `animate-pulse`)

### Language
- All UI text in Hungarian — matches header ("Oktatási Hivatal", "Felvételi pontszámoló") and API error messages

### Claude's Discretion
- Exact spacing, gap, and typography sizes within cards
- Hover effect color and transition timing
- Skeleton bar widths and exact positioning
- Icon choice for empty state
- Page-level layout adjustments (padding, margins beyond existing `max-w-4xl` container)
- Row interaction micro-animations
- Error state for failed API fetch (network error, not empty response)

</decisions>

<specifics>
## Specific Ideas

- Cards should feel clean and utilitarian — this is a government score calculator, not a social app
- The bordered card style should match the existing `border-b border-gray-200` pattern from the App.vue header
- No specific external reference provided — standard clean card list is the target

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `useApplicants()` composable: Ready-to-use TanStack Query hook returning typed `Applicant[]`
- `Applicant` type: `{ id: string, program: { university: string, faculty: string, name: string } }`
- Router: Named route `applicant-detail` with `:id` param already configured

### Established Patterns
- Tailwind CSS v4 for all styling (no component library)
- Container: `max-w-4xl mx-auto px-4` used in both App.vue header and ApplicantsView placeholder
- Color scheme: `bg-gray-50` page, `bg-white` surfaces, `border-gray-200` borders, `text-gray-900` primary text, `text-gray-500` secondary text, `bg-blue-500` accent
- TanStack Query for data fetching with `isLoading`/`isError`/`data` states
- `isNavigating` ref + progress bar pattern for route transitions

### Integration Points
- `ApplicantsView.vue`: Placeholder view at `src/views/ApplicantsView.vue` — list content replaces the comment
- Router: `router-link` or `router.push` to `{ name: 'applicant-detail', params: { id } }` for navigation
- App shell: `<main><RouterView /></main>` — views render inside main with no additional wrapper

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-applicant-list-view*
*Context gathered: 2026-02-28*
