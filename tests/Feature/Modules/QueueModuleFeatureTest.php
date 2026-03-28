<?php

namespace Tests\Feature\Modules;

use App\Services\ModuleLoader;
use App\Services\ModuleManager;
use Tests\ModuleTestCase;

class QueueModuleFeatureTest extends ModuleTestCase
{
    public function test_queue_module_is_registered_and_route_is_exposed(): void
    {
        $this->assertTrue(ModuleManager::exists('queue'));
        $this->assertTrue(ModuleManager::isEnabled('queue'));

        $info = ModuleLoader::getRoutesInfo();
        $this->assertTrue((bool) ($info['queue']['has_routes'] ?? false));
    }

    public function test_queue_module_dependencies_are_satisfied(): void
    {
        $result = ModuleLoader::checkDependencies('queue');

        $this->assertTrue((bool) ($result['valid'] ?? false));
        $this->assertSame([], $result['missing'] ?? []);
    }
}
