<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

use Core\module\CoreModuleLoader;

final class MediaLinkValidationService
{
    public function allowedLinkTypes(): array
    {
        return ['featured', 'gallery', 'inline_reference', 'social_image'];
    }

    public function normalizeLinkType(string $type): string
    {
        $type = strtolower(trim($type));
        return in_array($type, $this->allowedLinkTypes(), true) ? $type : 'gallery';
    }

    public function runtimeDependencies(): array
    {
        return [
            'imagick_ext' => extension_loaded('imagick'),
            'fileinfo_ext' => extension_loaded('fileinfo'),
            'gd_ext' => extension_loaded('gd'),
        ];
    }

    public function moduleDependencies(): array
    {
        $required = ['cat-imagick-addon', 'cat-cropper-addon'];
        $snapshot = (new CoreModuleLoader())->scan();
        $rows = [];
        foreach ($required as $slug) {
            $state = ['slug' => $slug, 'present' => false, 'enabled' => false];
            foreach ((array) ($snapshot['modules'] ?? []) as $module) {
                $mSlug = strtolower(trim((string) ($module['manifest']['slug'] ?? '')));
                if ($mSlug !== $slug) {
                    continue;
                }
                $state['present'] = true;
                $state['enabled'] = (bool) ($module['enabled'] ?? false);
                break;
            }
            $rows[] = $state;
        }
        return $rows;
    }

    public function canActivate(): array
    {
        $missing = [];
        foreach ($this->runtimeDependencies() as $key => $ok) {
            if (!$ok) {
                $missing[] = $key;
            }
        }
        foreach ($this->moduleDependencies() as $dep) {
            if (!(bool) ($dep['present'] ?? false) || !(bool) ($dep['enabled'] ?? false)) {
                $missing[] = (string) ($dep['slug'] ?? 'unknown');
            }
        }

        return ['ok' => $missing === [], 'missing' => $missing];
    }
}
