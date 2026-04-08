<?php

declare(strict_types=1);

final class CoreSettingsCache
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? (CATMIN_STORAGE . '/cache/settings.cache.php');
    }

    public function load(): ?array
    {
        if (!is_file($this->file)) {
            return null;
        }

        $payload = require $this->file;
        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            return null;
        }

        return $payload['data'];
    }

    public function save(array $data): bool
    {
        $dir = dirname($this->file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        $payload = [
            'generated_at' => date('c'),
            'data' => $data,
        ];

        $export = "<?php\n\nreturn " . var_export($payload, true) . ";\n";
        return @file_put_contents($this->file, $export) !== false;
    }

    public function flush(): bool
    {
        if (!is_file($this->file)) {
            return true;
        }
        return @unlink($this->file);
    }
}

