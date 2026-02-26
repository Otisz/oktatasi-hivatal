---
status: complete
phase: 01-domain-primitives
source: 01-01-SUMMARY.md, 01-02-SUMMARY.md
started: 2026-02-26T10:00:00Z
updated: 2026-02-26T10:10:00Z
---

## Current Test

[testing complete]

## Tests

### 1. SubjectName enum has all 13 subjects
expected: SubjectName::cases() returns exactly 13 cases covering all Hungarian secondary school subjects.
result: pass

### 2. Globally mandatory subjects
expected: SubjectName::globallyMandatory() returns exactly 3 subjects: Magyar, Matematika, Történelem.
result: pass

### 3. Language subject detection
expected: SubjectName::isLanguage() returns true for the 6 foreign language subjects (angol, német, francia, olasz, orosz, spanyol) and false for non-language subjects like matematika.
result: pass

### 4. ExamLevel Hungarian backing values
expected: ExamLevel::Intermediate->value is 'közép' and ExamLevel::Advanced->value is 'emelt'.
result: pass

### 5. LanguageCertificateType points mapping
expected: LanguageCertificateType::UpperIntermediate->points() returns 28, LanguageCertificateType::Advanced->points() returns 40.
result: pass

### 6. Exception hierarchy structure
expected: All 6 concrete exceptions extend AdmissionException. FailedExamException carries subject and percentage properties. ProgramMandatorySubjectLevelException carries subject and requiredLevel properties.
result: pass

### 7. PHPStan level 7 passes
expected: Running PHPStan on app/Enums and app/Exceptions produces zero errors at level 7.
result: pass

## Summary

total: 7
passed: 7
issues: 0
pending: 0
skipped: 0

## Gaps

[none yet]
