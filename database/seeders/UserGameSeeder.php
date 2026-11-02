<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserGame;
use Illuminate\Database\Seeder;

class UserGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(5)->get();

        foreach ($users as $user) {
            foreach (UserGame::STATUSES as $status) {
                UserGame::factory()->create([
                    'user_id' => $user->id,
                    'status' => $status,
                ]);
            }
        }
    }
}
