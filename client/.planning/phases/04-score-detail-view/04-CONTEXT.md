# Phase 4: Score Detail View - Context

**Gathered:** 2026-02-28
**Status:** Ready for planning

<domain>
## Phase Boundary

Display an applicant's full score breakdown (total, base points, bonus points) or a styled Hungarian error message when the API returns a 422, and provide back navigation to the applicant list. Completing the end-to-end user flow from list → detail → back.

</domain>

<decisions>
## Implementation Decisions

### Score presentation
- Large hero number for total score (`összpontszám`) — prominent focal point at the top
- Base points (`alappont`) and bonus points (`többletpont`) shown as two side-by-side cards below the hero
- All labels in Hungarian: "Összpontszám", "Alappont", "Többletpont"

### Error card styling
- 422 domain errors displayed in an amber/warning-colored card (distinct from generic errors)
- Heading "Pontozás nem lehetséges" above the verbatim Hungarian error message from the API
- Generic (non-422) errors reuse the gray icon pattern from ApplicantsView but add a "Próbálja újra" (Try again) retry button
- Back link ("← Vissza") always visible above all states (loading, success, domain error, generic error)

### Loading state
- Skeleton placeholders matching the score layout (hero number area + two breakdown cards) using `animate-pulse` — consistent with ApplicantsView
- Back link and page heading shown immediately; skeleton only for the score content area
- Skeleton appears while `useApplicantScore` is loading

### Applicant context
- Programme info (university, faculty, programme name) displayed above the score card as a header area
- Clear hierarchy: context first (programme), then data (score)

### Claude's Discretion
- Visual emphasis on the hero score (color accent, border treatment, or plain)
- Data source for programme info (TanStack Query cache from list vs separate query)
- Page title treatment (generic "Pontozás" vs dynamic with programme name)
- Progress bar integration (whether to use `useProgress` alongside skeleton)

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches within the decisions above.

</specifics>

<code_context>
## Existing Code Insights

### Reusable Assets
- `useApplicantScore` composable: Ready with 422 discrimination (`ScoreError` with `kind: 'domain'` and `kind: 'generic'`)
- `ApplicantDetailView.vue`: Placeholder exists with back link and route param — Phase 4 fills the score content
- `useApplicants` composable: Returns `Applicant[]` with programme info — potential cache source for detail view
- `useProgress` composable: Available for top progress bar integration

### Established Patterns
- Container: `max-w-4xl mx-auto px-4 py-6`
- Cards: `bg-white border border-gray-200 rounded-lg p-4`
- Loading skeleton: `animate-pulse` with gray placeholder rectangles
- Error state: SVG icon centered + Hungarian text messages
- All UI text in Hungarian

### Integration Points
- `ApplicantDetailView.vue` is the target view (route `/applicants/:id` already configured)
- Back link to `/applicants` already wired in placeholder
- `useApplicantScore` takes route param `id` as `MaybeRefOrGetter<string>`

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 04-score-detail-view*
*Context gathered: 2026-02-28*
