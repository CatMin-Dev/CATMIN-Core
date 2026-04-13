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
        private readonly MediaUsageService $usage
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
        return [
            'links' => $links,
            'featured' => $this->featured->pickFeatured($links),
        ];
    }
}
