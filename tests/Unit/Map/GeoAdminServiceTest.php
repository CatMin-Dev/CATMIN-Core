<?php

namespace Tests\Unit\Map;

use Addons\CatminMap\Models\GeoCategory;
use Addons\CatminMap\Models\GeoLocation;
use Addons\CatminMap\Services\GeoAdminService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GeoAdminServiceTest extends TestCase
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

    public function test_create_category_and_location(): void
    {
        $service = app(GeoAdminService::class);

        $cat = $service->createCategory([
            'name'  => 'Restaurants',
            'color' => '#EF4444',
            'icon'  => 'cup-hot',
        ]);

        $this->assertSame('Restaurants', $cat->name);
        $this->assertSame('restaurants', $cat->slug);
        $this->assertSame('#EF4444', $cat->color);
        $this->assertTrue($cat->active);

        $loc = $service->createLocation([
            'geo_category_id' => $cat->id,
            'name'            => 'Le Petit Bistro',
            'address'         => '12 rue de la Paix',
            'city'            => 'Paris',
            'lat'             => 48.8566,
            'lng'             => 2.3522,
            'status'          => 'published',
        ]);

        $this->assertSame('Le Petit Bistro', $loc->name);
        $this->assertSame('le-petit-bistro', $loc->slug);
        $this->assertSame(48.8566, $loc->lat);
        $this->assertSame(2.3522, $loc->lng);
        $this->assertTrue($loc->hasCoordinates());
        $this->assertSame($cat->id, $loc->geo_category_id);
    }

    public function test_map_points_returns_published_with_coords_only(): void
    {
        $service = app(GeoAdminService::class);

        $cat = $service->createCategory(['name' => 'Hôtels']);

        // Published with coords → should appear
        $service->createLocation([
            'name'   => 'Hôtel du Centre',
            'lat'    => 43.2965,
            'lng'    => 5.3698,
            'status' => 'published',
        ]);

        // Draft → should NOT appear
        $service->createLocation([
            'name'   => 'Hôtel Brouillon',
            'lat'    => 43.0,
            'lng'    => 5.0,
            'status' => 'draft',
        ]);

        // Published but no coords → should NOT appear
        $loc3 = $service->createLocation([
            'name'   => 'Hôtel Sans Coords',
            'status' => 'published',
        ]);
        GeoLocation::query()->where('id', $loc3->id)->update(['lat' => null, 'lng' => null]);

        $points = $service->mapPoints();

        $this->assertCount(1, $points);
        $this->assertSame('Hôtel du Centre', $points->first()->name);
    }

    public function test_pagination_filters(): void
    {
        $service = app(GeoAdminService::class);

        $cat = $service->createCategory(['name' => 'Musées']);
        $service->createLocation(['name' => 'Louvre', 'city' => 'Paris', 'status' => 'published', 'geo_category_id' => $cat->id]);
        $service->createLocation(['name' => 'Orsay', 'city' => 'Paris', 'status' => 'published', 'geo_category_id' => $cat->id]);
        $service->createLocation(['name' => 'Guggenheim', 'city' => 'Bilbao', 'status' => 'published']);

        $allResults = $service->locations();
        $this->assertSame(3, $allResults->total());

        $filteredByCity = $service->locations(['city' => 'Paris']);
        $this->assertSame(2, $filteredByCity->total());

        $filteredByCat = $service->locations(['category_id' => $cat->id]);
        $this->assertSame(2, $filteredByCat->total());

        $filteredByQ = $service->locations(['q' => 'Louvre']);
        $this->assertSame(1, $filteredByQ->total());
    }

    // ─── Schema helpers ────────────────────────────────────────────

    private function createTables(): void
    {
        Schema::dropAllTables();

        Schema::create('geo_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('color', 32)->default('#3B82F6');
            $table->string('icon', 64)->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('geo_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('geo_category_id')->nullable()->constrained('geo_categories')->nullOnDelete();
            $table->string('name', 191);
            $table->string('slug', 191)->unique();
            $table->text('description')->nullable();
            $table->string('address', 255)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 120)->nullable();
            $table->string('zip', 32)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 64)->nullable();
            $table->string('email', 191)->nullable();
            $table->string('website', 255)->nullable();
            $table->text('opening_hours')->nullable();
            $table->string('status', 32)->default('published');
            $table->boolean('featured')->default(false);
            $table->unsignedBigInteger('linked_event_id')->nullable();
            $table->unsignedBigInteger('linked_shop_id')->nullable();
            $table->unsignedBigInteger('linked_page_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
