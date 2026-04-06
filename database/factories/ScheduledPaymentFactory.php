<?php

namespace Database\Factories;

use App\Models\ScheduledPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduledPayment>
 */
class ScheduledPaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, -500, -10),
            'date' => fake()->dateTimeBetween('now', '+3 months'),
            'is_completed' => false,
        ];
    }

    public function income(): static
    {
        return $this->state(fn () => ['amount' => fake()->randomFloat(2, 100, 5000)]);
    }

    public function completed(): static
    {
        return $this->state(fn () => ['is_completed' => true]);
    }
}
