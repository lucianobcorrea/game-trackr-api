<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserGame;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<UserGame>
 */
class UserGameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'igdb_id' => fake()->unique()->numberBetween(1000, 999999),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'cover_url' => 'https://images.igdb.com/igdb/image/upload/t_cover_big/co'.fake()->lexify('????').'.jpg',
            'rating' => fake()->randomFloat(2, 40, 99),
            'status' => fake()->randomElement(UserGame::STATUSES),
            'hours_played' => fake()->randomFloat(1, 0, 150),
            'is_favorite' => fake()->boolean(20),
        ];
    }
}
