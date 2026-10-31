<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'school' => fake()->randomElement(['Université de Rennes', 'INSA Rennes', 'Rennes School of Business']),
            'field_of_study' => fake()->randomElement(['Informatique', 'Droit', 'Économie', 'Biologie']),
            'study_level' => fake()->randomElement(User::STUDY_LEVELS),
            'origin_city' => fake()->randomElement(['Conakry', 'Kankan', 'Labé', 'Kindia']),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Membre validé par le bureau : accès complet à l'espace membres.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function bureau(string $title = 'Secrétaire'): static
    {
        return $this->active()->state(fn (array $attributes) => [
            'role' => 'bureau',
            'bureau_title' => $title,
        ]);
    }

    public function admin(): static
    {
        return $this->active()->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }
}
