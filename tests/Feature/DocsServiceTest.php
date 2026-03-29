<?php

namespace Tests\Feature;

use Modules\Docs\Services\DocsService;
use Tests\TestCase;

class DocsServiceTest extends TestCase
{
    private string $docsDir;

    private string $modulesDir;

    protected function setUp(): void
    {
        parent::setUp();

        $base = storage_path('framework/testing-docs-' . uniqid());
        $this->docsDir = $base . '/docs-site';
        $this->modulesDir = $base . '/modules';

        @mkdir($this->docsDir, 0777, true);
        @mkdir($this->modulesDir . '/Mailer/docs', 0777, true);

        file_put_contents($this->docsDir . '/release.md', <<<MD
---
title: Release Playbook
version: V2.5
status: current
category: ops
tags: release,qa
summary: Checklist de release V2.5.
---
# Release Playbook

Guide de release et QA pour CATMIN.
MD);

        file_put_contents($this->modulesDir . '/Mailer/HELP.md', <<<MD
---
version: V2.5
status: current
category: mailer
tags: mailer,retry
summary: Aide du module mailer.
---
# Mailer Help

Le mailer gere retry, sandbox et alerting.
MD);

        file_put_contents($this->modulesDir . '/Mailer/docs/reliability.md', <<<MD
---
title: Mailer Reliability
version: V2.5
status: current
category: ops
tags: mailer,retry,queue
summary: Pipeline d'envoi fiable.
---
# Mailer Reliability

Retry automatique, queue et fallback driver.
MD);
    }

    protected function tearDown(): void
    {
        $this->deleteDirectory(dirname($this->docsDir));
        parent::tearDown();
    }

    public function test_search_matches_title_content_and_filters_by_version(): void
    {
        $service = $this->fakeService();

        $results = $service->search('retry fallback', ['version' => 'V2.5']);

        $this->assertNotEmpty($results);
        $this->assertSame('Mailer Reliability', $results[0]['title']);
        $this->assertSame('V2.5', $results[0]['version']);
    }

    public function test_find_exposes_metadata_and_related_docs(): void
    {
        $service = $this->fakeService();

        $doc = $service->find('mailer-reliability');

        $this->assertNotNull($doc);
        $this->assertSame('ops', $doc['category']);
        $this->assertSame('current', $doc['status']);
        $this->assertContains('retry', $doc['tags']);
        $this->assertNotEmpty($doc['related_docs']);
    }

    public function test_filtered_index_can_select_module_and_category(): void
    {
        $service = $this->fakeService();

        $docs = $service->filteredIndex(['module' => 'mailer', 'category' => 'ops']);

        $this->assertCount(1, $docs);
        $this->assertArrayHasKey('mailer-reliability', $docs);
    }

    private function fakeService(): DocsService
    {
        $docsDir = $this->docsDir;
        $modulesDir = $this->modulesDir;

        return new class($docsDir, $modulesDir) extends DocsService {
            public function __construct(private string $testDocsDir, private string $testModulesDir)
            {
            }

            public function docsPath(): string
            {
                return $this->testDocsDir;
            }

            public function modulesPath(): string
            {
                return $this->testModulesDir;
            }
        };
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . '/' . $item;
            if (is_dir($child)) {
                $this->deleteDirectory($child);
            } else {
                @unlink($child);
            }
        }

        @rmdir($path);
    }
}
