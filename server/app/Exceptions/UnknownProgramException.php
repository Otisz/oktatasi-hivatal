<?php

declare(strict_types=1);

namespace App\Exceptions;

final class UnknownProgramException extends AdmissionException
{
    public function __construct()
    {
        parent::__construct('nem lehetséges a pontszámítás ismeretlen szak miatt');
    }
}
