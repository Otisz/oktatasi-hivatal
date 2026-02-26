<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;

interface ProgramRequirementsInterface
{
    public function getMandatorySubject(): SubjectName;

    /** @return array<int, SubjectName> */
    public function getElectiveSubjects(): array;

    public function getMandatorySubjectLevel(): ?ExamLevel;
}
