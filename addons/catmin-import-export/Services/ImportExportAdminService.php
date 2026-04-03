<?php

namespace Addons\CatminImportExport\Services;

use Addons\CatminBooking\Models\Booking;
use Addons\CatminCrmLight\Models\CrmContact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Articles\Models\Article;
use Modules\Logger\Models\SystemLog;
use Modules\Logger\Services\SystemLogService;
use Modules\Pages\Models\Page;

class ImportExportAdminService
{
    public function __construct(private readonly SystemLogService $logger)
    {
    }

    /**
     * @return array<string, array{name: string, description: string}>
     */
    public function moduleOptions(): array
    {
        return collect($this->definitions())
            ->mapWithKeys(fn (array $definition, string $slug): array => [
                $slug => [
                    'name' => (string) $definition['label'],
                    'description' => (string) $definition['description'],
                ],
            ])
            ->all();
    }

    /**
     * @return array{filename: string, content: string, content_type: string, count: int}
     */
    public function export(string $module, string $format): array
    {
        $definition = $this->definition($module);
        $rows = $this->rowsForExport($module);
        $timestamp = now()->format('Ymd-His');

        if ($format === 'json') {
            $content = json_encode([
                'meta' => [
                    'module' => $module,
                    'format' => 'json',
                    'exported_at' => now()->toIso8601String(),
                    'count' => $rows->count(),
                ],
                'rows' => $rows->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{"rows":[]}';

            $contentType = 'application/json; charset=UTF-8';
        } else {
            $content = $this->toCsv($definition['fields'], $rows);
            $contentType = 'text/csv; charset=UTF-8';
        }

        $this->logger->logAudit('import_export.export', 'Export de données exécuté', [
            'module' => $module,
            'format' => $format,
            'count' => $rows->count(),
        ]);

        return [
            'filename' => $module . '-' . $timestamp . '.' . $format,
            'content' => $content,
            'content_type' => $contentType,
            'count' => $rows->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function import(string $module, string $format, string $payload, bool $dryRun = true, bool $overwrite = false): array
    {
        $definition = $this->definition($module);
        $rows = $this->parsePayload($module, $format, $payload);
        $errors = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($rows as $index => $row) {
            try {
                $normalized = $this->normalizeRow($module, $row);
                $validator = Validator::make($normalized, $definition['rules']);

                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $index + 1,
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                $result = $this->persistRow($module, $normalized, $overwrite);
                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $exception) {
                $errors[] = [
                    'row' => $index + 1,
                    'errors' => [$exception->getMessage() !== '' ? $exception->getMessage() : 'Erreur inattendue durant le traitement.'],
                ];
            }
        }

        $validRows = count($rows) - count($errors);
        $message = $dryRun
            ? sprintf('Dry-run terminé: %d lignes valides, %d erreurs.', $validRows, count($errors))
            : sprintf('Import terminé: %d créées, %d mises à jour, %d ignorées, %d erreurs.', $created, $updated, $skipped, count($errors));

        $this->logger->logAudit('import_export.import', 'Import de données exécuté', [
            'module' => $module,
            'format' => $format,
            'dry_run' => $dryRun,
            'overwrite' => $overwrite,
            'rows' => count($rows),
            'valid_rows' => $validRows,
            'errors_count' => count($errors),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ], count($errors) > 0 ? 'warning' : 'info');

        return [
            'message' => $message,
            'module' => $module,
            'format' => $format,
            'dry_run' => $dryRun,
            'overwrite' => $overwrite,
            'rows' => count($rows),
            'valid_rows' => $validRows,
            'errors' => $errors,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
        ];
    }

    /** @return Collection<int, SystemLog> */
    public function recentLogs(int $limit = 15): Collection
    {
        if (!Schema::hasTable('system_logs')) {
            return collect();
        }

        return SystemLog::query()
            ->where('channel', 'audit')
            ->where('event', 'like', 'import_export.%')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /** @return array<string, array<string, mixed>> */
    private function definitions(): array
    {
        return [
            'pages' => [
                'label' => 'Pages',
                'description' => 'Pages CMS (title, slug, excerpt, content, status).',
                'model' => Page::class,
                'fields' => ['id', 'title', 'slug', 'excerpt', 'content', 'status', 'published_at', 'meta_title', 'meta_description'],
                'rules' => [
                    'id' => ['nullable', 'integer'],
                    'title' => ['required', 'string', 'max:255'],
                    'slug' => ['required', 'string', 'max:255'],
                    'excerpt' => ['nullable', 'string'],
                    'content' => ['nullable', 'string'],
                    'status' => ['required', 'in:draft,published,archived'],
                    'published_at' => ['nullable', 'date'],
                    'meta_title' => ['nullable', 'string', 'max:255'],
                    'meta_description' => ['nullable', 'string', 'max:255'],
                ],
                'match_keys' => ['id', 'slug'],
            ],
            'articles' => [
                'label' => 'Articles',
                'description' => 'Articles/blog fusionnés (title, slug, content_type, status).',
                'model' => Article::class,
                'fields' => ['id', 'title', 'slug', 'excerpt', 'content', 'content_type', 'article_category_id', 'status', 'published_at', 'meta_title', 'meta_description'],
                'rules' => [
                    'id' => ['nullable', 'integer'],
                    'title' => ['required', 'string', 'max:255'],
                    'slug' => ['required', 'string', 'max:255'],
                    'excerpt' => ['nullable', 'string'],
                    'content' => ['nullable', 'string'],
                    'content_type' => ['required', 'string', 'max:32'],
                    'article_category_id' => ['nullable', 'integer'],
                    'status' => ['required', 'in:draft,published,archived'],
                    'published_at' => ['nullable', 'date'],
                    'meta_title' => ['nullable', 'string', 'max:255'],
                    'meta_description' => ['nullable', 'string', 'max:255'],
                ],
                'match_keys' => ['id', 'slug'],
            ],
            'users' => [
                'label' => 'Utilisateurs',
                'description' => 'Users de base (name, email, password optionnel).',
                'model' => User::class,
                'fields' => ['id', 'name', 'email', 'is_active', 'email_verified_at', 'created_at'],
                'rules' => [
                    'id' => ['nullable', 'integer'],
                    'name' => ['required', 'string', 'max:255'],
                    'email' => ['required', 'email', 'max:255'],
                    'password' => ['nullable', 'string', 'min:8', 'max:255'],
                    'is_active' => ['nullable', 'boolean'],
                    'email_verified_at' => ['nullable', 'date'],
                ],
                'match_keys' => ['id', 'email'],
            ],
            'booking' => [
                'label' => 'Booking',
                'description' => 'Réservations (booking) via confirmation_code.',
                'model' => Booking::class,
                'fields' => ['id', 'booking_service_id', 'booking_slot_id', 'status', 'customer_name', 'customer_email', 'customer_phone', 'notes', 'internal_note', 'confirmation_code', 'confirmed_at', 'cancelled_at'],
                'rules' => [
                    'id' => ['nullable', 'integer'],
                    'booking_service_id' => ['required', 'integer'],
                    'booking_slot_id' => ['required', 'integer'],
                    'status' => ['required', 'in:pending,confirmed,cancelled'],
                    'customer_name' => ['required', 'string', 'max:191'],
                    'customer_email' => ['required', 'email', 'max:191'],
                    'customer_phone' => ['nullable', 'string', 'max:64'],
                    'notes' => ['nullable', 'string'],
                    'internal_note' => ['nullable', 'string'],
                    'confirmation_code' => ['required', 'string', 'max:64'],
                    'confirmed_at' => ['nullable', 'date'],
                    'cancelled_at' => ['nullable', 'date'],
                ],
                'match_keys' => ['id', 'confirmation_code'],
            ],
            'crm' => [
                'label' => 'CRM',
                'description' => 'Contacts CRM (company, identité, email, statut).',
                'model' => CrmContact::class,
                'fields' => ['id', 'crm_company_id', 'first_name', 'last_name', 'email', 'phone', 'position', 'status', 'tags', 'notes'],
                'rules' => [
                    'id' => ['nullable', 'integer'],
                    'crm_company_id' => ['nullable', 'integer'],
                    'first_name' => ['required', 'string', 'max:120'],
                    'last_name' => ['nullable', 'string', 'max:120'],
                    'email' => ['nullable', 'email', 'max:191'],
                    'phone' => ['nullable', 'string', 'max:64'],
                    'position' => ['nullable', 'string', 'max:120'],
                    'status' => ['required', 'string', 'max:32'],
                    'tags' => ['nullable', 'string'],
                    'notes' => ['nullable', 'string'],
                ],
                'match_keys' => ['id', 'email'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function definition(string $module): array
    {
        $definition = $this->definitions()[$module] ?? null;

        if ($definition === null) {
            throw new \InvalidArgumentException('Module d\'import/export non supporté.');
        }

        return $definition;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function rowsForExport(string $module): Collection
    {
        $definition = $this->definition($module);
        $modelClass = $definition['model'];
        $fields = $definition['fields'];

        return $modelClass::query()
            ->orderBy('id')
            ->get($fields)
            ->map(function (Model $model) use ($fields): array {
                $row = [];

                foreach ($fields as $field) {
                    $value = $model->getAttribute($field);
                    $row[$field] = $value instanceof \DateTimeInterface ? $value->format(DATE_ATOM) : $value;
                }

                return $row;
            });
    }

    /** @return list<array<string, mixed>> */
    private function parsePayload(string $module, string $format, string $payload): array
    {
        if ($format === 'json') {
            $decoded = json_decode($payload, true);
            if (!is_array($decoded)) {
                throw new \InvalidArgumentException('JSON invalide.');
            }

            $rows = $decoded['rows'] ?? $decoded;
            if (!is_array($rows)) {
                throw new \InvalidArgumentException('Structure JSON invalide: tableau rows attendu.');
            }

            return array_values(array_map(fn ($row): array => is_array($row) ? $row : [], $rows));
        }

        return $this->fromCsv($module, $payload);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function normalizeRow(string $module, array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalized[(string) $key] = is_string($value) ? trim($value) : $value;
        }

        foreach (['published_at', 'confirmed_at', 'cancelled_at', 'email_verified_at'] as $dateField) {
            if (array_key_exists($dateField, $normalized) && $normalized[$dateField] !== '' && $normalized[$dateField] !== null) {
                $normalized[$dateField] = Carbon::parse((string) $normalized[$dateField])->toDateTimeString();
            }
        }

        foreach (['id', 'article_category_id', 'crm_company_id', 'booking_service_id', 'booking_slot_id'] as $intField) {
            if (array_key_exists($intField, $normalized) && $normalized[$intField] !== '' && $normalized[$intField] !== null) {
                $normalized[$intField] = (int) $normalized[$intField];
            }
        }

        if (array_key_exists('is_active', $normalized)) {
            $normalized['is_active'] = $this->normalizeBoolean($normalized['is_active']);
        }

        if ($module === 'users' && (!array_key_exists('password', $normalized) || $normalized['password'] === '')) {
            unset($normalized['password']);
        }

        return $normalized;
    }

    private function persistRow(string $module, array $row, bool $overwrite): string
    {
        $definition = $this->definition($module);
        $modelClass = $definition['model'];
        $match = $overwrite ? $this->findExistingModel($modelClass, $definition['match_keys'], $row) : null;
        $payload = $this->payloadForPersistence($module, $row, $match instanceof Model);

        if ($match instanceof Model) {
            $match->fill($payload)->save();
            return 'updated';
        }

        $modelClass::query()->create($payload);

        return 'created';
    }

    /** @param class-string<Model> $modelClass @param list<string> $matchKeys */
    private function findExistingModel(string $modelClass, array $matchKeys, array $row): ?Model
    {
        foreach ($matchKeys as $key) {
            if (!array_key_exists($key, $row) || $row[$key] === null || $row[$key] === '') {
                continue;
            }

            $match = $modelClass::query()->where($key, $row[$key])->first();
            if ($match instanceof Model) {
                return $match;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function payloadForPersistence(string $module, array $row, bool $isUpdate): array
    {
        unset($row['id'], $row['created_at'], $row['updated_at']);

        if ($module === 'users') {
            if (array_key_exists('password', $row) && $row['password'] !== null && $row['password'] !== '') {
                $row['password'] = Hash::make((string) $row['password']);
            } elseif (!$isUpdate) {
                $row['password'] = Hash::make(Str::password(16));
            }

            if (!Schema::hasColumn('users', 'is_active')) {
                unset($row['is_active']);
            }
        }

        if ($module === 'crm' && array_key_exists('email', $row) && $row['email'] === '') {
            $row['email'] = null;
        }

        return $row;
    }

    /** @param list<string> $fields @param Collection<int, array<string, mixed>> $rows */
    private function toCsv(array $fields, Collection $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new \RuntimeException('Impossible de générer le CSV.');
        }

        fputcsv($stream, $fields);

        foreach ($rows as $row) {
            $line = [];
            foreach ($fields as $field) {
                $value = $row[$field] ?? null;
                $line[] = is_scalar($value) || $value === null ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            fputcsv($stream, $line);
        }

        rewind($stream);
        $contents = stream_get_contents($stream) ?: '';
        fclose($stream);

        return $contents;
    }

    /** @return list<array<string, mixed>> */
    private function fromCsv(string $module, string $payload): array
    {
        $definition = $this->definition($module);
        $expectedFields = $definition['fields'];
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new \RuntimeException('Impossible de lire le CSV.');
        }

        fwrite($stream, $payload);
        rewind($stream);

        $header = fgetcsv($stream);
        if (!is_array($header)) {
            fclose($stream);
            throw new \InvalidArgumentException('CSV invalide: en-tête manquante.');
        }

        $rows = [];
        while (($line = fgetcsv($stream)) !== false) {
            if ($line === [null] || $line === []) {
                continue;
            }

            $row = [];
            foreach ($header as $index => $field) {
                $row[(string) $field] = $line[$index] ?? null;
            }

            $rows[] = $row;
        }

        fclose($stream);

        foreach ($header as $field) {
            if (!in_array($field, $expectedFields, true) && $field !== 'password') {
                throw new \InvalidArgumentException('CSV invalide: colonne non supportée [' . $field . '].');
            }
        }

        return $rows;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}