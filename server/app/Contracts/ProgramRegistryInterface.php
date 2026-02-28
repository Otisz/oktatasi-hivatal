<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Applicant;

interface ProgramRegistryInterface
{
    public function findByApplicant(Applicant $applicant): ProgramRequirementsInterface;
}
