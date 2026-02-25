<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\SubjectName;

final class FailedExamException extends AdmissionException
{
    public function __construct(
        public readonly SubjectName $subject,
        public readonly int $percentage,
    ) {
        parent::__construct(
            "nem lehetséges a pontszámítás a {$subject->value} tárgyból elért 20% alatti eredmény miatt"
        );
    }
}
