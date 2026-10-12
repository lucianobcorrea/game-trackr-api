<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserSetup;
use Illuminate\Database\Seeder;

class UserSetupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::take(5)->get();

        foreach ($users as $user) {
            UserSetup::factory()->count(2)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
