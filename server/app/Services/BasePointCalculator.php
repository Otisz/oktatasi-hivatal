<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\ExamResult;

final class BasePointCalculator
{
    public function calculate(ExamResult $mandatory, ExamResult $bestElective): int
    {
        return min(($mandatory->points() + $bestElective->points()) * 2, 400);
    }
}
