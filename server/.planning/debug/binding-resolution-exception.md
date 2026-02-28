---
status: diagnosed
trigger: "BindingResolutionException: Target [App\\Contracts\\ProgramRegistryInterface] is not instantiable while building AdmissionScoringService"
created: 2026-02-28T00:00:00Z
updated: 2026-02-28T00:00:00Z
---

## Current Focus

hypothesis: CONFIRMED - The 3 interfaces are not bound in any service provider
test: Searched entire app/ and bootstrap/ for bind(), singleton(), scoped() calls
expecting: Zero binding registrations found
next_action: Return diagnosis

## Symptoms

expected: AdmissionScoringService resolves from the container with its 3 interface dependencies injected
actual: BindingResolutionException - Target [App\Contracts\ProgramRegistryInterface] is not instantiable
errors: Illuminate\Contracts\Container\BindingResolutionException: Target [App\Contracts\ProgramRegistryInterface] is not instantiable while building [Laravel\Tinker\Console\TinkerCommand, App\Services\AdmissionScoringService]
reproduction: Attempt to resolve AdmissionScoringService from the container (e.g., via tinker)
started: Phase 07 implementation

## Eliminated

## Evidence

- timestamp: 2026-02-28T00:00:00Z
  checked: app/Providers/AppServiceProvider.php
  found: Only contains Model::unguard(), Model::preventLazyLoading(), Model::preventAccessingMissingAttributes() in register(). Boot() is empty. Zero bind/singleton/scoped calls.
  implication: The sole service provider has no interface bindings at all

- timestamp: 2026-02-28T00:00:00Z
  checked: bootstrap/providers.php
  found: Only registers App\Providers\AppServiceProvider::class. No other providers.
  implication: There is exactly one service provider and it has no bindings

- timestamp: 2026-02-28T00:00:00Z
  checked: bootstrap/app.php
  found: Standard Laravel 12 bootstrap with routing, middleware, and exceptions. No service bindings.
  implication: No bindings in bootstrap config either

- timestamp: 2026-02-28T00:00:00Z
  checked: Grep for bind()/singleton()/scoped() in entire app/ and bootstrap/ directories
  found: Zero matches across entire codebase
  implication: No interface-to-concrete bindings exist anywhere in the application

- timestamp: 2026-02-28T00:00:00Z
  checked: All 3 concrete implementations
  found: ProgramRegistry implements ProgramRegistryInterface, BasePointCalculator implements BasePointCalculatorInterface, BonusPointCalculator implements BonusPointCalculatorInterface. All exist and correctly implement their interfaces.
  implication: Implementations are correct; only the container wiring is missing

- timestamp: 2026-02-28T00:00:00Z
  checked: AdmissionScoringService constructor
  found: Constructor type-hints all 3 interfaces (ProgramRegistryInterface, BasePointCalculatorInterface, BonusPointCalculatorInterface) via constructor property promotion
  implication: Laravel's container cannot auto-resolve interfaces without explicit bindings; concrete classes are needed in the binding

## Resolution

root_cause: Phase 07 created 3 interfaces and refactored AdmissionScoringService to depend on them via constructor injection, but never registered the interface-to-concrete bindings in any service provider. Laravel's container cannot auto-resolve interfaces (only concrete classes), so any attempt to resolve AdmissionScoringService from the container fails.
fix:
verification:
files_changed: []
