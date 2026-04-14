<?php

declare(strict_types=1);

namespace Modules\CatMediaLink\services;

use Modules\CatMediaLink\repositories\MediaLinkRepository;

final class MediaLinkService
{
    public function __construct(
        private readonly MediaLinkRepository $repository,
        private readonly MediaLinkValidationService $validator,
        private readonly MediaGalleryService $gallery,
        private readonly FeaturedMediaService $featured,
        private readonly MediaUsageService $usage,
        private readonly ImageProcessingService $imageProcessing,
        private readonly CropperService $cropper
    ) {
    }

    public function dashboard(): array
    {
        return [
            'stats' => $this->repository->stats(),
            'assets' => $this->repository->listAssets(180),
            'usages' => $this->usage->latest(180),
            'runtime_dependencies' => $this->validator->runtimeDependencies(),
            'module_dependencies' => $this->validator->moduleDependencies(),
            'activation_state' => $this->validator->canActivate(),
            'presets' => $this->repository->listPresets(),
            'settings' => $this->repository->getSettings(),
        ];
    }

    public function variantState(int $mediaId): array
    {
        return [
            'selected_media_id' => $mediaId,
            'asset' => $mediaId > 0 ? $this->repository->findAsset($mediaId) : null,
            'variants' => $mediaId > 0 ? $this->repository->listVariantsByMedia($mediaId) : [],
            'presets' => $this->repository->listPresets(),
        ];
    }

    public function storeUploadedAsset(array $upload, string $title = '', string $alt = ''): array
    {
        if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'message' => 'Upload invalide.'];
        }

        $tmp = (string) ($upload['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'message' => 'Fichier temporaire invalide.'];
        }

        $mime = (string) (($upload['type'] ?? '') ?: (mime_content_type($tmp) ?: 'application/octet-stream'));
        $kind = str_starts_with($mime, 'video/') ? 'video' : (str_starts_with($mime, 'image/') ? 'image' : 'file');

        if (!in_array($kind, ['image', 'video'], true)) {
            return ['ok' => false, 'message' => 'Seuls les médias image/vidéo sont acceptés.'];
        }

        $ext = pathinfo((string) ($upload['name'] ?? ''), PATHINFO_EXTENSION);
        $ext = strtolower(trim($ext));
        if ($ext === '') {
            $ext = $kind === 'video' ? 'mp4' : 'jpg';
        }

        $relativeDir = '/uploads/media/' . gmdate('Y/m');
        $absoluteDir = CATMIN_PUBLIC . $relativeDir;
        if (!is_dir($absoluteDir) && !@mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            return ['ok' => false, 'message' => 'Impossible de créer le dossier média.'];
        }

        $file = bin2hex(random_bytes(12)) . '.' . $ext;
        $absolutePath = $absoluteDir . '/' . $file;
        if (!@move_uploaded_file($tmp, $absolutePath)) {
            return ['ok' => false, 'message' => 'Échec déplacement du média.'];
        }

        $width = null;
        $height = null;
        if ($kind === 'image') {
            $size = @getimagesize($absolutePath);
            if (is_array($size)) {
                $width = isset($size[0]) ? (int) $size[0] : null;
                $height = isset($size[1]) ? (int) $size[1] : null;
            }
        }

        $payload = [
            'media_type' => $kind,
            'source_type' => 'upload',
            'storage_path' => $relativeDir . '/' . $file,
            'public_url' => $relativeDir . '/' . $file,
            'mime_type' => $mime,
            'size_bytes' => (int) (@filesize($absolutePath) ?: 0),
            'width' => $width,
            'height' => $height,
            'title' => trim($title),
            'alt_text' => trim($alt),
        ];

        $insert = $this->repository->createAsset($payload);
        if ((bool) ($insert['ok'] ?? false) && $kind === 'image') {
            $assetId = (int) ($insert['id'] ?? 0);
            $asset = $assetId > 0 ? $this->repository->findAsset($assetId) : null;
            $settings = $this->repository->getSettings();
            if (is_array($asset) && (bool) ($settings['auto_generate_enabled'] ?? true)) {
                foreach ($this->imageProcessing->regenerateAllVariants($asset, $this->repository->listAutoPresets()) as $variantResult) {
                    if ((bool) ($variantResult['ok'] ?? false)) {
                        $payload = $variantResult['payload'] ?? null;
                        if (is_array($payload)) {
                            $this->repository->upsertVariant($payload);
                        }
                    }
                }
            }
        }

        return [
            'ok' => (bool) ($insert['ok'] ?? false),
            'message' => (bool) ($insert['ok'] ?? false) ? 'Média uploadé.' : 'Échec enregistrement média.',
            'id' => (int) ($insert['id'] ?? 0),
        ];
    }

    public function storeExternalAsset(string $url, string $type, string $title = '', string $alt = ''): array
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return ['ok' => false, 'message' => 'URL média invalide.'];
        }

        $type = strtolower(trim($type));
        if (!in_array($type, ['image', 'video'], true)) {
            $type = 'image';
        }

        $insert = $this->repository->createAsset([
            'media_type' => $type,
            'source_type' => 'url',
            'storage_path' => null,
            'public_url' => $url,
            'mime_type' => null,
            'size_bytes' => 0,
            'title' => trim($title),
            'alt_text' => trim($alt),
        ]);

        return [
            'ok' => (bool) ($insert['ok'] ?? false),
            'message' => (bool) ($insert['ok'] ?? false) ? 'Média distant ajouté.' : 'Échec ajout URL média.',
            'id' => (int) ($insert['id'] ?? 0),
        ];
    }

    public function syncEntity(string $entityType, int $entityId, int $featuredMediaId, string $galleryCsv, int $socialMediaId): array
    {
        $entityType = strtolower(trim($entityType));
        if ($entityType === '' || $entityId <= 0) {
            return ['ok' => false, 'message' => 'Entité invalide.'];
        }

        $links = [];
        if ($featuredMediaId > 0) {
            $links[] = [
                'media_id' => $featuredMediaId,
                'link_type' => 'featured',
                'sort_order' => 0,
                'is_primary' => true,
            ];
        }

        foreach ($this->gallery->buildGalleryLinks($this->gallery->collectGalleryIds($galleryCsv)) as $row) {
            $links[] = $row;
        }

        if ($socialMediaId > 0) {
            $links[] = [
                'media_id' => $socialMediaId,
                'link_type' => 'social_image',
                'sort_order' => 9999,
                'is_primary' => false,
            ];
        }

        return $this->repository->syncEntityLinks($entityType, $entityId, $links);
    }

    public function entityPreview(string $entityType, int $entityId): array
    {
        $links = $this->repository->entityLinks(strtolower(trim($entityType)), $entityId);
        $assetIds = array_values(array_unique(array_map(static fn (array $row): int => (int) ($row['media_id'] ?? 0), $links)));
        $variants = $this->repository->listVariantsForAssetIds($assetIds);
        $variantsByAsset = [];
        foreach ($variants as $variant) {
            $mid = (int) ($variant['media_id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }
            if (!isset($variantsByAsset[$mid])) {
                $variantsByAsset[$mid] = [];
            }
            $variantsByAsset[$mid][] = $variant;
        }

        return [
            'links' => $links,
            'featured' => $this->featured->pickFeatured($links),
            'variants_by_asset' => $variantsByAsset,
        ];
    }

    public function savePreset(array $payload): array
    {
        $presetKey = strtolower(trim((string) ($payload['preset_key'] ?? '')));
        if (!preg_match('/^[a-z0-9\-_]{2,80}$/', $presetKey)) {
            return ['ok' => false, 'message' => 'Preset key invalide.'];
        }

        $cropMode = strtolower(trim((string) ($payload['crop_mode'] ?? 'cover')));
        if (!in_array($cropMode, ['cover', 'contain', 'fit'], true)) {
            $cropMode = 'cover';
        }

        $format = strtolower(trim((string) ($payload['format'] ?? 'jpg')));
        if (!in_array($format, ['jpg', 'jpeg', 'webp', 'png'], true)) {
            $format = 'jpg';
        }

        $saved = $this->repository->savePreset([
            'id' => (int) ($payload['id'] ?? 0),
            'preset_key' => $presetKey,
            'label' => trim((string) ($payload['label'] ?? '')),
            'width' => max(1, (int) ($payload['width'] ?? 1)),
            'height' => max(1, (int) ($payload['height'] ?? 1)),
            'crop_mode' => $cropMode,
            'ratio_locked' => !empty($payload['ratio_locked']),
            'allow_manual_override' => !empty($payload['allow_manual_override']),
            'auto_generate' => !empty($payload['auto_generate']),
            'quality' => max(1, min(100, (int) ($payload['quality'] ?? 82))),
            'format' => $format,
            'is_enabled' => !empty($payload['is_enabled']),
            'sort_order' => max(0, (int) ($payload['sort_order'] ?? 0)),
        ]);

        return [
            'ok' => (bool) ($saved['ok'] ?? false),
            'message' => (bool) ($saved['ok'] ?? false) ? 'Preset enregistré.' : 'Échec enregistrement preset.',
        ];
    }

    public function deletePreset(int $id): array
    {
        $ok = $this->repository->deletePreset($id);
        return ['ok' => $ok, 'message' => $ok ? 'Preset supprimé.' : 'Échec suppression preset.'];
    }

    public function saveSettings(array $payload): array
    {
        $ok = $this->repository->saveSettings([
            'auto_generate_enabled' => !empty($payload['auto_generate_enabled']) ? '1' : '0',
            'manual_editor_enabled' => !empty($payload['manual_editor_enabled']) ? '1' : '0',
            'default_quality' => (string) max(1, min(100, (int) ($payload['default_quality'] ?? 82))),
            'allowed_formats' => trim((string) ($payload['allowed_formats'] ?? 'jpg,webp,png')),
            'crop_required' => !empty($payload['crop_required']) ? '1' : '0',
            'fallback_mode' => trim((string) ($payload['fallback_mode'] ?? 'original')) ?: 'original',
        ]);

        return ['ok' => $ok, 'message' => $ok ? 'Paramètres image enregistrés.' : 'Échec enregistrement paramètres.'];
    }

    public function regenerateVariants(int $mediaId): array
    {
        $asset = $this->repository->findAsset($mediaId);
        if (!is_array($asset)) {
            return ['ok' => false, 'message' => 'Média introuvable.'];
        }
        if (strtolower(trim((string) ($asset['media_type'] ?? ''))) !== 'image') {
            return ['ok' => false, 'message' => 'Seules les images peuvent être recadrées.'];
        }

        $okCount = 0;
        foreach ($this->imageProcessing->regenerateAllVariants($asset, $this->repository->listPresets()) as $result) {
            if ((bool) ($result['ok'] ?? false)) {
                $payload = $result['payload'] ?? null;
                if (is_array($payload) && (bool) ($this->repository->upsertVariant($payload)['ok'] ?? false)) {
                    $okCount++;
                }
            }
        }

        return [
            'ok' => $okCount > 0,
            'message' => $okCount > 0 ? ('Variantes régénérées: ' . $okCount) : 'Aucune variante régénérée.',
        ];
    }

    public function manualCropVariant(int $mediaId, string $presetKey, string $cropJson, bool $overrideExisting = true): array
    {
        $asset = $this->repository->findAsset($mediaId);
        if (!is_array($asset)) {
            return ['ok' => false, 'message' => 'Média introuvable.'];
        }

        $preset = $this->repository->getPresetByKey($presetKey);
        if (!is_array($preset)) {
            return ['ok' => false, 'message' => 'Preset introuvable.'];
        }

        $crop = $this->cropper->parseCropJson($cropJson);
        if ($crop === []) {
            return ['ok' => false, 'message' => 'Données de crop invalides.'];
        }

        try {
            $variant = $this->imageProcessing->generateVariant($asset, $preset, $crop, 'manual');
            if (!((bool) ($variant['ok'] ?? false))) {
                return ['ok' => false, 'message' => (string) ($variant['message'] ?? 'Échec génération variante.')];
            }
            $payload = $variant['payload'] ?? [];
            if (!$overrideExisting) {
                $payload['preset_key'] = (string) $presetKey . '-m' . gmdate('YmdHis');
            }
            $saved = $this->repository->upsertVariant(is_array($payload) ? $payload : []);
            return [
                'ok' => (bool) ($saved['ok'] ?? false),
                'message' => (bool) ($saved['ok'] ?? false) ? 'Variant manuelle sauvegardée.' : 'Échec sauvegarde variante manuelle.',
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Erreur crop manuel: ' . $e->getMessage()];
        }
    }

    public function deleteVariant(int $variantId): array
    {
        $variant = $this->repository->findVariant($variantId);
        if (!is_array($variant)) {
            return ['ok' => false, 'message' => 'Variant introuvable.'];
        }

        $relative = trim((string) ($variant['file_path'] ?? ''));
        if ($relative !== '') {
            $absolute = CATMIN_PUBLIC . $relative;
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }

        $ok = $this->repository->deleteVariant($variantId);
        return ['ok' => $ok, 'message' => $ok ? 'Variant supprimée.' : 'Échec suppression variant.'];
    }
}
