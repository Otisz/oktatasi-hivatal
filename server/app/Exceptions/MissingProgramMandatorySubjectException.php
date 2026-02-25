<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\SubjectName;

final class MissingProgramMandatorySubjectException extends AdmissionException
{
    public function __construct(
        public readonly SubjectName $subject,
    ) {
        parent::__construct(
            "nem lehetséges a pontszámítás a {$subject->value} kötelező tárgy hiánya miatt"
        );
    }
}
