<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LanguageCertificateType;
use App\Models\Applicant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ApplicantBonusPoint>
 */
final class ApplicantBonusPointFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'applicant_id' => Applicant::factory(),
            'category' => 'Nyelvvizsga',
            'type' => fake()->randomElement(LanguageCertificateType::cases())->value,
            'language' => fake()->randomElement(['angol', 'német', 'francia', 'olasz', 'orosz', 'spanyol']),
        ];
    }

    public function b2Certificate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => LanguageCertificateType::UpperIntermediate->value,
        ]);
    }

    public function c1Certificate(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => LanguageCertificateType::Advanced->value,
        ]);
    }

    public function forLanguage(string $language): static
    {
        return $this->state(fn (array $attributes): array => [
            'language' => $language,
        ]);
    }
}
