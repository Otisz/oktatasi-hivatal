# Milestones

## v1.1 MVP (Shipped: 2026-02-28)

**Phases:** 1–4 | **Plans:** 6 | **Tasks:** 12
**LOC:** 354 (TypeScript/Vue/CSS) | **Commits:** 38 (9 feat)
**Timeline:** 4 days (2026-02-25 → 2026-02-28)
**Git range:** `feat(01-01)` → `feat(04-01)`

**Delivered:** A working Vue 3 SPA that consumes both API endpoints and presents admission score results (or Hungarian error messages) in a responsive Tailwind CSS layout.

**Key accomplishments:**
1. Vue 3 + Vite + TypeScript + Tailwind CSS v4 project scaffolded with Biome linting, Axios HTTP client, and TanStack Query data layer
2. Vue Router with history mode — two named routes, root redirect, catch-all, and animated progress bar
3. TanStack Query composables with typed Applicant[] and ScoreResult returns, plus 422 domain error discrimination via ScoreError union
4. Card-based applicant list with animate-pulse loading skeleton, Hungarian empty/error states, and click-to-navigate
5. Score detail view with hero total score, base/bonus breakdown cards, amber 422 domain error card, and cache-sourced programme context
6. Full end-to-end user flow — applicant list → score detail → back navigation with cached data (no skeleton flash)

**Tech debt (from audit):**
- Dead `useRouter` import in ApplicantsView.vue
- Stale 02-02-SUMMARY.md documentation (envelope unwrap fix not reflected)
- ROADMAP.md Phase 2 checkbox was stale (fixed during archival)

**Requirements:** 22/22 satisfied (see `milestones/v1.1-REQUIREMENTS.md`)

---

