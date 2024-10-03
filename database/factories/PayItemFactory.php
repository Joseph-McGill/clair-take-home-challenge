<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayItem>
 */
class PayItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => fake()->randomFloat(2),
            'hours' => fake()->randomFloat(),
            'rate' => fake()->randomFloat(2),
            'item_date' => fake()->date(),
            'external_id' => fake()->unique()->uuid(),
        ];
    }
}
