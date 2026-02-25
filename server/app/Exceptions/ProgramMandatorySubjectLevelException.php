<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;

final class ProgramMandatorySubjectLevelException extends AdmissionException
{
    public function __construct(
        public readonly SubjectName $subject,
        public readonly ExamLevel $requiredLevel,
    ) {
        parent::__construct(
            "nem lehetséges a pontszámítás a {$subject->value} tárgyból elvárt {$requiredLevel->value} szint hiánya miatt"
        );
    }
}
