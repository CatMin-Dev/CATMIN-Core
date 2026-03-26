<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create base roles first
        $this->call([
            RoleSeeder::class,
            SettingSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
        ]);

        // Optionally create factory users
        // User::factory(10)->create();
    }
}
