---
status: complete
phase: 04-seed-data
source: 04-01-SUMMARY.md
started: 2026-02-26T10:00:00Z
updated: 2026-02-26T10:10:00Z
---

## Current Test

[testing complete]

## Tests

### 1. Fresh migrate and seed runs cleanly
expected: Running `php artisan migrate:fresh --seed` completes without errors. No exceptions, no constraint violations, exit code 0.
result: pass

### 2. ELTE IK programme seeded with correct subjects
expected: The `programs` table contains "ELTE IK Programtervező informatikus" with 5 subject requirements — 1 mandatory (matematika) and 4 elective.
result: pass

### 3. PPKE BTK programme seeded with correct subjects
expected: The `programs` table contains "PPKE BTK Anglisztika" with 7 subject requirements — 1 mandatory (emelt angol) and 6 elective.
result: pass

### 4. Four test applicants seeded
expected: The `applicants` table contains exactly 4 rows — one per acceptance-test case (CASE_1 through CASE_4), all assigned to the ELTE IK program.
result: pass

### 5. Exam results match homework_input.php
expected: The `applicant_exam_results` table contains 19 rows total across the 4 applicants, with subject names, levels, and scores matching the homework_input.php specification exactly.
result: pass

### 6. Bonus points match homework_input.php
expected: The `applicant_bonus_points` table contains 8 rows total across the 4 applicants, with categories and descriptions matching the homework_input.php specification exactly.
result: pass

## Summary

total: 6
passed: 6
issues: 0
pending: 0
skipped: 0

## Gaps

[none]
