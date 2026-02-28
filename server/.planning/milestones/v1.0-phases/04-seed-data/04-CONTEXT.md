# Phase 4: Seed Data - Context

**Gathered:** 2026-02-25
**Status:** Ready for planning

<domain>
## Phase Boundary

Seed two programmes (ELTE IK Programtervezo informatikus, PPKE BTK Anglisztika) and four acceptance-test applicants with exact exam results and bonus points from the homework specification. Data must be seeded in correct FK order (programmes first, then applicants). All data values come from `homework_input.php`.

</domain>

<decisions>
## Implementation Decisions

### Applicant ID strategy
- Set explicit IDs for all entities: programmes (ELTE IK = 1, PPKE BTK = 2) and applicants (1-4)
- Use named constants at the top of each seeder class for FK references (e.g., `ELTE_IK_ID = 1`) — no magic numbers
- Each applicant block gets a PHPDoc comment documenting expected outcome (e.g., "Applicant 1: ELTE IK, expected 470 (370 base + 100 bonus)")

### Seeder idempotency
- Assume `migrate:fresh --seed` workflow — seeders just insert, no upsert logic
- No transaction wrapping needed — re-run from scratch if anything fails
- No WithoutModelEvents — models won't have observers
- Silent operation — no progress messages, dataset is small

### Enum usage in seeders
- Reference Phase 1 enums throughout: SubjectName, ExamLevel, LanguageCertificateType
- Pass enum instances directly to Eloquent (rely on model casts for serialization), not `->value`
- Eloquent models (Phase 3) must cast all enum columns to their PHP enum types

### New enums discovered
- **BonusCategory enum** — for the bonus points 'category' column (e.g., BonusCategory::Nyelvvizsga). Belongs in Phase 1 scope.
- **RequirementType enum** — for programme_subjects 'requirement_type' column (RequirementType::Mandatory, RequirementType::Elective). Belongs in Phase 1 scope.
- Both enums should be added to Phase 1 (Domain Primitives) since they are domain concepts, not Phase 4

### Claude's Discretion
- Internal seeder method organization (single `run()` vs helper methods)
- Whether to use Eloquent `create()` or `insert()` for bulk data
- ProgramSubject seeding approach (inline with programme or separate)

</decisions>

<specifics>
## Specific Ideas

- All four test applicants are for ELTE IK (no PPKE BTK test applicants exist in the spec)
- PPKE BTK Anglisztika programme still needs to be seeded with its subject requirements (angol emelt mandatory, 6 elective languages + tortenelem)
- Exact data values must match `homework_input.php` precisely — this is a homework exercise with specific expected outputs
- Applicant 1: 5 exams + 2 language certs → expected 470 (370 base + 100 bonus)
- Applicant 2: 6 exams (adds fizika 98%) + 2 language certs → expected 476 (376 base + 100 bonus)
- Applicant 3: 3 exams (missing magyar + tortenelem) + 2 certs → expected error (missing global mandatory)
- Applicant 4: 5 exams (magyar 15%) + 2 language certs → expected error (magyar < 20%)

</specifics>

<deferred>
## Deferred Ideas

- BonusCategory and RequirementType enums need to be added to Phase 1 (Domain Primitives) before this phase executes — note for roadmap update

</deferred>

---

*Phase: 04-seed-data*
*Context gathered: 2026-02-25*
