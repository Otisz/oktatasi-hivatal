<?php

declare(strict_types=1);

namespace App\Services;

use App\ValueObjects\ExamResult;
use App\ValueObjects\LanguageCertificate;

final class BonusPointCalculator
{
    /**
     * @param  array<int, ExamResult>  $examResults
     * @param  array<int, LanguageCertificate>  $certificates
     */
    public function calculate(array $examResults, array $certificates): int
    {
        $emeltPoints = 0;

        foreach ($examResults as $result) {
            if ($result->isAdvancedLevel()) {
                $emeltPoints += 50;
            }
        }

        /** @var array<string, int> $langMap */
        $langMap = [];

        foreach ($certificates as $certificate) {
            $language = $certificate->language();
            $points = $certificate->points();

            $langMap[$language] = isset($langMap[$language])
                ? max($langMap[$language], $points)
                : $points;
        }

        $certPoints = array_sum($langMap);

        return min($emeltPoints + $certPoints, 100);
    }
}
