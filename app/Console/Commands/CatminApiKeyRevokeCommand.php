<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;

class CatminApiKeyRevokeCommand extends Command
{
    protected $signature = 'catmin:api:key-revoke
        {identifier : ID numerique ou nom de la cle}
        {--by=id : id|name}
        {--reason= : Motif de revocation}';

    protected $description = 'Revoque une cle API CATMIN sans exposition du token brut';

    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));
        $by = strtolower(trim((string) $this->option('by')));
        $reason = trim((string) $this->option('reason'));

        $query = ApiKey::query();
        if ($by === 'name') {
            $query->where('name', $identifier);
        } else {
            $query->whereKey((int) $identifier);
        }

        /** @var ApiKey|null $apiKey */
        $apiKey = $query->first();

        if (!$apiKey) {
            $this->error('Cle API introuvable.');
            return self::FAILURE;
        }

        if ($apiKey->revoked_at !== null || !$apiKey->is_active) {
            $this->warn('La cle API est deja revoquee ou inactive.');
            return self::SUCCESS;
        }

        $notes = trim((string) ($apiKey->notes ?? ''));
        if ($reason !== '') {
            $notes = trim($notes . "\n[revoked " . now()->toIso8601String() . '] ' . $reason);
        }

        $apiKey->forceFill([
            'is_active' => false,
            'revoked_at' => now(),
            'notes' => $notes !== '' ? $notes : $apiKey->notes,
        ])->save();

        $this->info('Cle API revoquee.');
        $this->line('ID: ' . $apiKey->id);
        $this->line('Name: ' . $apiKey->name);
        $this->line('Revoked at: ' . ($apiKey->revoked_at?->toIso8601String() ?? now()->toIso8601String()));

        return self::SUCCESS;
    }
}
