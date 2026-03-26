<?php

namespace Database\Seeders;

use App\Services\SettingService;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'site.name',
                'value' => 'CATMIN',
                'type' => 'string',
                'group' => 'site',
                'description' => 'Displayed project name',
                'is_public' => true,
            ],
            [
                'key' => 'site.url',
                'value' => config('app.url'),
                'type' => 'string',
                'group' => 'site',
                'description' => 'Public base URL',
                'is_public' => true,
            ],
            [
                'key' => 'admin.theme',
                'value' => 'catmin-light',
                'type' => 'string',
                'group' => 'admin',
                'description' => 'Default admin theme token',
                'is_public' => false,
            ],
            [
                'key' => 'admin.path',
                'value' => config('catmin.admin.path'),
                'type' => 'string',
                'group' => 'admin',
                'description' => 'Current admin path prefix',
                'is_public' => false,
            ],
            [
                'key' => 'site.frontend_enabled',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'site',
                'description' => 'Enable lightweight frontend foundation',
                'is_public' => true,
            ],
        ];

        foreach ($settings as $setting) {
            SettingService::put(
                $setting['key'],
                $setting['value'],
                $setting['type'],
                $setting['group'],
                $setting['description'],
                $setting['is_public']
            );
        }

        $this->command->info('Global settings seeded successfully.');
    }
}
