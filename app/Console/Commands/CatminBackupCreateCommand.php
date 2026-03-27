<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CatminBackupCreateCommand extends Command
{
    protected $signature = 'catmin:backup:create
        {--name= : Nom libre ajoute au dossier backup}
        {--with-db : Inclut un dump SQL (mysql uniquement en V1)}
        {--without-media : N\'inclut pas les medias}
        {--without-extensions : N\'inclut pas les addons personalises}';

    protected $description = 'Cree une sauvegarde locale CATMIN (manifest, settings, media, addons, db optionnelle)';

    public function handle(): int
    {
        $result = BackupService::create([
            'name' => $this->option('name'),
            'with_db' => (bool) $this->option('with-db'),
            'with_media' => !(bool) $this->option('without-media'),
            'with_extensions' => !(bool) $this->option('without-extensions'),
        ]);

        $this->info('Backup CATMIN termine.');
        $this->line('Dossier: ' . $result['backup_dir']);

        $this->line('Elements crees:');
        foreach ($result['created_files'] as $path) {
            $this->line('- ' . $path);
        }

        if ($result['warnings'] !== []) {
            $this->warn('Warnings:');
            foreach ($result['warnings'] as $warning) {
                $this->warn('- ' . $warning);
            }
        }

        return self::SUCCESS;
    }
}
