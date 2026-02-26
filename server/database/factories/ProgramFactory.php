<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Program>
 */
final class ProgramFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'university' => fake()->company(),
            'faculty' => fake()->word(),
            'name' => fake()->sentence(3),
        ];
    }
}
