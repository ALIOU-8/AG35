<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'group',
            'name' => fake()->words(2, true),
            'created_by' => User::factory()->active(),
        ];
    }

    public function direct(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'direct',
            'name' => null,
        ]);
    }
}
