<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

/**
 * MigrationCollisionService
 *
 * Detects migration filename collisions between module and addon migration
 * directories. Laravel tracks migrations by basename in the migrations table,
 * so duplicate filenames across sources can cause disorder.
 */
class MigrationCollisionService
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function detectBasenameCollisions(): array
    {
        $map = [];

        foreach (self::allMigrationFiles() as $filePath) {
            $base = basename($filePath);
            $map[$base] = $map[$base] ?? [];
            $map[$base][] = $filePath;
        }

        return collect($map)
            ->filter(fn (array $paths) => count($paths) > 1)
            ->sortKeys()
            ->toArray();
    }

    /**
     * @return Collection<int, string>
     */
    public static function allMigrationFiles(): Collection
    {
        $files = collect();

        foreach (ModuleManager::all() as $module) {
            $path = base_path('modules/' . $module->directory . '/Migrations');
            if (File::exists($path)) {
                foreach (File::files($path) as $file) {
                    if (strtolower($file->getExtension()) === 'php') {
                        $files->push($file->getPathname());
                    }
                }
            }
        }

        foreach (AddonManager::all() as $addon) {
            $path = $addon->path . '/Migrations';
            if (File::exists($path)) {
                foreach (File::files($path) as $file) {
                    if (strtolower($file->getExtension()) === 'php') {
                        $files->push($file->getPathname());
                    }
                }
            }
        }

        return $files->values();
    }
}
