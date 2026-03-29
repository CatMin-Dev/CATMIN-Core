<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CatminApiKeyGenerateCommand extends Command
{
    protected $signature = 'catmin:api:key-generate
        {name : Nom lisible de la cle API}
        {--scope=external.read : Scope principal (option repetable via virgules)}
        {--profile= : Profil de scopes predefini (readonly, content-manager, shop-manager, ops-reader)}
        {--expires-days=365 : Duree de validite en jours (0 = sans expiration)}
        {--notes= : Notes internes}';

    protected $description = 'Genere une cle API externe CATMIN (stockage hash uniquement)';

    public function handle(): int
    {
        $name = trim((string) $this->argument('name'));
        $scopeOption = trim((string) $this->option('scope'));
        $profileOption = trim((string) $this->option('profile'));
        $profileScopes = Arr::wrap(config('catmin.api.external.scope_profiles.' . $profileOption, []));
        $scopes = collect([...explode(',', $scopeOption), ...$profileScopes])
            ->map(fn (string $s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $expiresDays = (int) $this->option('expires-days');
        $rawKey = 'catmin_' . Str::random(48);

        $apiKey = ApiKey::query()->create([
            'name' => $name,
            'key_hash' => hash('sha256', $rawKey),
            'scopes' => empty($scopes) ? ['external.read'] : $scopes,
            'is_active' => true,
            'expires_at' => $expiresDays > 0 ? now()->addDays($expiresDays) : null,
            'notes' => (string) $this->option('notes', ''),
        ]);

        $this->info('Cle API creee. Copiez-la maintenant (elle ne sera plus affichée):');
        $this->line($rawKey);
        $this->newLine();
        $this->line('ID: ' . $apiKey->id);
        $this->line('Name: ' . $apiKey->name);
        $this->line('Scopes: ' . implode(', ', (array) $apiKey->scopes));
        $this->line('Profile: ' . ($profileOption !== '' ? $profileOption : 'none'));
        $this->line('Expires: ' . ($apiKey->expires_at?->toIso8601String() ?? 'never'));

        return self::SUCCESS;
    }
}
