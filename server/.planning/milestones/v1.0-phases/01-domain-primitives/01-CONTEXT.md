# Phase 1: Domain Primitives - Context

**Gathered:** 2026-02-25
**Status:** Ready for planning

<domain>
## Phase Boundary

Type-safe enums (SubjectName, ExamLevel, LanguageCertificateType) and an abstract exception hierarchy (AdmissionException + 6 subclasses). Zero external dependencies — this is the foundation every subsequent layer imports.

</domain>

<decisions>
## Implementation Decisions

### Enum naming convention
- Enum **case names are English**: SubjectName::Mathematics, ExamLevel::Intermediate, ExamLevel::Advanced
- Enum **backing values are accented Hungarian**: `'magyar nyelv és irodalom'`, `'biológia'`, `'kémia'`, `'közép'`, `'emelt'`
- LanguageCertificateType uses descriptive English names: `UpperIntermediate` (B2, 28pts), `Advanced` (C1, 40pts)
- ExamLevel uses `Intermediate` (közép) and `Advanced` (emelt)

### Subject enum helpers
- Add `SubjectName::globallyMandatory()` static method returning `[HungarianLanguageAndLiterature, History, Mathematics]`
- Add `SubjectName::isLanguage()` instance method for identifying language subjects
- Helpers co-locate business rules with the enum for downstream validation phases

### Exception design
- `AdmissionException` is **abstract** — cannot be thrown directly, only subclasses
- All 6 subclasses carry **rich context data** as typed properties (e.g., FailedExamException stores subject name + percentage)
- Error messages are **dynamic Hungarian strings** built from context: e.g., "nem lehetséges a pontszámítás a {subject} tárgyból elért 20% alatti eredmény miatt"
- Exceptions are **pure domain objects** — no HTTP status code awareness; API layer (Phase 8) maps AdmissionException → 422

### Code organization
- Enums in **App\Enums** flat: SubjectName.php, ExamLevel.php, LanguageCertificateType.php
- Exceptions in **App\Exceptions** flat: AdmissionException.php + 6 subclass files (FailedExamException.php, MissingGlobalMandatorySubjectException.php, etc.)
- No subdirectories for either enums or exceptions

### Claude's Discretion
- Exact English case names for the 13 subjects (Full English like HungarianLanguageAndLiterature vs shorter forms)
- Value Objects namespace for Phase 2 (App\ValueObjects vs App\DTOs)
- Any additional convenience methods on enums beyond the requested helpers

</decisions>

<specifics>
## Specific Ideas

- The homework input file (`homework_input.php`) defines the exact Hungarian strings to use as backing values — these are authoritative
- PRD Section 11 lists all 13 subjects, 2 exam levels, and 2 certificate types with exact values
- Error messages from acceptance test cases (PRD Section 12) define the expected Hungarian message format — exceptions must produce messages matching these patterns

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 01-domain-primitives*
*Context gathered: 2026-02-25*
