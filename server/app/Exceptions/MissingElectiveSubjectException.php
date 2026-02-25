<?php

declare(strict_types=1);

namespace App\Exceptions;

final class MissingElectiveSubjectException extends AdmissionException
{
    public function __construct()
    {
        parent::__construct('nem lehetséges a pontszámítás a kötelezően választható tárgy hiánya miatt');
    }
}
