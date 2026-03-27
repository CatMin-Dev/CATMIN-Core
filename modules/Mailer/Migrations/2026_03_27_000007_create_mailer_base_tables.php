<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mailer_configs')) {
            Schema::create('mailer_configs', function (Blueprint $table): void {
                $table->id();
                $table->string('driver', 64)->default('smtp');
                $table->string('from_email')->nullable();
                $table->string('from_name')->nullable();
                $table->string('reply_to_email')->nullable();
                $table->boolean('is_enabled')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mailer_templates')) {
            Schema::create('mailer_templates', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 120)->unique();
                $table->string('name');
                $table->string('subject');
                $table->longText('body_html')->nullable();
                $table->longText('body_text')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();

                $table->index('is_enabled');
            });
        }

        if (!Schema::hasTable('mailer_history')) {
            Schema::create('mailer_history', function (Blueprint $table): void {
                $table->id();
                $table->string('recipient');
                $table->string('subject');
                $table->string('template_code', 120)->nullable();
                $table->string('status', 32)->default('pending');
                $table->timestamp('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('sent_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mailer_history');
        Schema::dropIfExists('mailer_templates');
        Schema::dropIfExists('mailer_configs');
    }
};
