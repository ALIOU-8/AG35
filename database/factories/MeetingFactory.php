<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'scheduled_at' => now()->addDays(3)->setTime(18, 0),
            'created_by' => User::factory()->active(),
        ];
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now()->subHour(),
        ]);
    }
}
