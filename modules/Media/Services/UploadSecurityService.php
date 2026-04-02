<?php

namespace Modules\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Upload security service — real MIME detection, extension validation,
 * quarantine support.
 *
 * Called by MediaAdminService::create() before any file is stored.
 */
class UploadSecurityService
{
    /**
     * Canonical allowed MIME types and the extensions they map to.
     * Only these MIME types are accepted.
     *
     * @var array<string, list<string>>
     */
    private const MIME_EXTENSION_MAP = [
        // Images
        'image/jpeg'                                                                    => ['jpg', 'jpeg'],
        'image/png'                                                                     => ['png'],
        'image/gif'                                                                     => ['gif'],
        'image/webp'                                                                    => ['webp'],
        'image/svg+xml'                                                                 => ['svg'],
        // Documents
        'application/pdf'                                                               => ['pdf'],
        'text/plain'                                                                    => ['txt'],
        'text/csv'                                                                      => ['csv'],
        'application/json'                                                              => ['json'],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'      => ['docx'],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'            => ['xlsx'],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'   => ['pptx'],
        'application/msword'                                                            => ['doc'],
        'application/vnd.ms-excel'                                                     => ['xls'],
        // Video
        'video/mp4'                                                                     => ['mp4'],
        'video/webm'                                                                    => ['webm'],
        // Audio
        'audio/mpeg'                                                                    => ['mp3'],
        'audio/mp3'                                                                     => ['mp3'],
        // Archives
        'application/zip'                                                               => ['zip'],
        'application/x-zip-compressed'                                                 => ['zip'],
    ];

    /**
     * MIME types that are never allowed regardless of extension.
     *
     * @var list<string>
     */
    private const FORBIDDEN_MIME_PREFIXES = [
        'application/x-php',
        'application/x-httpd-php',
        'text/x-php',
        'application/x-executable',
        'application/x-dosexec',      // Windows PE
        'application/x-elf',          // Linux ELF
        'application/x-msdownload',
        'application/octet-stream',   // treated as dangerous unless explicitly whitelisted
    ];

    /**
     * Validate an uploaded file and decide whether to accept, quarantine, or reject.
     *
     * Returns an array with:
     *   - valid: bool
     *   - real_mime: string — real MIME detected by finfo
     *   - quarantine: bool — store but flag for review
     *   - error: ?string — human-readable error when valid=false
     *
     * @return array{valid: bool, real_mime: string, quarantine: bool, error: ?string}
     */
    public function inspect(UploadedFile $file, string $declaredExtension): array
    {
        $realMime = $this->detectRealMime($file);
        $suspiciousMime = $this->isSuspiciousMime($realMime);
        $extensionMismatch = !$this->extensionMatchesMime($declaredExtension, $realMime);

        $quarantineEnabled = (bool) config('catmin.uploads.quarantine_enabled', false);

        if ($suspiciousMime) {
            $error = "MIME réel interdit : {$realMime} (extension déclarée : .{$declaredExtension})";
            $this->logSuspicious($file, $realMime, $declaredExtension, 'forbidden_mime', $error);
            return ['valid' => false, 'real_mime' => $realMime, 'quarantine' => false, 'error' => $error];
        }

        if ($extensionMismatch) {
            $error = "Extension .{$declaredExtension} ne correspond pas au MIME réel {$realMime}";
            $this->logSuspicious($file, $realMime, $declaredExtension, 'extension_mismatch', $error);

            if ($quarantineEnabled) {
                // Store but flag for review
                return ['valid' => true, 'real_mime' => $realMime, 'quarantine' => true, 'error' => $error];
            }

            return ['valid' => false, 'real_mime' => $realMime, 'quarantine' => false, 'error' => $error];
        }

        if (!array_key_exists($realMime, self::MIME_EXTENSION_MAP)) {
            $error = "Type MIME non explicitement autorisé : {$realMime}";
            $this->logSuspicious($file, $realMime, $declaredExtension, 'unlisted_mime', $error);

            if ($quarantineEnabled) {
                return ['valid' => true, 'real_mime' => $realMime, 'quarantine' => true, 'error' => $error];
            }

            return ['valid' => false, 'real_mime' => $realMime, 'quarantine' => false, 'error' => $error];
        }

        $this->logAccepted($file, $realMime, $declaredExtension);

        return ['valid' => true, 'real_mime' => $realMime, 'quarantine' => false, 'error' => null];
    }

    /**
     * Detect the real MIME type of the file using finfo (magic bytes).
     * Falls back to getMimeType() if finfo is unavailable.
     */
    public function detectRealMime(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        if ($path !== false && extension_loaded('fileinfo')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($path);
            if ($detected !== false && $detected !== '') {
                return (string) $detected;
            }
        }

        // Fallback: Laravel's Symfony wrapper
        return (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream');
    }

    /**
     * Check if the real MIME is in the forbidden list.
     */
    private function isSuspiciousMime(string $realMime): bool
    {
        $lower = strtolower($realMime);

        foreach (self::FORBIDDEN_MIME_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return true;
            }
        }

        // Any PHP-like text content
        if (str_contains($lower, 'php') || str_contains($lower, 'perl') || str_contains($lower, 'python')) {
            return true;
        }

        // text/html should never be uploaded as a media asset
        if (str_starts_with($lower, 'text/html') || str_starts_with($lower, 'text/javascript')) {
            return true;
        }

        return false;
    }

    /**
     * Check that the declared extension (e.g. "jpg") is in the allowed list for the real MIME.
     * If the MIME is not in the map at all, returns false (unknown MIME).
     */
    private function extensionMatchesMime(string $extension, string $realMime): bool
    {
        $lower = strtolower($realMime);

        if (!array_key_exists($lower, self::MIME_EXTENSION_MAP)) {
            return false;
        }

        return in_array(strtolower($extension), self::MIME_EXTENSION_MAP[$lower], true);
    }

    // ─── Logging ─────────────────────────────────────────────────────────────

    private function logAccepted(UploadedFile $file, string $realMime, string $extension): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'uploads',
                'level'      => 'info',
                'event'      => 'upload.accepted',
                'message'    => 'Upload accepté — MIME validé',
                'context'    => json_encode([
                    'original_name' => $file->getClientOriginalName(),
                    'real_mime'     => $realMime,
                    'extension'     => $extension,
                    'size_bytes'    => $file->getSize(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }

    private function logSuspicious(UploadedFile $file, string $realMime, string $extension, string $reason, string $detail): void
    {
        try {
            DB::table('system_logs')->insert([
                'channel'    => 'uploads',
                'level'      => 'warning',
                'event'      => 'upload.rejected',
                'message'    => "Upload rejeté — {$reason}",
                'context'    => json_encode([
                    'original_name'    => $file->getClientOriginalName(),
                    'real_mime'        => $realMime,
                    'declared_ext'     => $extension,
                    'reason'           => $reason,
                    'detail'           => $detail,
                    'size_bytes'       => $file->getSize(),
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {}
    }
}
