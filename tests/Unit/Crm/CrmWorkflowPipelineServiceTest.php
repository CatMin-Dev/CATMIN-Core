<?php

namespace Tests\Unit\Crm;

use Addons\CatminCrmLight\Models\CrmCompany;
use Addons\CatminCrmLight\Models\CrmContact;
use Addons\CatminCrmLight\Services\CrmPipelineService;
use Addons\CatminCrmLight\Services\CrmRelationService;
use Addons\CatminCrmLight\Services\CrmWorkflowService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrmWorkflowPipelineServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available.');
        }

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        app('db')->purge('sqlite');
        app('db')->reconnect('sqlite');

        $this->createTables();
    }

    public function test_pipeline_move_updates_stage_and_legacy_status(): void
    {
        $pipeline = app(CrmPipelineService::class);

        $contact = CrmContact::query()->create([
            'first_name' => 'Lina',
            'email' => 'lina@example.test',
            'status' => 'lead',
            'pipeline_stage' => 'new',
        ]);

        $updated = $pipeline->move($contact, 'won');

        $this->assertSame('won', $updated->pipeline_stage);
        $this->assertSame('active', $updated->status);
    }

    public function test_workflow_add_interaction_and_complete_task(): void
    {
        $workflow = app(CrmWorkflowService::class);
        $relations = app(CrmRelationService::class);

        $company = CrmCompany::query()->create(['name' => 'Acme']);
        $contact = CrmContact::query()->create([
            'first_name' => 'Mia',
            'email' => 'mia@example.test',
            'status' => 'lead',
            'pipeline_stage' => 'new',
        ]);

        $contact = $relations->attachContactToCompany($contact, (int) $company->id);

        $interaction = $workflow->addInteraction($contact, [
            'type' => 'call',
            'content' => 'Qualification call done.',
        ]);

        $task = $workflow->createTask($contact, [
            'title' => 'Send proposal',
            'details' => 'Send pricing PDF',
        ]);

        $doneTask = $workflow->completeTask($task);

        $this->assertSame('call', $interaction->type);
        $this->assertSame((int) $company->id, (int) $interaction->crm_company_id);
        $this->assertSame('done', $doneTask->status);
        $this->assertNotNull($doneTask->completed_at);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('crm_companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->timestamps();
        });

        Schema::create('crm_contacts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('crm_company_id')->nullable();
            $table->string('first_name', 120);
            $table->string('last_name', 120)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('position', 120)->nullable();
            $table->string('status', 32)->default('lead');
            $table->string('pipeline_stage', 32)->default('new');
            $table->string('source', 60)->nullable();
            $table->text('tags')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_interactions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('crm_contact_id');
            $table->unsignedBigInteger('crm_company_id')->nullable();
            $table->string('type', 40)->default('note');
            $table->string('subject', 191)->nullable();
            $table->text('content');
            $table->string('source', 60)->default('crm');
            $table->timestamp('happened_at')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('crm_contact_id');
            $table->unsignedBigInteger('crm_company_id')->nullable();
            $table->string('title', 191);
            $table->text('details')->nullable();
            $table->string('status', 32)->default('open');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('assigned_to_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });
    }
}
