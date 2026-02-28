---
status: complete
phase: 03-database-schema-and-models
source: 03-01-SUMMARY.md, 03-02-SUMMARY.md
started: 2026-02-26T10:00:00Z
updated: 2026-02-26T10:06:00Z
---

## Current Test

[testing complete]

## Tests

### 1. Migrations run clean
expected: `php artisan migrate:fresh` completes without errors. All five domain tables created: programs, program_subjects, applicants, applicant_exam_results, applicant_bonus_points.
result: pass

### 2. Program with subjects via factory
expected: Creating a Program with 2 ProgramSubjects via factory succeeds. Both subjects are linked to the program and accessible via `$program->subjects`.
result: pass

### 3. Applicant with exam results and bonus points
expected: Creating an Applicant with exam results and bonus points via factory succeeds. Relationships `$applicant->examResults` and `$applicant->bonusPoints` return the correct related records.
result: pass

### 4. Enum casting returns enum instances
expected: Reading a ProgramSubject from DB returns `SubjectName` and `RequirementType` enum instances (not raw strings). E.g., `$ps->subject_name` is a `SubjectName` enum, `$ps->requirement_type` is a `RequirementType` enum.
result: pass

### 5. Cascade delete removes children
expected: Deleting a Program also deletes its ProgramSubjects and Applicants (and their exam results/bonus points). After `$program->delete()`, querying child tables returns zero rows for that program.
result: pass

### 6. Factory named states produce correct data
expected: `ApplicantExamResult::factory()->failingExam()->make()` produces percentage 0-19. `ApplicantExamResult::factory()->advancedLevel()->make()` produces `ExamLevel::Advanced` level. `ApplicantBonusPoint::factory()->b2Certificate()->make()` produces `LanguageCertificateType::B2` type.
result: pass

## Summary

total: 6
passed: 6
issues: 0
pending: 0
skipped: 0

## Gaps

[none yet]
