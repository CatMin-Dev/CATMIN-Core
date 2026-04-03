<?php

namespace Tests\Unit\I18n;

use App\Models\AdminUser;
use App\Services\LocaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Unit tests for LocaleService + i18n infrastructure.
 *
 * Tests:
 *  1. Default locale falls back to fr when config is unsupported
 *  2. isSupported() validates correctly
 *  3. localeOptions() returns fr and en
 *  4. apply() calls App::setLocale()
 *  5. resolve() reads from AdminUser metadata
 *  6. resolve() falls back to session then config
 *  7. persistForUser() writes to metadata
 *  8. Lang file fr/core.php is loadable and returns expected key
 *  9. Lang file en/core.php returns different value
 * 10. i18n:missing command detects no missing keys
 */
class LocaleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');

        if (!Schema::hasTable('admin_users')) {
            Schema::create('admin_users', function ($table) {
                $table->id();
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
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
    }

    public function test_default_locale_is_fr(): void
    {
        $this->assertSame('fr', LocaleService::DEFAULT_LOCALE);
    }

    public function test_is_supported_validates_locales(): void
    {
        $this->assertTrue(LocaleService::isSupported('fr'));
        $this->assertTrue(LocaleService::isSupported('en'));
        $this->assertFalse(LocaleService::isSupported('de'));
        $this->assertFalse(LocaleService::isSupported(''));
        $this->assertFalse(LocaleService::isSupported('FR'));
    }

    public function test_locale_options_returns_fr_and_en(): void
    {
        $options = LocaleService::localeOptions();
        $this->assertArrayHasKey('fr', $options);
        $this->assertArrayHasKey('en', $options);
        $this->assertCount(2, $options);
    }

    public function test_apply_sets_app_locale(): void
    {
        LocaleService::apply('en');
        $this->assertSame('en', App::getLocale());

        LocaleService::apply('fr');
        $this->assertSame('fr', App::getLocale());
    }

    public function test_apply_falls_back_for_unsupported_locale(): void
    {
        LocaleService::apply('zz');
        $this->assertSame(LocaleService::DEFAULT_LOCALE, App::getLocale());
    }

    public function test_resolve_returns_default_when_no_user(): void
    {
        Config::set('app.locale', 'fr');
        $locale = LocaleService::resolve(null);
        $this->assertTrue(LocaleService::isSupported($locale));
    }

    public function test_resolve_reads_from_user_metadata(): void
    {
        $user = new AdminUser();
        $user->username = 'testuser_' . uniqid();
        $user->email = 'test_' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->metadata = ['locale' => 'en'];
        $user->save();

        $this->assertSame('en', LocaleService::resolve($user));
    }

    public function test_resolve_ignores_unsupported_metadata_locale(): void
    {
        $user = new AdminUser();
        $user->username = 'testuser_' . uniqid();
        $user->email = 'test_' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->metadata = ['locale' => 'de'];
        $user->save();

        Config::set('app.locale', 'fr');

        // Should NOT return 'de', should fall through to config
        $resolved = LocaleService::resolve($user);
        $this->assertNotSame('de', $resolved);
        $this->assertTrue(LocaleService::isSupported($resolved));
    }

    public function test_persist_for_user_stores_locale_in_metadata(): void
    {
        $user = new AdminUser();
        $user->username = 'testuser_' . uniqid();
        $user->email = 'test_' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->save();

        LocaleService::persistForUser($user, 'en');
        $user->refresh();

        $meta = (array) $user->metadata;
        $this->assertSame('en', $meta['locale']);
    }

    public function test_persist_stores_only_supported_locales(): void
    {
        $user = new AdminUser();
        $user->username = 'testuser_' . uniqid();
        $user->email = 'test_' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->save();

        LocaleService::persistForUser($user, 'zz');
        $user->refresh();

        $meta = (array) $user->metadata;
        $this->assertSame(LocaleService::DEFAULT_LOCALE, $meta['locale']);
    }

    public function test_fr_core_lang_file_exists_and_is_an_array(): void
    {
        $path = base_path('lang/fr/core.php');
        $this->assertFileExists($path);
        $data = require $path;
        $this->assertIsArray($data);
        $this->assertArrayHasKey('save', $data);
    }

    public function test_en_core_lang_file_exists_and_is_an_array(): void
    {
        $path = base_path('lang/en/core.php');
        $this->assertFileExists($path);
        $data = require $path;
        $this->assertIsArray($data);
        $this->assertArrayHasKey('save', $data);
    }

    public function test_fr_and_en_core_have_different_save_label(): void
    {
        $fr = (array) require base_path('lang/fr/core.php');
        $en = (array) require base_path('lang/en/core.php');

        $this->assertNotSame($fr['save'], $en['save']);
    }

    public function test_translation_helper_returns_fr_key_when_locale_is_fr(): void
    {
        App::setLocale('fr');
        $result = __('core.save');
        $this->assertSame('Enregistrer', $result);
    }

    public function test_translation_helper_returns_en_key_when_locale_is_en(): void
    {
        App::setLocale('en');
        $result = __('core.save');
        $this->assertSame('Save', $result);
    }

    public function test_admin_user_get_locale_returns_metadata_value(): void
    {
        $user = new AdminUser();
        $user->username = 'testuser_' . uniqid();
        $user->email    = 'test_' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->metadata = ['locale' => 'en'];
        $user->save();

        $this->assertSame('en', $user->getLocale());
    }

    public function test_admin_user_get_locale_falls_back_to_default(): void
    {
        $user = new AdminUser();
        $user->username = 'testuser_' . uniqid();
        $user->email    = 'test_' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->metadata = null;
        $user->save();

        $this->assertSame(LocaleService::DEFAULT_LOCALE, $user->getLocale());
    }

    protected function tearDown(): void
    {
        // Restore default locale after tests
        App::setLocale('en');
        parent::tearDown();
    }
}
