<?php

declare(strict_types=1);

require_once CATMIN_CORE . '/module-installer.php';

final class CoreMarketInstaller
{
    public function installFromCatalogItem(array $item): array
    {
        return (new CoreModuleInstaller())->installFromMarket($item, true);
    }
}

