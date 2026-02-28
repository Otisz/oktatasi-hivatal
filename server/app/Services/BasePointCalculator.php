<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BasePointCalculatorInterface;
use App\ValueObjects\ExamResult;

final class BasePointCalculator implements BasePointCalculatorInterface
{
    public function calculate(ExamResult $mandatory, ExamResult $bestElective): int
    {
        return min(($mandatory->points() + $bestElective->points()) * 2, 400);
    }
}
