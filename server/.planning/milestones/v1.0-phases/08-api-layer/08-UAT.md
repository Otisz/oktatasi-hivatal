---
status: complete
phase: 08-api-layer
source: [08-01-SUMMARY.md, 08-02-SUMMARY.md]
started: 2026-02-28T16:00:00Z
updated: 2026-02-28T16:05:00Z
---

## Current Test

[testing complete]

## Tests

### 1. List Applicants Endpoint
expected: GET /api/v1/applicants returns 200 with JSON array of all applicants. Each applicant includes nested program data.
result: pass

### 2. Score Calculation — Successful Applicant
expected: GET /api/v1/applicants/{applicant}/score for a valid applicant returns 200 with Hungarian-keyed JSON score breakdown (e.g. alappontok, tobbletpontok, osszpontszam).
result: pass

### 3. Validation Error — 422 with Hungarian Message
expected: GET /api/v1/applicants/{applicant}/score for an applicant that fails admission rules returns 422 with JSON body {"error": "<Hungarian error message>"}.
result: pass

### 4. Unknown Applicant — 404
expected: GET /api/v1/applicants/{nonexistent-uuid}/score returns 404 for a UUID that doesn't exist in the database.
result: pass

### 5. Full Test Suite Passes
expected: Running `php artisan test` shows all 73 tests passing with zero failures.
result: pass

## Summary

total: 5
passed: 5
issues: 0
pending: 0
skipped: 0

## Gaps

[none yet]
