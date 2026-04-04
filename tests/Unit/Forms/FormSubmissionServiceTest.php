<?php

namespace Tests\Unit\Forms;

use Addons\CatminForms\Models\FormDefinition;
use Addons\CatminForms\Services\FormSubmissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FormSubmissionServiceTest extends TestCase
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

    public function test_submission_maps_to_crm_when_mapping_is_crm_lead(): void
    {
        $service = app(FormSubmissionService::class);

        $form = FormDefinition::query()->create([
            'name' => 'Contact',
            'slug' => 'contact',
            'type' => 'lead',
            'status' => 'active',
            'config' => ['mapping' => 'crm_lead'],
        ]);

        $submission = $service->submit($form, [
            'name' => 'Alice Doe',
            'email' => 'alice@example.test',
            'phone' => '0102030405',
            'message' => 'Need a demo',
        ]);

        $this->assertSame('new', $submission->status);
        $this->assertNotNull($submission->linked_contact_id);

        $this->assertDatabaseHas('crm_contacts', [
            'id' => $submission->linked_contact_id,
            'email' => 'alice@example.test',
            'pipeline_stage' => 'new',
            'source' => 'forms',
        ]);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('form_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->string('type', 40)->default('custom');
            $table->string('status', 20)->default('active');
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('form_definition_id');
            $table->json('payload');
            $table->string('source', 40)->default('public');
            $table->string('status', 40)->default('new');
            $table->unsignedBigInteger('linked_contact_id')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('crm_contacts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('crm_company_id')->nullable();
            $table->string('first_name', 120);
            $table->string('last_name', 120)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('status', 32)->default('lead');
            $table->string('pipeline_stage', 32)->default('new');
            $table->string('source', 60)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
        });
    }
}
