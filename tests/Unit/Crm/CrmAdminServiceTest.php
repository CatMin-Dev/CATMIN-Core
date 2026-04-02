<?php

namespace Tests\Unit\Crm;

use Addons\CatminCrmLight\Models\CrmCompany;
use Addons\CatminCrmLight\Models\CrmContact;
use Addons\CatminCrmLight\Services\CrmAdminService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CrmAdminServiceTest extends TestCase
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

    public function test_contact_search_by_name_email_and_company(): void
    {
        $service = app(CrmAdminService::class);

        $company = CrmCompany::query()->create(['name' => 'Acme Inc']);

        CrmContact::query()->create([
            'crm_company_id' => $company->id,
            'first_name' => 'Alice',
            'last_name' => 'Doe',
            'email' => 'alice@acme.test',
            'status' => 'lead',
        ]);

        CrmContact::query()->create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email' => 'bob@example.test',
            'status' => 'active',
        ]);

        $resultByName = $service->contacts(['q' => 'Alice']);
        $this->assertSame(1, $resultByName->total());

        $resultByEmail = $service->contacts(['q' => 'bob@example.test']);
        $this->assertSame(1, $resultByEmail->total());

        $resultByCompany = $service->contacts(['q' => 'Acme']);
        $this->assertSame(1, $resultByCompany->total());
    }

    public function test_contact_timeline_includes_crm_notes_booking_and_event_rows(): void
    {
        $service = app(CrmAdminService::class);

        $contact = CrmContact::query()->create([
            'first_name' => 'Carla',
            'email' => 'carla@example.test',
            'status' => 'lead',
        ]);

        $service->addNote($contact, 'Appel de qualification', 'call');

        // Simulate booking integration row
        \DB::table('bookings')->insert([
            'id' => 1,
            'customer_email' => 'carla@example.test',
            'status' => 'confirmed',
            'confirmation_code' => 'BK-XYZ',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simulate event integration row
        \DB::table('event_participants')->insert([
            'id' => 1,
            'email' => 'carla@example.test',
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $timeline = $service->contactTimeline($contact);

        $sources = collect($timeline)->pluck('source')->values()->all();

        $this->assertContains('crm', $sources);
        $this->assertContains('booking', $sources);
        $this->assertContains('event', $sources);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('crm_companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 191);
            $table->string('website', 255)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('phone', 64)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
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
            $table->text('tags')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_notes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('crm_contact_id');
            $table->string('type', 40)->default('note');
            $table->text('content');
            $table->string('module', 80)->nullable();
            $table->string('linked_type', 80)->nullable();
            $table->unsignedBigInteger('linked_id')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();
        });

        // Integration tables used by contactTimeline()
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_email', 191)->nullable();
            $table->string('status', 32)->default('pending');
            $table->string('confirmation_code', 64)->nullable();
            $table->timestamps();
        });

        Schema::create('event_participants', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 191)->nullable();
            $table->string('status', 32)->default('registered');
            $table->timestamps();
        });
    }
}
