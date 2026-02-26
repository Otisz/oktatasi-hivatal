<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use App\Models\Program;
use Illuminate\Database\Seeder;

final class ProgramSeeder extends Seeder
{
    public const string ELTE_IK_UUID = '0195a1b2-0000-7000-8000-000000000101';

    public const string PPKE_BTK_UUID = '0195a1b2-0000-7000-8000-000000000102';

    public function run(): void
    {
        $elteIk = Program::create([
            'id' => self::ELTE_IK_UUID,
            'university' => 'ELTE',
            'faculty' => 'IK',
            'name' => 'Programtervező informatikus',
        ]);

        $elteIk->subjects()->create([
            'subject_name' => SubjectName::Mathematics,
            'requirement_type' => RequirementType::Mandatory,
            'required_level' => null,
        ]);

        $elteIk->subjects()->create([
            'subject_name' => SubjectName::Biology,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $elteIk->subjects()->create([
            'subject_name' => SubjectName::Physics,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $elteIk->subjects()->create([
            'subject_name' => SubjectName::Informatics,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $elteIk->subjects()->create([
            'subject_name' => SubjectName::Chemistry,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $ppkeBtk = Program::create([
            'id' => self::PPKE_BTK_UUID,
            'university' => 'PPKE',
            'faculty' => 'BTK',
            'name' => 'Anglisztika',
        ]);

        $ppkeBtk->subjects()->create([
            'subject_name' => SubjectName::EnglishLanguage,
            'requirement_type' => RequirementType::Mandatory,
            'required_level' => ExamLevel::Advanced,
        ]);

        $ppkeBtk->subjects()->create([
            'subject_name' => SubjectName::FrenchLanguage,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $ppkeBtk->subjects()->create([
            'subject_name' => SubjectName::GermanLanguage,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $ppkeBtk->subjects()->create([
            'subject_name' => SubjectName::ItalianLanguage,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $ppkeBtk->subjects()->create([
            'subject_name' => SubjectName::RussianLanguage,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $ppkeBtk->subjects()->create([
            'subject_name' => SubjectName::SpanishLanguage,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);

        $ppkeBtk->subjects()->create([
            'subject_name' => SubjectName::History,
            'requirement_type' => RequirementType::Elective,
            'required_level' => null,
        ]);
    }
}
