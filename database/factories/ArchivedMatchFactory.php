<?php

namespace Database\Factories;

use App\Models\ArchivedMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArchivedMatch>
 */
class ArchivedMatchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'match_uuid' => fake()->uuid(),
            'game_slug'  => fake()->slug(2),
            'played_at'  => now(),
            'payload'    => ['score' => fake()->numberBetween(0, 100)],
            'status'     => 'pending',
            'attempts'   => 0,
        ];
    }
}
