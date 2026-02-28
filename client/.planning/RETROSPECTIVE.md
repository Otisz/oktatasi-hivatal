# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.1 — MVP

**Shipped:** 2026-02-28
**Phases:** 4 | **Plans:** 6 | **Sessions:** ~5

### What Was Built
- Vue 3 SPA with Vite 7, TypeScript, Tailwind CSS v4, Biome linting
- TanStack Query data layer with Axios, 422 domain error discrimination
- Card-based applicant list with loading skeleton, empty state, error state
- Score detail view with hero total, breakdown cards, amber domain error card
- Full end-to-end flow: list → detail → back navigation (cache-aware, no flash)

### What Worked
- Phase-per-concern separation kept each plan small and focused (1–2 tasks each)
- TanStack Query `isLoading` vs `isPending` insight caught early in Phase 3 and carried forward to Phase 4 — prevented skeleton flash on cached back-navigation
- Biome as single linter/formatter — zero config friction, caught issues instantly
- Parallel agent execution for Phase 2 plans (router + composables) saved time
- Browser verification checkpoints caught the data envelope mismatch (commit 77f55fc)

### What Was Inefficient
- ROADMAP.md Phase 2 checkbox stayed stale throughout execution — only caught by milestone audit
- 02-02-SUMMARY.md documented "no envelope" but the fix in 77f55fc wasn't reflected back — summaries should be updated when post-plan fixes land
- `useRouter` imported but unused in ApplicantsView.vue — leftover from switching to declarative RouterLink navigation

### Patterns Established
- `isLoading` (not `isPending`) for skeleton guards with TanStack Query v5
- Four-branch v-if template pattern: loading → error → empty → data
- Single Axios instance from `@/lib/http` — never import axios directly in feature code
- ScoreError discriminated union (kind: domain | generic) for exhaustive error handling
- Programme context from TanStack Query cache (`getQueryData`) for synchronous reads

### Key Lessons
1. Browser verification checkpoints are essential — the data envelope mismatch would have been invisible from static analysis alone
2. Summary files should be treated as living documents — post-plan fixes need to be reflected back
3. Biome 2.x schema changes between minor versions — always verify against installed version, not documentation
4. `npm create vue@latest` requires interactive TTY — manual scaffolding is the reliable path for automated workflows

### Cost Observations
- Model mix: ~70% sonnet, ~20% haiku, ~10% opus
- Sessions: ~5 sessions across 4 days
- Notable: Phases 1-2 executed very fast (1-7 min per plan); Phases 3-4 slower (10-15 min) due to browser verification checkpoints

---

## Cross-Milestone Trends

### Process Evolution

| Milestone | Sessions | Phases | Key Change |
|-----------|----------|--------|------------|
| v1.1 MVP | ~5 | 4 | First milestone — established baseline patterns |

### Cumulative Quality

| Milestone | Audit Score | Requirements | Tech Debt Items |
|-----------|-------------|--------------|-----------------|
| v1.1 MVP | 22/22 reqs, 6/6 flows | 100% satisfied | 3 minor |

### Top Lessons (Verified Across Milestones)

1. Browser verification catches what static analysis cannot — always include human-verify checkpoints for UI work
2. isLoading vs isPending distinction in TanStack Query v5 is critical for cache-aware UIs
