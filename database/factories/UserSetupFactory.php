<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserSetup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSetup>
 */
class UserSetupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
        ];
    }
}
