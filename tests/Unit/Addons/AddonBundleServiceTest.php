<?php

namespace Tests\Unit\Addons;

use App\Services\Addons\AddonBundleInstaller;
use App\Services\Addons\AddonBundleService;
use Tests\TestCase;

class AddonBundleServiceTest extends TestCase
{
    public function test_bundle_manifests_are_discoverable_and_evaluated(): void
    {
        $service = app(AddonBundleService::class);
        $bundles = $service->list();

        $this->assertNotEmpty($bundles);

        $slugs = collect($bundles)->pluck('slug')->all();
        $this->assertContains('association-events', $slugs);
        $this->assertContains('services-booking', $slugs);
        $this->assertContains('editorial-publishing', $slugs);

        foreach ($bundles as $bundle) {
            $this->assertIsArray($bundle['addons_included'] ?? null);
            $this->assertIsArray($bundle['required_modules'] ?? null);
            $this->assertIsArray($bundle['compatibility'] ?? null);
            $this->assertArrayHasKey('compatible', $bundle['compatibility']);
        }
    }

    public function test_installer_resolves_install_order_from_manifest(): void
    {
        $service = app(AddonBundleService::class);
        $installer = app(AddonBundleInstaller::class);

        $bundle = $service->find('services-booking');
        $this->assertNotNull($bundle);

        $order = $installer->resolveInstallOrder($bundle);

        $this->assertSame([
            'catmin-crm-light',
            'catmin-booking',
            'catmin-profile-extensions',
            'catmin-forms',
        ], $order);
    }
}
