<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@catmin.local'],
            [
                'name' => 'Administrator',
                'password' => \Illuminate\Support\Facades\Hash::make('admin12345'),
            ]
        );

        // Get admin role
        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        
        if ($adminRole && !$admin->roles()->where('role_id', $adminRole->id)->exists()) {
            $admin->assignRole($adminRole->id, null, 'System administrator account');
            $this->command->info('Admin user created/updated and assigned admin role');
        }

        // Create test editor user
        $editor = \App\Models\User::firstOrCreate(
            ['email' => 'editor@catmin.local'],
            [
                'name' => 'Editor',
                'password' => \Illuminate\Support\Facades\Hash::make('editor12345'),
            ]
        );

        $editorRole = \App\Models\Role::where('name', 'editor')->first();
        if ($editorRole && !$editor->roles()->where('role_id', $editorRole->id)->exists()) {
            $editor->assignRole($editorRole->id, $admin->id, 'Test editor account');
            $this->command->info('Editor user created and assigned editor role');
        }

        // Create test viewer user
        $viewer = \App\Models\User::firstOrCreate(
            ['email' => 'viewer@catmin.local'],
            [
                'name' => 'Viewer',
                'password' => \Illuminate\Support\Facades\Hash::make('viewer12345'),
            ]
        );

        $viewerRole = \App\Models\Role::where('name', 'viewer')->first();
        if ($viewerRole && !$viewer->roles()->where('role_id', $viewerRole->id)->exists()) {
            $viewer->assignRole($viewerRole->id, $admin->id, 'Test viewer account');
            $this->command->info('Viewer user created and assigned viewer role');
        }

        $this->command->line('');
        $this->command->info('Initial users seeded successfully:');
        $this->command->line('  - admin@catmin.local (password: admin12345)');
        $this->command->line('  - editor@catmin.local (password: editor12345)');
        $this->command->line('  - viewer@catmin.local (password: viewer12345)');
        $this->command->info('⚠️  Change these credentials after production deployment!');
    }
}
