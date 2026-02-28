<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BasePointCalculatorInterface;
use App\Contracts\BonusPointCalculatorInterface;
use App\Contracts\ProgramRegistryInterface;
use App\Contracts\ProgramRequirementsInterface;
use App\Enums\SubjectName;
use App\Exceptions\MissingElectiveSubjectException;
use App\Exceptions\MissingGlobalMandatorySubjectException;
use App\Exceptions\MissingProgramMandatorySubjectException;
use App\Exceptions\ProgramMandatorySubjectLevelException;
use App\Models\Applicant;
use App\Models\ApplicantBonusPoint;
use App\Models\ApplicantExamResult;
use App\ValueObjects\ExamResult;
use App\ValueObjects\LanguageCertificate;
use App\ValueObjects\Score;

final class AdmissionScoringService
{
    public function __construct(
        private ProgramRegistryInterface $programRegistry,
        private BasePointCalculatorInterface $basePointCalculator,
        private BonusPointCalculatorInterface $bonusPointCalculator,
    ) {}

    public function calculateForApplicant(Applicant $applicant): Score
    {
        // Step 1: Map Eloquent rows to VOs — ExamResult constructor throws FailedExamException if < 20%
        /** @var array<int, ExamResult> $examResults */
        $examResults = $applicant->examResults
            ->map(fn (ApplicantExamResult $row): ExamResult => new ExamResult(
                $row->subject_name,
                $row->level,
                $row->percentage,
            ))
            ->values()
            ->all();

        /** @var array<int, LanguageCertificate> $certificates */
        $certificates = $applicant->bonusPoints
            ->map(fn (ApplicantBonusPoint $row): LanguageCertificate => new LanguageCertificate(
                $row->type,
                $row->language,
            ))
            ->values()
            ->all();

        // Step 2: Global mandatory check
        $this->validateGlobalMandatorySubjects($examResults);

        // Resolve programme requirements (after step 1+2 to preserve exception priority)
        $requirements = $this->programRegistry->findByApplicant($applicant);

        // Step 3: Programme mandatory subject present
        $mandatoryResult = $this->validateProgramMandatorySubject($examResults, $requirements);

        // Step 4: Programme mandatory subject level
        $this->validateMandatoryLevel($mandatoryResult, $requirements);

        // Step 5: Elective subject present — returns the best-scoring elective
        $bestElective = $this->findBestElective($examResults, $requirements);

        // Calculate score
        $basePoints = $this->basePointCalculator->calculate($mandatoryResult, $bestElective);
        $bonusPoints = $this->bonusPointCalculator->calculate($examResults, $certificates);

        return new Score($basePoints, $bonusPoints);
    }

    /**
     * @param  array<int, ExamResult>  $examResults
     */
    private function validateGlobalMandatorySubjects(array $examResults): void
    {
        $subjectNames = array_map(
            fn (ExamResult $r): SubjectName => $r->subject,
            $examResults,
        );

        foreach (SubjectName::globallyMandatory() as $required) {
            if (!in_array($required, $subjectNames, true)) {
                throw new MissingGlobalMandatorySubjectException;
            }
        }
    }

    /**
     * @param  array<int, ExamResult>  $examResults
     */
    private function validateProgramMandatorySubject(
        array $examResults,
        ProgramRequirementsInterface $requirements,
    ): ExamResult {
        $mandatorySubject = $requirements->getMandatorySubject();

        foreach ($examResults as $result) {
            if ($result->subject === $mandatorySubject) {
                return $result;
            }
        }

        throw new MissingProgramMandatorySubjectException($mandatorySubject);
    }

    private function validateMandatoryLevel(
        ExamResult $mandatory,
        ProgramRequirementsInterface $requirements,
    ): void {
        $requiredLevel = $requirements->getMandatorySubjectLevel();

        if (null !== $requiredLevel && !$mandatory->isAdvancedLevel()) {
            throw new ProgramMandatorySubjectLevelException($mandatory->subject, $requiredLevel);
        }
    }

    /**
     * Find the best-scoring elective exam result. First-encountered wins on ties (strict > comparison).
     *
     * @param  array<int, ExamResult>  $examResults
     */
    private function findBestElective(
        array $examResults,
        ProgramRequirementsInterface $requirements,
    ): ExamResult {
        $electiveSubjects = $requirements->getElectiveSubjects();
        $best = null;

        foreach ($examResults as $result) {
            if (in_array($result->subject, $electiveSubjects, true)) {
                if (null === $best || $result->points() > $best->points()) {
                    $best = $result;
                }
            }
        }

        if (null === $best) {
            throw new MissingElectiveSubjectException;
        }

        return $best;
    }
}
