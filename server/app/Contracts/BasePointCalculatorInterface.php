<?php

declare(strict_types=1);

namespace App\Contracts;

use App\ValueObjects\ExamResult;

interface BasePointCalculatorInterface
{
    public function calculate(ExamResult $mandatory, ExamResult $bestElective): int;
}
