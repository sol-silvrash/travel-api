<?php

namespace Database\Factories;

use App\Models\Travel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tour>
 */
class TourFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $current = now();
        $starting_date = $current->copy()->addDays(rand(1, 10));
        $ending_date = $starting_date->copy()->addDays(rand(1, 10));

        return [
            'travel_id' => Travel::inRandomOrder()->first()->id,
            'name' => fake()->text(20),
            'starting_date' => $starting_date,
            'ending_date' => $ending_date,
            'price_in_cents' => rand(10, 999),
        ];
    }
}
