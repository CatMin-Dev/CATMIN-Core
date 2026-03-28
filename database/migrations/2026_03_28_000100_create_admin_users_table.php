<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_users')) {
            Schema::create('admin_users', function (Blueprint $table): void {
                $table->id();
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_super_admin')->default(false);
                $table->timestamp('last_login_at')->nullable();
                $table->unsignedInteger('failed_login_attempts')->default(0);
                $table->timestamp('locked_until')->nullable();
                $table->json('metadata')->nullable(); // 2FA settings, preferences, etc.
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Seed default super-admin from legacy config if not exists
        $username = config('catmin.admin.username', 'admin');
        $password = config('catmin.admin.password', 'admin12345');

        if ($username && $password && Schema::hasTable('admin_users')) {
            $exists = \DB::table('admin_users')->where('username', $username)->first();

            if (!$exists) {
                \DB::table('admin_users')->insert([
                    'username' => $username,
                    'email' => $username . '@catmin.local',
                    'password' => Hash::make($password),
                    'is_super_admin' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
