<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ExamLevel;
use App\Enums\SubjectName;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ApplicantExamResult>
 */
final class ApplicantExamResultFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'subject_name' => fake()->randomElement(SubjectName::cases())->value,
            'level' => fake()->randomElement(ExamLevel::cases())->value,
            'percentage' => fake()->numberBetween(20, 100),
        ];
    }

    public function failingExam(): static
    {
        return $this->state(fn (array $attributes): array => [
            'percentage' => fake()->numberBetween(0, 19),
        ]);
    }

    public function advancedLevel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => ExamLevel::Advanced->value,
        ]);
    }

    public function intermediateLevel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'level' => ExamLevel::Intermediate->value,
        ]);
    }

    public function forSubject(SubjectName $subject): static
    {
        return $this->state(fn (array $attributes): array => [
            'subject_name' => $subject->value,
        ]);
    }

    public function withPercentage(int $percentage): static
    {
        return $this->state(fn (array $attributes): array => [
            'percentage' => $percentage,
        ]);
    }
}
