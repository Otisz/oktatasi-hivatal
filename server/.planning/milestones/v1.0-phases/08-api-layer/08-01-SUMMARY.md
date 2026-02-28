---
phase: 08-api-layer
plan: "01"
subsystem: api-layer
tags: [api, routing, resources, exception-rendering]
dependency_graph:
  requires: [AdmissionScoringService, Score VO, AdmissionException hierarchy, Applicant model, Program model]
  provides: [GET /api/v1/applicants, GET /api/v1/applicants/{applicant}/score, 422 AdmissionException rendering]
  affects: [bootstrap/app.php, routes/api.php]
tech_stack:
  added: []
  patterns: [Eloquent API Resources, implicit route model binding, typed exception rendering closure]
key_files:
  created:
    - routes/api.php
    - app/Http/Controllers/ApplicantController.php
    - app/Http/Resources/ApplicantResource.php
    - app/Http/Resources/ScoreResource.php
  modified:
    - bootstrap/app.php
decisions:
  - ScoreResource accesses Score VO methods via $this->resource->method() — JsonResource proxies property access but not method calls on non-Model resources
  - Pint applied fully_qualified_strict_types fixer to bootstrap/app.php — short unqualified names used in closures without use imports
metrics:
  duration: "1 min"
  completed_date: "2026-02-28"
  tasks_completed: 2
  files_created: 4
  files_modified: 1
---

# Phase 8 Plan 1: API Layer Wiring Summary

**One-liner:** HTTP API layer wired to business logic — two endpoints expose applicant list and score calculation with 422 exception rendering for all AdmissionException subclasses.

## What Was Built

Two JSON API endpoints connecting the existing scoring engine to HTTP:

- `GET /api/v1/applicants` — returns all applicants with nested program data via `ApplicantResource::collection()`
- `GET /api/v1/applicants/{applicant}/score` — calculates admission score via `AdmissionScoringService`, returns Hungarian-keyed response via `ScoreResource`
- `AdmissionException` subclasses render as `422 { "error": "<Hungarian message>" }` via typed closure in `bootstrap/app.php`
- Unknown UUID `{applicant}` automatically returns 404 via implicit route model binding

## Tasks Completed

| # | Task | Commit | Key Files |
|---|------|--------|-----------|
| 1 | Create API routes, controller, and resources | 7bae350 | routes/api.php, ApplicantController.php, ApplicantResource.php, ScoreResource.php |
| 2 | Wire API routes and exception rendering in bootstrap/app.php | 6f54e92 | bootstrap/app.php |

## Decisions Made

1. **ScoreResource uses `$this->resource->method()`** — `JsonResource` proxies `__get` for property access but not `__call` for method calls on non-Eloquent resources. Score VO methods must be accessed through `$this->resource` directly.

2. **Pint `fully_qualified_strict_types` fixer** — Pint rewrote the FQCNs in the exception rendering closure to unqualified class names (`App\Exceptions\AdmissionException` → `App\Exceptions\AdmissionException`). This is valid PHP since the file uses `declare(strict_types=1)` and the names are fully qualified without leading backslash.

3. **No new service bindings needed** — `ProgramRegistryInterface`, `BasePointCalculatorInterface`, and `BonusPointCalculatorInterface` singletons were already bound in `AppServiceProvider::register()` during Phase 7. Laravel resolves `AdmissionScoringService` constructor dependencies automatically.

## Deviations from Plan

None - plan executed exactly as written.

## Verification Results

- `php artisan route:list --path=api` shows 2 routes: `GET /api/v1/applicants` and `GET /api/v1/applicants/{applicant}/score`
- `vendor/bin/pint --dirty --format agent` reports no changes after all modifications
- All 68 existing tests pass (96 assertions)
