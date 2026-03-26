<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin account
        Admin::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@catmin.local',
                'password' => Hash::make(env('CATMIN_ADMIN_PASSWORD', 'admin12345')),
                'first_name' => 'Administrator',
                'last_name' => 'System',
                'role' => 'admin',
                'permissions' => ['*'], // All permissions
                'is_active' => true,
            ]
        );

        // Create test moderator (optional)
        Admin::firstOrCreate(
            ['username' => 'moderator'],
            [
                'email' => 'moderator@catmin.local',
                'password' => Hash::make('moderator12345'),
                'first_name' => 'Moderator',
                'last_name' => 'Test',
                'role' => 'moderator',
                'permissions' => ['view', 'edit_own'],
                'is_active' => true,
            ]
        );

        $this->command->info('Admin accounts seeded successfully.');
    }
}
