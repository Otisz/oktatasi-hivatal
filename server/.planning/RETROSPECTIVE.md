# Project Retrospective

*A living document updated after each milestone. Lessons feed forward into future planning.*

## Milestone: v1.0 — MVP

**Shipped:** 2026-02-28
**Phases:** 8 | **Plans:** 13 | **Sessions:** ~6

### What Was Built
- Complete domain model: 3 enums, 6 exception types, 3 Value Objects with embedded validation
- Five-table database schema with UUID PKs, enum casts, typed Eloquent relationships, factories
- Seed data: 2 programmes (ELTE IK, PPKE BTK) and 4 acceptance-test applicants matching homework specification
- Strategy pattern for programme requirements (DatabaseProgramRequirements + ProgramRegistry)
- Scoring engine: BasePointCalculator + BonusPointCalculator with 5-step ordered validation chain
- REST API: 2 versioned endpoints with AdmissionException → 422 JSON rendering
- Test suite: 73 tests (55 unit + 18 feature), 105 assertions, all passing

### What Worked
- Bottom-up TDD build order eliminated integration surprises — each phase was testable in isolation before wiring to the next
- Interface extraction (Phase 7) enabled full mock coverage of AdmissionScoringService without touching the database
- Enum helper methods (globallyMandatory(), points()) co-located business rules at the type level — clean and discoverable
- Phase verification + UAT loop caught the missing AppServiceProvider bindings (Phase 7.2 gap closure) before reaching the API layer
- Milestone audit (45/45 requirements) confirmed zero gaps before archival

### What Was Inefficient
- SUMMARY.md files lacked `requirements_completed` frontmatter, reducing 3-source cross-check to 2 sources during audit
- Traceability table in REQUIREMENTS.md had 3 stale "Pending" statuses (DOM-01/02/03) despite requirements being satisfied — manual table sync overhead
- No feature test for GET /api/v1/applicants index endpoint (API-01 verified by code inspection only)
- PPKE BTK programme seeded but never exercised in acceptance tests — only ELTE IK scoring path tested end-to-end

### Patterns Established
- `final readonly class` for all Value Objects — Pint final_class rule enforced consistently
- Backed string enums with Hungarian values as the type-safe vocabulary layer across all domain boundaries
- Interface-per-service pattern for DI (ProgramRegistryInterface, BasePointCalculatorInterface, etc.) — enables Mockery in Pest
- `uses(RefreshDatabase::class)` per-file opt-in rather than global Pest.php declaration
- Closure-based enum filtering on Eloquent collections to avoid loose equality edge cases

### Key Lessons
1. Extract interfaces early when using `final` classes — Mockery cannot mock final classes, and retrofitting interfaces late (Phase 7.2) adds an unplanned plan
2. Keep traceability tables in sync with checkboxes — the dual status representation (checkbox + table Status column) creates staleness risk
3. Seed more than the minimum test cases — PPKE BTK programme exists but has zero acceptance coverage; one extra applicant would have validated a second programme path at low cost
4. VO constructor validation is the cheapest place to catch bad data — ExamResult's <20% check fires before any explicit validation step, eliminating an entire class of bugs

### Cost Observations
- Model mix: ~80% opus, ~15% sonnet, ~5% haiku (balanced profile)
- Sessions: ~6 across 4 days
- Notable: Average plan execution was 2 minutes; total execution time ~0.4 hours for 13 plans

---

## Cross-Milestone Trends

### Process Evolution

| Milestone | Sessions | Phases | Key Change |
|-----------|----------|--------|------------|
| v1.0 | ~6 | 8 | Initial project — established TDD bottom-up build order |

### Cumulative Quality

| Milestone | Tests | Assertions | Requirements Satisfied |
|-----------|-------|------------|----------------------|
| v1.0 | 73 | 105 | 45/45 |

### Top Lessons (Verified Across Milestones)

1. Bottom-up TDD with explicit dependency ordering eliminates integration surprises
2. Interface extraction for final classes must be planned upfront, not retrofitted
