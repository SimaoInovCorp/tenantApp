<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        // Default test user
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'email_verified_at' => now()]
        );

        // Main developer account
        User::updateOrCreate(
            ['email' => 'spmmazb@gmail.com'],
            [
                'name'              => 'Simao Morais',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}
