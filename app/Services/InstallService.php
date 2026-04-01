<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstallService
{
    private string $envPath;

    public function __construct()
    {
        $this->envPath = base_path('.env');
    }

    /**
     * Check if application is already installed
     */
    public static function isInstalled(): bool
    {
        try {
            // Check if we have a database connection and at least one admin user
            $adminCount = DB::table('admin_users')->count();
            return $adminCount > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Update .env file with new configuration
     */
    public function updateEnvFile(array $config): bool
    {
        try {
            if (!File::exists($this->envPath)) {
                return false;
            }

            $envContent = File::get($this->envPath);

            // Update each configuration value
            foreach ($config as $key => $value) {
                $pattern = "/^{$key}=.*/m";
                if (preg_match($pattern, $envContent)) {
                    $envContent = preg_replace($pattern, "{$key}={$value}", $envContent);
                } else {
                    // Append new key if it doesn't exist
                    $envContent .= "\n{$key}={$value}";
                }
            }

            File::put($this->envPath, $envContent);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Test database connection with provided credentials
     */
    public function testDatabaseConnection(array $credentials): array
    {
        try {
            $config = [
                'driver' => $credentials['DB_CONNECTION'] ?? 'mysql',
                'host' => $credentials['DB_HOST'] ?? 'localhost',
                'port' => $credentials['DB_PORT'] ?? 3306,
                'database' => $credentials['DB_DATABASE'] ?? '',
                'username' => $credentials['DB_USERNAME'] ?? '',
                'password' => $credentials['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ];

            // Try to connect
            $pdo = new \PDO(
                "mysql:host={$config['host']}:{$config['port']}",
                $config['username'],
                $config['password']
            );

            // Check if database exists
            $pdo->exec("USE {$config['database']}");

            return [
                'ok' => true,
                'message' => 'Connexion réussie.',
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Erreur connexion: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Run database migrations
     */
    public function runMigrations(): bool
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Seed initial roles and permissions
     */
    public function seedRolesAndPermissions(): bool
    {
        try {
            // Create default roles if they don't exist
            $roles = [
                'super_admin' => 'Super Administrator',
                'admin' => 'Administrator',
                'editor' => 'Editor',
                'viewer' => 'Viewer',
            ];

            foreach ($roles as $key => $name) {
                Role::firstOrCreate(
                    ['slug' => $key],
                    ['name' => $name, 'description' => "Role {$name}"]
                );
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Create initial admin user
     */
    public function createAdminUser(array $data): array
    {
        try {
            // Validate input
            if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
                return [
                    'ok' => false,
                    'message' => 'Email, password, et name sont requis.',
                ];
            }

            if (strlen($data['password']) < 12) {
                return [
                    'ok' => false,
                    'message' => 'Password doit faire minimum 12 caractères.',
                ];
            }

            // Check if user already exists
            if (AdminUser::where('email', $data['email'])->exists()) {
                return [
                    'ok' => false,
                    'message' => 'Un utilisateur avec cet email existe déjà.',
                ];
            }

            // Create admin user
            $admin = AdminUser::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => now(),
                'role_id' => Role::where('slug', 'super_admin')->first()?->id ?? 1,
                'is_active' => true,
                'two_factor_enabled' => false,
            ]);

            return [
                'ok' => true,
                'message' => 'Administrateur créé avec succès.',
                'admin_id' => $admin->id,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Erreur création admin: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate APP_KEY if not set
     */
    public function generateAppKey(): bool
    {
        try {
            if (!config('app.key')) {
                \Illuminate\Support\Facades\Artisan::call('key:generate', ['--force' => true]);
                return true;
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Compile configuration
     */
    public function compileConfig(): bool
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('config:cache');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Mark installation as complete
     */
    public function markInstallationComplete(): bool
    {
        try {
            // Create a marker file or update setting
            $setting = \App\Models\Setting::firstOrCreate(
                ['key' => 'installation_complete'],
                ['value' => json_encode(['completed_at' => now(), 'version' => '3.0.0'])]
            );
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get installation status
     */
    public function getInstallationStatus(): array
    {
        return [
            'system_checks' => InstallCheckService::run(),
            'is_installed' => self::isInstalled(),
            'env_exists' => File::exists($this->envPath),
        ];
    }
}
