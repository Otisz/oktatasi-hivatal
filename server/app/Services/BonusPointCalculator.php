<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BonusPointCalculatorInterface;
use App\ValueObjects\ExamResult;
use App\ValueObjects\LanguageCertificate;

final class BonusPointCalculator implements BonusPointCalculatorInterface
{
    /**
     * @param  array<int, ExamResult>  $examResults
     * @param  array<int, LanguageCertificate>  $certificates
     */
    public function calculate(array $examResults, array $certificates): int
    {
        $advancedPoints = 0;

        foreach ($examResults as $result) {
            if ($result->isAdvancedLevel()) {
                $advancedPoints += 50;
            }
        }

        /** @var array<string, int> $langMap */
        $langMap = array_reduce($certificates, function ($carry, $certificate): array {
            $language = $certificate->language();
            $points = $certificate->points();

            $carry[$language] = isset($carry[$language]) ? max($carry[$language], $points) : $points;

            return $carry;
        }, []);

        $certPoints = array_sum($langMap);

        return min($advancedPoints + $certPoints, 100);
    }
}
