<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Role::upsert([
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access with all permissions',
                'permissions' => json_encode(['*']), // All permissions
                'priority' => 100,
                'is_system' => true,  // Cannot be deleted
                'is_active' => true,
            ],
            [
                'name' => 'editor',
                'display_name' => 'Editor',
                'description' => 'Can create and edit content',
                'permissions' => json_encode(['create', 'read', 'update', 'publish', 'manage_own']),
                'priority' => 50,
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'viewer',
                'display_name' => 'Viewer',
                'description' => 'Can only view and read content',
                'permissions' => json_encode(['read']),
                'priority' => 10,
                'is_system' => false,
                'is_active' => true,
            ],
            [
                'name' => 'moderator',
                'display_name' => 'Moderator',
                'description' => 'Can moderate content and users',
                'permissions' => json_encode(['read', 'update', 'moderate', 'manage_own']),
                'priority' => 25,
                'is_system' => false,
                'is_active' => true,
            ],
        ], ['name']);

        $this->command->info('Roles seeded successfully:');
        $this->command->line('  - admin (full access, system role)');
        $this->command->line('  - editor (create, read, update, publish)');
        $this->command->line('  - viewer (read only)');
        $this->command->line('  - moderator (read, update, moderate)');
    }
}
