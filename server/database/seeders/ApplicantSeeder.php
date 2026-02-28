<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ExamLevel;
use App\Enums\LanguageCertificateType;
use App\Enums\SubjectName;
use App\Models\Applicant;
use Illuminate\Database\Seeder;

final class ApplicantSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Applicant 1 — expected outcome: 470 points (370 base + 100 bonus).
         */
        $applicant1 = Applicant::query()->create([
            'id' => Applicant::CASE_1_UUID,
            'program_id' => ProgramSeeder::ELTE_IK_UUID,
        ]);

        $applicant1->examResults()->create(['subject_name' => SubjectName::HungarianLanguageAndLiterature, 'level' => ExamLevel::Intermediate, 'percentage' => 70]);
        $applicant1->examResults()->create(['subject_name' => SubjectName::History, 'level' => ExamLevel::Intermediate, 'percentage' => 80]);
        $applicant1->examResults()->create(['subject_name' => SubjectName::Mathematics, 'level' => ExamLevel::Advanced, 'percentage' => 90]);
        $applicant1->examResults()->create(['subject_name' => SubjectName::EnglishLanguage, 'level' => ExamLevel::Intermediate, 'percentage' => 94]);
        $applicant1->examResults()->create(['subject_name' => SubjectName::Informatics, 'level' => ExamLevel::Intermediate, 'percentage' => 95]);

        $applicant1->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::UpperIntermediate, 'language' => 'angol']);
        $applicant1->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::Advanced, 'language' => 'német']);

        /**
         * Applicant 2 — expected outcome: 476 points (376 base + 100 bonus).
         * Same as Applicant 1 plus fizika at közép 98%.
         */
        $applicant2 = Applicant::query()->create([
            'id' => Applicant::CASE_2_UUID,
            'program_id' => ProgramSeeder::ELTE_IK_UUID,
        ]);

        $applicant2->examResults()->create(['subject_name' => SubjectName::HungarianLanguageAndLiterature, 'level' => ExamLevel::Intermediate, 'percentage' => 70]);
        $applicant2->examResults()->create(['subject_name' => SubjectName::History, 'level' => ExamLevel::Intermediate, 'percentage' => 80]);
        $applicant2->examResults()->create(['subject_name' => SubjectName::Mathematics, 'level' => ExamLevel::Advanced, 'percentage' => 90]);
        $applicant2->examResults()->create(['subject_name' => SubjectName::EnglishLanguage, 'level' => ExamLevel::Intermediate, 'percentage' => 94]);
        $applicant2->examResults()->create(['subject_name' => SubjectName::Informatics, 'level' => ExamLevel::Intermediate, 'percentage' => 95]);
        $applicant2->examResults()->create(['subject_name' => SubjectName::Physics, 'level' => ExamLevel::Intermediate, 'percentage' => 98]);

        $applicant2->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::UpperIntermediate, 'language' => 'angol']);
        $applicant2->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::Advanced, 'language' => 'német']);

        /**
         * Applicant 3 — expected outcome: MissingGlobalMandatorySubjectException.
         * Only 3 exams — missing magyar nyelv és irodalom and történelem.
         */
        $applicant3 = Applicant::query()->create([
            'id' => Applicant::CASE_3_UUID,
            'program_id' => ProgramSeeder::ELTE_IK_UUID,
        ]);

        $applicant3->examResults()->create(['subject_name' => SubjectName::Mathematics, 'level' => ExamLevel::Advanced, 'percentage' => 90]);
        $applicant3->examResults()->create(['subject_name' => SubjectName::EnglishLanguage, 'level' => ExamLevel::Intermediate, 'percentage' => 94]);
        $applicant3->examResults()->create(['subject_name' => SubjectName::Informatics, 'level' => ExamLevel::Intermediate, 'percentage' => 95]);

        $applicant3->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::UpperIntermediate, 'language' => 'angol']);
        $applicant3->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::Advanced, 'language' => 'német']);

        /**
         * Applicant 4 — expected outcome: FailedExamException (magyar nyelv és irodalom at 15%, below 20% threshold).
         */
        $applicant4 = Applicant::query()->create([
            'id' => Applicant::CASE_4_UUID,
            'program_id' => ProgramSeeder::ELTE_IK_UUID,
        ]);

        $applicant4->examResults()->create(['subject_name' => SubjectName::HungarianLanguageAndLiterature, 'level' => ExamLevel::Intermediate, 'percentage' => 15]);
        $applicant4->examResults()->create(['subject_name' => SubjectName::History, 'level' => ExamLevel::Intermediate, 'percentage' => 80]);
        $applicant4->examResults()->create(['subject_name' => SubjectName::Mathematics, 'level' => ExamLevel::Advanced, 'percentage' => 90]);
        $applicant4->examResults()->create(['subject_name' => SubjectName::EnglishLanguage, 'level' => ExamLevel::Intermediate, 'percentage' => 94]);
        $applicant4->examResults()->create(['subject_name' => SubjectName::Informatics, 'level' => ExamLevel::Intermediate, 'percentage' => 95]);

        $applicant4->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::UpperIntermediate, 'language' => 'angol']);
        $applicant4->bonusPoints()->create(['category' => 'Nyelvvizsga', 'type' => LanguageCertificateType::Advanced, 'language' => 'német']);
    }
}
