<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatminAddonDocsTemplateTest extends TestCase
{
    private string $slug = 'docs-template-addon-test';

    private string $addonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addonPath = base_path('addons/' . $this->slug);

        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }
    }

    protected function tearDown(): void
    {
        if (File::exists($this->addonPath)) {
            File::deleteDirectory($this->addonPath);
        }

        parent::tearDown();
    }

    public function test_generated_docs_follow_standard_template_with_events_hooks_and_permissions(): void
    {
        $this->artisan('catmin:addon:make', [
            'name' => 'Docs Template Addon',
            'slug' => $this->slug,
            '--addon-version' => '2.0.0',
            '--permissions' => 'addon.docs_template_addon.menu,addon.docs_template_addon.configure',
            '--with-events' => true,
            '--with-ui-hooks' => true,
            '--force' => true,
        ])->assertExitCode(0);

        $docs = (string) File::get($this->addonPath . '/Docs/README.md');

        $this->assertStringContainsString('## Presentation', $docs);
        $this->assertStringContainsString('## Version', $docs);
        $this->assertStringContainsString('## Dependances modules', $docs);
        $this->assertStringContainsString('## Routes', $docs);
        $this->assertStringContainsString('## Permissions', $docs);
        $this->assertStringContainsString('addon.docs_template_addon.configure', $docs);
        $this->assertStringContainsString('## Events emis', $docs);
        $this->assertStringContainsString('## Events ecoutes', $docs);
        $this->assertStringContainsString('## Hooks UI utilises', $docs);
        $this->assertStringContainsString('## Config disponible', $docs);
        $this->assertStringContainsString('## Prochaines etapes', $docs);
    }
}
