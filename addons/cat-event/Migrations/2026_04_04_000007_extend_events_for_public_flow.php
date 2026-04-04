<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table): void {
                if (!Schema::hasColumn('events', 'participation_mode')) {
                    $table->string('participation_mode', 40)
                        ->default('free_registration')
                        ->after('registration_enabled');
                }

                if (!Schema::hasColumn('events', 'external_url')) {
                    $table->string('external_url', 500)
                        ->nullable()
                        ->after('participation_mode');
                }

                if (!Schema::hasColumn('events', 'allow_waitlist')) {
                    $table->boolean('allow_waitlist')
                        ->default(false)
                        ->after('external_url');
                }

                if (!Schema::hasColumn('events', 'max_places_per_registration')) {
                    $table->unsignedSmallInteger('max_places_per_registration')
                        ->default(1)
                        ->after('allow_waitlist');
                }
            });
        }

        if (Schema::hasTable('event_participants')) {
            Schema::table('event_participants', function (Blueprint $table): void {
                if (!Schema::hasColumn('event_participants', 'seats_count')) {
                    $table->unsignedSmallInteger('seats_count')
                        ->default(1)
                        ->after('phone');
                }

                if (!Schema::hasColumn('event_participants', 'source')) {
                    $table->string('source', 40)
                        ->default('admin')
                        ->after('status');
                }

                if (!Schema::hasColumn('event_participants', 'idempotency_key')) {
                    $table->string('idempotency_key', 128)
                        ->nullable()
                        ->after('source');
                }
            });

            Schema::table('event_participants', function (Blueprint $table): void {
                $table->index(['event_id', 'idempotency_key'], 'event_participants_event_idempotency_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('event_participants')) {
            Schema::table('event_participants', function (Blueprint $table): void {
                if (Schema::hasColumn('event_participants', 'idempotency_key')) {
                    $table->dropIndex('event_participants_event_idempotency_idx');
                    $table->dropColumn('idempotency_key');
                }

                if (Schema::hasColumn('event_participants', 'source')) {
                    $table->dropColumn('source');
                }

                if (Schema::hasColumn('event_participants', 'seats_count')) {
                    $table->dropColumn('seats_count');
                }
            });
        }

        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table): void {
                if (Schema::hasColumn('events', 'max_places_per_registration')) {
                    $table->dropColumn('max_places_per_registration');
                }

                if (Schema::hasColumn('events', 'allow_waitlist')) {
                    $table->dropColumn('allow_waitlist');
                }

                if (Schema::hasColumn('events', 'external_url')) {
                    $table->dropColumn('external_url');
                }

                if (Schema::hasColumn('events', 'participation_mode')) {
                    $table->dropColumn('participation_mode');
                }
            });
        }
    }
};
