<?php

declare(strict_types=1);

use App\Contracts\ProgramRequirementsInterface;
use App\Models\Applicant;
use App\Models\Program;
use App\Services\DatabaseProgramRequirements;
use App\Services\ProgramRegistry;
use Illuminate\Database\Eloquent\Collection;

it('returns DatabaseProgramRequirements for an applicant', function (): void {
    $program = new Program;
    $program->setRelation('subjects', Collection::make([]));

    $applicant = new Applicant;
    $applicant->setRelation('program', $program);

    $registry = new ProgramRegistry;
    $result = $registry->findByApplicant($applicant);

    expect($result)
        ->toBeInstanceOf(DatabaseProgramRequirements::class)
        ->toBeInstanceOf(ProgramRequirementsInterface::class);
});
