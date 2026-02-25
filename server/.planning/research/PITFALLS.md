# Domain Pitfalls

**Domain:** Laravel scoring engine with Value Objects, Strategy pattern, and ordered validation chain
**Researched:** 2026-02-25
**Confidence:** HIGH (derived from IMPLEMENTATION.md, PRD.md, homework_input.php, and established Laravel/PHP patterns)

---

## Critical Pitfalls

Mistakes that cause wrong scores, silent failures, or test suite structural collapse.

---

### Pitfall 1: Validation order violation breaks determinism

**What goes wrong:** The five validation steps (failed exam → global mandatory → programme mandatory → level → elective) must fire in strict order. If the order slips — or if the VO constructor throws `FailedExamException` before the collection is mapped — a test applicant who should fail with "missing global mandatory subjects" instead fails with a different exception, breaking Case 3 vs Case 4 determinism.

**Why it happens:** `ExamResult`'s constructor throws `FailedExamException` when `$percentage < 20`. If `AdmissionScoringService` maps the entire Eloquent collection to VOs first (before any validation method), Applicant 4 (15% Hungarian) throws at mapping time — which happens before the explicit global mandatory check. That's actually correct per the spec (step 1 precedes step 2). The danger is the inverse: a developer moves the VO mapping after the global mandatory check to "clean up" the service, silently swapping step 1 and step 2.

**Consequences:** Case 3 and Case 4 error messages swap. Feature tests that pin exact exception types and messages fail. The bug is invisible without the full 4-case test suite.

**Prevention:** Keep VO mapping (`new ExamResult(...)`) as the very first action in `calculateForApplicant()`. The `FailedExamException` thrown by the constructor IS step 1 of the validation chain. Document this explicitly in the service with a comment referencing the validation order from the PRD.

**Detection:** Run all 4 acceptance cases. If Case 4 (15% exam) returns a missing-subjects error instead of a failed-exam error, the mapping is happening after the global mandatory check.

**Phase:** Domain model + service implementation phase.

---

### Pitfall 2: Hungarian diacritics in enum values cause silent `ValueError` at runtime

**What goes wrong:** `SubjectName` enum values contain UTF-8 diacritics: `'magyar nyelv és irodalom'`, `'történelem'`, `'biológia'`, `'kémia'`, `'fizika'`, `'angol nyelv'`, etc. If the database stores these strings but the file encoding is wrong, or if a migration or seeder uses a hard-coded ASCII approximation, `SubjectName::from($r->subject_name)` throws a `ValueError` at runtime that is not caught by `AdmissionException` rendering.

**Why it happens:** Developers copy-paste subject name strings from non-UTF-8 terminal output or documentation with mojibake. SQLite, used for dev/test, stores whatever bytes it receives without charset enforcement. The mismatch only surfaces when `::from()` is called.

**Consequences:** 500 Server Error instead of a scored result. The exception is not an `AdmissionException` so it bypasses the 422 handler and returns an unformatted Laravel error page or a Whoops dump.

**Prevention:**
1. Define enum case values once in the enum file. Copy them from the enum — never retype them in seeders or tests.
2. All PHP source files must be saved as UTF-8. Verify with `file -i` or editor encoding indicator.
3. In `DatabaseSeeder` / `ProgramSeeder`, reference `SubjectName::MagyarNyelvEsIrodalom->value` rather than the string literal `'magyar nyelv és irodalom'`.

**Detection:** `php artisan tinker` — `SubjectName::from('matematika')` succeeds; `SubjectName::from('biológia')` should too. If a `ValueError` appears for an accented value, encoding is wrong in the source file.

**Phase:** Enums + seeder phase (Step 1 and 3 of TDD order).

---

### Pitfall 3: Best-elective selection uses wrong comparison when mandatory subject also appears in elective list

**What goes wrong:** ELTE IK has `matematika` as mandatory and `{biológia, fizika, informatika, kémia}` as electives. Matematika is NOT in the elective list, so this is not a problem for the seeded data. BUT if a future programme has a subject that is both mandatory and could appear in elective scoring (or if the elective filter is done on exam results instead of on the programme's elective list), the mandatory subject could be double-counted: used once for mandatory points and again as the best elective.

**Why it happens:** `BasePointCalculator::calculate()` receives two explicit `ExamResult` arguments (mandatory, bestElective). The problem lives upstream in `AdmissionScoringService` when it selects `$bestElective`. If the service filters exam results to "any result that matches any programme elective" it is correct. If it instead takes the highest-scoring exam result from ALL results, the mandatory subject (matematika at 90%) competes with electieves — and in Case 1 it loses to informatika 95%, so the bug hides. In Case 2, fizika 98% wins, also hiding the bug. A future test case where the mandatory subject scores highest would expose it.

**Prevention:** In `AdmissionScoringService`, filter `$examResults` to only those whose `SubjectName` appears in `$requirements->getElectiveSubjects()` before selecting the best. Never take `max($examResults)` across the full set.

**Detection:** Write a unit test where the mandatory subject has the highest score (e.g., mandatory matematika at 99%, electieve fizika at 50%). Verify `BasePointCalculator` receives fizika 50% as `$bestElective`, not matematika 99%.

**Phase:** Service implementation phase (Step 8 of TDD order).

---

### Pitfall 4: Language certificate deduplication applied incorrectly — per language, not per type

**What goes wrong:** The rule is: if a student holds both B2 and C1 for the same language, only the C1 (40 pts) counts. The pitfall is implementing deduplication by `LanguageCertificateType` instead of by `language`. That would mean: only one B2 certificate ever counts, even across different languages (e.g., B2 English + B2 German → only 28 pts instead of 56 pts).

**Why it happens:** The word "deduplication" makes developers think "remove duplicates" which reads as "one of each type." The actual semantics are "per language, take the highest."

**Consequences:** Bonus points undercount for students with multiple language certificates in different languages.

**Detection:** Unit test in `BonusPointCalculatorTest`:
- B2 English + C1 German → 28 + 40 = 68 (no dedup, different languages)
- B2 English + C1 English → 40 (dedup, same language — only C1 counts)
- B2 English + C1 English + C1 German → 40 + 40 = 80

**Prevention:** Group `LanguageCertificate` by `$certificate->language()` (not by type). For each language group, take the one with the highest `points()` value.

**Phase:** BonusPointCalculator unit test phase (Step 7 of TDD order).

---

### Pitfall 5: `ProgramRegistry::findByApplicant()` triggers N+1 queries in the list endpoint

**What goes wrong:** `GET /api/v1/applicants` loads all applicants. If `ApplicantController` calls `Applicant::all()` and then the resource or service accesses `$applicant->program` for each, Laravel fires one query per applicant (N+1). For the seeded 4-record dataset this is invisible. Larastan and Pest won't catch it.

**Why it happens:** The list endpoint only needs `program` (not `subjects`). If the same eager-load that `ProgramRegistry` uses (`with('subjects')`) is copy-pasted into the list query, it loads unnecessary data. Conversely, if the list query does not eager-load `program` at all, it N+1s.

**Prevention:** `GET /api/v1/applicants` query: `Applicant::with('program')->get()`. The score endpoint's `ProgramRegistry::findByApplicant()` uses `$applicant->program()->with('subjects')->firstOrFail()`. These are different queries serving different needs — do not share them.

**Detection:** Laravel Debugbar or `DB::enableQueryLog()` in a test. Expect exactly 2 queries for the list endpoint (one for applicants, one for programs via eager loading).

**Phase:** API layer phase (Step 11-14 of TDD order).

---

### Pitfall 6: Exception renderer catches too broadly or too narrowly in `bootstrap/app.php`

**What goes wrong:** The exception handler in `bootstrap/app.php` maps `AdmissionException` → `422`. Two failure modes:

1. **Too broad:** Catching `\Throwable` or `\Exception` and returning 422 for everything — a `ValueError` from a bad enum `::from()` call returns 422 with a PHP error message in the `error` field.
2. **Too narrow:** Using a `render()` callback that only fires for the exact abstract class and not subclasses — this never fires because `AdmissionException` is abstract and is only ever thrown as a subclass instance.

**Why it happens:** Laravel's `withExceptions()->render()` matches on the exact class via `instanceof`, so subclasses ARE matched. The "too narrow" concern is only a risk if someone writes `if (get_class($e) === AdmissionException::class)` instead of `instanceof`.

**Consequences:** Either all errors return 422 (leaks implementation details) or no domain errors return 422 (all become 500s).

**Prevention:** The `bootstrap/app.php` renderer shown in IMPLEMENTATION.md is correct:
```php
$exceptions->render(function (AdmissionException $e): JsonResponse {
    return response()->json(['error' => $e->getMessage()], 422);
});
```
Laravel uses type-hinted parameter matching which is `instanceof`-based. This correctly catches all subclasses.

**Detection:** Feature test asserting `422` with the exact `error` key for each of Cases 3 and 4. Separately, ensure a 500 is returned (not 422) for a route that throws a plain `\RuntimeException`.

**Phase:** API layer + exception wiring phase (Step 14 of TDD order).

---

### Pitfall 7: `Score` VO caps bonus points — but the cap belongs in `BonusPointCalculator`

**What goes wrong:** The IMPLEMENTATION.md explicitly states: "A többletpont cap-elése (max 100) a `BonusPointCalculator`-ban történik, nem itt" (the bonus point cap happens in `BonusPointCalculator`, not in `Score`). If a developer puts `min($bonusPoints, 100)` inside `Score::__construct()`, the cap is applied twice (once in BonusPointCalculator, once in Score) — harmlessly, since `min(min(x, 100), 100) = min(x, 100)`. But it also means that `Score` hides uncapped values, making it impossible to test the raw-before-cap value, and `BonusPointCalculator` unit tests that verify capping cannot observe what `Score` was constructed with.

**Prevention:** `Score` constructor stores raw values. `BonusPointCalculator::calculate()` returns `min($raw, 100)`. `Score::bonusPoints()` returns whatever was passed in.

**Detection:** If `ScoreTest` has a test case with `bonusPoints: 118` that expects `total(): 500` (400 base + 100 bonus), and it passes, then the cap is wrongly in `Score`. The correct test expects `total(): 518` if 118 is passed raw, or the test should never pass 118 to `Score` directly because the calculator already capped it.

**Phase:** Value Object + Calculator implementation phases (Steps 4 and 7 of TDD order).

---

## Moderate Pitfalls

---

### Pitfall 8: Feature tests do not use `RefreshDatabase` + seeders — test against wrong data

**What goes wrong:** `ApplicantScoreTest` tests the 4 acceptance cases against IDs 1–4. If the test does not use `RefreshDatabase` and re-run seeders, the test may run against stale state, or a different test that creates applicants leaves IDs shifted so that ID 1 is not Applicant 1 from the spec.

**Prevention:** Use `RefreshDatabase` trait in `ApplicantScoreTest`. Ensure `DatabaseSeeder` calls `ProgramSeeder` then `ApplicantSeeder` in order. Do not create applicants in test factories without explicit IDs unless the test is for a non-seeded scenario.

**Detection:** Run tests twice in sequence. If the second run fails where the first passed (due to ID drift), `RefreshDatabase` is missing.

**Phase:** Feature test phase (Step 9 of TDD order).

---

### Pitfall 9: `DatabaseProgramRequirements` unit tests rely on a real database instead of mocked models

**What goes wrong:** `DatabaseProgramRequirements` takes a `Program` model. Its unit test should mock the `Program` and its `subjects` relationship with a fake `Collection`. If the test hits the real SQLite database, it becomes a slow integration test and breaks in isolation (no seeded data outside feature tests that use `RefreshDatabase`).

**Prevention:** In `DatabaseProgramRequirementsTest`, construct a `Program` model stub with `subjects` set as a `Collection` of `ProgramSubject` instances created without DB persistence (using `ProgramSubject::make([...])`). Do not call `DatabaseSeeder` in unit tests.

**Detection:** If the unit test suite requires `RefreshDatabase` or runs the seeder, it is an integration test pretending to be a unit test.

**Phase:** Service unit test phase (Steps 5-8 of TDD order).

---

### Pitfall 10: API route file path mismatch with `bootstrap/app.php` routing configuration

**What goes wrong:** IMPLEMENTATION.md specifies the API route at `routes/api/v1.php` with `apiPrefix: 'api/v1'` in `bootstrap/app.php`. Laravel 12's default `withRouting()` uses `api: __DIR__.'/../routes/api.php'` with `apiPrefix: 'api'`. If a developer edits `bootstrap/app.php` but places routes in `routes/api.php` (the default location), routes are registered but under `/api/...` not `/api/v1/...`. Feature tests calling `/api/v1/applicants` get 404s that look like missing applicants.

**Prevention:** Create `routes/api/` directory explicitly. Match the path in `bootstrap/app.php` exactly to `routes/api/v1.php`. Verify with `php artisan route:list` before writing feature tests.

**Detection:** `php artisan route:list | grep applicants` shows the routes listed at the expected URI.

**Phase:** Route + app bootstrap phase (Steps 13-14 of TDD order).

---

### Pitfall 11: `ProgramRegistry` registered as singleton causes state bleed between requests

**What goes wrong:** `AppServiceProvider` registers `ProgramRegistry` as a singleton. `ProgramRegistry` itself is stateless (it takes an `Applicant` as a method argument), so the singleton is correct. The risk: if a developer adds instance-level state to `ProgramRegistry` (e.g., caching the resolved `DatabaseProgramRequirements` in a property), the cache persists across multiple requests in long-running process contexts (Octane, queues).

**Prevention:** `ProgramRegistry` must remain stateless. Any caching should use Laravel's cache layer, not instance properties.

**Detection:** If the registry stores a resolved programme in `$this->cachedRequirements`, a request for Applicant 1 (ELTE IK) followed by Applicant 4 (also ELTE IK, same programme) would return the same requirements — harmless here, but dangerous if programmes ever differ across applicants.

**Phase:** Service + provider wiring phase (Step 8 + Step 15 of TDD order).

---

## Minor Pitfalls

---

### Pitfall 12: `AngoNyelv` enum key has a typo in IMPLEMENTATION.md

**What goes wrong:** IMPLEMENTATION.md lists `AngoNyelv` (missing the final 'l') in the `SubjectName` enum keys, while the PRD lists it as `AngoNyelv` too. The actual Hungarian word is "Angol". This is a homework specification artifact. If the enum key is `AngoNyelv` but everywhere else uses `AngoNyelv`, it is internally consistent. The danger is if a developer "fixes" the typo to `AngoLNyelv` or `AngoiNyelv` without updating all references.

**Prevention:** Use whatever spelling is in the authoritative spec (the homework_input.php file uses `'angol nyelv'` as the value). The enum key is cosmetic; the value `'angol nyelv'` is what matters for `::from()` lookups. Keep the key as-is from the spec.

**Detection:** Larastan will catch undefined enum cases at static analysis time.

**Phase:** Enum implementation phase (Step 3 of TDD order).

---

### Pitfall 13: `unsignedTinyInteger` column for `percentage` does not enforce 0-100 at the DB level

**What goes wrong:** `unsignedTinyInteger` stores 0–255. A seeder could insert 101 or 255, and the database will accept it. The VO constructor does not validate the upper bound (only the lower 20% bound is checked in the spec). A percentage of 150 would produce 300 base points from a single subject, breaking the 400 cap.

**Prevention:** The `ExamResult` VO constructor should validate `0 <= $percentage <= 100` and throw an appropriate exception. This is not in the current IMPLEMENTATION.md — it only validates `< 20`. Add an `InvalidArgumentException` (or a new `AdmissionException` subclass) for out-of-range percentages if robustness matters.

**Detection:** `new ExamResult(SubjectName::Matematika, ExamLevel::Emelt, 101)` — should throw, currently may not.

**Phase:** Value Object phase (Step 4 of TDD order). Low priority for the seeded-data-only project, but worth noting for production hardening.

---

### Pitfall 14: `applicant_bonus_points.language` column is nullable but `LanguageCertificate` constructor requires a non-null string

**What goes wrong:** The database schema marks `language` as nullable (to support future non-language bonus categories). `ApplicantBonusPoint` records with `category = 'Nyelvvizsga'` always have a language. But if a seeder accidentally inserts a `Nyelvvizsga` record with `language = null`, the `LanguageCertificate` constructor receives `null` for `string $language`, which PHP 8 with strict types will reject with a `TypeError`. That `TypeError` is not an `AdmissionException` and will produce a 500.

**Prevention:** In `AdmissionScoringService`, filter `$applicant->bonusPoints` to only records with `category = 'Nyelvvizsga'` before mapping to `LanguageCertificate`. Ensure all `Nyelvvizsga` seeder records have a non-null language.

**Detection:** Pass `null` as `$language` in `LanguageCertificateTest` — PHP will throw `TypeError`. Ensure seeders are reviewed for null language on language-certificate records.

**Phase:** Seeder + service mapping phase (Steps 6 and 10 of TDD order).

---

## Phase-Specific Warnings

| Phase Topic | Likely Pitfall | Mitigation |
|-------------|---------------|------------|
| Enums | Diacritics in enum values cause `ValueError` at runtime | Reference `SubjectName::X->value` in seeders, never retype strings |
| Value Objects | `FailedExamException` thrown at mapping time enforces validation order | Keep VO mapping as step 1 in the service; do not reorder |
| BonusPointCalculator | Language dedup applied per-type instead of per-language | Group certs by `language()` not by `type`; test B2+C1 same language vs different |
| BonusPointCalculator | Cap placed in `Score` instead of calculator | Cap in calculator; `Score` stores raw values passed in |
| BasePointCalculator | Mandatory subject included in elective candidate pool | Filter elective candidates to `getElectiveSubjects()` list only |
| Seeder | All 4 applicants must be seeded before feature tests run | `DatabaseSeeder` calls `ProgramSeeder` then `ApplicantSeeder` |
| Unit tests | `DatabaseProgramRequirements` tests hit real DB | Use `ProgramSubject::make()` for mock subjects; no `RefreshDatabase` in unit tests |
| Feature tests | ID drift breaks acceptance cases | Use `RefreshDatabase` in `ApplicantScoreTest` |
| Route wiring | Route file path mismatch with `apiPrefix` | Verify with `php artisan route:list` before writing feature tests |
| Exception handler | Wrong exception type triggers 422 for non-domain errors | Test that plain `RuntimeException` does not hit the 422 handler |

---

## Sources

- IMPLEMENTATION.md — architecture, TDD order, scoring formula, validation order specification (HIGH confidence, primary spec)
- PRD.md — API contract, validation rules, acceptance test cases (HIGH confidence, primary spec)
- kovetelmenyek.md + homework_input.php — original Hungarian homework specification and reference inputs/outputs (HIGH confidence, ground truth)
- Laravel 12 exception handling documentation — `withExceptions()->render()` uses `instanceof`-based matching (HIGH confidence)
- PHP 8 strict types and enum `::from()` behavior — documented language behavior (HIGH confidence)
