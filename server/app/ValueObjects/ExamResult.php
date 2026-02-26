<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Exceptions\FailedExamException;

final readonly class ExamResult
{
    public function __construct(
        public SubjectName $subject,
        public ExamLevel $level,
        public int $percentage,
    ) {
        if ($percentage < 0 || $percentage > 100) {
            throw new \InvalidArgumentException(
                "Percentage must be between 0 and 100, got {$percentage}.",
            );
        }

        if ($percentage < 20) {
            throw new FailedExamException($subject, $percentage);
        }
    }

    public function points(): int
    {
        return $this->percentage;
    }

    public function isAdvancedLevel(): bool
    {
        return ExamLevel::Advanced === $this->level;
    }
}
