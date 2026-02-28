<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ApplicantResource;
use App\Http\Resources\ScoreResource;
use App\Models\Applicant;
use App\Services\AdmissionScoringService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ApplicantController extends Controller
{
    public function __construct(
        private readonly AdmissionScoringService $scoringService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return ApplicantResource::collection(
            Applicant::query()->with('program')->get()
        );
    }

    public function score(Applicant $applicant): ScoreResource
    {
        $applicant->load('program.subjects', 'examResults', 'bonusPoints');

        $score = $this->scoringService->calculateForApplicant($applicant);

        return new ScoreResource($score);
    }
}
