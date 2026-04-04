<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('event_tickets')) {
            Schema::table('event_tickets', function (Blueprint $table): void {
                if (!Schema::hasColumn('event_tickets', 'participant_id')) {
                    $table->unsignedBigInteger('participant_id')->nullable()->after('event_participant_id');
                }
                if (!Schema::hasColumn('event_tickets', 'source')) {
                    $table->string('source', 20)->default('manual')->after('participant_id');
                }
                if (!Schema::hasColumn('event_tickets', 'code')) {
                    $table->string('code', 80)->nullable()->after('ticket_number');
                }
                if (!Schema::hasColumn('event_tickets', 'token')) {
                    $table->string('token', 120)->nullable()->after('code');
                }
                if (!Schema::hasColumn('event_tickets', 'qr_payload')) {
                    $table->text('qr_payload')->nullable()->after('qr_code');
                }
                if (!Schema::hasColumn('event_tickets', 'used_at')) {
                    $table->dateTime('used_at')->nullable()->after('issued_at');
                }
            });

            DB::table('event_tickets')->whereNull('code')->update(['code' => DB::raw('ticket_number')]);
            DB::table('event_tickets')->where('status', 'active')->update(['status' => 'issued']);
            DB::table('event_tickets')->whereNull('used_at')->whereNotNull('checkin_at')->update(['used_at' => DB::raw('checkin_at')]);

            Schema::table('event_tickets', function (Blueprint $table): void {
                if (!Schema::hasColumn('event_tickets', 'code')) {
                    return;
                }
                $table->unique('code', 'event_tickets_code_unique');
                $table->unique('token', 'event_tickets_token_unique');
                $table->index(['event_id', 'source'], 'event_tickets_event_source_idx');
            });
        }

        if (Schema::hasTable('event_checkins')) {
            Schema::table('event_checkins', function (Blueprint $table): void {
                if (!Schema::hasColumn('event_checkins', 'ticket_id')) {
                    $table->unsignedBigInteger('ticket_id')->nullable()->after('event_ticket_id');
                }
                if (!Schema::hasColumn('event_checkins', 'checked_in_by')) {
                    $table->unsignedBigInteger('checked_in_by')->nullable()->after('ticket_id');
                }
                if (!Schema::hasColumn('event_checkins', 'checked_in_at')) {
                    $table->dateTime('checked_in_at')->nullable()->after('checked_in_by');
                }
                if (!Schema::hasColumn('event_checkins', 'location')) {
                    $table->string('location', 120)->nullable()->after('checked_in_at');
                }
            });

            DB::table('event_checkins')->whereNull('ticket_id')->update(['ticket_id' => DB::raw('event_ticket_id')]);
            DB::table('event_checkins')->whereNull('checked_in_by')->update(['checked_in_by' => DB::raw('admin_user_id')]);
            DB::table('event_checkins')->whereNull('checked_in_at')->update(['checked_in_at' => DB::raw('checkin_at')]);
        }
    }

    public function down(): void
    {
        // Intentionally no destructive down migration for additive columns.
    }
};
