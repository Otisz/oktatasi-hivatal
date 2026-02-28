---
status: complete
phase: 06-calculators
source: 06-01-SUMMARY.md
started: 2026-02-28T14:30:00Z
updated: 2026-02-28T14:35:00Z
---

## Current Test

[testing complete]

## Tests

### 1. All Unit Tests Pass
expected: Run `php artisan test --compact` — all 55 tests pass with 0 failures, 0 errors.
result: pass

### 2. BasePointCalculator Formula
expected: BasePointCalculator calculates (mandatory + bestElective) * 2, capped at 400. For example, mandatory=85, elective=90 should produce (85+90)*2 = 350.
result: pass

### 3. BasePointCalculator 400 Cap
expected: When mandatory + elective would exceed 200 (e.g., both 100), result is capped at 400, not 400+.
result: pass

### 4. BonusPointCalculator Emelt Bonus
expected: Each emelt-level exam result adds +50 bonus points. Two emelt exams = 100 bonus points.
result: pass

### 5. BonusPointCalculator Language Cert Dedup
expected: Two language certs for the same language (e.g., B2 and C1 German) only count the higher one, not both.
result: pass

### 6. BonusPointCalculator 100 Cap
expected: Total bonus points never exceed 100, even with many emelt exams and language certs.
result: pass

## Summary

total: 6
passed: 6
issues: 0
pending: 0
skipped: 0

## Gaps

[none yet]
