<?php

declare(strict_types=1);

use App\ValueObjects\Score;

it('calculates total correctly', function (int $base, int $bonus, int $expected): void {
    $score = new Score($base, $bonus);
    expect($score->total())->toBe($expected);
})->with([
    'zero base and bonus' => [0, 0, 0],
    'typical admission' => [370, 100, 470],
    'max possible' => [400, 100, 500],
]);

it('exposes basePoints via property and method', function (): void {
    $score = new Score(370, 100);
    expect($score->basePoints)->toBe(370)
        ->and($score->basePoints())->toBe(370);
});

it('exposes bonusPoints via property and method', function (): void {
    $score = new Score(370, 100);
    expect($score->bonusPoints)->toBe(100)
        ->and($score->bonusPoints())->toBe(100);
});

it('throws InvalidArgumentException for negative base points', function (): void {
    expect(fn () => new Score(-1, 0))->toThrow(InvalidArgumentException::class);
});

it('throws InvalidArgumentException for negative bonus points', function (): void {
    expect(fn () => new Score(0, -1))->toThrow(InvalidArgumentException::class);
});
