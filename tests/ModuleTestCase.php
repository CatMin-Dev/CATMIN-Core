<?php

namespace Tests;

use Tests\Concerns\InteractsWithModules;

abstract class ModuleTestCase extends TestCase
{
    use InteractsWithModules;
}
