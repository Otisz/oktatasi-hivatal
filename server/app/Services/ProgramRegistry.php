<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProgramRegistryInterface;
use App\Contracts\ProgramRequirementsInterface;
use App\Models\Applicant;

final class ProgramRegistry implements ProgramRegistryInterface
{
    public function findByApplicant(Applicant $applicant): ProgramRequirementsInterface
    {
        return new DatabaseProgramRequirements($applicant->program);
    }
}
