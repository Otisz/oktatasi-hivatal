# Milestones

## v1.0 MVP (Shipped: 2026-02-28)

**Phases:** 1-8 | **Plans:** 13 | **LOC:** 2,525 PHP | **Tests:** 73 (55 unit + 18 feature)
**Timeline:** 4 days (2026-02-25 → 2026-02-28) | **Commits:** 95
**Audit:** PASSED (45/45 requirements, 8/8 phases, 10/10 integration, 2/2 E2E flows)

**Delivered:** A read-only Laravel 12 REST API that calculates Hungarian university admission scores with a 5-step validation chain, producing exact expected results for all 4 acceptance test cases.

**Key accomplishments:**
1. Type-safe domain model: 3 backed string enums, 6 exception classes, 3 immutable Value Objects
2. Five-table database schema with UUID PKs, enum casts, typed Eloquent relationships, and factories
3. Comprehensive seed data: 2 programmes and 4 acceptance-test applicants matching homework specification
4. Strategy pattern for programme requirements with DatabaseProgramRequirements + ProgramRegistry
5. Scoring engine with BasePointCalculator, BonusPointCalculator, and 5-step ordered validation chain
6. REST API: 2 endpoints with domain exception rendering (422 JSON) and full acceptance test coverage

**Git range:** bd03f78 (Initial commit) → 188785b (milestone audit)

**Archives:**
- `.planning/milestones/v1.0-ROADMAP.md`
- `.planning/milestones/v1.0-REQUIREMENTS.md`
- `.planning/milestones/v1.0-MILESTONE-AUDIT.md`

---

