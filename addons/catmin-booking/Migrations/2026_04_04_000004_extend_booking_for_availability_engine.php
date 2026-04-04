<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('booking_services')) {
            Schema::table('booking_services', function (Blueprint $table): void {
                if (!Schema::hasColumn('booking_services', 'buffer_before_minutes')) {
                    $table->unsignedSmallInteger('buffer_before_minutes')->default(0)->after('duration_minutes');
                }

                if (!Schema::hasColumn('booking_services', 'buffer_after_minutes')) {
                    $table->unsignedSmallInteger('buffer_after_minutes')->default(0)->after('buffer_before_minutes');
                }
            });
        }

        if (Schema::hasTable('booking_slots')) {
            Schema::table('booking_slots', function (Blueprint $table): void {
                if (!Schema::hasColumn('booking_slots', 'status')) {
                    $table->string('status', 30)->default('open')->after('booked_count');
                }

                if (!Schema::hasColumn('booking_slots', 'allow_overbooking')) {
                    $table->boolean('allow_overbooking')->default(false)->after('status');
                }

                if (!Schema::hasColumn('booking_slots', 'blocked_reason')) {
                    $table->string('blocked_reason', 255)->nullable()->after('allow_overbooking');
                }
            });

            Schema::table('booking_slots', function (Blueprint $table): void {
                $table->index(['status', 'start_at'], 'booking_slots_status_start_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('booking_slots')) {
            Schema::table('booking_slots', function (Blueprint $table): void {
                if (Schema::hasColumn('booking_slots', 'status')) {
                    $table->dropIndex('booking_slots_status_start_idx');
                    $table->dropColumn('status');
                }

                if (Schema::hasColumn('booking_slots', 'allow_overbooking')) {
                    $table->dropColumn('allow_overbooking');
                }

                if (Schema::hasColumn('booking_slots', 'blocked_reason')) {
                    $table->dropColumn('blocked_reason');
                }
            });
        }

        if (Schema::hasTable('booking_services')) {
            Schema::table('booking_services', function (Blueprint $table): void {
                if (Schema::hasColumn('booking_services', 'buffer_before_minutes')) {
                    $table->dropColumn('buffer_before_minutes');
                }

                if (Schema::hasColumn('booking_services', 'buffer_after_minutes')) {
                    $table->dropColumn('buffer_after_minutes');
                }
            });
        }
    }
};
