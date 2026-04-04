<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_contacts')) {
            return;
        }

        Schema::table('crm_contacts', function (Blueprint $table): void {
            if (!Schema::hasColumn('crm_contacts', 'pipeline_stage')) {
                $table->string('pipeline_stage', 32)->default('new')->after('status');
            }

            if (!Schema::hasColumn('crm_contacts', 'source')) {
                $table->string('source', 64)->default('manual')->after('pipeline_stage');
            }

            if (!Schema::hasColumn('crm_contacts', 'last_interaction_at')) {
                $table->dateTime('last_interaction_at')->nullable()->after('source');
            }
        });

        Schema::table('crm_contacts', function (Blueprint $table): void {
            $table->index(['pipeline_stage', 'created_at'], 'crm_contacts_pipeline_stage_idx');
            $table->index(['source', 'created_at'], 'crm_contacts_source_idx');
            $table->index('last_interaction_at', 'crm_contacts_last_interaction_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('crm_contacts')) {
            return;
        }

        Schema::table('crm_contacts', function (Blueprint $table): void {
            if (Schema::hasColumn('crm_contacts', 'pipeline_stage')) {
                $table->dropIndex('crm_contacts_pipeline_stage_idx');
                $table->dropColumn('pipeline_stage');
            }

            if (Schema::hasColumn('crm_contacts', 'source')) {
                $table->dropIndex('crm_contacts_source_idx');
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('crm_contacts', 'last_interaction_at')) {
                $table->dropIndex('crm_contacts_last_interaction_idx');
                $table->dropColumn('last_interaction_at');
            }
        });
    }
};
