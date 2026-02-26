<?php

declare(strict_types=1);

namespace App\ValueObjects;

final readonly class Score
{
    public function __construct(
        public int $basePoints,
        public int $bonusPoints,
    ) {
        throw_if($basePoints < 0, \InvalidArgumentException::class, "Base points must be non-negative, got {$basePoints}.");

        throw_if($bonusPoints < 0, \InvalidArgumentException::class, "Bonus points must be non-negative, got {$bonusPoints}.");
    }

    public function total(): int
    {
        return $this->basePoints + $this->bonusPoints;
    }

    public function basePoints(): int
    {
        return $this->basePoints;
    }

    public function bonusPoints(): int
    {
        return $this->bonusPoints;
    }
}
