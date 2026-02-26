<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExamLevel;
use App\Enums\RequirementType;
use App\Enums\SubjectName;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ProgramSubject>
 */
final class ProgramSubjectFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'subject_name' => fake()->randomElement(SubjectName::cases())->value,
            'requirement_type' => fake()->randomElement(RequirementType::cases())->value,
            'required_level' => null,
        ];
    }

    public function mandatory(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requirement_type' => RequirementType::Mandatory->value,
        ]);
    }

    public function elective(): static
    {
        return $this->state(fn (array $attributes): array => [
            'requirement_type' => RequirementType::Elective->value,
        ]);
    }

    public function requiredAdvancedLevel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'required_level' => ExamLevel::Advanced->value,
        ]);
    }

    public function forSubject(SubjectName $subject): static
    {
        return $this->state(fn (array $attributes): array => [
            'subject_name' => $subject->value,
        ]);
    }
}
