<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MissingGlobalMandatorySubjectException extends AdmissionException
{
    public function __construct()
    {
        parent::__construct('nem lehetséges a pontszámítás a kötelező érettségi tárgyak hiánya miatt');
    }
}
