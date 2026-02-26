<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ProgramRequirementsInterface;
use App\Models\Applicant;

final class ProgramRegistry
{
    public function findByApplicant(Applicant $applicant): ProgramRequirementsInterface
    {
        return new DatabaseProgramRequirements($applicant->program);
    }
}
