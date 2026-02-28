<?php

declare(strict_types=1);

use App\Contracts\BasePointCalculatorInterface;
use App\Contracts\BonusPointCalculatorInterface;
use App\Contracts\ProgramRegistryInterface;
use App\Services\AdmissionScoringService;
use App\Services\BasePointCalculator;
use App\Services\BonusPointCalculator;
use App\Services\ProgramRegistry;

it('resolves ProgramRegistryInterface to ProgramRegistry singleton', function (): void {
    expect(app(ProgramRegistryInterface::class))->toBeInstanceOf(ProgramRegistry::class);
});

it('resolves BasePointCalculatorInterface to BasePointCalculator singleton', function (): void {
    expect(app(BasePointCalculatorInterface::class))->toBeInstanceOf(BasePointCalculator::class);
});

it('resolves BonusPointCalculatorInterface to BonusPointCalculator singleton', function (): void {
    expect(app(BonusPointCalculatorInterface::class))->toBeInstanceOf(BonusPointCalculator::class);
});

it('resolves AdmissionScoringService from the container without BindingResolutionException', function (): void {
    expect(app(AdmissionScoringService::class))->toBeInstanceOf(AdmissionScoringService::class);
});
