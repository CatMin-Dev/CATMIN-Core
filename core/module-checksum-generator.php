<?php

declare(strict_types=1);

final class CoreModuleChecksumGenerator
{
    public function generate(string $modulePath): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }
            $full = str_replace('\\', '/', $item->getPathname());
            $relative = ltrim(str_replace(str_replace('\\', '/', rtrim($modulePath, '/')), '', $full), '/');
            if ($relative === '' || $relative === 'checksums.json' || $relative === 'signature.json') {
                continue;
            }
            if ($this->isIgnored($relative)) {
                continue;
            }
            $files[$relative] = hash_file('sha256', $full);
        }

        ksort($files);
        $pairs = [];
        foreach ($files as $relative => $hash) {
            $pairs[] = $relative . ':' . $hash;
        }

        return [
            'schema_version' => '1.0.0',
            'algorithm' => 'sha256',
            'generated_at' => gmdate('c'),
            'files' => $files,
            'module_hash' => hash('sha256', implode("\n", $pairs)),
        ];
    }

    private function isIgnored(string $relative): bool
    {
        foreach (['.git/', '.svn/', '__MACOSX/', '.DS_Store', 'logs/', 'cache/', 'tmp/'] as $ignored) {
            if (str_contains($relative, $ignored)) {
                return true;
            }
        }
        return false;
    }
}

