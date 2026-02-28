---
status: diagnosed
phase: 07-scoring-service
source: 07-01-SUMMARY.md
started: 2026-02-28T12:00:00Z
updated: 2026-02-28T12:05:00Z
---

## Current Test

[testing complete]

## Tests

### 1. All unit tests pass
expected: Run `php artisan test --compact` — all tests pass with 0 failures including the 9 new AdmissionScoringService tests.
result: pass

### 2. AdmissionScoringService resolves from container
expected: Run in tinker: `app(App\Services\AdmissionScoringService::class)` — should return an AdmissionScoringService instance without errors, confirming DI wiring is correct.
result: issue
reported: "Illuminate\Contracts\Container\BindingResolutionException  Target [App\Contracts\ProgramRegistryInterface] is not instantiable while building [Laravel\Tinker\Console\TinkerCommand, App\Services\AdmissionScoringService]."
severity: major

### 3. Step 1 — Failed exam throws FailedExamException
expected: A student with an exam result below 20% should trigger FailedExamException before any other validation runs. The 9 unit tests cover this; confirm the test passes in the test output.
result: pass

### 4. Step 2 — Missing global mandatory subject throws exception
expected: A student missing one of the 3 globally mandatory subjects (after passing step 1) should trigger MissingGlobalMandatorySubjectException. Confirm this test passes in the output.
result: pass

### 5. Step 3-5 — Programme-specific validation chain
expected: The service validates programme mandatory subject presence (step 3), level (step 4), and elective availability (step 5) in strict order. Confirm these tests pass in the output.
result: pass

### 6. Happy path — Score returned with base + bonus points
expected: When all validations pass, the service returns a Score VO with basePoints and bonusPoints from the respective calculators. Confirm the happy-path test passes.
result: pass

### 7. Interfaces created for mockability
expected: Three new interfaces exist in app/Contracts/: ProgramRegistryInterface, BasePointCalculatorInterface, BonusPointCalculatorInterface. The concrete classes implement them.
result: pass

## Summary

total: 7
passed: 6
issues: 1
pending: 0
skipped: 0

## Gaps

- truth: "AdmissionScoringService resolves from container without errors"
  status: failed
  reason: "User reported: Illuminate\\Contracts\\Container\\BindingResolutionException  Target [App\\Contracts\\ProgramRegistryInterface] is not instantiable while building [Laravel\\Tinker\\Console\\TinkerCommand, App\\Services\\AdmissionScoringService]."
  severity: major
  test: 2
  root_cause: "Phase 07 created 3 interfaces and refactored AdmissionScoringService to depend on them via constructor injection, but never registered interface-to-concrete bindings in any service provider. AppServiceProvider::register() has zero bind/singleton calls."
  artifacts:
    - path: "app/Providers/AppServiceProvider.php"
      issue: "Missing interface-to-concrete bindings in register()"
    - path: "app/Contracts/ProgramRegistryInterface.php"
      issue: "Unbound interface"
    - path: "app/Contracts/BasePointCalculatorInterface.php"
      issue: "Unbound interface"
    - path: "app/Contracts/BonusPointCalculatorInterface.php"
      issue: "Unbound interface"
  missing:
    - "Add singleton binding: ProgramRegistryInterface -> ProgramRegistry"
    - "Add singleton binding: BasePointCalculatorInterface -> BasePointCalculator"
    - "Add singleton binding: BonusPointCalculatorInterface -> BonusPointCalculator"
  debug_session: ".planning/debug/binding-resolution-exception.md"
