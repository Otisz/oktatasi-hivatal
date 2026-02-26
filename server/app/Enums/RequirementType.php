<?php

declare(strict_types=1);

namespace App\Enums;

enum RequirementType: string
{
    case Mandatory = 'mandatory';
    case Elective = 'elective';
}
