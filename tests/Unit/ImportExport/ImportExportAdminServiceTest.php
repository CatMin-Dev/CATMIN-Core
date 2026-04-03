<?php

namespace Tests\Unit\ImportExport;

use Addons\CatminCrmLight\Models\CrmContact;
use Addons\CatminImportExport\Services\ImportExportAdminService;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Modules\Pages\Models\Page;
use Tests\TestCase;

class ImportExportAdminServiceTest extends TestCase
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

    public function test_export_pages_to_json_contains_meta_and_rows(): void
    {
        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'status' => 'published',
        ]);

        $result = app(ImportExportAdminService::class)->export('pages', 'json');
        $decoded = json_decode($result['content'], true);

        $this->assertSame('application/json; charset=UTF-8', $result['content_type']);
        $this->assertSame('pages', $decoded['meta']['module']);
        $this->assertCount(1, $decoded['rows']);
        $this->assertSame('accueil', $decoded['rows'][0]['slug']);
    }

    public function test_export_pages_to_csv_contains_header_and_row(): void
    {
        Page::query()->create([
            'title' => 'À propos',
            'slug' => 'a-propos',
            'status' => 'draft',
        ]);

        $result = app(ImportExportAdminService::class)->export('pages', 'csv');

        $this->assertStringContainsString('id,title,slug,excerpt,content,status,published_at,meta_title,meta_description', trim($result['content']));
        $this->assertStringContainsString('a-propos', $result['content']);
    }

    public function test_dry_run_import_does_not_write_users(): void
    {
        $payload = json_encode([
            'rows' => [[
                'name' => 'Alice',
                'email' => 'alice@example.test',
                'password' => 'Password123!',
                'is_active' => true,
            ]],
        ], JSON_THROW_ON_ERROR);

        $result = app(ImportExportAdminService::class)->import('users', 'json', $payload, true, false);

        $this->assertSame(1, $result['valid_rows']);
        $this->assertSame(0, User::query()->count());
    }

    public function test_import_users_with_overwrite_updates_existing_user(): void
    {
        $user = User::query()->create([
            'name' => 'Bob',
            'email' => 'bob@example.test',
            'password' => 'secret',
        ]);

        $payload = json_encode([
            'rows' => [[
                'email' => 'bob@example.test',
                'name' => 'Bob Updated',
                'password' => 'Password123!',
                'is_active' => true,
            ]],
        ], JSON_THROW_ON_ERROR);

        $result = app(ImportExportAdminService::class)->import('users', 'json', $payload, false, true);

        $this->assertSame(1, $result['updated']);
        $this->assertSame('Bob Updated', $user->fresh()->name);
    }

    public function test_duplicate_import_without_overwrite_is_reported_as_error(): void
    {
        Page::query()->create([
            'title' => 'Accueil',
            'slug' => 'accueil',
            'status' => 'published',
        ]);

        $payload = json_encode([
            'rows' => [[
                'title' => 'Accueil bis',
                'slug' => 'accueil',
                'status' => 'published',
            ]],
        ], JSON_THROW_ON_ERROR);

        $result = app(ImportExportAdminService::class)->import('pages', 'json', $payload, false, false);

        $this->assertCount(1, $result['errors']);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, Page::query()->count());
    }

    public function test_import_crm_from_csv_creates_contact(): void
    {
        $csv = implode("\n", [
            'id,crm_company_id,first_name,last_name,email,phone,position,status,tags,notes',
            ',,Carla,Doe,carla@example.test,0102030405,CEO,lead,,Premier contact',
        ]);

        $result = app(ImportExportAdminService::class)->import('crm', 'csv', $csv, false, false);

        $this->assertSame(1, $result['created']);
        $this->assertSame(1, CrmContact::query()->count());
        $this->assertSame('Carla', CrmContact::query()->first()->first_name);
    }

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('media_asset_id')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
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

        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('content_type', 32)->default('article');
            $table->unsignedBigInteger('article_category_id')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->json('taxonomy_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('booking_service_id');
            $table->unsignedBigInteger('booking_slot_id');
            $table->string('status', 32)->default('pending');
            $table->string('customer_name', 191);
            $table->string('customer_email', 191);
            $table->string('customer_phone', 64)->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_note')->nullable();
            $table->string('confirmation_code', 64)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 32);
            $table->string('level', 32);
            $table->string('event', 191);
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('admin_username')->nullable();
            $table->string('method')->nullable();
            $table->text('url')->nullable();
            $table->string('ip_address')->nullable();
            $table->unsignedInteger('status_code')->default(0);
            $table->timestamps();
        });
    }
}