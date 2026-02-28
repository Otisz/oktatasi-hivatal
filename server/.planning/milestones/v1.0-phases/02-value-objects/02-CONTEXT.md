# Phase 2: Value Objects - Context

**Gathered:** 2026-02-25
**Status:** Ready for planning

<domain>
## Phase Boundary

Three immutable Value Objects (ExamResult, LanguageCertificate, Score) with embedded validation and full Pest unit test coverage. Depends on Phase 1 enums and exceptions. No database, no service layer, no API — pure domain objects.

</domain>

<decisions>
## Implementation Decisions

### ExamResult data shape
- ExamResult carries SubjectName, ExamLevel, and percentage (int) — not a pure score container
- All three stored as public readonly properties via constructor promotion
- Computed methods: points() returns percentage value, isAdvancedLevel() returns true only for ExamLevel::Emelt
- Single all-in-one constructor: new ExamResult(SubjectName, ExamLevel, int $percentage)

### LanguageCertificate data shape
- Same pattern as ExamResult: constructor with all fields
- new LanguageCertificate(LanguageCertificateType $type, string $language)
- Public $language property for direct access, points() as computed method (delegates to $type->points())

### Score data shape
- Stores basePoints and bonusPoints as int, both non-negative validated
- total() method returns basePoints + bonusPoints
- No cap enforcement — Score is a dumb container. Caps (400 base, 100 bonus) are the calculators' responsibility (Phase 6)

### Validation boundaries
- ExamResult validates range 0-100 first (throws InvalidArgumentException), then < 20% business rule (throws FailedExamException)
- Percentage is strictly int — no floats
- Score validates basePoints and bonusPoints are non-negative (throws InvalidArgumentException)

### VO construction style
- All VOs use PHP 8.2+ readonly class
- Public properties via constructor promotion (no private + getters)
- Simple data = public property, computed/derived = method
- No static factory methods — plain constructors only

### Test patterns
- Pest datasets for parametric boundary coverage
- Full boundary testing: 0, 19, 20, 100, -1, 101 for ExamResult percentage
- VO tests only test VO behavior — don't re-test Phase 1 enum logic
- Three test files: ExamResultTest, LanguageCertificateTest, ScoreTest

### Claude's Discretion
- Namespace/directory structure for VOs (under App\Domain or App\Domain\ValueObjects)
- Exact dataset organization in tests
- PHPDoc block content
- Error message wording for InvalidArgumentException

</decisions>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 02-value-objects*
*Context gathered: 2026-02-25*
