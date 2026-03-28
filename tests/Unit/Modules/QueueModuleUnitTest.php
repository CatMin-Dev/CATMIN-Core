<?php

namespace Tests\Unit\Modules;

use Tests\ModuleTestCase;

class QueueModuleUnitTest extends ModuleTestCase
{
    public function test_queue_module_manifest_is_valid(): void
    {
        $this->assertModuleVersionValid('queue');
        $this->assertModuleDependsOn('queue', 'core');
        $this->assertModuleHasRoutesFile('queue');
        $this->assertModuleHasConfigFile('queue');
    }
}
