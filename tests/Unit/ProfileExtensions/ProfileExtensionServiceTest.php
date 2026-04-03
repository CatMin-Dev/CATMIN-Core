<?php

namespace Tests\Unit\ProfileExtensions;

use Addons\CatminProfileExtensions\Services\ProfileExtensionService;
use App\Models\AdminUser;
use App\Services\ProfileExtensionResolverService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class ProfileExtensionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        if (!Schema::hasTable('admin_users')) {
            Schema::create('admin_users', function (Blueprint $table): void {
                $table->id();
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('contact_email')->nullable();
                $table->string('password');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('phone', 64)->nullable();
                $table->unsignedBigInteger('avatar_media_asset_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_super_admin')->default(false);
                $table->timestamp('last_login_at')->nullable();
                $table->unsignedInteger('failed_login_attempts')->default(0);
                $table->timestamp('locked_until')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('user_profiles_extended')) {
            Schema::create('user_profiles_extended', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('admin_user_id')->nullable()->index();
                $table->string('phone', 64)->nullable();
                $table->string('mobile', 64)->nullable();
                $table->string('company_name')->nullable();
                $table->string('address_line_1')->nullable();
                $table->string('address_line_2')->nullable();
                $table->string('postal_code', 32)->nullable();
                $table->string('city', 120)->nullable();
                $table->string('state', 120)->nullable();
                $table->string('country_code', 2)->nullable();
                $table->string('identity_type', 64)->nullable();
                $table->string('identity_number', 120)->nullable();
                $table->string('preferred_contact_method', 32)->nullable();
                $table->boolean('contact_opt_in')->default(false);
                $table->timestamps();
                $table->unique(['user_id']);
                $table->unique(['admin_user_id']);
            });
        }
    }

    public function test_it_creates_and_updates_extended_profile_for_admin_user(): void
    {
        $admin = AdminUser::query()->create([
            'username' => 'admin_ext_1',
            'email' => 'admin_ext_1@example.com',
            'password' => bcrypt('password1234'),
            'phone' => '+33111111111',
        ]);

        $service = app(ProfileExtensionService::class);

        $service->upsertForAdminUser((int) $admin->id, [
            'phone' => '+33123456789',
            'mobile' => '+33612345678',
            'company_name' => 'CATMIN SAS',
            'address_line_1' => '1 rue de la paix',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country_code' => 'fr',
            'preferred_contact_method' => 'mobile',
            'contact_opt_in' => true,
        ]);

        $profile = $service->forAdminUser((int) $admin->id);

        $this->assertNotNull($profile);
        $this->assertSame('+33123456789', $profile->phone);
        $this->assertSame('+33612345678', $profile->mobile);
        $this->assertSame('FR', $profile->country_code);

        $service->upsertForAdminUser((int) $admin->id, [
            'phone' => '+33999999999',
            'city' => 'Lyon',
            'country_code' => 'fr',
            'preferred_contact_method' => 'phone',
        ]);

        $profile = $service->forAdminUser((int) $admin->id);

        $this->assertNotNull($profile);
        $this->assertSame('+33999999999', $profile->phone);
        $this->assertSame('Lyon', $profile->city);
        $this->assertSame('phone', $profile->preferred_contact_method);
    }

    public function test_resolver_reads_extended_profile_data_when_available(): void
    {
        $admin = AdminUser::query()->create([
            'username' => 'admin_ext_2',
            'email' => 'admin_ext_2@example.com',
            'password' => bcrypt('password1234'),
            'phone' => '+33101010101',
        ]);

        $service = app(ProfileExtensionService::class);
        $service->upsertForAdminUser((int) $admin->id, [
            'phone' => '+33121212121',
            'mobile' => '+33698989898',
            'address_line_1' => '10 avenue test',
            'postal_code' => '31000',
            'city' => 'Toulouse',
            'country_code' => 'fr',
            'preferred_contact_method' => 'mobile',
            'contact_opt_in' => true,
        ]);

        $resolver = app(ProfileExtensionResolverService::class);
        $data = $resolver->forAdminUser((int) $admin->id);

        $this->assertSame('profile_extension', $data['resolved_from']);
        $this->assertSame('+33698989898', $resolver->contactPhoneForAdmin((int) $admin->id));

        $address = $resolver->billingAddressForAdmin((int) $admin->id);
        $this->assertSame('Toulouse', $address['city']);

        $prefs = $resolver->contactPreferencesForAdmin((int) $admin->id);
        $this->assertSame('mobile', $prefs['preferred_contact_method']);
        $this->assertTrue($prefs['contact_opt_in']);
    }

    public function test_resolver_fallback_when_profile_extension_disabled(): void
    {
        Config::set('catmin.profile_extensions.enabled', false);

        $admin = AdminUser::query()->create([
            'username' => 'admin_ext_3',
            'email' => 'admin_ext_3@example.com',
            'password' => bcrypt('password1234'),
            'phone' => '+33444444444',
        ]);

        $resolver = app(ProfileExtensionResolverService::class);
        $data = $resolver->forAdminUser((int) $admin->id);

        $this->assertSame('fallback', $data['resolved_from']);
        $this->assertSame('+33444444444', $resolver->contactPhoneForAdmin((int) $admin->id));
    }
}
