# Requirements: Hungarian Admission Score Calculator — Vue Client

**Defined:** 2026-02-28
**Core Value:** A clean, responsive UI that lets users quickly view any applicant's admission score breakdown.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Infrastructure

- [x] **INFRA-01**: Project scaffolded with Vue 3 + Vite + TypeScript + Tailwind CSS v4
- [x] **INFRA-02**: Biome configured for linting and formatting (replaces ESLint/Prettier)
- [ ] **INFRA-03**: API base URL configurable via `VITE_API_BASE_URL` environment variable
- [ ] **INFRA-04**: TypeScript interfaces defined for all API response shapes (`Applicant`, `Program`, `ScoreResult`, `ApiError`)
- [ ] **INFRA-05**: Axios HTTP client instance configured with base URL from environment

### Data Fetching

- [ ] **DATA-01**: TanStack Query (Vue) configured as the data fetching/caching layer
- [ ] **DATA-02**: Query for listing applicants consuming GET /api/v1/applicants
- [ ] **DATA-03**: Query for fetching applicant score consuming GET /api/v1/applicants/{id}/score
- [ ] **DATA-04**: 422 domain errors distinguished from network errors in score query

### Navigation

- [ ] **NAV-01**: Vue Router with history mode and two named routes (`/applicants`, `/applicants/:id`)
- [ ] **NAV-02**: Back navigation from score detail view to applicant list
- [ ] **NAV-03**: Default route (`/`) redirects to `/applicants`

### Applicant List

- [ ] **LIST-01**: User can view all applicants with programme info (university, faculty, name)
- [ ] **LIST-02**: User can click an applicant row to navigate to their score view
- [ ] **LIST-03**: Loading skeleton displayed while applicants are being fetched
- [ ] **LIST-04**: Empty state displayed when no applicants exist

### Score Display

- [ ] **SCORE-01**: User can view score breakdown (total, base points, bonus points)
- [ ] **SCORE-02**: Total score (`osszpontszam`) displayed prominently above breakdown
- [ ] **SCORE-03**: Styled error card displaying verbatim Hungarian error message on 422
- [ ] **SCORE-04**: Loading state displayed while score is being fetched

### Layout

- [ ] **LAYOUT-01**: Responsive Tailwind CSS layout (single-column mobile, comfortable desktop)
- [ ] **LAYOUT-02**: Consistent page structure with header and content areas

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Polish

- **POLISH-01**: Skeleton screens matching exact list/detail DOM structure
- **POLISH-02**: Meaningful error categorisation by Hungarian message pattern (icon/colour per error type)

### Accessibility

- **A11Y-01**: ARIA labels on interactive elements
- **A11Y-02**: Keyboard navigation support for applicant list

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Authentication / authorization | API is public, no auth needed |
| CRUD operations | API is read-only, data is seeded |
| Client-side search/filter | Small seeded dataset, not needed until list grows |
| Pagination | API returns all applicants in one call, dataset is small |
| Translation layer for error messages | API is the authoritative Hungarian-language source |
| State management library (Pinia) | TanStack Query handles server state; app state is minimal |
| Real-time polling / WebSocket | Static seeded data, no live updates needed |
| Offline / PWA support | Data-display tool fully dependent on API |
| Server-side rendering | SPA is sufficient for this use case |
| Unit/E2E testing | Deferred to v1.1 |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| INFRA-01 | Phase 1 | Complete (01-01) |
| INFRA-02 | Phase 1 | Complete (01-01) |
| INFRA-03 | Phase 1 | Pending |
| INFRA-04 | Phase 1 | Pending |
| INFRA-05 | Phase 1 | Pending |
| DATA-01 | Phase 1 | Pending |
| DATA-02 | Phase 2 | Pending |
| DATA-03 | Phase 2 | Pending |
| DATA-04 | Phase 2 | Pending |
| NAV-01 | Phase 2 | Pending |
| NAV-02 | Phase 4 | Pending |
| NAV-03 | Phase 2 | Pending |
| LIST-01 | Phase 3 | Pending |
| LIST-02 | Phase 3 | Pending |
| LIST-03 | Phase 3 | Pending |
| LIST-04 | Phase 3 | Pending |
| SCORE-01 | Phase 4 | Pending |
| SCORE-02 | Phase 4 | Pending |
| SCORE-03 | Phase 4 | Pending |
| SCORE-04 | Phase 4 | Pending |
| LAYOUT-01 | Phase 3 | Pending |
| LAYOUT-02 | Phase 3 | Pending |

**Coverage:**
- v1 requirements: 22 total
- Mapped to phases: 22
- Unmapped: 0

---
*Requirements defined: 2026-02-28*
*Last updated: 2026-02-28 after plan 01-01 execution — INFRA-01, INFRA-02 complete*
