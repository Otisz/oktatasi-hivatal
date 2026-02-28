<?php

declare(strict_types=1);

namespace App\Contracts;

use App\ValueObjects\ExamResult;
use App\ValueObjects\LanguageCertificate;

interface BonusPointCalculatorInterface
{
    /**
     * @param  array<int, ExamResult>  $examResults
     * @param  array<int, LanguageCertificate>  $certificates
     */
    public function calculate(array $examResults, array $certificates): int;
}
