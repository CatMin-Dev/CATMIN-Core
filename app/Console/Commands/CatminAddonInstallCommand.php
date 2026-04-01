<?php

namespace App\Console\Commands;

use App\Services\AddonDistributionService;
use App\Services\AddonMarketplaceService;
use App\Services\CatminEventBus;
use Illuminate\Console\Command;

class CatminAddonInstallCommand extends Command
{
    protected $signature = 'catmin:addon:install
        {slug? : Addon slug}
        {--package= : Installer depuis une archive zip dans storage/app/addons/packages ou chemin absolu}
        {--no-enable : N active pas automatiquement}
        {--no-migrate : N execute pas les migrations}';

    protected $description = 'Installer un addon present dans addons/ (validation + activation + migrations)';

    public function __construct(private readonly AddonDistributionService $addonDistributionService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = (string) ($this->argument('slug') ?? '');
        $package = trim((string) ($this->option('package') ?? ''));

        if ($slug === '' && $package === '') {
            $this->error('Preciser soit un slug local, soit --package=archive.zip.');
            return self::FAILURE;
        }

        $enable = !(bool) $this->option('no-enable');
        $migrate = !(bool) $this->option('no-migrate');

        if ($package !== '') {
            $packagePath = str_starts_with($package, '/')
                ? $package
                : AddonMarketplaceService::packagesPath() . '/' . basename($package);

            $result = $this->addonDistributionService->installPackage($packagePath, $enable, $migrate);
            if (($result['ok'] ?? false) !== true) {
                $this->error((string) ($result['message'] ?? 'Installation package echouee.'));
                return self::FAILURE;
            }

            $slug = (string) ($result['slug'] ?? $slug);
        } else {
            $result = $this->addonDistributionService->installLocalAddon($slug, $enable, $migrate);
            if (($result['ok'] ?? false) !== true) {
                $this->error((string) ($result['message'] ?? 'Installation addon echouee.'));
                return self::FAILURE;
            }
        }

        $this->info((string) ($result['message'] ?? 'Addon installe.'));

        CatminEventBus::dispatch(CatminEventBus::ADDON_INSTALLED, [
            'slug' => $slug,
            'enabled' => $enable,
            'migrations_ran' => $migrate,
            'from_package' => $package !== '',
        ]);

        return self::SUCCESS;
    }
}
